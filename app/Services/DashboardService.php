<?php

namespace App\Services;

use App\Enums\JenisPembayaran;
use Illuminate\Support\Facades\DB;

/**
 * Ringkasan dashboard Finance (CP-4) — BACA SAJA.
 *
 * Semua angka diturunkan dari `RekapService` agar konsisten dengan modul lain.
 */
class DashboardService
{
    public function __construct(private readonly RekapService $rekap) {}

    /**
     * Dashboard: ringkasan hari ini, bulan berjalan, dan tren 7 hari.
     *
     * @return array<string, mixed>
     */
    public function ringkas(?string $tanggal = null): array
    {
        $hariIni = $tanggal ?? now()->toDateString();
        $awalBulan = now()->startOfMonth()->toDateString();

        return [
            'hari_ini' => [
                'tanggal' => $hariIni,
                'penjualan' => $this->rekap->pendapatan($hariIni, $hariIni),
                'laba_kotor' => $this->rekap->labaKotor($hariIni, $hariIni),
            ],
            'bulan_berjalan' => [
                'dari' => $awalBulan,
                'sampai' => $hariIni,
                'penjualan' => $this->rekap->pendapatan($awalBulan, $hariIni),
                'laba_kotor' => $this->rekap->labaKotor($awalBulan, $hariIni),
            ],
            'tren_7_hari' => $this->rekap->harian(
                now()->subDays(6)->toDateString(),
                $hariIni,
            ),
            'persediaan' => $this->rekap->stok(),
            'piutang_berjalan' => $this->piutangBerjalan(),
            'perlu_perhatian' => [
                'barang_menunggu_diantar' => $this->hitungStatusBarang('MENUNGGU_DIANTAR'),
                'barang_gagal_diantar' => $this->hitungStatusBarang('GAGAL_DIANTAR'),
                'pengadaan_menunggu_keputusan' => $this->hitungPengadaan('DIAJUKAN'),
                'opname_menunggu_persetujuan' => $this->hitungOpname('DIHITUNG'),
            ],
        ];
    }

    /**
     * Transaksi BELUM_LUNAS yang belum dibatalkan.
     *
     * @return array{jumlah:int, nilai:int}
     */
    public function piutangBerjalan(): array
    {
        $baris = DB::table('transaksi')
            ->whereNull('deleted_at')
            ->where('status_pembayaran', 'BELUM_LUNAS')
            ->selectRaw('COUNT(*) AS jumlah')
            ->selectRaw('COALESCE(SUM(total_pembayaran), 0) AS nilai')
            ->first();

        return [
            'jumlah' => (int) ($baris->jumlah ?? 0),
            'nilai' => (int) ($baris->nilai ?? 0),
        ];
    }

    private function hitungStatusBarang(string $status): int
    {
        return DB::table('detail_transaksi')
            ->join('transaksi', 'transaksi.id_transaksi', '=', 'detail_transaksi.id_transaksi')
            ->whereNull('transaksi.deleted_at')
            ->where('detail_transaksi.status_barang', $status)
            ->count();
    }

    private function hitungPengadaan(string $status): int
    {
        return DB::table('pengadaan')->where('status', $status)->count();
    }

    private function hitungOpname(string $status): int
    {
        return DB::table('opname')->where('status', $status)->count();
    }

    /**
     * Opsi jenis pembayaran (untuk filter dashboard).
     *
     * @return array<string, string>
     */
    public function opsiPembayaran(): array
    {
        $hasil = [];

        foreach (JenisPembayaran::cases() as $jenis) {
            $hasil[$jenis->value] = $jenis->label();
        }

        return $hasil;
    }
}
