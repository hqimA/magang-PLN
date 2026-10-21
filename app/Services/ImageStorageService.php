<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\ImageManager;
use RuntimeException;
use Throwable;

class ImageStorageService
{
    private const DISK = 'public';

    public function store(UploadedFile $file, string $folder): string
    {
        $mime = strtolower((string) $file->getMimeType());
        $originalName = $file->getClientOriginalName();

        if ($mime === 'application/pdf' || self::isPdf($originalName)) {
            $filename = Str::uuid().'.pdf';
            $path = trim($folder, '/').'/'.$filename;

            Storage::disk(self::DISK)->putFileAs($folder, $file, $filename);

            return $path;
        }

        return $this->storeOptimizedImage($file->getRealPath(), $folder);
    }

    public function storeFromPath(string $absolutePath, string $folder): string
    {
        if (! is_file($absolutePath) || ! is_readable($absolutePath)) {
            throw new RuntimeException("Berkas tidak dapat dibaca: {$absolutePath}");
        }

        if (self::isPdf($absolutePath)) {
            $filename = Str::uuid().'.pdf';
            $path = trim($folder, '/').'/'.$filename;
            $contents = file_get_contents($absolutePath);

            if ($contents === false) {
                throw new RuntimeException("Gagal membaca berkas PDF: {$absolutePath}");
            }

            Storage::disk(self::DISK)->put($path, $contents);

            return $path;
        }

        return $this->storeOptimizedImage($absolutePath, $folder);
    }

    public function delete(?string $path): void
    {
        if (! is_string($path) || trim($path) === '') {
            return;
        }

        if (preg_match('~^https?://~i', $path)) {
            return;
        }

        $cleanPath = ltrim($path, '/');

        if (Storage::disk(self::DISK)->exists($cleanPath)) {
            Storage::disk(self::DISK)->delete($cleanPath);
        }
    }

    public function deleteSafely(?string $path): void
    {
        try {
            $this->delete($path);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    public static function url(?string $path, ?string $transform = null): ?string
    {
        if ($path === null || trim($path) === '') {
            return null;
        }

        if (preg_match('~^https?://~i', $path)) {
            return $path;
        }

        return asset('storage/'.ltrim($path, '/'));
    }

    public static function isPdf(?string $path): bool
    {
        if ($path === null || trim($path) === '') {
            return false;
        }

        $parsedPath = (string) parse_url($path, PHP_URL_PATH);

        return strtolower(pathinfo($parsedPath, PATHINFO_EXTENSION)) === 'pdf';
    }

    private function storeOptimizedImage(string $sourcePath, string $folder): string
    {
        if (! function_exists('imagewebp')) {
            throw new RuntimeException('Dukungan GD WebP (imagewebp) tidak aktif pada instalasi PHP.');
        }

        $image = (new ImageManager(new GdDriver))
            ->read($sourcePath)
            ->orient()
            ->scaleDown(1600, 1600);

        $encoded = $image->toWebp(80);
        $filename = Str::uuid().'.webp';
        $path = trim($folder, '/').'/'.$filename;

        Storage::disk(self::DISK)->put($path, (string) $encoded);

        return $path;
    }
}
