<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OdometerLog extends Model
{
    use HasFactory;

    protected $table = 'odometer_logs';

    public $timestamps = false;

    protected $fillable = [
        'id_kendaraan',
        'kilometer',
        'tanggal_pencatatan',
        'keterangan',
        'id_penginput',
    ];

    protected function casts(): array
    {
        return [
            'kilometer' => 'integer',
            'tanggal_pencatatan' => 'date',
            'dibuat_pada' => 'datetime',
        ];
    }

    public function kendaraan(): BelongsTo
    {
        return $this->belongsTo(Kendaraan::class, 'id_kendaraan');
    }

    public function penginput(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_penginput');
    }
}
