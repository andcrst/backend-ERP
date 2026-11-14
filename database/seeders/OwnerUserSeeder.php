<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Akun awal untuk setiap nilai ENUM role ERP (4 peran).
 *
 * Kredensial diambil dari `.env` (tidak pernah ditulis di dokumentasi publik).
 */
class OwnerUserSeeder extends Seeder
{
    public function run(): void
    {
        /** @var array{name: string, email: string, password: string} $owner */
        $owner = config('erp.owner');

        $akun = [
            ['name' => $owner['name'], 'email' => $owner['email'], 'role' => UserRole::Owner],
            ['name' => 'Finance Toko', 'email' => 'finance@erp.local', 'role' => UserRole::Finance],
            ['name' => 'Petugas Gudang', 'email' => 'gudang@erp.local', 'role' => UserRole::Gudang],
            ['name' => 'Kasir Toko', 'email' => 'kasir@erp.local', 'role' => UserRole::Kasir],
        ];

        foreach ($akun as $satu) {
            /** @var User $user */
            // `email` adalah kunci unik yang sah; `name` boleh kembar (BUG-008).
            $user = User::query()->firstOrNew(['email' => $satu['email']]);

            $user->fill([
                'name' => $satu['name'],
                'email' => $satu['email'],
                'password' => $owner['password'],
                'role' => $satu['role']->value,
                'is_active' => true,
            ]);

            $user->save();
            $user->syncRoleFromColumn();
        }
    }
}
