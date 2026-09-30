<?php

namespace App\Models;

use Database\Factories\KendaraanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'plat_nomor',
    'merk_tipe',
    'tahun_pembuatan',
    'transmisi',
    'jenis_bbm',
    'kategori_penggunaan',
    'kilometer_terakhir',
    'tanggal_pembelian',
    'status_perawatan',
    'foto_kendaraan',
    'id_pengelola',
])]
class Kendaraan extends Model
{
    /** @use HasFactory<KendaraanFactory> */
    use HasFactory;

    protected $table = 'kendaraan';

    public $timestamps = false;

    public function pengelola(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_pengelola');
    }

    public function mileages(): HasMany
    {
        return $this->hasMany(Mileage::class, 'id_kendaraan');
    }

    /**
     * Catatan odometer terbaru, dipakai sebagai kilometer terakhir.
     */
    public function mileageTerakhir(): ?Mileage
    {
        return $this->mileages()->orderByDesc('kilometer_akhir')->orderByDesc('tanggal_perjalanan')->first();
    }

    protected function casts(): array
    {
        return [
            'tahun_pembuatan' => 'integer',
            'kilometer_terakhir' => 'integer',
            'tanggal_pembelian' => 'date',
            'dibuat_pada' => 'datetime',
        ];
    }
}
