<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class KendaraanImportTemplateExport implements FromArray, WithHeadings
{
    public function array(): array
    {
        return [];
    }

    public function headings(): array
    {
        return [
            'Nomor Polisi',
            'Nama Kendaraan',
            'Tahun Pembuatan',
            'Kilometer',
            'Transmisi',
            'Jenis BBM',
            'Kategori Penggunaan',
            'Tanggal Pembelian',
            'STNK Berlaku Sampai',
            'KIR Berlaku Sampai',
            'Foto Kendaraan',
        ];
    }
}
