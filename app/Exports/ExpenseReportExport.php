<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ExpenseReportExport implements FromCollection, WithHeadings
{
    /**
     * @param  array<int, array<int, mixed>>  $rows
     */
    public function __construct(private readonly array $rows) {}

    public function collection(): Collection
    {
        return collect($this->rows);
    }

    public function headings(): array
    {
        return [
            'Plat Nomor',
            'Merk/Tipe',
            'Pengelola',
            'Total Biaya Servis',
            'Total Biaya Perbaikan',
            'Grand Total',
        ];
    }
}
