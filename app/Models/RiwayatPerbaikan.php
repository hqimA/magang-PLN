<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiwayatPerbaikan extends Model
{
    protected $table = 'riwayat_perbaikan';

    public $timestamps = false;

    protected $fillable = [
        'id_laporan_kerusakan',
        'tanggal_perbaikan',
        'nama_bengkel',
        'ringkasan_perbaikan',
        'total_biaya_perbaikan',
        'id_pembuat',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_perbaikan' => 'date',
            'total_biaya_perbaikan' => 'decimal:2',
        ];
    }

    public function laporanKerusakan()
    {
        return $this->belongsTo(LaporanKerusakan::class, 'id_laporan_kerusakan');
    }

    public function pembuat()
    {
        return $this->belongsTo(User::class, 'id_pembuat');
    }
}
