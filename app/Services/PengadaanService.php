<?php

namespace App\Services;

use App\Enums\KeputusanPengadaan;
use App\Enums\StatusPengadaan;
use App\Exceptions\ApiException;
use App\Models\Pengadaan;
use App\Models\Stok;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;

/**
 * Pengadaan 4 tahap (D-19).
 *
 * Ajukan (GUDANG) → putuskan (FINANCE/OWNER) → terima (GUDANG, via
 * PenerimaanService) → tutup (FINANCE/OWNER).
 *
 * Aturan kunci:
 * - Gudang TIDAK boleh menyetujui/menutup pengadaannya sendiri (P5).
 * - Pengadaan tidak pernah menyentuh stok; stok hanya naik lewat posting penerimaan.
 * - Penolakan/revisi WAJIB beralasan agar riwayatnya dapat diaudit.
 * - Penutupan hanya boleh setelah barang diterima dan pengadaan `DITERIMA`.
 */
class PengadaanService
{
    public function __construct(
        private readonly NomorDokumenService $nomor,
        private readonly AuditService $audit,
    ) {}

    /**
     * Ajukan pengadaan baru (GUDANG).
     *
     * @param  array{
     *     tanggal_pengadaan?: string|null,
     *     tanggal_kebutuhan?: string|null,
     *     id_supplier?: int|null,
     *     alasan?: string|null,
     *     item: list<array{id_produk:int, jumlah_diminta:int, perkiraan_harga?:int|null, catatan?:string|null}>
     * }  $data
     */
    public function ajukan(array $data, ?int $idPengaju = null): Pengadaan
    {
        return DB::transaction(function () use ($data, $idPengaju): Pengadaan {
            $supplier = $this->cariSupplier($data['id_supplier'] ?? null);
            $baris = $this->gabungItem($data['item']);

            $this->pastikanProdukAda(array_keys($baris));

            $pengadaan = Pengadaan::create([
                'nomor_pengadaan' => $this->nomor->pengadaan(),
                'tanggal_pengadaan' => $data['tanggal_pengadaan'] ?? now()->toDateString(),
                'tanggal_kebutuhan' => $data['tanggal_kebutuhan'] ?? null,
                'id_supplier' => $supplier?->id_supplier,
                'id_pengaju' => $idPengaju ?? (int) auth()->id(),
                'status' => StatusPengadaan::Diajukan->value,
                'alasan' => $data['alasan'] ?? null,
            ]);

            foreach ($baris as $idProduk => $satu) {
                $pengadaan->item()->create([
                    'id_produk' => $idProduk,
                    'jumlah_diminta' => $satu['jumlah_diminta'],
                    'perkiraan_harga' => $satu['perkiraan_harga'],
                    'jumlah_diterima' => 0,
                    'catatan' => $satu['catatan'],
                ]);
            }

            $this->audit->catat(
                AuditService::PENGAJUAN_PENGADAAN,
                $pengadaan,
                null,
                [
                    'nomor_pengadaan' => $pengadaan->nomor_pengadaan,
                    'id_supplier' => $pengadaan->id_supplier,
                    'barang' => $baris,
                ],
                $idPengaju,
            );

            return $pengadaan->load('item.produk', 'pengaju', 'supplier');
        });
    }

