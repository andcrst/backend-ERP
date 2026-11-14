<?php

namespace App\Services;

use App\Enums\AlasanPengeluaran;
use App\Enums\JenisMutasiStok;
use App\Enums\StatusDokumen;
use App\Exceptions\ApiException;
use App\Models\Pengeluaran;
use App\Models\Stok;
use Illuminate\Support\Facades\DB;

/**
 * Barang keluar non-penjualan (G-05).
 *
 * Aturan:
 * - `alasan` WAJIB; alasan `LAINNYA` wajib disertai `catatan`.
 * - Draf tidak menyentuh stok. Posting mengurangi stok lewat `StokMutasiService`
 *   (satu-satunya pintu perubahan `stok.kuantitas`).
 * - Stok minus ditolak `422 STOK_TIDAK_CUKUP` + rollback.
 * - Dokumen yang sudah diposting tidak dapat diposting ulang (idempoten aman).
 */
class PengeluaranService
{
    public function __construct(
        private readonly StokMutasiService $mutasi,
        private readonly NomorDokumenService $nomor,
        private readonly AuditService $audit,
    ) {}

    /**
     * Buat dokumen pengeluaran berstatus draf.
     *
     * @param  array{
     *     tanggal_pengeluaran?: string|null,
     *     alasan: string,
     *     catatan?: string|null,
     *     item: list<array{id_produk:int, jumlah:int, catatan?:string|null}>
     * }  $data
     */
    public function buat(array $data, ?int $idPengguna = null): Pengeluaran
    {
        $alasan = AlasanPengeluaran::from($data['alasan']);
        $catatan = $data['catatan'] ?? null;

        // LAINNYA wajib keterangan (agar sebabnya dapat diaudit).
        if ($alasan->wajibCatatan() && ($catatan === null || trim($catatan) === '')) {
            throw new ApiException(
                'Alasan "Lainnya" wajib disertai catatan.',
                'VALIDATION_ERROR',
                422,
                ['catatan' => ['Catatan wajib diisi untuk alasan Lainnya.']],
            );
        }

        return DB::transaction(function () use ($data, $alasan, $catatan, $idPengguna): Pengeluaran {
            $baris = $this->gabungItem($data['item']);

            $this->pastikanProdukAda(array_keys($baris));

            $dokumen = Pengeluaran::create([
                'nomor_pengeluaran' => $this->nomor->barangKeluar(),
                'tanggal_pengeluaran' => $data['tanggal_pengeluaran'] ?? now()->toDateString(),
                'alasan' => $alasan->value,
                'id_pengguna' => $idPengguna ?? (int) auth()->id(),
                'status' => StatusDokumen::Draft->value,
                'catatan' => $catatan,
            ]);

            foreach ($baris as $idProduk => $satu) {
                $dokumen->item()->create([
                    'id_produk' => $idProduk,
                    'jumlah' => $satu['jumlah'],
                    'catatan' => $satu['catatan'],
                ]);
            }

            return $dokumen->load('item.produk', 'pengguna');
        });
    }

    /**
     * Posting dokumen: stok berkurang + tercatat di ledger & audit.
     */
    public function posting(Pengeluaran $dokumen, ?int $idPengguna = null): Pengeluaran
    {
        if ($dokumen->sudahDiposting()) {
            throw new ApiException(
                'Dokumen pengeluaran ini sudah diposting.',
                'VALIDATION_ERROR',
                422,
                ['status' => ['Dokumen sudah diposting.']],
            );
        }

        return DB::transaction(function () use ($dokumen, $idPengguna): Pengeluaran {
            $item = $dokumen->item()->get();

            if ($item->isEmpty()) {
                throw new ApiException(
                    'Dokumen pengeluaran belum memiliki barang.',
                    'VALIDATION_ERROR',
                    422,
                    ['item' => ['Minimal satu barang wajib diisi.']],
                );
            }

            $mutasi = $item->map(fn ($satu): array => [
                'id_produk' => (int) $satu->id_produk,
                'jumlah' => -(int) $satu->jumlah,
                'jenis' => JenisMutasiStok::Pengeluaran,
                'referensi_tipe' => 'pengeluaran',
                'referensi_id' => $dokumen->id_pengeluaran,
                'catatan' => sprintf(
                    '%s — %s',
                    $dokumen->nomor_pengeluaran,
                    $dokumen->alasan->label(),
                ),
            ])->all();

            $this->mutasi->catatBanyak($mutasi, $idPengguna);

            $dokumen->forceFill([
                'status' => StatusDokumen::Posted->value,
                'posted_at' => now(),
            ])->save();

            $this->audit->catat(
                AuditService::STOK_PENYESUAIAN,
                $dokumen,
                null,
                [
                    'nomor_pengeluaran' => $dokumen->nomor_pengeluaran,
                    'alasan' => $dokumen->alasan->value,
                    'barang' => $dokumen->jumlahPerProduk(),
                ],
                $idPengguna,
            );

            return $dokumen->refresh()->load('item.produk', 'pengguna');
        });
    }

    /**
     * Gabungkan baris duplikat per produk agar satu produk hanya satu mutasi.
     *
     * @param  list<array{id_produk:int, jumlah:int, catatan?:string|null}>  $item
     * @return array<int, array{jumlah:int, catatan:string|null}>
     */
    private function gabungItem(array $item): array
    {
        $hasil = [];

        foreach ($item as $satu) {
            $idProduk = (int) $satu['id_produk'];

            $hasil[$idProduk] ??= ['jumlah' => 0, 'catatan' => $satu['catatan'] ?? null];
            $hasil[$idProduk]['jumlah'] += (int) $satu['jumlah'];
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
