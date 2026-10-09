<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop tabel lama jika ada
        Schema::dropIfExists('detail_komponen_servis');
        Schema::dropIfExists('pengajuan_komponen');
        Schema::dropIfExists('status_komponen_kendaraan');

        // Tabel baru: Komponen fisik spesifik milik masing-masing kendaraan
        Schema::create('komponen_kendaraan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_kendaraan')
                ->constrained('kendaraan')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('id_template_komponen')
                ->nullable()
                ->constrained('template_komponen')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->string('nama_komponen', 150);
            $table->enum('kategori', [
                'KOMPONEN_DASAR_MESIN',
                'SISTEM_PENGAPIAN',
                'BAHAN_BAKAR_EMISI',
                'CHASSIS_BODI',
            ]);
            $table->unsignedTinyInteger('nomor_urut')->default(1);
            $table->unsignedInteger('interval_km')->nullable();        // Interval ganti/periksa (km)
            $table->unsignedTinyInteger('interval_bulan')->nullable();   // Interval waktu (bulan)
            $table->enum('jenis_aksi_default', ['P', 'G'])->default('G');

            // Tracking siklus servis spesifik unit ini
            $table->unsignedInteger('km_terakhir_servis')->nullable();
            $table->date('tgl_terakhir_servis')->nullable();
            $table->enum('aksi_terakhir', ['P', 'G'])->nullable();

            $table->unsignedInteger('km_jatuh_tempo')->nullable();
            $table->date('tgl_jatuh_tempo')->nullable();

            $table->enum('status', [
                'AMAN',
                'SEGERA',
                'JATUH_TEMPO',
                'BELUM_DATA',
            ])->default('BELUM_DATA');

            $table->boolean('is_aktif')->default(true);
            $table->timestamp('dibuat_pada')->nullable()->useCurrent();

            $table->index(['id_kendaraan', 'status'], 'kk_kendaraan_status_index');
        });

        // Tabel pengajuan komponen untuk kendaraan
        Schema::create('pengajuan_komponen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_pengajuan')
                ->constrained('pengajuan_servis')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('id_komponen_kendaraan')
                ->constrained('komponen_kendaraan')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->enum('jenis_aksi', ['P', 'G']);
            $table->text('catatan')->nullable();
            $table->timestamp('dibuat_pada')->nullable()->useCurrent();

            $table->unique(['id_pengajuan', 'id_komponen_kendaraan'], 'pengajuan_komponen_unique');
        });

        // Detail riwayat pengerjaan aktual per komponen kendaraan
        Schema::create('detail_komponen_servis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_riwayat_servis')
                ->constrained('riwayat_servis')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('id_komponen_kendaraan')
                ->constrained('komponen_kendaraan')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->enum('jenis_aksi_dilakukan', ['P', 'G']);
            $table->text('catatan')->nullable();
            $table->timestamp('dibuat_pada')->nullable()->useCurrent();

            $table->unique(['id_riwayat_servis', 'id_komponen_kendaraan'], 'riwayat_komponen_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_komponen_servis');
        Schema::dropIfExists('pengajuan_komponen');
        Schema::dropIfExists('komponen_kendaraan');
    }
};
