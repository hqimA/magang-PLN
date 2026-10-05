<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RiwayatServis extends Model
{
    use HasFactory;

    protected $table = 'riwayat_servis';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'id_pengajuan',
        'id_kendaraan',
        'tanggal_servis',
        'kilometer_servis',
        'nama_bengkel',
        'total_biaya',
        'foto_nota',
        'target_kilometer_berikutnya',
        'id_pembuat',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_servis' => 'date',
            'kilometer_servis' => 'integer',
            'total_biaya' => 'decimal:2',
            'target_kilometer_berikutnya' => 'integer',
        ];
    }

    public function kendaraan(): BelongsTo
    {
        return $this->belongsTo(Kendaraan::class, 'id_kendaraan');
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_pembuat');
    }

    public function pengajuan(): BelongsTo
    {
        return $this->belongsTo(PengajuanServis::class, 'id_pengajuan');
    }

    public function rincianSparepart(): HasMany
    {
        return $this->hasMany(RincianSparepart::class, 'id_riwayat_servis');
    }
}
