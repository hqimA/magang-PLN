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
        Schema::create('mileage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_kendaraan')->constrained('kendaraan')->cascadeOnUpdate()->cascadeOnDelete();
            $table->date('tanggal_perjalanan');
            $table->integer('kilometer_awal');
            $table->integer('kilometer_akhir');
            $table->enum('status_perjalanan', ['SELESAI', 'BERJALAN', 'TERBATAS'])->default('SELESAI');
            $table->text('keterangan')->nullable();
            $table->foreignId('id_pencatat')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->timestamp('dibuat_pada')->nullable()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mileage');
    }
};
