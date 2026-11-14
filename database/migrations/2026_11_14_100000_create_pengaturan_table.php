<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CP-5 · Pengaturan toko (key-value).
 *
 * Aditif: tabel baru, tidak mengubah kolom lama. Dipakai modul Sistem untuk
 * identitas toko, prefix struk, dan default stok minimum.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengaturan', function (Blueprint $table) {
            $table->id('id_pengaturan');
            $table->string('kunci', 50)->unique();
            $table->text('nilai')->nullable();
            $table->string('grup', 30)->default('umum');
            $table->string('keterangan', 150)->nullable();
            $table->timestamps();

            $table->index('grup', 'pengaturan_grup_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengaturan');
    }
};
