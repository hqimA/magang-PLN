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
        Schema::table('users', function (Blueprint $table) {
            $table->string('unit_pln')->nullable()->after('peran');
        });

        Schema::table('kendaraan', function (Blueprint $table) {
            $table->string('unit_pln')->nullable()->after('kategori_penggunaan')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kendaraan', function (Blueprint $table) {
            $table->dropIndex(['unit_pln']);
            $table->dropColumn('unit_pln');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('unit_pln');
        });
    }
};
