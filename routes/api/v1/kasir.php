<?php

use App\Http\Controllers\Api\V1\Kasir\PelangganController;
use App\Http\Controllers\Api\V1\Kasir\PesananController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Modul Kasir / POS (CP-1)
|--------------------------------------------------------------------------
| Status melekat pada BARANG (4 status). Stok berkurang untuk DIAMBIL
| (saat simpan) dan SUDAH_DIANTAR (saat gudang konfirmasi).
*/
Route::middleware(['auth:sanctum', 'active'])->group(function (): void {

    // --- Pelanggan (D-03=A) ---
    Route::get('kasir/pelanggan', [PelangganController::class, 'index'])
        ->middleware('permission:pelanggan.view')->name('api.v1.kasir.pelanggan.index');
    Route::post('kasir/pelanggan', [PelangganController::class, 'store'])
        ->middleware('permission:pelanggan.create')->name('api.v1.kasir.pelanggan.store');
    Route::get('kasir/pelanggan/{pelanggan}', [PelangganController::class, 'show'])
        ->middleware('permission:pelanggan.view')->name('api.v1.kasir.pelanggan.show');
    Route::put('kasir/pelanggan/{pelanggan}', [PelangganController::class, 'update'])
        ->middleware('permission:pelanggan.update')->name('api.v1.kasir.pelanggan.update');
    Route::delete('kasir/pelanggan/{pelanggan}', [PelangganController::class, 'destroy'])
        ->middleware('permission:pelanggan.delete')->name('api.v1.kasir.pelanggan.destroy');
    Route::get('kasir/pelanggan/{pelanggan}/riwayat', [PelangganController::class, 'riwayat'])
        ->middleware('permission:pelanggan.view')->name('api.v1.kasir.pelanggan.riwayat');

    // --- Pesanan (checkout) ---
    Route::get('kasir/pesanan', [PesananController::class, 'index'])
        ->middleware('permission:transaksi.view')->name('api.v1.kasir.pesanan.index');
    Route::post('kasir/pesanan', [PesananController::class, 'store'])
        ->middleware('permission:transaksi.create')->name('api.v1.kasir.pesanan.store');
    Route::get('kasir/pesanan/{pesanan}', [PesananController::class, 'show'])
        ->middleware('permission:transaksi.view')->name('api.v1.kasir.pesanan.show');
    Route::get('kasir/pesanan/{pesanan}/struk', [PesananController::class, 'struk'])
        ->middleware('permission:transaksi.view')->name('api.v1.kasir.pesanan.struk');

    // Void — KHUSUS OWNER (D-04a=A).
    Route::delete('kasir/pesanan/{pesanan}', [PesananController::class, 'destroy'])
        ->middleware('permission:transaksi.void')->name('api.v1.kasir.pesanan.destroy');

    // --- Pengantaran: dikerjakan Petugas Gudang (D-21) ---
    Route::get('kasir/tugas-antar', [PesananController::class, 'tugasAntar'])
        ->middleware('permission:stok.mutasi')->name('api.v1.kasir.tugas_antar');

    Route::patch('kasir/pesanan/{pesanan}/barang/{barang}', [PesananController::class, 'ubahStatusBarang'])
        ->middleware('permission:stok.keluar.posting')->name('api.v1.kasir.barang.status');

    Route::patch('kasir/pesanan/{pesanan}/biaya-angkut', [PesananController::class, 'ubahBiayaAngkut'])
        ->middleware('permission:stok.keluar.posting')->name('api.v1.kasir.biaya_angkut');
});
