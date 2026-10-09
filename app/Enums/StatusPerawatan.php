<?php

namespace App\Enums;

enum StatusPerawatan: string
{
    case BAIK = 'BAIK';
    case PERLU_SERVIS = 'PERLU_SERVIS';
    case SEDANG_SERVIS = 'SEDANG_SERVIS';
    case RUSAK = 'RUSAK';
}
