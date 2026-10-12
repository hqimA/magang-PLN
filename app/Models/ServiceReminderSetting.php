<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceReminderSetting extends Model
{
    use HasFactory;

    // Tabel ini menggunakan timestamps standar (created_at, updated_at)
    public $timestamps = true;

    protected $fillable = [
        'is_active',
        'reminder_days_before',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'reminder_days_before' => 'integer',
        ];
    }
}
