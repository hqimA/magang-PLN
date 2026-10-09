<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengajuan_komponen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_pengajuan')
                ->constrained('pengajuan_servis')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('id_template_komponen')
                ->constrained('template_komponen')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->enum('jenis_aksi', ['P', 'G']);
            $table->text('catatan')->nullable();
            $table->timestamp('dibuat_pada')->nullable()->useCurrent();

            $table->unique(['id_pengajuan', 'id_template_komponen']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan_komponen');
    }
};
