<?php

namespace App\Services;

use App\Enums\JenisMutasiStok;
use App\Enums\JenisPembayaran;
use App\Enums\StatusBarang;
use App\Enums\StatusPembayaran;
use App\Exceptions\ApiException;
use App\Models\DetailTransaksi;
use App\Models\Pelanggan;
use App\Models\Stok;
use App\Models\Transaksi;
use Illuminate\Support\Facades\DB;

/**
 * Inti alur kasir (CP-1).
 *
 * Aturan kunci:
 * - Harga SELALU dari database; snapshot disalin ke baris (B9/AR4).
 * - Stok dipotong HANYA untuk barang berstatus `DIAMBIL` (B1/B2).
 * - `MENUNGGU_DIANTAR` belum menyentuh stok; stok berkurang saat gudang
 *   mengonfirmasi `SUDAH_DIANTAR` (lihat ubahStatusBarang).
 * - `biaya_angkut` DITANGGUNG TOKO: tidak masuk `total_pembayaran` (B6/D-32=B).
 */
class PenjualanService
{
    public function __construct(
        private readonly StokMutasiService $mutasi,
        private readonly NomorDokumenService $nomor,
        private readonly AuditService $audit,
    ) {}

    /**
     * Buat transaksi penjualan.
     *
     * @param  array{
     *     detail: list<array{id_produk:int, jumlah:int, status_barang?:string}>,
     *     jenis_pembayaran: string,
     *     jumlah_bayar?: float|int|string|null,
     *     referensi_pembayaran?: string|null,
     *     id_pelanggan?: int|null,
     *     catatan?: string|null
     * }  $data
     */
    public function buat(array $data, ?int $idPengguna = null): Transaksi
    {
        $jenis = JenisPembayaran::from($data['jenis_pembayaran']);
        $idPelanggan = $data['id_pelanggan'] ?? null;

        return DB::transaction(function () use ($data, $jenis, $idPelanggan, $idPengguna): Transaksi {
            $pelanggan = $this->cariPelanggan($idPelanggan);

            // Urutkan id produk agar penguncian baris konsisten (bebas deadlock).
            $ids = collect($data['detail'])->pluck('id_produk')->sort()->values()->all();

            /** @var array<int, Stok> $stok */
            $stok = Stok::query()
                ->whereIn('id_produk', $ids)
                ->lockForUpdate()
                ->get()
                ->keyBy('id_produk')
                ->all();

            $total = 0;
            $baris = [];
            $potong = [];

            foreach ($data['detail'] as $item) {
                $produk = $stok[$item['id_produk']] ?? null;

                if ($produk === null) {
                    throw new ApiException('Produk tidak ditemukan pada tabel stok.', 'NOT_FOUND', 404);
                }

                $jumlah = (int) $item['jumlah'];
                $status = isset($item['status_barang'])
                    ? StatusBarang::from($item['status_barang'])
                    : StatusBarang::Diambil;

                $subTotal = $jumlah * (int) $produk->harga;

                // Biaya angkut di luar total (D-32=B): total hanya nilai barang.
                $total += $subTotal;

                $baris[] = [
                    'id_produk' => $produk->id_produk,
                    'jumlah' => $jumlah,
                    'status_barang' => $status->value,
                    'harga_satuan' => (int) $produk->harga,
                    'nama_produk_snapshot' => $produk->nama_produk,
                    'harga_modal_snapshot' => $produk->harga_modal,
                    'sub_total' => $subTotal,
                ];

                // Hanya barang yang langsung dibawa pelanggan yang memotong stok.
                if ($status === StatusBarang::Diambil) {
                    $potong[] = ['produk' => $produk, 'jumlah' => $jumlah];
                }
            }

            $this->pastikanTujuanPengantaran($baris, $pelanggan);

            // Stok diperiksa LEBIH DULU agar pesan error paling relevan muncul
            // (perilaku lama dipertahankan): stok kurang > pembayaran kurang.
            foreach ($potong as $satu) {
                $this->mutasi->pastikanCukup($satu['produk'], $satu['jumlah']);
            }

            [$statusBayar, $kembalian] = $this->hitungPembayaran($jenis, $total, $data['jumlah_bayar'] ?? null);

            $transaksi = Transaksi::create([
                'nomor_struk' => $this->nomor->struk(),
                'id_pengguna' => $idPengguna ?? auth()->id(),
                'id_pelanggan' => $pelanggan?->id_pelanggan,
                'tanggal_transaksi' => now(),
                'total_pembayaran' => $total,
                'jenis_pembayaran' => $jenis->value,
                'status_pembayaran' => $statusBayar->value,
                'jumlah_bayar' => $jenis === JenisPembayaran::Tunai && ($data['jumlah_bayar'] ?? null) !== null
                    ? (float) $data['jumlah_bayar']
                    : null,
                'referensi_pembayaran' => $data['referensi_pembayaran'] ?? null,
                'kembalian' => $kembalian,
                'biaya_angkut' => 0,
                'catatan' => $data['catatan'] ?? null,
            ]);

            foreach ($baris as $satu) {
                DetailTransaksi::create($satu + ['id_transaksi' => $transaksi->id_transaksi]);
            }

            $this->potongStok($potong, $transaksi, $idPengguna);

            $this->audit->catat(
                AuditService::SALE_CREATE,
                $transaksi,
                null,
                [
                    'nomor_struk' => $transaksi->nomor_struk,
                    'total_pembayaran' => (float) $transaksi->total_pembayaran,
                    'barang_diambil' => count(array_filter($baris, fn (array $b): bool => $b['status_barang'] === StatusBarang::Diambil->value)),
                    'barang_diantar' => count(array_filter($baris, fn (array $b): bool => $b['status_barang'] === StatusBarang::MenungguDiantar->value)),
                ],
                $idPengguna,
            );

            return $transaksi->load('detail.produk', 'pengguna', 'pelanggan');
        });
    }

