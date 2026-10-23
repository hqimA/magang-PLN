<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'email' => 'admin@pln.co.id',
                'name' => 'Administrator STI',
                'peran' => 'ADMIN',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
            [
                'email' => 'pengelola@pln.co.id',
                'name' => 'Budi Santoso (Pengelola STI)',
                'peran' => 'PENGELOLA',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
            [
                'email' => 'ahmad.teknisi@pln.co.id',
                'name' => 'Ahmad Hidayat (Operasional Lapangan)',
                'peran' => 'PENGELOLA',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
            [
                'email' => 'siti.aminah@pln.co.id',
                'name' => 'Siti Aminah (Staff Pengelola)',
                'peran' => 'PENGELOLA',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
            [
                'email' => 'test@example.com',
                'name' => 'Test User',
                'peran' => 'PENGELOLA',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        ];

        foreach ($users as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }
    }
}
