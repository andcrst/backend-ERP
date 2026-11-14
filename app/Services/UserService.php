<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Exceptions\ApiException;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Manajemen pengguna sistem (CP-5).
 *
 * Aturan kunci:
 * - **OWNER tidak boleh kehilangan akses**: tidak bisa mengubah peran / menonaktifkan /
 *   menghapus dirinya sendiri, dan OWNER aktif terakhir dilindungi.
 * - **Perubahan peran / nonaktif / reset password mencabut token** agar hak lama berhenti.
 * - Aksi sensitif dicatat ke `audit_log` lewat `AuditService`.
 */
class UserService
{
    public function __construct(private readonly AuditService $audit) {}

    /**
     * Buat pengguna baru (role Spatie disamakan dari kolom `role`).
     *
     * @param  array{name:string, email:string, password:string, role:string, phone?:string|null, is_active?:bool|null}  $data
     */
    public function buat(array $data, ?int $idPelaku = null): User
    {
        return DB::transaction(function () use ($data, $idPelaku): User {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => Hash::make($data['password']),
                'role' => $data['role'],
                'is_active' => $data['is_active'] ?? true,
            ]);

            $user->syncRoleFromColumn();

            $this->audit->catat(
                AuditService::USER_UBAH_ROLE,
                $user,
                null,
                ['role' => $data['role'], 'email' => $data['email']],
                $idPelaku,
            );

            return $user;
        });
    }

    /**
     * Ubah data dasar pengguna (bukan peran / status).
     *
     * @param  array{name?:string, email?:string, phone?:string|null, password?:string|null}  $data
     */
    public function ubah(User $user, array $data, ?int $idPelaku = null): User
    {
        return DB::transaction(function () use ($user, $data, $idPelaku): User {
            $sebelum = ['name' => $user->name, 'email' => $user->email, 'phone' => $user->phone];

            $user->fill([
                'name' => $data['name'] ?? $user->name,
                'email' => $data['email'] ?? $user->email,
                'phone' => array_key_exists('phone', $data) ? $data['phone'] : $user->phone,
            ]);

            if (! empty($data['password'])) {
                $user->forceFill(['password' => Hash::make($data['password'])]);
                // Password baru → sesi lama tidak boleh tetap hidup.
                $user->tokens()->delete();
            }

            $user->save();

            $this->audit->catat(
                AuditService::USER_UBAH_ROLE,
                $user,
                $sebelum,
                ['name' => $user->name, 'email' => $user->email, 'phone' => $user->phone],
                $idPelaku,
            );

            return $user->refresh();
        });
    }

    /**
     * Ubah peran pengguna (khusus OWNER).
     */
    public function ubahRole(User $user, UserRole $roleBaru, ?int $idPelaku = null): User
    {
        $this->pastikanBukanDiriSendiri($user, $idPelaku, 'Peran Anda sendiri tidak dapat diubah.');

        if ($user->role === $roleBaru) {
            return $user;
        }

        // Menurunkan OWNER terakhir akan mengunci sistem.
        if ($user->role === UserRole::Owner && $roleBaru !== UserRole::Owner) {
            $this->pastikanBukanOwnerTerakhir($user);
        }

        return DB::transaction(function () use ($user, $roleBaru, $idPelaku): User {
            $sebelum = ['role' => $user->role?->value];

            $user->forceFill(['role' => $roleBaru->value])->save();
            $user->syncRoleFromColumn();

            // Hak lama harus langsung berhenti berlaku.
            $user->tokens()->delete();

            $this->audit->catat(
                AuditService::USER_UBAH_ROLE,
                $user,
                $sebelum,
                ['role' => $roleBaru->value],
                $idPelaku,
            );

            return $user->refresh();
        });
    }

    /**
     * Aktifkan / nonaktifkan akun.
     */
    public function ubahStatus(User $user, bool $aktif, ?int $idPelaku = null): User
    {
        $this->pastikanBukanDiriSendiri($user, $idPelaku, 'Status akun Anda sendiri tidak dapat diubah.');

        if (! $aktif && $user->role === UserRole::Owner) {
            $this->pastikanBukanOwnerTerakhir($user);
        }

        return DB::transaction(function () use ($user, $aktif, $idPelaku): User {
            $sebelum = ['is_active' => (bool) $user->is_active];

            $user->forceFill(['is_active' => $aktif])->save();

            // Akun nonaktif tidak boleh punya sesi hidup.
            if (! $aktif) {
                $user->tokens()->delete();
            }

            $this->audit->catat(
                $aktif ? AuditService::USER_UBAH_ROLE : AuditService::USER_NONAKTIF,
                $user,
                $sebelum,
                ['is_active' => $aktif],
                $idPelaku,
            );

            return $user->refresh();
        });
    }

    /**
     * Reset password oleh OWNER (token lama dicabut).
     */
    public function resetPassword(User $user, string $passwordBaru, ?int $idPelaku = null): User
    {
        return DB::transaction(function () use ($user, $passwordBaru, $idPelaku): User {
            $user->forceFill(['password' => Hash::make($passwordBaru)])->save();
            $user->tokens()->delete();

            $this->audit->catat(
                AuditService::USER_UBAH_ROLE,
                $user,
                null,
                ['aksi' => 'reset_password', 'email' => $user->email],
                $idPelaku,
            );

            return $user->refresh();
        });
    }

    /**
     * Hapus (soft delete) pengguna.
     */
    public function hapus(User $user, ?int $idPelaku = null): void
    {
        $this->pastikanBukanDiriSendiri($user, $idPelaku, 'Akun Anda sendiri tidak dapat dihapus.');

        if ($user->role === UserRole::Owner) {
            $this->pastikanBukanOwnerTerakhir($user);
        }

        DB::transaction(function () use ($user, $idPelaku): void {
            $user->tokens()->delete();

            $this->audit->catat(
                AuditService::USER_NONAKTIF,
                $user,
                ['email' => $user->email, 'role' => $user->role?->value],
                ['dihapus' => true],
                $idPelaku,
            );

            $user->delete();
        });
    }

    private function pastikanBukanDiriSendiri(User $user, ?int $idPelaku, string $pesan): void
    {
        if ($idPelaku !== null && (int) $user->id === $idPelaku) {
            throw new ApiException($pesan, 'VALIDATION_ERROR', 422, ['id' => [$pesan]]);
        }
    }

    /**
     * Minimal satu OWNER aktif harus selalu ada.
     */
    private function pastikanBukanOwnerTerakhir(User $user): void
    {
        $ownerAktifLain = User::query()
            ->where('role', UserRole::Owner->value)
            ->where('is_active', true)
            ->whereKeyNot($user->getKey())
            ->exists();

        if (! $ownerAktifLain) {
            throw new ApiException(
                'Minimal harus ada satu OWNER aktif. Operasi ini akan mengunci sistem.',
                'OWNER_TERAKHIR',
                422,
                ['role' => ['Minimal satu OWNER aktif harus tetap ada.']],
            );
        }
    }
}
