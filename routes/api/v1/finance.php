<?php

use App\Http\Controllers\Api\V1\Finance\DashboardController;
use App\Http\Controllers\Api\V1\Finance\RekapController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Modul Finance (CP-4) — BACA SAJA
|--------------------------------------------------------------------------
| Finance tidak membuat/mengubah transaksi. Semua agregasi di RekapService.
| Transaksi dibatalkan otomatis dikecualikan; D-06 (rekonsiliasi kas &
| tutup buku) TETAP HOLD — endpoint-nya sengaja tidak dibuat.
*/
Route::middleware(['auth:sanctum', 'active'])->group(function (): void {

    Route::get('finance/dashboard', [DashboardController::class, 'index'])
        ->middleware('permission:finance.dashboard')->name('api.v1.finance.dashboard');
    Route::get('finance/dashboard/opsi-pembayaran', [DashboardController::class, 'opsiPembayaran'])
        ->middleware('permission:finance.dashboard')->name('api.v1.finance.dashboard.opsi_pembayaran');

    Route::get('finance/jenis-rekap', [RekapController::class, 'jenis'])
        ->middleware('permission:finance.rekap')->name('api.v1.finance.jenis_rekap');

    // Rekap penjualan & pendapatan.
    Route::get('finance/rekap/penjualan/harian', [RekapController::class, 'harian'])
        ->middleware('permission:finance.rekap')->name('api.v1.finance.rekap.harian');
    Route::get('finance/rekap/penjualan/bulanan', [RekapController::class, 'bulanan'])
        ->middleware('permission:finance.rekap')->name('api.v1.finance.rekap.bulanan');
    Route::get('finance/rekap/pendapatan', [RekapController::class, 'pendapatan'])
        ->middleware('permission:finance.rekap')->name('api.v1.finance.rekap.pendapatan');
    Route::get('finance/rekap/pembayaran', [RekapController::class, 'pembayaran'])
        ->middleware('permission:finance.rekap')->name('api.v1.finance.rekap.pembayaran');

    // Rekap per kasir, produk, laba kotor, transaksi, stok.
    Route::get('finance/rekap/kasir', [RekapController::class, 'kasir'])
        ->middleware('permission:finance.rekap')->name('api.v1.finance.rekap.kasir');
    Route::get('finance/rekap/produk', [RekapController::class, 'produk'])
        ->middleware('permission:finance.rekap')->name('api.v1.finance.rekap.produk');
    Route::get('finance/rekap/laba-kotor', [RekapController::class, 'labaKotor'])
        ->middleware('permission:finance.rekap')->name('api.v1.finance.rekap.laba_kotor');
    Route::get('finance/rekap/transaksi', [RekapController::class, 'transaksi'])
        ->middleware('permission:finance.rekap')->name('api.v1.finance.rekap.transaksi');
    Route::get('finance/rekap/stok', [RekapController::class, 'stok'])
        ->middleware('permission:finance.rekap')->name('api.v1.finance.rekap.stok');

    // Ekspor CSV.
    Route::get('finance/rekap/{jenis}/ekspor', [RekapController::class, 'ekspor'])
        ->middleware('permission:finance.ekspor')->name('api.v1.finance.rekap.ekspor');
});
