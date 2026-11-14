<?php

namespace App\Http\Controllers\Api\V1\Kasir;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePelangganRequest;
use App\Http\Resources\PelangganResource;
use App\Http\Resources\TransaksiResource;
use App\Models\Pelanggan;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Master pelanggan (CP-1 · D-03=A).
 */
class PelangganController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 20), 100);

        $data = Pelanggan::query()
            ->search($request->query('search'))
            ->when($request->boolean('aktif_saja', false), fn ($q) => $q->where('is_active', true))
            ->orderBy('nama_pelanggan')
            ->paginate($perPage)
            ->through(fn (Pelanggan $item) => new PelangganResource($item));

        return ApiResponse::paginated($data, 'Daftar pelanggan.');
    }

    public function store(StorePelangganRequest $request): JsonResponse
    {
        $pelanggan = Pelanggan::create($request->validated());

        return ApiResponse::success(
            new PelangganResource($pelanggan),
            'Pelanggan berhasil dibuat.',
            201,
        );
    }

    public function show(Pelanggan $pelanggan): JsonResponse
    {
        return ApiResponse::success(new PelangganResource($pelanggan));
    }

    public function update(StorePelangganRequest $request, Pelanggan $pelanggan): JsonResponse
    {
        $pelanggan->fill($request->validated())->save();

        return ApiResponse::success(
            new PelangganResource($pelanggan->refresh()),
            'Pelanggan berhasil diperbarui.',
        );
    }

    /**
     * Soft delete — riwayat transaksi pelanggan tetap utuh.
     */
    public function destroy(Pelanggan $pelanggan): JsonResponse
    {
        $pelanggan->delete();

        return ApiResponse::success(null, 'Pelanggan berhasil dihapus.');
    }

    /**
     * Riwayat transaksi pelanggan.
     */
    public function riwayat(Request $request, Pelanggan $pelanggan): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 20), 100);

        $data = $pelanggan->transaksi()
            ->with('pengguna:id,name,email')
            ->withCount('detail')
            ->orderByDesc('tanggal_transaksi')
            ->paginate($perPage)
            ->through(fn ($item) => new TransaksiResource($item));

        return ApiResponse::paginated($data, 'Riwayat transaksi pelanggan.');
    }
}
