<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MaintenanceExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(
        private readonly Builder $rows,
        private readonly string $type,
    ) {}

    public function query(): Builder
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return match ($this->type) {
            'usai' => [
                'Tanggal Servis',
                'Unit PLN',
                'Nomor Polisi',
                'Nama Kendaraan',
                'Jenis Maintenance',
                'Bengkel / Tindakan',
                'Odometer (KM)',
                'Total Biaya',
            ],
            'ditolak' => [
                'Tanggal Pengajuan',
                'Unit PLN',
                'Nomor Polisi',
                'Nama Kendaraan',
                'Jenis Maintenance',
                'Pengaju',
                'Alasan Penolakan',
                'Estimasi Biaya',
                'Odometer (KM)',
            ],
            default => throw new \InvalidArgumentException('Jenis ekspor maintenance tidak valid.'),
        };
    }

    public function map(mixed $row): array
    {
        return match ($this->type) {
            'usai' => [
                $row->tanggal,
                $row->unit_pln,
                $row->plat_nomor,
                $row->merk_tipe,
                $row->jenis_pengajuan ?? 'UMUM',
                $row->nama_bengkel,
                $row->kilometer_servis,
                $row->total_biaya,
            ],
            'ditolak' => [
                $row->tanggal,
                $row->unit_pln,
                $row->plat_nomor,
                $row->merk_tipe,
                $row->jenis_pengajuan,
                $row->pengaju,
                $row->alasan_penolakan,
                $row->estimasi_biaya,
                $row->kilometer_pengajuan,
            ],
            default => throw new \InvalidArgumentException('Jenis ekspor maintenance tidak valid.'),
        };
    }
}
