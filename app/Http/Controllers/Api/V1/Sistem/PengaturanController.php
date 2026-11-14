<?php

namespace App\Http\Controllers\Api\V1\Sistem;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePengaturanRequest;
use App\Services\AuditService;
use App\Services\PengaturanService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * Pengaturan toko (CP-5).
 */
class PengaturanController extends Controller
{
    public function __construct(
        private readonly PengaturanService $pengaturan,
        private readonly AuditService $audit,
    ) {}

    public function show(): JsonResponse
    {
        return ApiResponse::success([
            'nilai' => $this->pengaturan->semua(),
            'meta' => $this->pengaturan->meta(),
        ], 'Pengaturan toko.');
    }

    public function update(UpdatePengaturanRequest $request): JsonResponse
    {
        $sebelum = $this->pengaturan->semua();
        $sesudah = $this->pengaturan->simpan($request->validated());

        $this->audit->catat(
            AuditService::USER_UBAH_ROLE,
            null,
            $sebelum,
            $sesudah,
        );

        return ApiResponse::success(['nilai' => $sesudah], 'Pengaturan toko disimpan.');
    }
}
