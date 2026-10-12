<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('template_jadwal_servis', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100);
            $table->string('deskripsi', 255)->nullable();
            $table->enum('jenis_bbm', ['BENSIN', 'SOLAR', 'DIESEL', 'LISTRIK', 'SEMUA']);
            $table->enum('transmisi', ['MANUAL', 'OTOMATIS', 'SEMUA']);
            $table->boolean('is_aktif')->default(true);
            $table->timestamp('dibuat_pada')->nullable()->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_jadwal_servis');
    }
};
