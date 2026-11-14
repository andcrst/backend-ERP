<?php

namespace Database\Seeders;

use App\Enums\JenisMutasiStok;
use App\Models\Kategori;
use App\Models\Stok;
use App\Services\StokMutasiService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Impor katalog dari database/data/produk_dobrak_mandiri.csv.
 *
 * Stok awal TIDAK diisi langsung ke `stok.kuantitas`: setiap produk baru
 * mendapat mutasi `SALDO_AWAL` lewat `StokMutasiService`, sehingga invarian
 * Σ `mutasi_stok.jumlah_perubahan` = `stok.kuantitas` berlaku sejak data awal.
 */
class ProductCatalogSeeder extends Seeder
{
    private const INITIAL_QTY = 10;

    public function run(StokMutasiService $mutasi): void
    {
        $path = database_path('data/produk_dobrak_mandiri.csv');

        if (! is_file($path)) {
            $this->command?->warn("Katalog tidak ditemukan: {$path}");

            return;
        }

        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return;
        }

        fgetcsv($handle, null, ',', '"', '\\'); // lewati header

        DB::transaction(function () use ($handle, $mutasi): void {
            while (($row = fgetcsv($handle, null, ',', '"', '\\')) !== false) {
                if (count($row) < 4) {
                    continue;
                }

                $nama = trim((string) $row[2]);
                $harga = (int) $row[3];
                $kategori = trim((string) $row[1]);

                if ($nama === '') {
                    continue;
                }

                $sudahAda = Stok::query()->where('nama_produk', $nama)->exists();

                if ($sudahAda) {
                    continue;
                }

                $produk = Stok::create([
                    'kode_sku' => $this->uniqueSku($nama),
                    'nama_produk' => $nama,
                    'id_kategori' => $this->idKategori($kategori),
                    'harga' => $harga,
                    'kuantitas' => 0,
                ]);

                // Satu-satunya jalur perubahan stok (AR2).
                $mutasi->catat(
                    $produk,
                    self::INITIAL_QTY,
                    JenisMutasiStok::SaldoAwal,
                    'produk',
                    $produk->id_produk,
                    null,
                    'Stok awal dari impor katalog',
                );
            }
        });

        fclose($handle);
    }

    private function uniqueSku(string $nama): string
    {
        $base = Str::upper(Str::slug($nama, '-'));
        $base = $base !== '' ? Str::limit($base, 40, '') : 'PRODUK';

        $candidate = $base;
        $counter = 1;

        while (Stok::query()->where('kode_sku', $candidate)->exists()) {
            $candidate = Str::limit($base, 40, '').'-'.(++$counter);
        }

        return $candidate;
    }

    /**
     * Id kategori berdasarkan nama; dibuat bila belum ada.
     */
    private function idKategori(string $nama): ?int
    {
        if ($nama === '') {
            return null;
        }

        return Kategori::query()->firstOrCreate(['nama_kategori' => $nama])->id_kategori;
    }
}
