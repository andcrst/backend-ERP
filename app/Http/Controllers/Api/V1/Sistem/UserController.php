<?php

namespace App\Http\Controllers\Api\V1\Sistem;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\ResetUserPasswordRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRoleRequest;
use App\Http\Requests\UpdateUserStatusRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Manajemen pengguna (CP-5) — khusus OWNER.
 */
class UserController extends Controller
{
    public function __construct(private readonly UserService $users) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 20), 100);

        $data = User::query()
            ->when($request->query('search'), function ($q, $cari): void {
                $like = '%'.$cari.'%';
                $q->where(fn ($sub) => $sub->where('name', 'like', $like)->orWhere('email', 'like', $like));
            })
            ->when($request->query('role'), fn ($q, $role) => $q->where('role', $role))
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('name')
            ->paginate($perPage)
            ->through(fn (User $item) => new UserResource($item));

        return ApiResponse::paginated($data, 'Daftar pengguna.');
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->users->buat($request->validated(), auth()->id());

        return ApiResponse::success(new UserResource($user), 'Pengguna berhasil dibuat.', 201);
    }

    public function show(User $user): JsonResponse
    {
        return ApiResponse::success(new UserResource($user));
    }

    /**
     * Ubah data dasar (nama, email, telepon, password opsional).
     */
    public function update(StoreUserRequest $request, User $user): JsonResponse
    {
        $user = $this->users->ubah($user, $request->validated(), auth()->id());

        return ApiResponse::success(new UserResource($user), 'Data pengguna diperbarui.');
    }

    /**
     * Ubah peran (D-02=A: 4 peran final).
     */
    public function ubahRole(UpdateUserRoleRequest $request, User $user): JsonResponse
    {
        $user = $this->users->ubahRole(
            $user,
            UserRole::from($request->validated('role')),
            auth()->id(),
        );

        return ApiResponse::success(new UserResource($user), 'Peran pengguna diperbarui dan sesi lama dicabut.');
    }

    /**
     * Aktif / nonaktifkan akun.
     */
    public function ubahStatus(UpdateUserStatusRequest $request, User $user): JsonResponse
    {
        $user = $this->users->ubahStatus(
            $user,
            (bool) $request->validated('is_active'),
            auth()->id(),
        );

        return ApiResponse::success(
            new UserResource($user),
            $user->is_active ? 'Akun diaktifkan.' : 'Akun dinonaktifkan dan sesi dicabut.',
        );
    }

    /**
     * Reset password oleh OWNER.
     */
    public function resetPassword(ResetUserPasswordRequest $request, User $user): JsonResponse
    {
        $user = $this->users->resetPassword($user, $request->validated('password'), auth()->id());

        return ApiResponse::success(
            new UserResource($user),
            'Password berhasil direset dan sesi lama pengguna dicabut.',
        );
    }

    public function destroy(User $user): JsonResponse
    {
        $this->users->hapus($user, auth()->id());

        return ApiResponse::success(null, 'Pengguna berhasil dihapus.');
    }

    /**
     * Opsi peran untuk frontend.
     */
    public function opsiRole(): JsonResponse
    {
        $hasil = [];

        foreach (UserRole::cases() as $role) {
            $hasil[$role->value] = $role->label();
        }

        return ApiResponse::success($hasil, 'Opsi peran pengguna.');
    }
}
