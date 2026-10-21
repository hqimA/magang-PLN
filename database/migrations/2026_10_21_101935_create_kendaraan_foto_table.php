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
        Schema::create('kendaraan_foto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_kendaraan')->constrained('kendaraan')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('path', 500);
            $table->boolean('is_utama')->default(false);
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamp('diunggah_pada')->useCurrent();
        });

        // Migrasi otomatis foto lama ke tabel baru.
        if (Schema::hasColumn('kendaraan', 'foto_kendaraan')) {
            $now = now();
            $existing = DB::table('kendaraan')
                ->whereNotNull('foto_kendaraan')
                ->where('foto_kendaraan', '!=', '')
                ->select(['id', 'foto_kendaraan'])
                ->get();

            foreach ($existing as $item) {
                DB::table('kendaraan_foto')->insert([
                    'id_kendaraan' => $item->id,
                    'path' => $item->foto_kendaraan,
                    'is_utama' => true,
                    'urutan' => 0,
                    'diunggah_pada' => $now,
                ]);
            }

            Schema::table('kendaraan', function (Blueprint $table) {
                $table->dropColumn('foto_kendaraan');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kendaraan', function (Blueprint $table) {
            $table->string('foto_kendaraan')->nullable()->after('status_perawatan');
        });

        // Kembalikan foto utama ke kolom lama.
        if (Schema::hasTable('kendaraan_foto')) {
            $utamaList = DB::table('kendaraan_foto')
                ->where('is_utama', true)
                ->select(['id_kendaraan', 'path'])
                ->get();

            foreach ($utamaList as $utama) {
                DB::table('kendaraan')
                    ->where('id', $utama->id_kendaraan)
                    ->update(['foto_kendaraan' => $utama->path]);
            }

            Schema::dropIfExists('kendaraan_foto');
        }
    }
};
