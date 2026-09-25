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
        Schema::create('laporan_kerusakan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_kendaraan')->constrained('kendaraan')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('id_pelapor')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->timestamp('tanggal_kejadian');
            $table->string('lokasi_kejadian', 255);
            $table->text('deskripsi_kerusakan');
            $table->string('foto_bukti', 255)->nullable();
            $table->enum('tingkat_kerusakan', ['RINGAN', 'SEDANG', 'BERAT']);
            $table->enum('status_penanganan', ['DILAPORKAN', 'SEDANG_DIPERBAIKI', 'SELESAI'])->default('DILAPORKAN');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laporan_kerusakan');
    }
};
