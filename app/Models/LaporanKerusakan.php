<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LaporanKerusakan extends Model
{
    use HasFactory;

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

    // ── Relasi ───────────────────────────────────────────────────────────────

    public function kendaraan(): BelongsTo
    {
        return $this->belongsTo(Kendaraan::class, 'id_kendaraan');
    }

    public function pelapor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_pelapor');
    }

    public function riwayatPerbaikan(): HasOne
    {
        return $this->hasOne(RiwayatPerbaikan::class, 'id_laporan_kerusakan');
    }
}
