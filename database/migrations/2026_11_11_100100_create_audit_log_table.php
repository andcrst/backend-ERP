<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CP-0 · Jejak audit aksi sensitif (AR9). Append-only.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_log', function (Blueprint $table) {
            $table->id('id_audit');
            $table->foreignId('id_pengguna')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->string('aksi', 50);
            $table->string('entitas_tipe', 50);
            $table->unsignedBigInteger('entitas_id')->nullable();
            $table->json('data_sebelum')->nullable();
            $table->json('data_sesudah')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['entitas_tipe', 'entitas_id'], 'audit_entitas_index');
            $table->index(['id_pengguna', 'created_at'], 'audit_pengguna_index');
            $table->index(['aksi', 'created_at'], 'audit_aksi_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_log');
    }
};
