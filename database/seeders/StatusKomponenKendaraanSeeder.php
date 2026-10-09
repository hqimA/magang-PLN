<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class StatusKomponenKendaraanSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(KomponenKendaraanSeeder::class);
    }
}
