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
        Schema::create('riwayat_perbaikan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_laporan_kerusakan')->constrained('laporan_kerusakan')->cascadeOnUpdate()->cascadeOnDelete();
            $table->date('tanggal_perbaikan');
            $table->string('nama_bengkel', 150);
            $table->text('ringkasan_perbaikan');
            $table->decimal('total_biaya_perbaikan', 12, 2);
            $table->foreignId('id_pembuat')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('riwayat_perbaikan');
    }
};
