<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::transaction(function (): void {
            $managersByEmail = [
                'bahar@gmail.com' => 'PEJABAT',
                'pengelola@pln.co.id' => 'MOTOR_OPERASIONAL',
                'ahmad.teknisi@pln.co.id' => 'ANGKUT_BARANG',
                'twha@pln.co.id' => 'TEKNISI',
            ];

            foreach ($managersByEmail as $email => $category) {
                $managerId = DB::table('users')
                    ->where('email', $email)
                    ->where('peran', 'PENGELOLA')
                    ->value('id');

                if ($managerId === null) {
                    continue;
                }

                DB::table('users')
                    ->where('id', $managerId)
                    ->update(['kategori_kendaraan' => $category]);

                DB::table('kendaraan')
                    ->whereNull('id_pengelola')
                    ->where('kategori_penggunaan', $category)
                    ->update(['id_pengelola' => $managerId]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::transaction(function (): void {
            DB::table('users')
                ->whereIn('email', [
                    'bahar@gmail.com',
                    'pengelola@pln.co.id',
                    'ahmad.teknisi@pln.co.id',
                    'twha@pln.co.id',
                ])
                ->where('peran', 'PENGELOLA')
                ->update(['kategori_kendaraan' => null]);
        });
    }
};
