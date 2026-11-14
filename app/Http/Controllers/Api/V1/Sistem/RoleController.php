<?php

namespace App\Http\Controllers\Api\V1\Sistem;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Daftar role & permission (CP-5).
 */
class RoleController extends Controller
{
    public function index(): JsonResponse
    {
        $roles = Role::query()
            ->with('permissions:id,name')
            ->orderBy('name')
            ->get()
            ->map(fn (Role $role): array => [
                'name' => $role->name,
                'jumlah_permission' => $role->permissions->count(),
                'permissions' => $role->permissions->pluck('name')->sort()->values()->all(),
            ]);

        return ApiResponse::success($roles, 'Daftar role & permission.');
    }

    /**
     * Seluruh permission baku, dikelompokkan per modul (untuk UI matriks).
     */
    public function permission(): JsonResponse
    {
        $dikelompokkan = Permission::query()
            ->orderBy('name')
            ->pluck('name')
            ->groupBy(fn (string $nama): string => explode('.', $nama)[0])
            ->map(fn ($daftar) => $daftar->values()->all());

        return ApiResponse::success($dikelompokkan, 'Daftar permission per modul.');
    }

    /**
     * Ubah permission satu role.
     *
     * Validasi: nama permission harus ada supaya tidak ada salah ketik yang
     * diam-diam menghapus akses.
     */
    public function update(Request $request, string $role): JsonResponse
    {
        $data = $request->validate([
            'permissions' => ['required', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ], [
            'permissions.required' => 'Daftar permission wajib diisi.',
            'permissions.*.exists' => 'Ada permission yang tidak dikenal.',
        ]);

        $model = Role::query()->where('name', $role)->first();

        if ($model === null) {
            return ApiResponse::error('Peran tidak ditemukan.', 'NOT_FOUND', 404);
        }

        $model->syncPermissions($data['permissions']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return ApiResponse::success([
            'name' => $model->name,
            'permissions' => $model->getPermissionNames()->sort()->values()->all(),
        ], 'Permission peran diperbarui.');
    }
}
