<?php

namespace App\Console\Commands;

use App\Services\CloudinaryService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

#[Signature('images:migrate-cloudinary {--dry-run} {--delete-local}')]
#[Description('Migrasikan gambar lama dari storage lokal ke Cloudinary')]
class MigrateImagesToCloudinary extends Command
{
    /**
     * @var array<string, array{0: string, 1: string}>
     */
    private const IMAGE_COLUMNS = [
        'kendaraan' => ['foto_kendaraan', 'kendaraan'],
        'riwayat_servis' => ['foto_nota', 'nota_servis'],
        'laporan_kerusakan' => ['foto_bukti', 'laporan_kerusakan'],
        'mileage' => ['foto_bukti', 'mileage'],
        'pengajuan_servis' => ['foto_bukti_odometer', 'bukti_odometer'],
    ];

    public function handle(CloudinaryService $cloudinary): int
    {
        $counts = [
            'sukses' => 0,
            'dry-run' => 0,
            'hilang' => 0,
            'gagal' => 0,
        ];
        $dryRun = $this->option('dry-run');

        foreach (self::IMAGE_COLUMNS as $table => [$column, $folder]) {
            DB::table($table)
                ->select(['id', $column])
                ->whereNotNull($column)
                ->where($column, '<>', '')
                ->orderBy('id')
                ->chunkById(100, function ($rows) use (
                    $cloudinary,
                    $table,
                    $column,
                    $dryRun,
                    &$counts,
                ): void {
                    foreach ($rows as $row) {
                        $legacyPath = trim((string) $row->{$column});

                        if ($legacyPath === '' || preg_match('~^https?://~i', $legacyPath)) {
                            continue;
                        }

                        [$diskName, $absolutePath] = $this->findLocalFile($legacyPath);

                        if ($absolutePath === null || $diskName === null) {
                            $this->warn("File tidak ditemukan: {$table}.{$row->id} ({$legacyPath})");
                            $counts['hilang']++;

                            continue;
                        }

                        if ($dryRun) {
                            $this->line("DRY RUN {$table}.{$row->id}: {$legacyPath}");
                            $counts['dry-run']++;

                            continue;
                        }

                        $uploadedUrl = null;
                        $databaseUpdated = false;

                        try {
                            $publicId = pathinfo($legacyPath, PATHINFO_DIRNAME);
                            $filename = pathinfo($legacyPath, PATHINFO_FILENAME);
                            $publicId = $publicId === '.' ? $filename : $publicId.'/'.$filename;
                            $uploadedUrl = $cloudinary->uploadFromPath($absolutePath, $publicId);

                            $affectedRows = DB::table($table)
                                ->where('id', $row->id)
                                ->where($column, $legacyPath)
                                ->update([$column => $uploadedUrl]);

                            if ($affectedRows !== 1) {
                                throw new RuntimeException('Baris berubah atau tidak ditemukan saat URL Cloudinary disimpan.');
                            }

                            $databaseUpdated = true;

                            if ($this->option('delete-local') && ! Storage::disk($diskName)->delete($legacyPath)) {
                                throw new RuntimeException("File lokal tidak dapat dihapus dari disk {$diskName}.");
                            }

                            $counts['sukses']++;
                            $this->info("Berhasil {$table}.{$row->id}: {$legacyPath}");
                        } catch (Throwable $exception) {
                            if (! $databaseUpdated) {
                                $cloudinary->deleteSafely($uploadedUrl);
                            }

                            $counts['gagal']++;
                            $this->error("Gagal {$table}.{$row->id}: {$exception->getMessage()}");
                        }
                    }
                });
        }

        $this->newLine();
        $this->table(
            ['Status', 'Jumlah'],
            [
                ['Sukses', $counts['sukses']],
                ['Dry-run', $counts['dry-run']],
                ['Hilang', $counts['hilang']],
                ['Gagal', $counts['gagal']],
            ],
        );

        return $counts['gagal'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function findLocalFile(string $path): array
    {
        if (str_contains($path, '..') || str_starts_with($path, '/') || str_starts_with($path, '\\')) {
            return [null, null];
        }

        foreach (['public', 'local'] as $diskName) {
            $disk = Storage::disk($diskName);

            if ($disk->exists($path)) {
                return [$diskName, $disk->path($path)];
            }
        }

        return [null, null];
    }
}
