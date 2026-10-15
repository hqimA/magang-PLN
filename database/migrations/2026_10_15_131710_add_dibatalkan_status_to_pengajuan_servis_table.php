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
        Schema::table('pengajuan_servis', function (Blueprint $table) {
            $table->enum('status_persetujuan', [
                'MENUNGGU',
                'DISETUJUI',
                'DITOLAK',
                'DIBATALKAN',
            ])->default('MENUNGGU')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('pengajuan_servis')
            ->where('status_persetujuan', 'DIBATALKAN')
            ->update(['status_persetujuan' => 'DITOLAK']);

        Schema::table('pengajuan_servis', function (Blueprint $table) {
            $table->enum('status_persetujuan', [
                'MENUNGGU',
                'DISETUJUI',
                'DITOLAK',
            ])->default('MENUNGGU')->change();
        });
    }
};
