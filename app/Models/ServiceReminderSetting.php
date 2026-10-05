<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceReminderSetting extends Model
{
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
