<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CP-2 · Sesi opname stok (G-06).
 *
 * `kuantitas_sistem` adalah snapshot saat sesi dibuat, sehingga selisih tetap
 * dapat diaudit walau stok bergerak selama penghitungan.
 * Persetujuan WAJIB OWNER/FINANCE (D-04b=B) — GUDANG tidak boleh menyetujui sendiri.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opname', function (Blueprint $table) {
            $table->id('id_opname');
            $table->string('nomor_opname', 30)->unique();
            $table->date('tanggal_opname');
            $table->string('status', 12)->default('DRAFT');
            $table->foreignId('id_pengguna')->constrained('users', 'id')->restrictOnDelete();
            $table->foreignId('id_penyetuju')->nullable()->constrained('users', 'id')->restrictOnDelete();
            $table->timestamp('dihitung_at')->nullable();
            $table->timestamp('disetujui_at')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index(['tanggal_opname', 'status'], 'opname_tanggal_status_index');
        });

        Schema::create('opname_item', function (Blueprint $table) {
            $table->id('id_item');
            $table->foreignId('id_opname')->constrained('opname', 'id_opname')->cascadeOnDelete();
            $table->foreignId('id_produk')->constrained('stok', 'id_produk')->restrictOnDelete();
            $table->integer('kuantitas_sistem');
            $table->integer('kuantitas_fisik')->nullable();
            $table->integer('selisih')->default(0);
            $table->timestamps();

            $table->unique(['id_opname', 'id_produk'], 'opname_item_unik');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opname_item');
        Schema::dropIfExists('opname');
    }
};
