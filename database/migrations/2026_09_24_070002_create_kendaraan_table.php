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
        Schema::create('kendaraan', function (Blueprint $table) {
            $table->id();
            $table->string('plat_nomor', 15)->unique();
            $table->string('merk_tipe', 100);
            $table->integer('tahun_pembuatan');
            $table->enum('transmisi', ['MANUAL', 'OTOMATIS']);
            $table->enum('jenis_bbm', ['BENSIN', 'SOLAR', 'DIESEL', 'LISTRIK']);
            $table->enum('kategori_penggunaan', ['PEJABAT', 'TEKNISI', 'ANGKUT_BARANG', 'MOTOR_OPERASIONAL']);
            $table->integer('kilometer_terakhir')->default(0);
            $table->date('tanggal_pembelian');
            $table->enum('status_perawatan', ['BAIK', 'PERLU_SERVIS', 'SEDANG_SERVIS', 'RUSAK'])->default('BAIK');
            $table->foreignId('id_pengelola')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->timestamp('dibuat_pada')->nullable()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kendaraan');
    }
};
