<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CP-1 · Snapshot + status per BARANG pada `detail_transaksi` (D-20a).
 *
 * Snapshot (harga, nama, modal) membuat laporan historis stabil (AR4/T-02).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detail_transaksi', function (Blueprint $table) {
            $table->string('status_barang', 20)
                ->default('DIAMBIL')
                ->after('jumlah');

            $table->integer('harga_satuan')->nullable()->after('status_barang');
            $table->string('nama_produk_snapshot', 150)->nullable()->after('harga_satuan');
            $table->integer('harga_modal_snapshot')->nullable()->after('nama_produk_snapshot');

            $table->index('status_barang', 'detail_status_barang_index');
        });
    }

    public function down(): void
    {
        Schema::table('detail_transaksi', function (Blueprint $table) {
            $table->dropIndex('detail_status_barang_index');
            $table->dropColumn([
                'status_barang',
                'harga_satuan',
                'nama_produk_snapshot',
                'harga_modal_snapshot',
            ]);
        });
    }
};
