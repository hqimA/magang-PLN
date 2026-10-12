<?php

namespace App\Enums;

enum StatusPenanganan: string
{
    case DILAPORKAN = 'DILAPORKAN';
    case SEDANG_DIPERBAIKI = 'SEDANG_DIPERBAIKI';
    case SELESAI = 'SELESAI';
}
