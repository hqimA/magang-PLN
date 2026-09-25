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
        Schema::create('notifikasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_pengguna')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('judul', 150);
            $table->text('pesan');
            $table->boolean('sudah_dibaca')->default(false);
            $table->string('tipe_referensi', 50)->nullable();
            $table->timestamp('dibuat_pada')->nullable()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifikasi');
    }
};
