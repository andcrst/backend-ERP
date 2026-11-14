<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CP-0 · Ledger pergerakan stok (P1/AR2).
 *
 * `stok.kuantitas` hanya berubah lewat tabel ini. Append-only (tanpa updated_at).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mutasi_stok', function (Blueprint $table) {
            $table->id('id_mutasi');
            $table->foreignId('id_produk')->constrained('stok', 'id_produk')->restrictOnDelete();
            $table->timestamp('tanggal_mutasi');
            $table->string('jenis_mutasi', 30);
            $table->integer('jumlah_perubahan');
            $table->integer('kuantitas_sebelum');
            $table->integer('kuantitas_sesudah');
            $table->string('referensi_tipe', 30)->nullable();
            $table->unsignedBigInteger('referensi_id')->nullable();
            $table->foreignId('id_pengguna')->nullable()->constrained('users', 'id')->restrictOnDelete();
            $table->string('catatan', 255)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['id_produk', 'tanggal_mutasi'], 'mutasi_produk_tanggal_index');
            $table->index(['jenis_mutasi', 'tanggal_mutasi'], 'mutasi_jenis_tanggal_index');
            $table->index(['referensi_tipe', 'referensi_id'], 'mutasi_referensi_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mutasi_stok');
    }
};
