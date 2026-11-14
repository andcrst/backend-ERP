<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CP-2 · Master supplier (D-03=A).
 *
 * Dipakai pengajuan pengadaan, penerimaan barang, dan retur ke supplier.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier', function (Blueprint $table) {
            $table->id('id_supplier');
            $table->string('nama_supplier', 100)->unique();
            $table->string('telepon', 20)->nullable();
            $table->text('alamat')->nullable();
            $table->text('catatan')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->index('is_active', 'supplier_aktif_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier');
    }
};