    /**
     * Ajukan ulang setelah REVISI/DITOLAK (GUDANG).
     *
     * @param  array{item: list<array{id_produk:int, jumlah_diminta:int, perkiraan_harga?:int|null, catatan?:string|null}>, alasan?:string|null, id_supplier?:int|null, tanggal_kebutuhan?:string|null}  $data
     */
    public function ajukanUlang(Pengadaan $pengadaan, array $data, ?int $idPengguna = null): Pengadaan
    {
        if (! $pengadaan->status->dapatDiajukanUlang()) {
            throw new ApiException(
                'Pengadaan berstatus '.$pengadaan->status->label().' tidak dapat diajukan ulang.',
                'VALIDATION_ERROR',
                422,
                ['status' => ['Hanya pengadaan DITOLAK atau REVISI yang dapat diajukan ulang.']],
            );
        }

        return DB::transaction(function () use ($pengadaan, $data, $idPengguna): Pengadaan {
            $baris = $this->gabungItem($data['item']);

            $this->pastikanProdukAda(array_keys($baris));

            // Ganti seluruh baris — riwayat keputusan tetap tersimpan di kolom pengadaan.
            $pengadaan->item()->delete();

            foreach ($baris as $idProduk => $satu) {
                $pengadaan->item()->create([
                    'id_produk' => $idProduk,
                    'jumlah_diminta' => $satu['jumlah_diminta'],
                    'perkiraan_harga' => $satu['perkiraan_harga'],
                    'jumlah_diterima' => 0,
                    'catatan' => $satu['catatan'],
                ]);
            }

            $sebelum = [
                'status' => $pengadaan->status->value,
                'catatan_keputusan' => $pengadaan->catatan_keputusan,
            ];

            $pengadaan->forceFill([
                'status' => StatusPengadaan::Diajukan->value,
                'id_supplier' => $data['id_supplier'] ?? $pengadaan->id_supplier,
                'tanggal_kebutuhan' => $data['tanggal_kebutuhan'] ?? $pengadaan->tanggal_kebutuhan,
                'alasan' => $data['alasan'] ?? $pengadaan->alasan,
                'id_penyetuju' => null,
                'catatan_keputusan' => null,
                'diputuskan_at' => null,
            ])->save();

            $this->audit->catat(
                AuditService::PENGAJUAN_PENGADAAN,
                $pengadaan,
                $sebelum,
                ['status' => StatusPengadaan::Diajukan->value],
                $idPengguna,
            );

            return $pengadaan->refresh()->load('item.produk', 'pengaju', 'supplier');
        });
    }

    /**
     * Putuskan pengajuan: SETUJUI / TOLAK / REVISI (FINANCE/OWNER).
     */
    public function putuskan(
        Pengadaan $pengadaan,
        KeputusanPengadaan $keputusan,
        ?string $catatan = null,
        ?int $idPenyetuju = null,
    ): Pengadaan {
        if (! $pengadaan->status->dapatDiputuskan()) {
            throw new ApiException(
                'Pengadaan berstatus '.$pengadaan->status->label().' tidak dapat diputuskan.',
                'VALIDATION_ERROR',
                422,
                ['status' => ['Hanya pengadaan DIAJUKAN yang dapat diputuskan.']],
            );
        }

        $catatan = $catatan === null ? null : trim($catatan);

        if ($keputusan->wajibAlasan() && ($catatan === null || $catatan === '')) {
            throw new ApiException(
                'Alasan wajib diisi untuk penolakan atau permintaan revisi.',
                'VALIDATION_ERROR',
                422,
                ['catatan' => ['Alasan wajib diisi.']],
            );
        }

        $penyetuju = $idPenyetuju ?? (int) auth()->id();

        // Pemisahan tugas: pengaju tidak boleh memutuskan pengadaannya sendiri (P5).
        if ((int) $pengadaan->id_pengaju === $penyetuju) {
            throw new ApiException(
                'Pengaju tidak boleh menyetujui pengadaannya sendiri.',
                'FORBIDDEN',
                403,
            );
        }

        return DB::transaction(function () use ($pengadaan, $keputusan, $catatan, $penyetuju): Pengadaan {
            $sebelum = ['status' => $pengadaan->status->value];

            $pengadaan->forceFill([
                'status' => $keputusan->statusBaru()->value,
                'id_penyetuju' => $penyetuju,
                'catatan_keputusan' => $catatan,
                'diputuskan_at' => now(),
            ])->save();

            $this->audit->catat(
                $keputusan === KeputusanPengadaan::Setujui
                    ? AuditService::PENGADAAN_SETUJUI
                    : AuditService::PENGADAAN_TOLAK,
                $pengadaan,
                $sebelum,
                [
                    'keputusan' => $keputusan->value,
                    'status' => $pengadaan->status->value,
                    'catatan' => $catatan,
                ],
                $penyetuju,
            );

            return $pengadaan->refresh()->load('item.produk', 'pengaju', 'penyetuju', 'supplier');
        });
    }

