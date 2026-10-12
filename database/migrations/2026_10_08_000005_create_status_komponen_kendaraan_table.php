<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('status_komponen_kendaraan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_kendaraan')
                ->constrained('kendaraan')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('id_template_komponen')
                ->constrained('template_komponen')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // Data servis terakhir komponen ini
            $table->unsignedInteger('km_terakhir_servis')->nullable();
            $table->date('tgl_terakhir_servis')->nullable();
            $table->enum('aksi_terakhir', ['P', 'G'])->nullable();

            // Jatuh tempo berikutnya — diisi otomatis saat detail_komponen_servis disimpan
            $table->unsignedInteger('km_jatuh_tempo')->nullable();
            $table->date('tgl_jatuh_tempo')->nullable();

            $table->enum('status', [
                'AMAN',
                'SEGERA',
                'JATUH_TEMPO',
                'BELUM_DATA',
            ])->default('BELUM_DATA');

            $table->timestamp('dibuat_pada')->nullable()->useCurrent();

            $table->unique(['id_kendaraan', 'id_template_komponen'], 'skk_kendaraan_komponen_unique');
            $table->index(['status', 'id_kendaraan'], 'skk_status_kendaraan_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('status_komponen_kendaraan');
    }
};
