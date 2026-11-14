<?php

namespace App\Http\Controllers\Api\V1\Gudang;

use App\Enums\JenisMutasiStok;
use App\Http\Controllers\Controller;
use App\Http\Resources\MutasiStokResource;
use App\Http\Resources\StokResource;
use App\Models\Stok;
use App\Services\KartuStokService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Kartu stok & pemantauan persediaan (G-09).
 */
class KartuStokController extends Controller
{
    public function __construct(private readonly KartuStokService $kartu) {}

    /**
     * Riwayat pergerakan satu produk beserta sumbernya.
     */
    public function kartu(Request $request, Stok $stok): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 50), 200);

        $data = $this->kartu
            ->kartu(
                $stok,
                $request->query('dari'),
                $request->query('sampai'),
                $request->query('jenis_mutasi'),
                $perPage,
            )
            ->through(fn ($item) => new MutasiStokResource($item));

        return ApiResponse::paginated($data, 'Kartu stok '.$stok->nama_produk.'.');
    }

    /**
     * Daftar produk dengan stok di bawah/di batas minimum.
     */
    public function stokMenipis(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 50), 100);

        $data = $this->kartu
            ->stokMenipis(
                $request->filled('id_kategori') ? (int) $request->query('id_kategori') : null,
                $perPage,
            )
            ->through(fn (Stok $item) => new StokResource($item));

        return ApiResponse::paginated($data, 'Daftar stok menipis.');
    }

    /**
     * Ringkasan persediaan untuk dasbor gudang.
     */
    public function ringkasan(): JsonResponse
    {
        return ApiResponse::success($this->kartu->ringkasan(), 'Ringkasan persediaan.');
    }

    /**
     * Label jenis mutasi untuk filter frontend.
     */
    public function jenisMutasi(): JsonResponse
    {
        return ApiResponse::success($this->kartu->jenisMutasiOptions(), 'Jenis mutasi stok.');
    }

    /**
     * Opsi filter baku kombinasi (dipakai seeder/test bila perlu).
     */
    public function opsi(): JsonResponse
    {
        return ApiResponse::success([
            'jenis_mutasi' => JenisMutasiStok::values(),
        ], 'Opsi kartu stok.');
    }
}
