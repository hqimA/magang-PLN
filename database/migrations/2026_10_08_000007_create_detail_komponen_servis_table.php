<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detail_komponen_servis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_riwayat_servis')
                ->constrained('riwayat_servis')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('id_template_komponen')
                ->constrained('template_komponen')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Aksi aktual — bisa beda dari pengajuan jika kondisi lapangan berbeda
            $table->enum('jenis_aksi_dilakukan', ['P', 'G']);
            $table->text('catatan')->nullable();
            $table->timestamp('dibuat_pada')->nullable()->useCurrent();

            // Custom name applied here to prevent the 1059 error
            $table->unique(['id_riwayat_servis', 'id_template_komponen'], 'riwayat_komponen_unique');
            $table->index('id_template_komponen');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_komponen_servis');
    }
};
