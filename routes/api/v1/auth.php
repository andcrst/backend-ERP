<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Autentikasi
|--------------------------------------------------------------------------
| Rate limit login dapat diatur lewat config `erp.throttle.login`
| (format Laravel: "{maks},{menit}"). Default produksi: 5 percobaan/menit.
| Smoke test dapat menaikkan lewat env ERP_THROTTLE_LOGIN tanpa mengubah kode.
*/
Route::prefix('auth')->group(function (): void {
    Route::post('login', [AuthController::class, 'login'])
        ->middleware('throttle:'.config('erp.throttle.login', '5,1'))
        ->name('api.v1.auth.login');

    Route::middleware(['auth:sanctum', 'active'])->group(function (): void {
        Route::post('logout', [AuthController::class, 'logout'])->name('api.v1.auth.logout');
        Route::get('me', [AuthController::class, 'me'])->name('api.v1.auth.me');
        Route::patch('password', [AuthController::class, 'changePassword'])->name('api.v1.auth.password');
    });
});
