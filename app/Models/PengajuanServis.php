<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PengajuanServis extends Model
{
    protected $table = 'pengajuan_servis';

    public $timestamps = false;

    protected $fillable = [
        'id_kendaraan',
        'id_pengaju',
        'jenis_pengajuan',
        'deskripsi_keluhan',
        'estimasi_biaya',
        'status_persetujuan',
        'alasan_penolakan',
        'id_disetujui_oleh',
        'dibuat_pada',
    ];

    protected function casts(): array
    {
        return [
            'estimasi_biaya' => 'decimal:2',
            'dibuat_pada' => 'datetime',
        ];
    }

    public function kendaraan()
    {
        return $this->belongsTo(Kendaraan::class, 'id_kendaraan');
    }

    public function pengaju()
    {
        return $this->belongsTo(User::class, 'id_pengaju');
    }

    public function disetujuiOleh()
    {
        return $this->belongsTo(User::class, 'id_disetujui_oleh');
    }

    public function riwayatServis()
    {
        return $this->hasOne(RiwayatServis::class, 'id_pengajuan');
    }
}
