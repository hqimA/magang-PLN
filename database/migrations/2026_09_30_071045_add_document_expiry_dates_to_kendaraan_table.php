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
        Schema::table('kendaraan', function (Blueprint $table) {
            $table->date('tanggal_stnk_berlaku_sampai')->nullable()->index();
            $table->date('tanggal_kir_berlaku_sampai')->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kendaraan', function (Blueprint $table) {
            $table->dropColumn([
                'tanggal_stnk_berlaku_sampai',
                'tanggal_kir_berlaku_sampai',
            ]);
        });
    }
};
