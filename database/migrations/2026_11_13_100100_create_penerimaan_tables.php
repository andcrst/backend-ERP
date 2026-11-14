<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CP-3 · Penerimaan barang (G-02).
 *
 * WAJIB terikat `id_pengadaan` yang sudah DISETUJUI (P8) — gudang tidak boleh
 * menerima barang tanpa pengadaan yang disetujui.
 * Posting menaikkan stok dan memperbarui `stok.harga_modal` (D-05=A).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penerimaan', function (Blueprint $table) {
            $table->id('id_penerimaan');
            $table->string('nomor_penerimaan', 30)->unique();
            $table->date('tanggal_penerimaan');
            $table->foreignId('id_pengadaan')->constrained('pengadaan', 'id_pengadaan')->restrictOnDelete();
            $table->foreignId('id_supplier')->nullable()->constrained('supplier', 'id_supplier')->nullOnDelete();
            $table->string('nomor_surat_jalan', 60)->nullable();
            $table->foreignId('id_pengguna')->constrained('users', 'id')->restrictOnDelete();
            $table->string('status', 10)->default('DRAFT');
            $table->decimal('total_nilai', 15, 2)->default(0);
            $table->text('catatan')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            $table->index(['tanggal_penerimaan', 'id_supplier'], 'penerimaan_tanggal_supplier_index');
            $table->index('id_pengadaan', 'penerimaan_pengadaan_index');
        });

        Schema::create('penerimaan_item', function (Blueprint $table) {
            $table->id('id_item');
            $table->foreignId('id_penerimaan')->constrained('penerimaan', 'id_penerimaan')->cascadeOnDelete();
            $table->foreignId('id_produk')->constrained('stok', 'id_produk')->restrictOnDelete();
            $table->integer('jumlah');
            $table->integer('harga_modal')->nullable();
            $table->decimal('sub_total', 15, 2)->default(0);
            $table->string('catatan', 255)->nullable();
            $table->timestamps();

            $table->index('id_produk', 'penerimaan_item_produk_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penerimaan_item');
        Schema::dropIfExists('penerimaan');
    }
};
