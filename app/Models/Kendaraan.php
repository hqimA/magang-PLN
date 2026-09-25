<?php

namespace App\Models;

use Database\Factories\KendaraanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
