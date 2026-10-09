<?php

namespace App\Enums;

enum StatusPerjalanan: string
{
    case SELESAI = 'SELESAI';
    case BERJALAN = 'BERJALAN';
    case TERBATAS = 'TERBATAS';
}
