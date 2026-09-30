<?php

namespace Database\Seeders;

use App\Models\Kendaraan;
use App\Models\PengajuanServis;
use App\Models\User;
use Illuminate\Database\Seeder;

class PengajuanServisSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('email', 'admin@pln.co.id')->first() ?? User::where('peran', 'ADMIN')->first();
        $pengaju = User::where('email', 'ahmad.teknisi@pln.co.id')->first() ?? User::where('peran', 'PENGELOLA')->first();

        $mobilHilux = Kendaraan::where('plat_nomor', 'B 2345 PLN')->first();
        $mobilTriton = Kendaraan::where('plat_nomor', 'B 6789 PLN')->first();
        $motor = Kendaraan::where('plat_nomor', 'B 5678 PLN')->first();

        if (! $pengaju || ! $mobilHilux || ! $mobilTriton || ! $motor) {
            return;
        }

        $pengajuans = [
            [
                'id_kendaraan' => $mobilHilux->id,
                'id_pengaju' => $pengaju->id,
                'jenis_pengajuan' => 'RUTIN',
                'deskripsi_keluhan' => 'Pengajuan servis berkala kelipatan 60.000 km, penggantian oli mesin, oli gardan, dan pengecekan rem.',
                'estimasi_biaya' => 2500000.00,
                'status_persetujuan' => 'MENUNGGU',
                'alasan_penolakan' => null,
                'id_disetujui_oleh' => null,
                'dibuat_pada' => now()->subDays(2),
            ],
            [
                'id_kendaraan' => $mobilTriton->id,
                'id_pengaju' => $pengaju->id,
                'jenis_pengajuan' => 'DARURAT',
                'deskripsi_keluhan' => 'Kopling selip berat di lapangan saat inspeksi gardu, memerlukan perbaikan darurat clutch set komplit.',
                'estimasi_biaya' => 6800000.00,
                'status_persetujuan' => 'DISETUJUI',
                'alasan_penolakan' => null,
                'id_disetujui_oleh' => $admin?->id,
                'dibuat_pada' => now()->subDays(3),
            ],
            [
                'id_kendaraan' => $motor->id,
                'id_pengaju' => $pengaju->id,
                'jenis_pengajuan' => 'RUTIN',
                'deskripsi_keluhan' => 'Permintaan modifikasi penggantian knalpot racing aftermarket.',
                'estimasi_biaya' => 1500000.00,
                'status_persetujuan' => 'DITOLAK',
                'alasan_penolakan' => 'Penggantian sparepart kendaraan operasional PLN harus sesuai standar pabrikan (OEM).',
                'id_disetujui_oleh' => $admin?->id,
                'dibuat_pada' => now()->subDays(10),
            ],
        ];

        foreach ($pengajuans as $data) {
            PengajuanServis::firstOrCreate(
                [
                    'id_kendaraan' => $data['id_kendaraan'],
                    'deskripsi_keluhan' => $data['deskripsi_keluhan'],
                ],
                $data
            );
        }
    }
}
