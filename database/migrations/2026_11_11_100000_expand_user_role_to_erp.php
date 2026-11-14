<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * CP-0 · D-02=A — Peran final 4 nilai: OWNER, FINANCE, GUDANG, KASIR.
 *
 * `MANAGER` dihapus: seluruh akun lama dipetakan ke `OWNER` sebelum ENUM diubah.
 * Aditif & aman untuk data lama.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1) Perluas ENUM lebih dulu agar nilai OWNER & FINANCE sah.
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('KASIR','GUDANG','MANAGER','OWNER','FINANCE') NOT NULL DEFAULT 'KASIR'");

        // 2) Petakan akun lama.
        DB::table('users')->where('role', 'MANAGER')->update(['role' => 'OWNER']);

        // 3) Hapus nilai MANAGER dari ENUM.
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('OWNER','FINANCE','GUDANG','KASIR') NOT NULL DEFAULT 'KASIR'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('KASIR','GUDANG','MANAGER','OWNER','FINANCE') NOT NULL DEFAULT 'KASIR'");
        DB::table('users')->where('role', 'OWNER')->update(['role' => 'MANAGER']);
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('KASIR','GUDANG','MANAGER') NOT NULL DEFAULT 'KASIR'");
    }
};
