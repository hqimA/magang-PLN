<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('kendaraan', function (Blueprint $table): void {
            $table->foreignId('id_pengelola')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('kendaraan')->whereNull('id_pengelola')->exists()) {
            throw new RuntimeException(
                'Tetapkan Pengelola untuk semua kendaraan sebelum mengembalikan kolom id_pengelola menjadi wajib.',
            );
        }

        Schema::table('kendaraan', function (Blueprint $table): void {
            $table->foreignId('id_pengelola')->nullable(false)->change();
        });
    }
};
