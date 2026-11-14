<?php

namespace App\Services;

use App\Enums\JenisMutasiStok;
use App\Models\MutasiStok;
use App\Models\Stok;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Kartu stok & pemantauan persediaan (G-09).
 *
 * Kartu stok = seluruh baris `mutasi_stok` satu produk beserta sumbernya,
 * sehingga setiap angka stok dapat ditelusuri (AR2).
 */
class KartuStokService
{
    /**
     * Kartu stok satu produk.
     *
     * `jumlah_perubahan` bertanda: positif = masuk, negatif = keluar.
     *
     * @return LengthAwarePaginator<int, MutasiStok>
     */
    public function kartu(
        Stok $stok,
        ?string $dari = null,
        ?string $sampai = null,
        ?string $jenis = null,
        int $perPage = 50,
    ): LengthAwarePaginator {
        return MutasiStok::query()
            ->with('pengguna:id,name,email')
            ->where('id_produk', $stok->id_produk)
            ->betweenDates($dari, $sampai)
            ->when($jenis !== null && $jenis !== '', fn (Builder $q) => $q->where('jenis_mutasi', $jenis))
            ->orderBy('tanggal_mutasi')
            ->orderBy('id_mutasi')
            ->paginate($perPage);
    }

    /**
     * Produk yang kuantitasnya di bawah/di batas minimum — dasar pengajuan pengadaan.
     *
     * @return LengthAwarePaginator<int, Stok>
     */
    public function stokMenipis(?int $idKategori = null, int $perPage = 50): LengthAwarePaginator
    {
        return Stok::query()
            ->with('kategori:id_kategori,nama_kategori')
            ->whereColumn('kuantitas', '<=', 'stok_minimum')
            ->byKategori($idKategori)
            ->orderByRaw('(CAST(kuantitas AS SIGNED) - CAST(stok_minimum AS SIGNED)) asc')
            ->orderBy('nama_produk')
            ->paginate($perPage);
    }

    /**
     * Ringkasan persediaan untuk dasbor gudang.
     *
     * @return array<string, int|float>
     */
    public function ringkasan(): array
    {
        $totalProduk = Stok::query()->count();
        $produkMenipis = Stok::query()->whereColumn('kuantitas', '<=', 'stok_minimum')->count();
        $produkKosong = Stok::query()->where('kuantitas', '<=', 0)->count();
        $nilaiPersediaan = (int) Stok::query()
            ->selectRaw('COALESCE(SUM(kuantitas * COALESCE(harga_modal, 0)), 0) AS nilai')
            ->value('nilai');

        return [
            'total_produk' => $totalProduk,
            'produk_menipis' => $produkMenipis,
            'produk_kosong' => $produkKosong,
            'nilai_persediaan' => $nilaiPersediaan,
        ];
    }

    /**
     * Label jenis mutasi yang tersedia (untuk filter frontend).
     *
     * @return array<string, string>
     */
    public function jenisMutasiOptions(): array
    {
        $hasil = [];

        foreach (JenisMutasiStok::cases() as $jenis) {
            $hasil[$jenis->value] = $jenis->label();
        }

        return $hasil;
    }
}
