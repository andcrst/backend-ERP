<?php

namespace App\Services;

use App\Enums\JenisMutasiStok;
use App\Enums\StatusDokumen;
use App\Exceptions\ApiException;
use App\Models\Penerimaan;
use App\Models\Pengadaan;
use App\Models\Stok;
use Illuminate\Support\Facades\DB;

/**
 * Penerimaan barang dari supplier (G-02).
 *
 * Aturan kunci:
 * - WAJIB terikat pengadaan `DISETUJUI` (P8) — tanpa itu `422 PENGAADAAN_BELUM_DISETUJUI`.
 * - Jumlah boleh kurang/lebih dari pengajuan, tetapi selisih WAJIB bercatatan.
 * - `harga_modal` wajib diisi; saat posting memperbarui `stok.harga_modal` (D-05=A).
 * - Draf belum menyentuh stok; posting menaikkan stok lewat `StokMutasiService`.
 */
class PenerimaanService
{
    public function __construct(
        private readonly StokMutasiService $mutasi,
        private readonly NomorDokumenService $nomor,
        private readonly AuditService $audit,
        private readonly PengadaanService $pengadaan,
    ) {}

    /**
     * Buat dokumen penerimaan (draf).
     *
     * @param  array{
     *     tanggal_penerimaan?: string|null,
     *     nomor_surat_jalan?: string|null,
     *     catatan?: string|null,
     *     item: list<array{id_produk:int, jumlah:int, harga_modal:int, catatan?:string|null}>
     * }  $data
     */
    public function buat(Pengadaan $pengadaan, array $data, ?int $idPengguna = null): Penerimaan
    {
        if (! $pengadaan->status->dapatDiterima()) {
            throw new ApiException(
                'Barang hanya dapat diterima untuk pengadaan yang sudah disetujui.',
                'PENGAADAAN_BELUM_DISETUJUI',
                422,
                ['id_pengadaan' => ['Pengadaan belum disetujui.']],
            );
        }

        return DB::transaction(function () use ($pengadaan, $data, $idPengguna): Penerimaan {
            $baris = $this->gabungItem($data['item']);

            $this->pastikanProdukDalamPengadaan($pengadaan, array_keys($baris));
            $this->pastikanSelisihBercatatan($pengadaan, $baris, $data['catatan'] ?? null);

            $total = 0;

            foreach ($baris as $satu) {
                $total += $satu['jumlah'] * $satu['harga_modal'];
            }

            $penerimaan = Penerimaan::create([
                'nomor_penerimaan' => $this->nomor->barangMasuk(),
                'tanggal_penerimaan' => $data['tanggal_penerimaan'] ?? now()->toDateString(),
                'id_pengadaan' => $pengadaan->id_pengadaan,
                'id_supplier' => $pengadaan->id_supplier,
                'nomor_surat_jalan' => $data['nomor_surat_jalan'] ?? null,
                'id_pengguna' => $idPengguna ?? (int) auth()->id(),
                'status' => StatusDokumen::Draft->value,
                'total_nilai' => $total,
                'catatan' => $data['catatan'] ?? null,
            ]);

            foreach ($baris as $idProduk => $satu) {
                $penerimaan->item()->create([
                    'id_produk' => $idProduk,
                    'jumlah' => $satu['jumlah'],
                    'harga_modal' => $satu['harga_modal'],
                    'sub_total' => $satu['jumlah'] * $satu['harga_modal'],
                    'catatan' => $satu['catatan'],
                ]);
            }

            return $penerimaan->load('item.produk', 'pengadaan', 'supplier');
        });
    }

