<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Penomoran dokumen ERP: {PREFIX}/{YYYY}/{MM}/{NNNN}.
 *
 * Satu pintu untuk seluruh jenis dokumen (AR3). Nomor yang pernah terbit
 * TIDAK pernah dipakai ulang — termasuk dokumen yang di-soft delete.
 */
class NomorDokumenService
{
    /**
     * Terbitkan nomor berikutnya untuk jenis dokumen tertentu.
     *
     * @param  string  $prefix  Prefix dokumen, mis. `INV`, `PGD`, `BM`, `BK`, `OPN`.
     * @param  string  $tabel  Nama tabel sumber, mis. `transaksi`.
     * @param  string  $kolom  Nama kolom nomor, mis. `nomor_struk`.
     */
    public function berikutnya(string $prefix, string $tabel, string $kolom): string
    {
        $periode = now()->format('Y/m');
        $awalan = $prefix.'/'.$periode.'/';

        // Query builder tidak menerapkan global scope soft delete, sehingga baris
        // yang sudah di-soft delete tetap terhitung — nomor tidak pernah didaur ulang.
        $maksimum = DB::table($tabel)
            ->where($kolom, 'like', $awalan.'%')
            ->pluck($kolom)
            ->reduce(function (int $bawa, string $nomor): int {
                if (preg_match('#/(\d+)$#', $nomor, $cocok) !== 1) {
                    return $bawa;
                }

                return max($bawa, (int) $cocok[1]);
            }, 0);

        return sprintf('%s%04d', $awalan, $maksimum + 1);
    }

    /**
     * Nomor struk penjualan.
     */
    public function struk(): string
    {
        return $this->berikutnya(
            (string) config('erp.dokumen.struk', 'INV'),
            'transaksi',
            'nomor_struk',
        );
    }

    /**
     * Nomor pengadaan.
     */
    public function pengadaan(): string
    {
        return $this->berikutnya(
            (string) config('erp.dokumen.pengadaan', 'PGD'),
            'pengadaan',
            'nomor_pengadaan',
        );
    }

    /**
     * Nomor barang masuk (penerimaan).
     */
    public function barangMasuk(): string
    {
        return $this->berikutnya(
            (string) config('erp.dokumen.barang_masuk', 'BM'),
            'penerimaan',
            'nomor_penerimaan',
        );
    }

    /**
     * Nomor barang keluar (pengeluaran).
     */
    public function barangKeluar(): string
    {
        return $this->berikutnya(
            (string) config('erp.dokumen.barang_keluar', 'BK'),
            'pengeluaran',
            'nomor_pengeluaran',
        );
    }

    /**
     * Nomor opname.
     */
    public function opname(): string
    {
        return $this->berikutnya(
            (string) config('erp.dokumen.opname', 'OPN'),
            'opname',
            'nomor_opname',
        );
    }
}
