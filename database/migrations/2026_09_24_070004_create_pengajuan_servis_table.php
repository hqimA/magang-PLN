<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pengajuan_servis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_kendaraan')->constrained('kendaraan')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('id_pengaju')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->enum('jenis_pengajuan', ['RUTIN', 'DARURAT']);
            $table->text('deskripsi_keluhan');
            $table->decimal('estimasi_biaya', 12, 2)->nullable();
            $table->enum('status_persetujuan', ['MENUNGGU', 'DISETUJUI', 'DITOLAK'])->default('MENUNGGU');
            $table->text('alasan_penolakan')->nullable();
            $table->foreignId('id_disetujui_oleh')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->timestamp('dibuat_pada')->nullable()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengajuan_servis');
    }
};
