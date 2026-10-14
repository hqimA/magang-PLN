<?php

namespace App\Imports;

use App\Models\Kendaraan;
use DateTimeImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\BaseDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class KendaraanImport
{
    private int $importedCount = 0;

    public function import(UploadedFile $file): void
    {
        try {
            $reader = IOFactory::createReaderForFile($file->getRealPath());
            $reader->setReadDataOnly(false);
            $spreadsheet = $reader->load($file->getRealPath());
        } catch (SpreadsheetException|Exception) {
            throw ValidationException::withMessages([
                'file' => 'File tidak dapat dibaca sebagai workbook Excel yang valid.',
            ]);
        }

        try {
            $worksheet = $spreadsheet->getActiveSheet();
            $this->validateHeadings($worksheet);

            $drawingsByRow = $this->drawingsByRow($worksheet);
            $preparedRows = [];
            $errors = [];
            $lastRow = $worksheet->getHighestDataRow();

            for ($rowNumber = 2; $rowNumber <= $lastRow; $rowNumber++) {
                $values = [];
                $hasFormula = false;

                foreach (range('A', 'K') as $column) {
                    $cell = $worksheet->getCell($column.$rowNumber);
                    $values[] = $cell->getValue();
                    $hasFormula = $hasFormula || $cell->isFormula();
                }

                if ($hasFormula) {
                    $errors[] = "Baris {$rowNumber}: Formula Excel tidak diperbolehkan. Isi kolom dengan nilai langsung.";

                    continue;
                }

                if ($values[10] !== null && trim((string) $values[10]) !== '') {
                    $errors[] = "Baris {$rowNumber}: Foto Kendaraan harus ditempel sebagai gambar di kolom ini, bukan diisi dengan teks atau nama file.";

                    continue;
                }

                if (count(array_filter(array_slice($values, 0, 10), fn (mixed $value): bool => $value !== null && trim((string) $value) !== '')) === 0
                    && ! isset($drawingsByRow[$rowNumber])) {
                    continue;
                }

                $data = $this->normalizeRow($worksheet, $rowNumber);
                $drawing = $drawingsByRow[$rowNumber] ?? null;

                if ($drawing !== null) {
                    $data['foto_kendaraan'] = $this->imageData($drawing, $rowNumber, $errors);
                }

                $validator = Validator::make($data, [
                    'plat_nomor' => ['required', 'string', 'max:15'],
                    'merk_tipe' => ['required', 'string', 'max:100'],
                    'tahun_pembuatan' => ['required', 'integer', 'between:1900,'.(now()->year + 1)],
                    'kilometer_terakhir' => ['required', 'integer', 'min:0'],
                    'transmisi' => ['required', Rule::in(['MANUAL', 'OTOMATIS'])],
                    'jenis_bbm' => ['required', Rule::in(['BENSIN', 'SOLAR', 'DIESEL', 'LISTRIK'])],
                    'kategori_penggunaan' => ['required', Rule::in(['PEJABAT', 'TEKNISI', 'ANGKUT_BARANG', 'MOTOR_OPERASIONAL'])],
                    'tanggal_pembelian' => ['required', 'date_format:Y-m-d'],
                    'tanggal_stnk_berlaku_sampai' => ['nullable', 'date_format:Y-m-d'],
                    'tanggal_kir_berlaku_sampai' => ['nullable', 'date_format:Y-m-d'],
                ], [], [
                    'plat_nomor' => 'Nomor Polisi',
                    'merk_tipe' => 'Nama Kendaraan',
                    'tahun_pembuatan' => 'Tahun Pembuatan',
                    'kilometer_terakhir' => 'Kilometer',
                    'transmisi' => 'Transmisi',
                    'jenis_bbm' => 'Jenis BBM',
                    'kategori_penggunaan' => 'Kategori Penggunaan',
                    'tanggal_pembelian' => 'Tanggal Pembelian',
                    'tanggal_stnk_berlaku_sampai' => 'STNK Berlaku Sampai',
                    'tanggal_kir_berlaku_sampai' => 'KIR Berlaku Sampai',
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->all() as $message) {
                        $errors[] = "Baris {$rowNumber}: {$message}";
                    }

                    continue;
                }

                $maximumExpiryDate = Carbon::parse($data['tanggal_pembelian'])->addYears(5)->toDateString();
                $expiryValidator = Validator::make($data, [
                    'tanggal_stnk_berlaku_sampai' => [
                        'nullable',
                        'date_format:Y-m-d',
                        'after_or_equal:tanggal_pembelian',
                        'before_or_equal:'.$maximumExpiryDate,
                    ],
                    'tanggal_kir_berlaku_sampai' => [
                        'nullable',
                        'date_format:Y-m-d',
                        'after_or_equal:tanggal_pembelian',
                        'before_or_equal:'.$maximumExpiryDate,
                    ],
                ], [], [
                    'tanggal_stnk_berlaku_sampai' => 'STNK Berlaku Sampai',
                    'tanggal_kir_berlaku_sampai' => 'KIR Berlaku Sampai',
                ]);

                if ($expiryValidator->fails()) {
                    foreach ($expiryValidator->errors()->all() as $message) {
                        $errors[] = "Baris {$rowNumber}: {$message}";
                    }

                    continue;
                }

                $data['plat_nomor'] = Str::upper(trim($data['plat_nomor']));
                $preparedRows[] = [
                    'baris' => $rowNumber,
                    'data' => $data,
                ];
            }

            if ($preparedRows === [] && $errors === []) {
                $errors[] = 'File Excel tidak berisi data kendaraan untuk diimpor.';
            }

            $this->validateUniquePlates($preparedRows, $errors);

            if ($errors !== []) {
                throw ValidationException::withMessages(['file' => $errors]);
            }

            $storedFiles = [];
            $transactionSucceeded = false;

            try {
                DB::transaction(function () use ($preparedRows, &$storedFiles): void {
                    foreach ($preparedRows as $row) {
                        $data = $row['data'];
                        $image = $data['foto_kendaraan'] ?? null;
                        unset($data['foto_kendaraan']);

                        if ($image !== null) {
                            $filename = 'kendaraan/'.Str::uuid().'.'.$image['extension'];

                            if (! Storage::disk('public')->put($filename, $image['contents'])) {
                                throw new \RuntimeException('Foto kendaraan tidak dapat disimpan ke penyimpanan public.');
                            }

                            $storedFiles[] = $filename;
                            $data['foto_kendaraan'] = $filename;
                        }

                        $data['id_pengelola'] = null;
                        $data['interval_servis_km'] = 5000;
                        $data['interval_servis_bulan'] = 3;
                        $data['threshold_servis_km'] = 200;
                        $data['threshold_servis_hari'] = 10;
                        $data['status_perawatan'] = 'BAIK';

                        Kendaraan::query()->create($data);
                        $this->importedCount++;
                    }
                });

                $transactionSucceeded = true;
            } finally {
                if (! $transactionSucceeded && $storedFiles !== []) {
                    Storage::disk('public')->delete($storedFiles);
                }
            }

        } finally {
            $spreadsheet->disconnectWorksheets();
        }
    }

    public function importedCount(): int
    {
        return $this->importedCount;
    }

    private function validateHeadings(Worksheet $worksheet): void
    {
        $expected = [
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
        $actual = [];

        foreach (range('A', $worksheet->getHighestDataColumn()) as $column) {
            $actual[] = trim((string) $worksheet->getCell($column.'1')->getValue());
        }

        if ($actual !== $expected) {
            throw ValidationException::withMessages([
                'file' => 'Format template tidak sesuai. Gunakan template impor kendaraan tanpa mengubah, menambah, atau menghapus nama dan urutan kolom.',
            ]);
        }
    }

    /**
     * @param  array<int, string>  $errors
     * @return array{contents: string, extension: string}|null
     */
    private function imageData(BaseDrawing $drawing, int $rowNumber, array &$errors): ?array
    {
        if ($drawing instanceof MemoryDrawing) {
            $imageResource = $drawing->getImageResource();
            ob_start();
            $rendered = $imageResource !== null
                && ($drawing->getRenderingFunction())($imageResource);
            $contents = ob_get_clean();
            $sourceMimeType = $drawing->getMimeType();
        } elseif ($drawing instanceof Drawing && ! $drawing->getIsURL()) {
            $path = $drawing->getPath();
            $contents = str_starts_with($path, 'data:image/')
                ? base64_decode(substr($path, strpos($path, ',') + 1), true)
                : ($path !== '' ? file_get_contents($path) : false);
            $sourceMimeType = null;
            $rendered = true;
        } else {
            $contents = false;
            $sourceMimeType = null;
            $rendered = false;
        }

        $image = $contents === false ? false : getimagesizefromstring($contents);
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];
        $mimeType = is_array($image) ? ($image['mime'] ?? null) : null;

        if (! $rendered || $contents === false || $image === false || ! isset($extensions[$mimeType])
            || ($sourceMimeType !== null && $sourceMimeType !== $mimeType)) {
            $errors[] = "Baris {$rowNumber}: Foto Kendaraan harus berupa gambar JPG, PNG, atau WebP yang valid dan tertanam di file Excel.";

            return null;
        }

        if (strlen($contents) > 2 * 1024 * 1024) {
            $errors[] = "Baris {$rowNumber}: Ukuran Foto Kendaraan maksimal 2 MB.";

            return null;
        }

        return [
            'contents' => $contents,
            'extension' => $extensions[$mimeType],
        ];
    }

    /**
     * @return array<int, BaseDrawing>
     */
    private function drawingsByRow(Worksheet $worksheet): array
    {
        $drawingsByRow = [];
        $lastRow = $worksheet->getHighestDataRow();

        foreach ($worksheet->getDrawingCollection() as $drawing) {
            [$column, $row] = Coordinate::coordinateFromString($drawing->getCoordinates());
            $columnIndex = Coordinate::columnIndexFromString($column);

            if ($columnIndex !== 11 || $row < 2 || $row > $lastRow || isset($drawingsByRow[$row])) {
                throw ValidationException::withMessages([
                    'file' => 'Gambar hanya boleh satu buah pada kolom Foto Kendaraan dan harus sejajar dengan baris data kendaraan.',
                ]);
            }

            if (! $drawing instanceof Drawing && ! $drawing instanceof MemoryDrawing) {
                throw ValidationException::withMessages([
                    'file' => "Gambar pada baris {$row} menggunakan format yang tidak didukung. Sisipkan gambar JPG, PNG, atau WebP.",
                ]);
            }

            $drawingsByRow[$row] = $drawing;
        }

        return $drawingsByRow;
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeRow(Worksheet $worksheet, int $rowNumber): array
    {
        $fields = [
            'plat_nomor',
            'merk_tipe',
            'tahun_pembuatan',
            'kilometer_terakhir',
            'transmisi',
            'jenis_bbm',
            'kategori_penggunaan',
            'tanggal_pembelian',
            'tanggal_stnk_berlaku_sampai',
            'tanggal_kir_berlaku_sampai',
        ];
        $data = [];

        foreach ($fields as $index => $field) {
            $cell = $worksheet->getCell(Coordinate::stringFromColumnIndex($index + 1).$rowNumber);
            $value = $cell->getValue();

            if (is_string($value)) {
                $value = trim($value);
            }

            if (in_array($field, ['tanggal_pembelian', 'tanggal_stnk_berlaku_sampai', 'tanggal_kir_berlaku_sampai'], true)
                && is_numeric($value)
                && ExcelDate::isDateTime($cell)
                && (float) $value >= 1
                && (float) $value <= 2958465) {
                $value = ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } elseif (in_array($field, ['tanggal_pembelian', 'tanggal_stnk_berlaku_sampai', 'tanggal_kir_berlaku_sampai'], true)
                && is_string($value)
                && $value !== '') {
                $date = DateTimeImmutable::createFromFormat('!d/m/Y', $value);
                $dateErrors = DateTimeImmutable::getLastErrors();

                if ($date !== false
                    && ($dateErrors === false || ($dateErrors['warning_count'] === 0 && $dateErrors['error_count'] === 0))
                    && $date->format('d/m/Y') === $value) {
                    $value = $date->format('Y-m-d');
                }
            } elseif ($value === '') {
                $value = null;
            }

            if (in_array($field, ['transmisi', 'jenis_bbm', 'kategori_penggunaan'], true) && is_string($value)) {
                $value = Str::upper($value);
            }

            $data[$field] = $value;
        }

        return $data;
    }

    /**
     * @param  array<int, array{baris: int, data: array<string, mixed>}>  $rows
     * @param  array<int, string>  $errors
     */
    private function validateUniquePlates(array $rows, array &$errors): void
    {
        $seenPlates = [];

        foreach ($rows as $row) {
            $plate = $row['data']['plat_nomor'];

            if (! is_string($plate) || $plate === '') {
                continue;
            }

            if (isset($seenPlates[$plate])) {
                $errors[] = "Baris {$row['baris']}: Nomor Polisi duplikat di dalam file.";

                continue;
            }

            $seenPlates[$plate] = true;
        }

        $existingPlates = $seenPlates === []
            ? []
            : Kendaraan::query()
                ->whereIn(DB::raw('UPPER(TRIM(plat_nomor))'), array_keys($seenPlates))
                ->pluck('plat_nomor')
                ->map(fn (string $plate): string => Str::upper(trim($plate)))
                ->all();

        foreach ($rows as $row) {
            if (is_string($row['data']['plat_nomor']) && in_array($row['data']['plat_nomor'], $existingPlates, true)) {
                $errors[] = "Baris {$row['baris']}: Nomor Polisi {$row['data']['plat_nomor']} sudah terdaftar.";
            }
        }
    }
}
