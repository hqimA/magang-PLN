<?php

namespace Database\Seeders;

use App\Models\TemplateJadwalServis;
use Illuminate\Database\Seeder;

class TemplateJadwalServisSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'nama' => 'Bensin Manual',
                'deskripsi' => 'Template untuk kendaraan berbahan bakar bensin dengan transmisi manual',
                'jenis_bbm' => 'BENSIN',
                'transmisi' => 'MANUAL',
                'is_aktif' => true,
            ],
            [
                'nama' => 'Bensin Otomatis',
                'deskripsi' => 'Template untuk kendaraan berbahan bakar bensin dengan transmisi otomatis',
                'jenis_bbm' => 'BENSIN',
                'transmisi' => 'OTOMATIS',
                'is_aktif' => true,
            ],
            [
                'nama' => 'Diesel Manual',
                'deskripsi' => 'Template untuk kendaraan berbahan bakar solar/diesel dengan transmisi manual',
                'jenis_bbm' => 'SOLAR',
                'transmisi' => 'MANUAL',
                'is_aktif' => true,
            ],
            [
                'nama' => 'Diesel Otomatis',
                'deskripsi' => 'Template untuk kendaraan berbahan bakar solar/diesel dengan transmisi otomatis',
                'jenis_bbm' => 'SOLAR',
                'transmisi' => 'OTOMATIS',
                'is_aktif' => true,
            ],
            [
                'nama' => 'EV',
                'deskripsi' => 'Template untuk kendaraan listrik (Electric Vehicle)',
                'jenis_bbm' => 'LISTRIK',
                'transmisi' => 'OTOMATIS',
                'is_aktif' => true,
            ],
            [
                'nama' => 'Motor',
                'deskripsi' => 'Template untuk sepeda motor operasional',
                'jenis_bbm' => 'BENSIN',
                'transmisi' => 'SEMUA',
                'is_aktif' => true,
            ],
        ];

        foreach ($templates as $data) {
            TemplateJadwalServis::firstOrCreate(
                ['nama' => $data['nama']],
                $data
            );
        }
    }
}
