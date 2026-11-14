<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CP-1 · Master pelanggan (D-03=A).
 *
 * Alamat wajib untuk barang yang perlu diantar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pelanggan', function (Blueprint $table) {
            $table->id('id_pelanggan');
            $table->string('nama_pelanggan', 100);
            $table->string('telepon', 20)->nullable();
            $table->text('alamat')->nullable();
            $table->text('catatan')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->index('nama_pelanggan', 'pelanggan_nama_index');
            $table->index('telepon', 'pelanggan_telepon_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pelanggan');
    }
};
