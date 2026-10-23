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
        Schema::create('riwayat_servis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_pengajuan')->nullable()->constrained('pengajuan_servis')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('id_kendaraan')->constrained('kendaraan')->cascadeOnUpdate()->cascadeOnDelete();
            $table->date('tanggal_servis');
            $table->integer('kilometer_servis');
            $table->string('nama_bengkel', 150);
            $table->decimal('total_biaya', 12, 2)->default(0);
            $table->string('foto_nota', 255)->nullable();
            $table->integer('target_kilometer_berikutnya')->nullable();
            $table->foreignId('id_pembuat')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('riwayat_servis');
    }
};
