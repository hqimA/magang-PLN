<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'id_kendaraan',
    'tanggal_perjalanan',
    'kilometer_awal',
    'kilometer_akhir',
    'status_perjalanan',
    'keterangan',
    'id_pencatat',
])]
class Mileage extends Model
{
    use HasFactory;

    protected $table = 'mileage';

    public $timestamps = false;

    public function kendaraan(): BelongsTo
    {
        return $this->belongsTo(Kendaraan::class, 'id_kendaraan');
    }

    public function pencatat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_pencatat');
    }

    /**
     * Jarak tempuh perhitungan, sama dengan kolom virtual total_jarak.
     */
    public function getTotalJarakAttribute(): int
    {
        return (int) $this->kilometer_akhir - (int) $this->kilometer_awal;
    }

    protected function casts(): array
    {
        return [
            'tanggal_perjalanan' => 'date',
            'kilometer_awal' => 'integer',
            'kilometer_akhir' => 'integer',
            'dibuat_pada' => 'datetime',
        ];
    }
}
