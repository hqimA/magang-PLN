<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RincianSparepart extends Model
{
    protected $table = 'rincian_sparepart';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'id_riwayat_servis',
        'nama_sparepart',
        'jumlah',
        'harga_satuan',
        'subtotal',
    ];

    public function riwayatServis(): BelongsTo
    {
        return $this->belongsTo(RiwayatServis::class, 'id_riwayat_servis');
    }
}
