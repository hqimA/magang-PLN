<?php

namespace App\View\Components;

use App\Models\Kendaraan;
use App\Models\ServiceReminderSetting;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;

class ServiceReminderBadge extends Component
{
    public int $count = 0;

    public bool $isActive = true;

    public function __construct()
    {
        $setting = ServiceReminderSetting::first();
        if ($setting && ! $setting->is_active) {
            $this->isActive = false;

            return;
        }

        $user = Auth::user();
        if (! $user) {
            return;
        }

        $this->count = Kendaraan::query()
            ->with(['riwayatServisTerbaru'])
            ->when($user->peran !== 'ADMIN', fn ($query) => $query->whereBelongsTo($user, 'pengelola'))
            ->get()
            ->map(function ($vehicle) use ($setting) {
                if ($setting) {
                    $vehicle->threshold_servis_hari = $setting->reminder_days_before;
                }

                return $vehicle->serviceReminder();
            })
            ->filter(fn ($reminder) => $reminder['status'] !== 'TERJADWAL')
            ->count();
    }

    public function render(): View|Closure|string
    {
        return view('components.service-reminder-badge');
    }
}
