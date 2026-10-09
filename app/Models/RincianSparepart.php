<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RincianSparepart extends Model
{
    use HasFactory;

    protected $table = 'rincian_sparepart';

    public $timestamps = false;

    protected $fillable = [
        'id_riwayat_servis',
        'nama_sparepart',
        'jumlah',
        'harga_satuan',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'jumlah' => 'integer',
            'harga_satuan' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    // ── Relasi ───────────────────────────────────────────────────────────────

    public function riwayatServis(): BelongsTo
    {
        return $this->belongsTo(RiwayatServis::class, 'id_riwayat_servis');
    }
}
