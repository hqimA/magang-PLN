<?php

namespace App\Imports;

use App\Enums\UnitPln;
use App\Models\Kendaraan;
use App\Services\ImageStorageService;
use App\Services\KomponenServisService;
use App\Services\RemoteImageDownloader;
use DateTimeImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;
use Throwable;

class KendaraanImport
{
    private int $importedCount = 0;

    public function __construct(
        private ImageStorageService $imageStorage,
        private RemoteImageDownloader $imageDownloader,
        private KomponenServisService $komponenService,
    ) {}

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

            $this->rejectEmbeddedImages($worksheet);
            $preparedRows = [];
            $errors = [];
            $lastRow = $worksheet->getHighestDataRow();

            for ($rowNumber = 2; $rowNumber <= $lastRow; $rowNumber++) {
                $values = [];
                $hasFormula = false;

                foreach (range('A', 'L') as $column) {
                    $cell = $worksheet->getCell($column.$rowNumber);
                    $values[] = $cell->getValue();
                    $hasFormula = $hasFormula || $cell->isFormula();
                }

                if ($hasFormula) {
                    $errors[] = "Baris {$rowNumber}: Formula Excel tidak diperbolehkan. Isi kolom dengan nilai langsung.";

                    continue;
                }

                if (count(array_filter($values, fn (mixed $value): bool => $value !== null && trim((string) $value) !== '')) === 0) {
                    continue;
                }

                $data = $this->normalizeRow($worksheet, $rowNumber);
                $data['foto_kendaraan'] = is_string($values[11]) ? trim($values[11]) : $values[11];

                $validator = Validator::make($data, [
                    'plat_nomor' => ['required', 'string', 'max:15'],
                    'merk_tipe' => ['required', 'string', 'max:100'],
                    'tahun_pembuatan' => ['required', 'integer', 'between:1900,'.(now()->year + 1)],
                    'kilometer_terakhir' => ['required', 'integer', 'min:0'],
                    'transmisi' => ['required', Rule::in(['MANUAL', 'OTOMATIS'])],
                    'jenis_bbm' => ['required', Rule::in(['BENSIN', 'SOLAR', 'DIESEL', 'LISTRIK'])],
                    'kategori_penggunaan' => ['required', Rule::in(['PEJABAT', 'TEKNISI', 'ANGKUT_BARANG', 'MOTOR_OPERASIONAL'])],
                    'unit_pln' => ['required', Rule::in(array_column(UnitPln::cases(), 'value'))],
                    'tanggal_pembelian' => ['required', 'date_format:Y-m-d'],
                    'tanggal_stnk_berlaku_sampai' => ['nullable', 'date_format:Y-m-d'],
                    'tanggal_kir_berlaku_sampai' => ['nullable', 'date_format:Y-m-d'],
                    'foto_kendaraan' => ['nullable', 'string', 'max:2048'],
                ], [], [
                    'plat_nomor' => 'Nomor Polisi',
                    'merk_tipe' => 'Nama Kendaraan',
                    'tahun_pembuatan' => 'Tahun Pembuatan',
                    'kilometer_terakhir' => 'Kilometer',
                    'transmisi' => 'Transmisi',
                    'jenis_bbm' => 'Jenis BBM',
                    'kategori_penggunaan' => 'Kategori Penggunaan',
                    'unit_pln' => 'Unit PLN',
                    'tanggal_pembelian' => 'Tanggal Pembelian',
                    'tanggal_stnk_berlaku_sampai' => 'STNK Berlaku Sampai',
                    'tanggal_kir_berlaku_sampai' => 'KIR Berlaku Sampai',
                    'foto_kendaraan' => 'URL Foto Kendaraan',
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

            $uploadedUrls = [];
            try {
                foreach ($preparedRows as &$row) {
                    $imageUrl = $row['data']['foto_kendaraan'];

                    if ($imageUrl !== null && $imageUrl !== '') {
                        try {
                            $localPath = $this->imageDownloader->download($imageUrl);
                        } catch (RuntimeException $exception) {
                            report($exception);

                            throw ValidationException::withMessages([
                                'file' => [
                                    "Baris {$row['baris']}: Foto gagal diunduh: {$exception->getMessage()} Gunakan URL HTTPS langsung ke JPG, PNG, atau WebP yang dapat diakses publik, bukan tautan berbagi atau halaman preview.",
                                ],
                            ]);
                        } catch (Throwable $exception) {
                            report($exception);

                            throw ValidationException::withMessages([
                                'file' => [
                                    "Baris {$row['baris']}: Server gagal mengunduh foto. Pastikan URL HTTPS langsung ke gambar dapat diakses publik.",
                                ],
                            ]);
                        }

                        try {
                            $uploadedUrl = $this->imageStorage->storeFromPath($localPath, 'kendaraan');
                        } catch (Throwable $exception) {
                            report($exception);

                            throw ValidationException::withMessages([
                                'file' => [
                                    "Baris {$row['baris']}: Foto berhasil diunduh, tetapi gagal disimpan ke storage lokal.",
                                ],
                            ]);
                        } finally {
                            if (is_file($localPath)) {
                                unlink($localPath);
                            }
                        }

                        $uploadedUrls[] = $uploadedUrl;
                        $row['data']['foto_kendaraan'] = $uploadedUrl;
                    } else {
                        $row['data']['foto_kendaraan'] = null;
                    }
                }
                unset($row);

                DB::transaction(function () use ($preparedRows): void {
                    foreach ($preparedRows as $row) {
                        $data = $row['data'];
                        $data['interval_servis_km'] = 5000;
                        $data['interval_servis_bulan'] = 3;
                        $data['threshold_servis_km'] = 200;
                        $data['threshold_servis_hari'] = 10;
                        $data['status_perawatan'] = 'BAIK';

                        $fotoUrl = $data['foto_kendaraan'] ?? null;
                        unset($data['foto_kendaraan']);

                        $kendaraan = Kendaraan::query()->create($data);
                        if ($fotoUrl) {
                            $kendaraan->fotos()->create([
                                'path' => $fotoUrl,
                                'is_utama' => true,
                                'urutan' => 0,
                            ]);
                        }
                        $this->komponenService->applyRecommendedTemplateToKendaraan($kendaraan);
                        $this->importedCount++;
                    }
                });
            } catch (Throwable $exception) {
                foreach ($uploadedUrls as $uploadedUrl) {
                    $this->imageStorage->deleteSafely($uploadedUrl);
                }

                throw $exception;
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
            'Unit PLN',
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

    private function rejectEmbeddedImages(Worksheet $worksheet): void
    {
        if ($worksheet->getDrawingCollection()->count() > 0) {
            throw ValidationException::withMessages([
                'file' => 'Foto yang ditempel atau ditautkan sebagai objek Excel tidak didukung. Masukkan URL HTTPS langsung ke gambar pada kolom Foto Kendaraan.',
            ]);
        }
    }

    private function imageExtension(string $path): string
    {
        return match (mime_content_type($path)) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => throw new RuntimeException('Format foto kendaraan tidak didukung.'),
        };
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
            'unit_pln',
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

            if (in_array($field, ['transmisi', 'jenis_bbm', 'kategori_penggunaan', 'unit_pln'], true) && is_string($value)) {
                if ($field === 'kategori_penggunaan') {
                    $value = str_replace(' ', '_', $value);
                }

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
