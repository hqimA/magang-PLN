<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LaporanKerusakan extends Model
{
    protected $table = 'laporan_kerusakan';

    public $timestamps = false;

    protected $fillable = [
        'id_kendaraan',
        'id_pelapor',
        'tanggal_kejadian',
        'lokasi_kejadian',
        'deskripsi_kerusakan',
        'foto_bukti',
        'tingkat_kerusakan',
        'status_penanganan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_kejadian' => 'datetime',
        ];
    }

    public function kendaraan()
    {
        return $this->belongsTo(Kendaraan::class, 'id_kendaraan');
    }

    public function pelapor()
    {
        return $this->belongsTo(User::class, 'id_pelapor');
    }

    public function riwayatPerbaikan()
    {
        return $this->hasOne(RiwayatPerbaikan::class, 'id_laporan_kerusakan');
    }
}
