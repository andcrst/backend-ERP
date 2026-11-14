<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CP-0 · Kolom aditif pada `stok`.
 *
 * - `satuan`: GAP-10 — satuan barang (sak, m³, batang, lembar, kg).
 * - `stok_minimum`: alarm stok menipis.
 * - `harga_modal`: D-05=A — dasar HPP rata-rata. NULL = belum diisi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stok', function (Blueprint $table) {
            $table->string('satuan', 20)->default('unit')->after('nama_produk');
            $table->integer('stok_minimum')->default(5)->after('kuantitas');
            $table->integer('harga_modal')->nullable()->after('harga');

            $table->index('stok_minimum', 'stok_minimum_index');
        });
    }

    public function down(): void
    {
        Schema::table('stok', function (Blueprint $table) {
            $table->dropIndex('stok_minimum_index');
            $table->dropColumn(['satuan', 'stok_minimum', 'harga_modal']);
        });
    }
};
