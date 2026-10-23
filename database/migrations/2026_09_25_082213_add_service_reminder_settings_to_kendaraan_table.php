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
            $table->unsignedInteger('interval_servis_km')->default(5000)->after('kilometer_terakhir');
            $table->unsignedInteger('interval_servis_bulan')->default(3)->after('interval_servis_km');
            $table->unsignedInteger('threshold_servis_km')->default(200)->after('interval_servis_bulan');
            $table->unsignedInteger('threshold_servis_hari')->default(10)->after('threshold_servis_km');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kendaraan', function (Blueprint $table) {
            $table->dropColumn([
                'interval_servis_km',
                'interval_servis_bulan',
                'threshold_servis_km',
                'threshold_servis_hari',
            ]);
        });
    }
};
