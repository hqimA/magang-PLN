<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class ExpenseReportArchiveExport implements Export, WithMultipleSheets
{
    /**
     * @param  array<string, array{headings: array<int, string>, rows: array<int, array<int, mixed>>}>  $tables
     */
    public function __construct(private readonly array $tables) {}

    public function sheets(): array
    {
        $sheets = [];

        foreach ($this->tables as $title => $table) {
            $sheets[] = new ExpenseReportArchiveSheet($title, $table['headings'], $table['rows']);
        }

        return $sheets;
    }
}

class ExpenseReportArchiveSheet implements FromArray, WithHeadings, WithTitle
{
    /**
     * @param  array<int, string>  $headings
     * @param  array<int, array<int, mixed>>  $rows
     */
    public function __construct(
        private readonly string $title,
        private readonly array $headings,
        private readonly array $rows,
    ) {}

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function title(): string
    {
        return $this->title;
    }
}
