<?php

use App\Http\Controllers\Api\V1\Finance\PengadaanController as PengadaanFinanceController;
use App\Http\Controllers\Api\V1\Gudang\PenerimaanController;
use App\Http\Controllers\Api\V1\Gudang\PengadaanController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Modul Pengadaan (CP-3) — 4 tahap (D-19)
|--------------------------------------------------------------------------
| Ajukan (GUDANG) → putuskan (FINANCE/OWNER) → terima (GUDANG) → tutup (FINANCE/OWNER).
| Gudang hanya mengajukan & menerima; keputusan & penutupan ada di /finance/*.
*/
Route::middleware(['auth:sanctum', 'active'])->group(function (): void {

    // --- Pengajuan oleh GUDANG ---
    Route::get('gudang/pengadaan/opsi-status', [PengadaanController::class, 'opsiStatus'])
        ->middleware('permission:pengadaan.view')->name('api.v1.gudang.pengadaan.opsi_status');

    Route::get('gudang/pengadaan', [PengadaanController::class, 'index'])
        ->middleware('permission:pengadaan.view')->name('api.v1.gudang.pengadaan.index');
    Route::post('gudang/pengadaan', [PengadaanController::class, 'store'])
        ->middleware('permission:pengadaan.create')->name('api.v1.gudang.pengadaan.store');
    Route::get('gudang/pengadaan/{pengadaan}', [PengadaanController::class, 'show'])
        ->middleware('permission:pengadaan.view')->name('api.v1.gudang.pengadaan.show');
    Route::get('gudang/pengadaan/{pengadaan}/sisa', [PengadaanController::class, 'sisa'])
        ->middleware('permission:pengadaan.view')->name('api.v1.gudang.pengadaan.sisa');

    // Ajukan ulang setelah REVISI/DITOLAK (G-02a).
    Route::put('gudang/pengadaan/{pengadaan}/ajukan-ulang', [PengadaanController::class, 'ajukanUlang'])
        ->middleware('permission:pengadaan.create')->name('api.v1.gudang.pengadaan.ajukan_ulang');

    // --- Penerimaan barang (G-02) ---
    Route::get('gudang/penerimaan', [PenerimaanController::class, 'index'])
        ->middleware('permission:stok.masuk.view')->name('api.v1.gudang.penerimaan.index');
    Route::post('gudang/pengadaan/{pengadaan}/penerimaan', [PenerimaanController::class, 'store'])
        ->middleware('permission:stok.masuk.create')->name('api.v1.gudang.penerimaan.store');
    Route::get('gudang/penerimaan/{penerimaan}', [PenerimaanController::class, 'show'])
        ->middleware('permission:stok.masuk.view')->name('api.v1.gudang.penerimaan.show');
    Route::post('gudang/penerimaan/{penerimaan}/posting', [PenerimaanController::class, 'posting'])
        ->middleware('permission:stok.masuk.posting')->name('api.v1.gudang.penerimaan.posting');

    // --- Keputusan & penutupan oleh FINANCE/OWNER ---
    Route::get('finance/pengadaan/opsi-keputusan', [PengadaanFinanceController::class, 'opsiKeputusan'])
        ->middleware('permission:pengadaan.view')->name('api.v1.finance.pengadaan.opsi_keputusan');

    Route::get('finance/pengadaan', [PengadaanFinanceController::class, 'index'])
        ->middleware('permission:pengadaan.view')->name('api.v1.finance.pengadaan.index');
    Route::get('finance/pengadaan/{pengadaan}', [PengadaanFinanceController::class, 'show'])
        ->middleware('permission:pengadaan.view')->name('api.v1.finance.pengadaan.show');

    Route::patch('finance/pengadaan/{pengadaan}/putuskan', [PengadaanFinanceController::class, 'putuskan'])
        ->middleware('permission:pengadaan.setujui')->name('api.v1.finance.pengadaan.putuskan');

    Route::patch('finance/pengadaan/{pengadaan}/tutup', [PengadaanFinanceController::class, 'tutup'])
        ->middleware('permission:pengadaan.tutup')->name('api.v1.finance.pengadaan.tutup');
});
