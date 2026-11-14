<?php

use App\Http\Controllers\Api\V1\Sistem\AuditLogController;
use App\Http\Controllers\Api\V1\Sistem\PengaturanController;
use App\Http\Controllers\Api\V1\Sistem\RoleController;
use App\Http\Controllers\Api\V1\Sistem\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Modul Sistem (CP-5)
|--------------------------------------------------------------------------
| Manajemen pengguna, role & permission, audit log, pengaturan toko.
| Perubahan sensitif mencabut token pengguna terdampak dan dicatat di audit_log.
*/
Route::middleware(['auth:sanctum', 'active'])->group(function (): void {

    // --- Pengguna ---
    Route::get('sistem/user/opsi-role', [UserController::class, 'opsiRole'])
        ->middleware('permission:user.view')->name('api.v1.sistem.user.opsi_role');

    Route::get('sistem/user', [UserController::class, 'index'])
        ->middleware('permission:user.view')->name('api.v1.sistem.user.index');
    Route::post('sistem/user', [UserController::class, 'store'])
        ->middleware('permission:user.create')->name('api.v1.sistem.user.store');
    Route::get('sistem/user/{user}', [UserController::class, 'show'])
        ->middleware('permission:user.view')->name('api.v1.sistem.user.show');
    Route::put('sistem/user/{user}', [UserController::class, 'update'])
        ->middleware('permission:user.update')->name('api.v1.sistem.user.update');
    Route::delete('sistem/user/{user}', [UserController::class, 'destroy'])
        ->middleware('permission:user.delete')->name('api.v1.sistem.user.destroy');

    // Perubahan peran — permission khusus (OWNER).
    Route::patch('sistem/user/{user}/role', [UserController::class, 'ubahRole'])
        ->middleware('permission:user.ubah_role')->name('api.v1.sistem.user.role');
    Route::patch('sistem/user/{user}/status', [UserController::class, 'ubahStatus'])
        ->middleware('permission:user.update')->name('api.v1.sistem.user.status');
    Route::post('sistem/user/{user}/reset-password', [UserController::class, 'resetPassword'])
        ->middleware('permission:user.reset_password')->name('api.v1.sistem.user.reset_password');

    // --- Role & permission ---
    Route::get('sistem/role', [RoleController::class, 'index'])
        ->middleware('permission:role.view')->name('api.v1.sistem.role.index');
    Route::get('sistem/permission', [RoleController::class, 'permission'])
        ->middleware('permission:role.view')->name('api.v1.sistem.permission.index');
    Route::put('sistem/role/{role}/permission', [RoleController::class, 'update'])
        ->middleware('permission:role.update')->name('api.v1.sistem.role.permission');

    // --- Audit log ---
    Route::get('sistem/audit-log', [AuditLogController::class, 'index'])
        ->middleware('permission:audit.view')->name('api.v1.sistem.audit_log');

    // --- Pengaturan toko ---
    Route::get('sistem/pengaturan', [PengaturanController::class, 'show'])
        ->middleware('permission:pengaturan.view')->name('api.v1.sistem.pengaturan.show');
    Route::put('sistem/pengaturan', [PengaturanController::class, 'update'])
        ->middleware('permission:pengaturan.update')->name('api.v1.sistem.pengaturan.update');
});
