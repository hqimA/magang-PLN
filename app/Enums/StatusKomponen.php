<?php

namespace App\Enums;

enum StatusKomponen: string
{
    case AMAN = 'AMAN';
    case SEGERA = 'SEGERA';
    case JATUH_TEMPO = 'JATUH_TEMPO';
    case BELUM_DATA = 'BELUM_DATA';
}
