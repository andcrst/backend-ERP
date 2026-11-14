<?php

use App\Http\Controllers\Api\V1\Gudang\KartuStokController;
use App\Http\Controllers\Api\V1\Gudang\OpnameController;
use App\Http\Controllers\Api\V1\Gudang\PengeluaranController;
use App\Http\Controllers\Api\V1\Gudang\SupplierController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Modul Gudang & Persediaan (CP-2)
|--------------------------------------------------------------------------
| Barang keluar beralasan, sesi opname dua tahap, kartu stok, stok menipis.
| Setiap perubahan stok lewat StokMutasiService — tidak ada jalur lain.
*/
Route::middleware(['auth:sanctum', 'active'])->group(function (): void {

    // --- Supplier (G-08, D-03=A) ---
    Route::get('gudang/supplier', [SupplierController::class, 'index'])
        ->middleware('permission:supplier.view')->name('api.v1.gudang.supplier.index');
    Route::post('gudang/supplier', [SupplierController::class, 'store'])
        ->middleware('permission:supplier.create')->name('api.v1.gudang.supplier.store');
    Route::get('gudang/supplier/{supplier}', [SupplierController::class, 'show'])
        ->middleware('permission:supplier.view')->name('api.v1.gudang.supplier.show');
    Route::put('gudang/supplier/{supplier}', [SupplierController::class, 'update'])
        ->middleware('permission:supplier.update')->name('api.v1.gudang.supplier.update');
    Route::delete('gudang/supplier/{supplier}', [SupplierController::class, 'destroy'])
        ->middleware('permission:supplier.delete')->name('api.v1.gudang.supplier.destroy');

    // --- Barang keluar non-penjualan (G-05) ---
    Route::get('gudang/pengeluaran/opsi-alasan', [PengeluaranController::class, 'opsiAlasan'])
        ->middleware('permission:stok.keluar.view')->name('api.v1.gudang.pengeluaran.opsi_alasan');

    Route::get('gudang/pengeluaran', [PengeluaranController::class, 'index'])
        ->middleware('permission:stok.keluar.view')->name('api.v1.gudang.pengeluaran.index');
    Route::post('gudang/pengeluaran', [PengeluaranController::class, 'store'])
        ->middleware('permission:stok.keluar.create')->name('api.v1.gudang.pengeluaran.store');
    Route::get('gudang/pengeluaran/{pengeluaran}', [PengeluaranController::class, 'show'])
        ->middleware('permission:stok.keluar.view')->name('api.v1.gudang.pengeluaran.show');
    Route::post('gudang/pengeluaran/{pengeluaran}/posting', [PengeluaranController::class, 'posting'])
        ->middleware('permission:stok.keluar.posting')->name('api.v1.gudang.pengeluaran.posting');

    // --- Opname (G-06) ---
    Route::get('gudang/opname/opsi-status', [OpnameController::class, 'opsiStatus'])
        ->middleware('permission:stok.view')->name('api.v1.gudang.opname.opsi_status');

    Route::get('gudang/opname', [OpnameController::class, 'index'])
        ->middleware('permission:stok.view')->name('api.v1.gudang.opname.index');
    Route::post('gudang/opname', [OpnameController::class, 'store'])
        ->middleware('permission:stok.opname.create')->name('api.v1.gudang.opname.store');
    Route::get('gudang/opname/{opname}', [OpnameController::class, 'show'])
        ->middleware('permission:stok.view')->name('api.v1.gudang.opname.show');

    // Hitung fisik — GUDANG.
    Route::patch('gudang/opname/{opname}/item/{item}', [OpnameController::class, 'hitungItem'])
        ->middleware('permission:stok.opname.create')->name('api.v1.gudang.opname.item');

    // Persetujuan — OWNER + FINANCE saja (D-04b=B).
    Route::post('gudang/opname/{opname}/setujui', [OpnameController::class, 'setujui'])
        ->middleware('permission:stok.opname.setujui')->name('api.v1.gudang.opname.setujui');

    // Pembatalan — OWNER saja.
    Route::post('gudang/opname/{opname}/batalkan', [OpnameController::class, 'batalkan'])
        ->middleware('permission:stok.opname.setujui')->name('api.v1.gudang.opname.batalkan');

    // --- Kartu stok & stok menipis (G-09) ---
    Route::get('gudang/stok-menipis', [KartuStokController::class, 'stokMenipis'])
        ->middleware('permission:stok.view')->name('api.v1.gudang.stok_menipis');
    Route::get('gudang/ringkasan-persediaan', [KartuStokController::class, 'ringkasan'])
        ->middleware('permission:stok.view')->name('api.v1.gudang.ringkasan');
    Route::get('gudang/jenis-mutasi', [KartuStokController::class, 'jenisMutasi'])
        ->middleware('permission:stok.view')->name('api.v1.gudang.jenis_mutasi');
    Route::get('gudang/kartu-stok/{stok}', [KartuStokController::class, 'kartu'])
        ->middleware('permission:stok.mutasi')->name('api.v1.gudang.kartu_stok');
});
