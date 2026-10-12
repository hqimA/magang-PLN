<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('template_jadwal_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_template_komponen')
                ->constrained('template_komponen')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->unsignedInteger('interval_km');      // dalam km penuh, contoh: 10000, 20000
            $table->unsignedTinyInteger('interval_bulan'); // pasangan km, contoh: 6, 12
            $table->enum('jenis_aksi', ['P', 'G']);      // P = Periksa, G = Ganti
            $table->timestamp('dibuat_pada')->nullable()->useCurrent();

            $table->unique(['id_template_komponen', 'interval_km']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_jadwal_detail');
    }
};
