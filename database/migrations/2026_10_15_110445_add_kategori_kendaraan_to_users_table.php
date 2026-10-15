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
            $table->string('kategori_kendaraan')->nullable()->after('peran');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unique('kategori_kendaraan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_kategori_kendaraan_unique');
            $table->dropColumn('kategori_kendaraan');
        });
    }
};
