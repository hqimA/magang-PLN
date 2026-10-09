<?php

namespace App\Enums;

enum StatusPersetujuan: string
{
    case MENUNGGU = 'MENUNGGU';
    case DISETUJUI = 'DISETUJUI';
    case DITOLAK = 'DITOLAK';
}
