<?php

namespace App\Enums;

// Dipakai di template_jadwal_servis — ada nilai SEMUA
enum JenisBbmTemplate: string
{
    case BENSIN = 'BENSIN';
    case SOLAR = 'SOLAR';
    case DIESEL = 'DIESEL';
    case LISTRIK = 'LISTRIK';
    case SEMUA = 'SEMUA';
}