    /**
     * Ubah status satu barang (oleh Petugas Gudang).
     *
     * - `SUDAH_DIANTAR`: memotong stok saat itu juga (B4).
     * - `GAGAL_DIANTAR`: stok TIDAK berubah (B5).
     * - Biaya angkut wajib terisi lebih dulu (B7).
     */
    public function ubahStatusBarang(
        Transaksi $transaksi,
        DetailTransaksi $baris,
        StatusBarang $tujuan,
        ?int $idPengguna = null,
        ?string $catatan = null,
    ): DetailTransaksi {
        if ($baris->id_transaksi !== $transaksi->id_transaksi) {
            throw new ApiException('Barang tidak termasuk transaksi ini.', 'NOT_FOUND', 404);
        }

        return DB::transaction(function () use ($transaksi, $baris, $tujuan, $idPengguna, $catatan): DetailTransaksi {
            $asal = $baris->statusBarang();

            if (! $asal->bisaBerubahKe($tujuan)) {
                throw new ApiException(
                    sprintf('Status barang %s tidak dapat diubah menjadi %s.', $asal->label(), $tujuan->label()),
                    'VALIDATION_ERROR',
                    422,
                    ['status_barang' => ['Perubahan status tidak diizinkan.']],
                );
            }

            if ($tujuan === StatusBarang::SudahDiantar) {
                $this->pastikanBiayaAngkutTerisi($transaksi);
            }

            $sebelum = ['status_barang' => $asal->value];
            $baris->forceFill(['status_barang' => $tujuan->value])->save();

            if ($tujuan === StatusBarang::SudahDiantar) {
                $produk = Stok::query()->lockForUpdate()->find($baris->id_produk);

                if ($produk === null) {
                    throw new ApiException('Produk tidak ditemukan.', 'NOT_FOUND', 404);
                }

                $this->mutasi->catat(
                    $produk,
                    -1 * (int) $baris->jumlah,
                    JenisMutasiStok::Penjualan,
                    'transaksi',
                    $transaksi->id_transaksi,
                    $idPengguna,
                    $catatan ?? 'Konfirmasi pengantaran '.$transaksi->nomor_struk,
                );
            }

            $this->audit->catat(
                AuditService::BARANG_STATUS_UBAH,
                $baris,
                $sebelum,
                ['status_barang' => $tujuan->value, 'catatan' => $catatan],
                $idPengguna,
            );

            return $baris->refresh();
        });
    }

    /**
     * Isi biaya angkut (oleh Petugas Gudang). D-32=B: tidak menambah total tagihan.
     */
    public function isiBiayaAngkut(Transaksi $transaksi, int $biayaAngkut, ?int $idPengguna = null): Transaksi
    {
        if ($biayaAngkut < 0) {
            throw new ApiException('Biaya angkut tidak boleh negatif.', 'VALIDATION_ERROR', 422, [
                'biaya_angkut' => ['Biaya angkut tidak boleh negatif.'],
            ]);
        }

        if (! $transaksi->perluDiantar()) {
            throw new ApiException(
                'Transaksi ini tidak memiliki barang yang perlu diantar.',
                'BIAYA_ANGKUT_TIDAK_RELEVAN',
                422,
                ['biaya_angkut' => ['Tidak ada barang yang diantar pada transaksi ini.']],
            );
        }

        $sebelum = ['biaya_angkut' => (int) $transaksi->biaya_angkut];

        $transaksi->forceFill(['biaya_angkut' => $biayaAngkut])->save();

        $this->audit->catat(
            AuditService::BIAYA_ANGKUT_UBAH,
            $transaksi,
            $sebelum,
            ['biaya_angkut' => $biayaAngkut],
            $idPengguna,
        );

        return $transaksi->refresh();
    }

