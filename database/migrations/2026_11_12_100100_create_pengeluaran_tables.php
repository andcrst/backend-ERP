<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CP-2 · Barang keluar non-penjualan (G-05).
 *
 * `alasan` WAJIB — setiap perubahan stok harus punya sebab yang dapat ditelusuri.
 * Draf tidak menyentuh stok; posting mengurangi stok lewat StokMutasiService.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengeluaran', function (Blueprint $table) {
            $table->id('id_pengeluaran');
            $table->string('nomor_pengeluaran', 30)->unique();
            $table->date('tanggal_pengeluaran');
            $table->string('alasan', 20);
            $table->foreignId('id_pengguna')->constrained('users', 'id')->restrictOnDelete();
            $table->string('status', 10)->default('DRAFT');
            $table->text('catatan')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            $table->index(['tanggal_pengeluaran', 'status'], 'pengeluaran_tanggal_status_index');
            $table->index(['alasan', 'tanggal_pengeluaran'], 'pengeluaran_alasan_tanggal_index');
        });

        Schema::create('pengeluaran_item', function (Blueprint $table) {
            $table->id('id_item');
            $table->foreignId('id_pengeluaran')->constrained('pengeluaran', 'id_pengeluaran')->cascadeOnDelete();
            $table->foreignId('id_produk')->constrained('stok', 'id_produk')->restrictOnDelete();
            $table->integer('jumlah');
            $table->string('catatan', 255)->nullable();
            $table->timestamps();

            $table->index('id_produk', 'pengeluaran_item_produk_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengeluaran_item');
        Schema::dropIfExists('pengeluaran');
    }
};