    /**
     * Posting penerimaan: stok bertambah, `harga_modal` diperbarui,
     * `jumlah_diterima` pengadaan bertambah, status pengadaan → DITERIMA.
     */
    public function posting(Penerimaan $penerimaan, ?int $idPengguna = null): Penerimaan
    {
        if ($penerimaan->sudahDiposting()) {
            throw new ApiException(
                'Dokumen penerimaan ini sudah diposting.',
                'VALIDATION_ERROR',
                422,
                ['status' => ['Dokumen sudah diposting.']],
            );
        }

        return DB::transaction(function () use ($penerimaan, $idPengguna): Penerimaan {
            $item = $penerimaan->item()->get();

            if ($item->isEmpty()) {
                throw new ApiException(
                    'Dokumen penerimaan belum memiliki barang.',
                    'VALIDATION_ERROR',
                    422,
                    ['item' => ['Minimal satu barang wajib diisi.']],
                );
            }

            foreach ($item as $satu) {
                if ($satu->harga_modal === null) {
                    throw new ApiException(
                        'Harga beli (modal) wajib diisi untuk setiap barang.',
                        'VALIDATION_ERROR',
                        422,
                        ['item' => ['Harga beli wajib diisi.']],
                    );
                }
            }

            // 1) Stok bertambah lewat satu-satunya pintu yang sah.
            $mutasi = $item->map(fn ($satu): array => [
                'id_produk' => (int) $satu->id_produk,
                'jumlah' => (int) $satu->jumlah,
                'jenis' => JenisMutasiStok::Penerimaan,
                'referensi_tipe' => 'penerimaan',
                'referensi_id' => $penerimaan->id_penerimaan,
                'catatan' => 'Penerimaan '.$penerimaan->nomor_penerimaan,
            ])->all();

            $this->mutasi->catatBanyak($mutasi, $idPengguna);

            // 2) Modal produk diperbarui (D-05=A: nilai terakhir dari penerimaan).
            $modal = [];

            foreach ($item as $satu) {
                /** @var Stok|null $produk */
                $produk = Stok::query()->find($satu->id_produk);

                if ($produk === null) {
                    continue;
                }

                $produk->forceFill(['harga_modal' => (int) $satu->harga_modal])->save();
                $modal[(int) $satu->id_produk] = (int) $satu->harga_modal;
            }

            // 3) Akumulasi jumlah diterima pada pengadaan.
            $pengadaan = Pengadaan::query()
                ->lockForUpdate()
                ->findOrFail($penerimaan->id_pengadaan);

            foreach ($item as $satu) {
                $pengadaan->item()
                    ->where('id_produk', $satu->id_produk)
                    ->increment('jumlah_diterima', (int) $satu->jumlah);
            }

            // 4) Tandai posting + status pengadaan.
            $penerimaan->forceFill([
                'status' => StatusDokumen::Posted->value,
                'posted_at' => now(),
            ])->save();

            $this->pengadaan->tandaiDiterima($pengadaan);

            // 5) Audit.
            $this->audit->catat(
                AuditService::PENGAJUAN_PENGADAAN,
                $penerimaan,
                null,
                [
                    'nomor_penerimaan' => $penerimaan->nomor_penerimaan,
                    'id_pengadaan' => $penerimaan->id_pengadaan,
                    'modal_baru' => $modal,
                ],
                $idPengguna,
            );

            return $penerimaan->refresh()->load('item.produk', 'pengadaan', 'supplier');
        });
    }

    /**
     * Barang yang diterima harus termasuk dalam pengajuan pengadaan.
     *
     * @param  list<int>  $ids
     */
    private function pastikanProdukDalamPengadaan(Pengadaan $pengadaan, array $ids): void
    {
        $dalamPengadaan = $pengadaan->item()->pluck('id_produk')
            ->map(fn ($id): int => (int) $id)
            ->all();

        if (array_diff($ids, $dalamPengadaan) !== []) {
            throw new ApiException(
                'Ada barang yang tidak termasuk dalam pengajuan pengadaan.',
                'VALIDATION_ERROR',
                422,
                ['item' => ['Barang tidak termasuk pengajuan pengadaan.']],
            );
        }
    }

    /**
     * Jumlah diterima berbeda dari pengajuan → wajib bercatatan (G-02).
     *
     * @param  array<int, array{jumlah:int, harga_modal:int, catatan:string|null}>  $baris
     */
    private function pastikanSelisihBercatatan(Pengadaan $pengadaan, array $baris, ?string $catatan): void
    {
        if ($catatan !== null && trim($catatan) !== '') {
            return;
        }

        $diminta = $pengadaan->item()->pluck('jumlah_diminta', 'id_produk');

        foreach ($baris as $idProduk => $satu) {
            if ((int) $diminta[$idProduk] !== $satu['jumlah']) {
                throw new ApiException(
                    'Jumlah diterima berbeda dari pengajuan — catatan wajib diisi.',
                    'VALIDATION_ERROR',
                    422,
                    ['catatan' => ['Catatan wajib diisi bila jumlah berbeda dari pengajuan.']],
                );
            }
        }
    }

    /**
     * @param  list<array{id_produk:int, jumlah:int, harga_modal:int, catatan?:string|null}>  $item
     * @return array<int, array{jumlah:int, harga_modal:int, catatan:string|null}>
     */
    private function gabungItem(array $item): array
    {
        $hasil = [];

        foreach ($item as $satu) {
            $idProduk = (int) $satu['id_produk'];

            $hasil[$idProduk] ??= [
                'jumlah' => 0,
                'harga_modal' => (int) $satu['harga_modal'],
                'catatan' => $satu['catatan'] ?? null,
            ];

            $hasil[$idProduk]['jumlah'] += (int) $satu['jumlah'];
        }

        return $hasil;
    }
}