    /**
     * Batalkan transaksi (khusus OWNER). Stok dikembalikan HANYA untuk barang
     * yang stoknya sudah terpotong (B10).
     */
    public function batalkan(Transaksi $transaksi, string $alasan, ?int $idPengguna = null): Transaksi
    {
        if (trim($alasan) === '') {
            throw new ApiException('Alasan pembatalan wajib diisi.', 'VALIDATION_ERROR', 422, [
                'alasan' => ['Alasan pembatalan wajib diisi.'],
            ]);
        }

        return DB::transaction(function () use ($transaksi, $alasan, $idPengguna): Transaksi {
            $sudahKeluar = $transaksi->detailSudahKeluar()->get();

            foreach ($sudahKeluar as $baris) {
                $produk = Stok::query()->lockForUpdate()->find($baris->id_produk);

                if ($produk !== null) {
                    $this->mutasi->catat(
                        $produk,
                        (int) $baris->jumlah,
                        JenisMutasiStok::PembatalanPenjualan,
                        'transaksi',
                        $transaksi->id_transaksi,
                        $idPengguna,
                        'Batal: '.$alasan,
                    );
                }
            }

            $this->audit->catat(
                AuditService::SALE_VOID,
                $transaksi,
                ['nomor_struk' => $transaksi->nomor_struk],
                ['alasan' => $alasan, 'barang_dikembalikan' => $sudahKeluar->count()],
                $idPengguna,
            );

            // Soft delete: nomor struk tidak pernah dipakai ulang.
            $transaksi->delete();

            return $transaksi;
        });
    }

    /**
     * @param  list<array{produk: Stok, jumlah: int}>  $daftar
     */
    private function potongStok(array $daftar, Transaksi $transaksi, ?int $idPengguna): void
    {
        foreach ($daftar as $satu) {
            $this->mutasi->pastikanCukup($satu['produk'], $satu['jumlah']);

            $this->mutasi->catat(
                $satu['produk'],
                -1 * $satu['jumlah'],
                JenisMutasiStok::Penjualan,
                'transaksi',
                $transaksi->id_transaksi,
                $idPengguna,
                'Penjualan '.$transaksi->nomor_struk,
            );
        }
    }

    private function cariPelanggan(?int $idPelanggan): ?Pelanggan
    {
        if ($idPelanggan === null) {
            return null;
        }

        $pelanggan = Pelanggan::query()->find($idPelanggan);

        if ($pelanggan === null) {
            throw new ApiException('Pelanggan tidak ditemukan.', 'NOT_FOUND', 404, [
                'id_pelanggan' => ['Pelanggan tidak ditemukan.'],
            ]);
        }

        return $pelanggan;
    }

    /**
     * Barang yang perlu diantar wajib punya pelanggan beralamat (K-02).
     *
     * @param  list<array{status_barang: string}>  $baris
     */
    private function pastikanTujuanPengantaran(array $baris, ?Pelanggan $pelanggan): void
    {
        $adaAntar = collect($baris)->contains(
            fn (array $satu): bool => $satu['status_barang'] === StatusBarang::MenungguDiantar->value,
        );

        if (! $adaAntar) {
            return;
        }

        if ($pelanggan === null) {
            throw new ApiException(
                'Barang yang perlu diantar wajib menyertakan pelanggan beserta alamatnya.',
                'ALAMAT_WAJIB',
                422,
                ['id_pelanggan' => ['Pelanggan wajib diisi untuk barang yang diantar.']],
            );
        }

        if (! $pelanggan->punyaAlamat()) {
            throw new ApiException(
                'Alamat pelanggan wajib diisi untuk barang yang perlu diantar.',
                'ALAMAT_WAJIB',
                422,
                ['id_pelanggan' => ['Alamat pelanggan wajib diisi.']],
            );
        }
    }

    /**
     * @return array{0: StatusPembayaran, 1: float}
     */
    private function hitungPembayaran(JenisPembayaran $jenis, int $total, float|int|string|null $jumlahBayar): array
    {
        // QRIS/TRANSFER: referensi OPSIONAL (D-08=B), kembalian selalu 0.
        if (! $jenis->hasChange()) {
            return [StatusPembayaran::Lunas, 0.0];
        }

        // TUNAI tanpa jumlah_bayar: transaksi tetap dicatat sebagai BELUM_LUNAS
        // (perilaku lama: `jumlah_bayar` opsional, kembalian 0).
        if ($jumlahBayar === null) {
            return [StatusPembayaran::BelumLunas, 0.0];
        }

        $bayar = (float) $jumlahBayar;

        if ($bayar < $total) {
            throw new ApiException(
                'Jumlah bayar kurang dari total pembayaran.',
                'PEMBAYARAN_KURANG',
                422,
                ['jumlah_bayar' => ['Jumlah bayar kurang dari total.']],
            );
        }

        return [StatusPembayaran::Lunas, $bayar - $total];
    }

    private function pastikanBiayaAngkutTerisi(Transaksi $transaksi): void
    {
        if (! $transaksi->perluDiantar()) {
            return;
        }

        // Biaya angkut boleh 0 (ditanggung toko) — namun kolom tetap wajib
        // bernilai angka; default 0 sudah memenuhi aturan B7.
        if ($transaksi->biaya_angkut === null) {
            throw new ApiException(
                'Biaya angkut wajib diisi sebelum barang dinyatakan sudah diantar.',
                'BIAYA_ANGKUT_WAJIB',
                422,
                ['biaya_angkut' => ['Biaya angkut wajib diisi.']],
            );
        }
    }
}
