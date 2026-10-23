<?php

namespace Database\Seeders;

use App\Models\LaporanKerusakan;
use App\Models\RiwayatPerbaikan;
use App\Models\User;
use Illuminate\Database\Seeder;

class RiwayatPerbaikanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pembuat = User::where('email', 'ahmad.teknisi@pln.co.id')->first() ?? User::first();

        // Cari laporan kerusakan yang sudah SELESAI
        $laporanSelesai = LaporanKerusakan::where('status_penanganan', 'SELESAI')->first();

        if (! $pembuat || ! $laporanSelesai) {
            return;
        }

        RiwayatPerbaikan::firstOrCreate(
            ['id_laporan_kerusakan' => $laporanSelesai->id],
            [
                'id_laporan_kerusakan' => $laporanSelesai->id,
                'tanggal_perbaikan' => now()->subDays(10)->toDateString(),
                'nama_bengkel' => 'Bengkel Dinamo & Kelistrikan Berkah Jaya',
                'ringkasan_perbaikan' => 'Penggantian aki baru GS Astra Hybrid 70Ah, perakitan ulang jalur relay rotator derek, dan uji beban alternator.',
                'total_biaya_perbaikan' => 1650000.00,
                'id_pembuat' => $pembuat->id,
            ]
        );
    }
}
