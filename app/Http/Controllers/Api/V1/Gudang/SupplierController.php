<?php

namespace App\Http\Controllers\Api\V1\Gudang;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Master supplier (G-08, D-03=A).
 */
class SupplierController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 20), 100);

        $data = Supplier::query()
            ->search($request->query('search'))
            ->aktifSaja($request->boolean('aktif_saja'))
            ->orderBy('nama_supplier')
            ->paginate($perPage)
            ->through(fn (Supplier $item) => new SupplierResource($item));

        return ApiResponse::paginated($data, 'Daftar supplier.');
    }

    public function store(StoreSupplierRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['is_active'] = $data['is_active'] ?? true;

        $supplier = Supplier::create($data);

        return ApiResponse::success(
            new SupplierResource($supplier),
            'Supplier berhasil dibuat.',
            201,
        );
    }

    public function show(Supplier $supplier): JsonResponse
    {
        return ApiResponse::success(new SupplierResource($supplier));
    }

    public function update(StoreSupplierRequest $request, Supplier $supplier): JsonResponse
    {
        $supplier->fill($request->validated())->save();

        return ApiResponse::success(
            new SupplierResource($supplier->refresh()),
            'Supplier berhasil diperbarui.',
        );
    }

    public function destroy(Supplier $supplier): JsonResponse
    {
        $supplier->delete();

        return ApiResponse::success(null, 'Supplier berhasil dihapus.');
    }
}
