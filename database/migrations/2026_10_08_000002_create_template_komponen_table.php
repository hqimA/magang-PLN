<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('template_komponen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_template')
                ->constrained('template_jadwal_servis')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->unsignedTinyInteger('nomor_urut');
            $table->string('nama_komponen', 150);
            $table->enum('kategori', [
                'KOMPONEN_DASAR_MESIN',
                'SISTEM_PENGAPIAN',
                'BAHAN_BAKAR_EMISI',
                'CHASSIS_BODI',
            ]);
            $table->boolean('is_aktif')->default(true);
            $table->timestamp('dibuat_pada')->nullable()->useCurrent();

            $table->unique(['id_template', 'nomor_urut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_komponen');
    }
};
