<?php

namespace App\Console\Commands;

use App\Models\Kendaraan;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

#[Signature('kendaraan:alert-masa-berlaku')]
#[Description('Buat notifikasi STNK dan KIR yang habis dalam 30 hari')]
class SendDocumentExpiryAlerts extends Command
{
    public function handle(): int
    {
        $reminderDate = today()->addDays(30);
        $created = 0;

        Kendaraan::query()
            ->where(function (Builder $query) use ($reminderDate): void {
                $query->whereDate('tanggal_stnk_berlaku_sampai', $reminderDate)
                    ->orWhereDate('tanggal_kir_berlaku_sampai', $reminderDate);
            })
            ->chunkById(100, function ($kendaraans) use ($reminderDate, &$created): void {
                foreach ($kendaraans as $kendaraan) {
                    foreach ([
                        'stnk' => ['label' => 'STNK', 'column' => 'tanggal_stnk_berlaku_sampai'],
                        'kir' => ['label' => 'KIR', 'column' => 'tanggal_kir_berlaku_sampai'],
                    ] as $type => $document) {
                        $expiryDate = $kendaraan->{$document['column']};

                        if (! $expiryDate?->isSameDay($reminderDate)) {
                            continue;
                        }

                        $reference = 'kendaraan:'.$kendaraan->id.':'.$type.':'.$expiryDate->format('Ymd');
                        $alreadyNotified = DB::table('notifikasi')
                            ->where('id_pengguna', $kendaraan->id_pengelola)
                            ->where('tipe_referensi', $reference)
                            ->exists();

                        if ($alreadyNotified) {
                            continue;
                        }

                        DB::table('notifikasi')->insert([
                            'id_pengguna' => $kendaraan->id_pengelola,
                            'judul' => $document['label'].' kendaraan '.$kendaraan->plat_nomor.' akan habis',
                            'pesan' => 'Masa berlaku '.$document['label'].' kendaraan '.$kendaraan->plat_nomor
                                .' akan habis pada '.$expiryDate->format('d-m-Y').'.',
                            'sudah_dibaca' => false,
                            'tipe_referensi' => $reference,
                            'dibuat_pada' => now(),
                        ]);

                        $created++;
                    }
                }
            });

        $this->info("{$created} notifikasi STNK/KIR dibuat.");

        return self::SUCCESS;
    }
}
