<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CP-1 · Kolom aditif pada `transaksi`.
 *
 * - `id_pelanggan`: relasi pelanggan (antar/berulang).
 * - `status_pembayaran`, `jumlah_bayar`: DP / tempo.
 * - `referensi_pembayaran`: OPSIONAL (D-08=B).
 * - `biaya_angkut`: D-31 + D-32=B — pengeluaran, DI LUAR total_pembayaran.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksi', function (Blueprint $table) {
            $table->foreignId('id_pelanggan')
                ->nullable()
                ->after('id_pengguna')
                ->constrained('pelanggan', 'id_pelanggan')
                ->nullOnDelete();

            $table->string('status_pembayaran', 15)->default('LUNAS')->after('jenis_pembayaran');
            $table->decimal('jumlah_bayar', 15, 2)->nullable()->after('status_pembayaran');
            $table->string('referensi_pembayaran', 100)->nullable()->after('jumlah_bayar');
            $table->integer('biaya_angkut')->default(0)->after('kembalian');
            $table->text('catatan')->nullable()->after('biaya_angkut');

            $table->index(['status_pembayaran', 'tanggal_transaksi'], 'transaksi_status_bayar_index');
        });
    }

    public function down(): void
    {
        Schema::table('transaksi', function (Blueprint $table) {
            $table->dropIndex('transaksi_status_bayar_index');
            $table->dropForeign(['id_pelanggan']);
            $table->dropColumn([
                'id_pelanggan',
                'status_pembayaran',
                'jumlah_bayar',
                'referensi_pembayaran',
                'biaya_angkut',
                'catatan',
            ]);
        });
    }
};