    /**
     * Tutup pengadaan (FINANCE/OWNER) — hanya setelah barang diterima.
     */
    public function tutup(Pengadaan $pengadaan, ?string $catatan = null, ?int $idPengguna = null): Pengadaan
    {
        if (! $pengadaan->status->dapatDitutup()) {
            throw new ApiException(
                'Pengadaan belum diterima sehingga belum dapat ditutup.',
                'PENGADAAN_BELUM_DITERIMA',
                422,
                ['status' => ['Hanya pengadaan DITERIMA yang dapat ditutup.']],
            );
        }

        return DB::transaction(function () use ($pengadaan, $catatan, $idPengguna): Pengadaan {
            $sebelum = ['status' => $pengadaan->status->value];

            $pengadaan->forceFill([
                'status' => StatusPengadaan::Ditutup->value,
                'ditutup_at' => now(),
                'catatan_keputusan' => $catatan ?? $pengadaan->catatan_keputusan,
            ])->save();

            $this->audit->catat(
                AuditService::PENGADAAN_TUTUP,
                $pengadaan,
                $sebelum,
                ['status' => StatusPengadaan::Ditutup->value],
                $idPengguna,
            );

            return $pengadaan->refresh()->load('item.produk', 'pengaju', 'penyetuju', 'supplier');
        });
    }

    /**
     * Tandai pengadaan sudah menerima barang (dipanggil PenerimaanService).
     */
    public function tandaiDiterima(Pengadaan $pengadaan): void
    {
        if ($pengadaan->status !== StatusPengadaan::Disetujui) {
            throw new ApiException(
                'Pengadaan belum disetujui sehingga barang tidak dapat diterima.',
                'PENGAADAAN_BELUM_DISETUJUI',
                422,
                ['id_pengadaan' => ['Pengadaan belum disetujui.']],
            );
        }

        $pengadaan->forceFill([
            'status' => StatusPengadaan::Diterima->value,
            'diterima_at' => now(),
        ])->save();
    }

    private function cariSupplier(?int $idSupplier): ?Supplier
    {
        if ($idSupplier === null) {
            return null;
        }

        $supplier = Supplier::query()->find($idSupplier);

        if ($supplier === null) {
            throw new ApiException('Supplier tidak ditemukan.', 'NOT_FOUND', 404, [
                'id_supplier' => ['Supplier tidak ditemukan.'],
            ]);
        }

        return $supplier;
    }

    /**
     * Gabungkan baris duplikat per produk.
     *
     * @param  list<array{id_produk:int, jumlah_diminta:int, perkiraan_harga?:int|null, catatan?:string|null}>  $item
     * @return array<int, array{jumlah_diminta:int, perkiraan_harga:int|null, catatan:string|null}>
     */
    private function gabungItem(array $item): array
    {
        $hasil = [];

        foreach ($item as $satu) {
            $idProduk = (int) $satu['id_produk'];

            $hasil[$idProduk] ??= [
                'jumlah_diminta' => 0,
                'perkiraan_harga' => isset($satu['perkiraan_harga']) ? (int) $satu['perkiraan_harga'] : null,
                'catatan' => $satu['catatan'] ?? null,
            ];

            $hasil[$idProduk]['jumlah_diminta'] += (int) $satu['jumlah_diminta'];
        }

        return $hasil;
    }

    /**
     * @param  list<int>  $ids
     */
    private function pastikanProdukAda(array $ids): void
    {
        $ada = Stok::query()->whereIn('id_produk', $ids)->count();

        if ($ada !== count($ids)) {
            throw new ApiException(
                'Ada produk yang tidak ditemukan pada tabel stok.',
                'NOT_FOUND',
                404,
                ['item' => ['Produk tidak ditemukan.']],
            );
        }
    }
}
