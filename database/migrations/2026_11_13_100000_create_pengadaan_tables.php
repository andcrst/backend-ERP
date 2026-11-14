<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CP-3 · Pengadaan 4 tahap (D-19).
 *
 * DIAJUKAN → DISETUJUI/DITOLAK/REVISI → DITERIMA → DITUTUP.
 * `id_pengaju` = GUDANG, `id_penyetuju` = FINANCE/OWNER (pemisahan tugas).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengadaan', function (Blueprint $table) {
            $table->id('id_pengadaan');
            $table->string('nomor_pengadaan', 30)->unique();
            $table->date('tanggal_pengadaan');
            $table->date('tanggal_kebutuhan')->nullable();
            $table->foreignId('id_supplier')->nullable()->constrained('supplier', 'id_supplier')->nullOnDelete();
            $table->foreignId('id_pengaju')->constrained('users', 'id')->restrictOnDelete();
            $table->foreignId('id_penyetuju')->nullable()->constrained('users', 'id')->restrictOnDelete();
            $table->string('status', 12)->default('DIAJUKAN');
            $table->text('alasan')->nullable();
            $table->text('catatan_keputusan')->nullable();
            $table->timestamp('diputuskan_at')->nullable();
            $table->timestamp('diterima_at')->nullable();
            $table->timestamp('ditutup_at')->nullable();
            $table->timestamps();

            $table->index(['tanggal_pengadaan', 'status'], 'pengadaan_tanggal_status_index');
            $table->index('status', 'pengadaan_status_index');
        });

        Schema::create('pengadaan_item', function (Blueprint $table) {
            $table->id('id_item');
            $table->foreignId('id_pengadaan')->constrained('pengadaan', 'id_pengadaan')->cascadeOnDelete();
            $table->foreignId('id_produk')->constrained('stok', 'id_produk')->restrictOnDelete();
            $table->integer('jumlah_diminta');
            $table->integer('perkiraan_harga')->nullable();
            $table->integer('jumlah_diterima')->default(0);
            $table->string('catatan', 255)->nullable();
            $table->timestamps();

            $table->unique(['id_pengadaan', 'id_produk'], 'pengadaan_item_unik');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengadaan_item');
        Schema::dropIfExists('pengadaan');
    }
};
