<?php

namespace App\Services;

use App\Enums\JenisMutasiStok;
use App\Exceptions\ApiException;
use App\Models\MutasiStok;
use App\Models\Stok;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Satu-satunya pintu perubahan `stok.kuantitas` (P1 & AR2).
 *
 * Setiap pemanggilan WAJIB berada di dalam DB transaction milik pemanggil agar
 * baris stok terkunci konsisten dan rollback mencakup ledger + stok sekaligus.
 */
class StokMutasiService
{
    /**
     * Catat beberapa perubahan stok sekaligus (atomik).
     *
     * @param  list<array{id_produk:int, jumlah:int, jenis:JenisMutasiStok, referensi_tipe?:string|null, referensi_id?:int|null, catatan?:string|null}>  $baris
     * @return Collection<int, MutasiStok>
     */
    public function catatBanyak(array $baris, ?int $idPengguna = null): Collection
    {
        if ($baris === []) {
            return collect();
        }

        return DB::transaction(function () use ($baris, $idPengguna): Collection {
            // Kunci baris stok dengan urutan id agar bebas deadlock.
            $ids = collect($baris)->pluck('id_produk')->unique()->sort()->values()->all();

            /** @var array<int, Stok> $stok */
            $stok = Stok::query()
                ->whereIn('id_produk', $ids)
                ->lockForUpdate()
                ->get()
                ->keyBy('id_produk')
                ->all();

            $hasil = collect();

            foreach ($baris as $item) {
                $produk = $stok[$item['id_produk']] ?? null;

                if ($produk === null) {
                    throw new ApiException(
                        'Produk tidak ditemukan pada tabel stok.',
                        'NOT_FOUND',
                        404,
                    );
                }

                $hasil->push($this->catatSatuBaris($produk, $item, $idPengguna));
            }

            return $hasil;
        });
    }

    /**
     * Catat satu perubahan stok.
     *
     * @param  array{jumlah:int, jenis:JenisMutasiStok, referensi_tipe?:string|null, referensi_id?:int|null, catatan?:string|null}  $item
     */
    public function catat(
        Stok $stok,
        int $jumlah,
        JenisMutasiStok $jenis,
        ?string $referensiTipe = null,
        ?int $referensiId = null,
        ?int $idPengguna = null,
        ?string $catatan = null,
    ): MutasiStok {
        return $this->catatSatuBaris($stok, [
            'jumlah' => $jumlah,
            'jenis' => $jenis,
            'referensi_tipe' => $referensiTipe,
            'referensi_id' => $referensiId,
            'catatan' => $catatan,
        ], $idPengguna);
    }

    /**
     * Pastikan stok mencukupi sebelum mutasi negatif (pesan spesifik per field).
     */
    public function pastikanCukup(Stok $stok, int $jumlah): void
    {
        if ($jumlah > 0 && $stok->kuantitas < $jumlah) {
            throw new ApiException(
                sprintf(
                    'Stok %s tidak mencukupi (tersedia %d, diminta %d).',
                    $stok->nama_produk,
                    $stok->kuantitas,
                    $jumlah,
                ),
                'STOK_TIDAK_CUKUP',
                422,
                ['jumlah' => ['Stok tidak mencukupi.']],
            );
        }
    }

    /**
     * @param  array{jumlah:int, jenis:JenisMutasiStok, referensi_tipe?:string|null, referensi_id?:int|null, catatan?:string|null}  $item
     */
    private function catatSatuBaris(Stok $stok, array $item, ?int $idPengguna): MutasiStok
    {
        $jumlah = (int) $item['jumlah'];

        if ($jumlah === 0) {
            throw new ApiException(
                'Jumlah perubahan stok tidak boleh nol.',
                'VALIDATION_ERROR',
                422,
                ['jumlah' => ['Jumlah perubahan tidak boleh nol.']],
            );
        }

        $sebelum = (int) $stok->kuantitas;
        $sesudah = $sebelum + $jumlah;

        if ($sesudah < 0) {
            $this->pastikanCukup($stok, -$jumlah);
        }

        // Update stok on-hand (satu-satunya jalur yang sah).
        $stok->forceFill(['kuantitas' => $sesudah])->save();

        return MutasiStok::create([
            'id_produk' => $stok->id_produk,
            'tanggal_mutasi' => now(),
            'jenis_mutasi' => $item['jenis']->value,
            'jumlah_perubahan' => $jumlah,
            'kuantitas_sebelum' => $sebelum,
            'kuantitas_sesudah' => $sesudah,
            'referensi_tipe' => $item['referensi_tipe'] ?? null,
            'referensi_id' => $item['referensi_id'] ?? null,
            'id_pengguna' => $idPengguna,
            'catatan' => $item['catatan'] ?? null,
        ]);
    }
}
