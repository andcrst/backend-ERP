<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BUG-008 · Lepas unique index `users.name`.
 *
 * Migrasi `2026_10_26_100000_add_role_to_users_table` memasang
 * `unique('name', 'pengguna_nama_unique')`. Aturan skema ERP hanya mewajibkan
 * **`email` unik** — nama pengguna boleh kembar (dua "Budi" adalah hal wajar).
 * Unique itu membuat `POST /sistem/user` gagal `500 Duplicate entry` alih-alih
 * menyimpan pengguna baru.
 *
 * Migrasi ini TIDAK mengubah tipe kolom dan tidak menghapus data — hanya
 * melepas index, sesuai aturan skema (perubahan aditif/penyesuaian index).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('pengguna_nama_unique');
        });

        // Index biasa tetap berguna untuk pencarian nama.
        Schema::table('users', function (Blueprint $table) {
            $table->index('name', 'users_nama_index');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_nama_index');
            $table->unique('name', 'pengguna_nama_unique');
        });
    }
};
