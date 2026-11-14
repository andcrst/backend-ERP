<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Dashboard Finance (CP-4) — BACA SAJA.
 */
class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboard) {}

    public function index(Request $request): JsonResponse
    {
        return ApiResponse::success(
            $this->dashboard->ringkas($request->query('tanggal')),
            'Ringkasan dashboard Finance.',
        );
    }

    public function opsiPembayaran(): JsonResponse
    {
        return ApiResponse::success($this->dashboard->opsiPembayaran(), 'Opsi jenis pembayaran.');
    }
}
