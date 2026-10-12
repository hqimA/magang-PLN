<?php

namespace App\Enums;

// Dipakai di template_jadwal_servis — ada nilai SEMUA
enum TransmisiTemplate: string
{
    case MANUAL = 'MANUAL';
    case OTOMATIS = 'OTOMATIS';
    case SEMUA = 'SEMUA';
}
