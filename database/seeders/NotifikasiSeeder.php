<?php

namespace Database\Seeders;

use App\Models\Notifikasi;
use App\Models\User;
use Illuminate\Database\Seeder;

class NotifikasiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('email', 'admin@pln.co.id')->first() ?? User::where('peran', 'ADMIN')->first();
        $pengelola = User::where('email', 'pengelola@pln.co.id')->first() ?? User::first();
        $teknisi = User::where('email', 'ahmad.teknisi@pln.co.id')->first() ?? User::first();

        if (! $admin || ! $pengelola || ! $teknisi) {
            return;
        }

        $notifikasis = [
            [
                'id_pengguna' => $admin->id,
                'judul' => 'Pengajuan Servis Baru Menunggu Persetujuan',
                'pesan' => 'Kendaraan Toyota Hilux (B 2345 PLN) mengajukan servis rutin dengan estimasi biaya Rp 2.500.000.',
                'sudah_dibaca' => false,
                'tipe_referensi' => 'pengajuan_servis',
                'dibuat_pada' => now()->subDays(2),
            ],
            [
                'id_pengguna' => $admin->id,
                'judul' => 'Laporan Kerusakan Darurat Dilaporkan',
                'pesan' => 'Laporan kerusakan berat untuk Mitsubishi Triton (B 6789 PLN) di Jl. Gatot Subroto.',
                'sudah_dibaca' => true,
                'tipe_referensi' => 'laporan_kerusakan',
                'dibuat_pada' => now()->subDays(3),
            ],
            [
                'id_pengguna' => $teknisi->id,
                'judul' => 'Pengajuan Servis Disetujui',
                'pesan' => 'Pengajuan perbaikan darurat kopling untuk kendaraan B 6789 PLN telah disetujui oleh Administrator.',
                'sudah_dibaca' => true,
                'tipe_referensi' => 'pengajuan_servis',
                'dibuat_pada' => now()->subDays(3),
            ],
            [
                'id_pengguna' => $pengelola->id,
                'judul' => 'Peringatan Jadwal Servis Kendaraan',
                'pesan' => 'Kendaraan Toyota Innova Zenix (B 1234 PLN) mendekati batas kilometer jadwal servis berkala.',
                'sudah_dibaca' => false,
                'tipe_referensi' => 'kendaraan',
                'dibuat_pada' => now()->subDay(),
            ],
        ];

        foreach ($notifikasis as $data) {
            Notifikasi::firstOrCreate(
                [
                    'id_pengguna' => $data['id_pengguna'],
                    'judul' => $data['judul'],
                ],
                $data
            );
        }
    }
}
