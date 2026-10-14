<?php

namespace App\Services;

use Cloudinary\Cloudinary;
use Cloudinary\Configuration\ConfigUtils;
use Illuminate\Http\UploadedFile;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\ImageManager;
use RuntimeException;
use Throwable;

class CloudinaryService
{
    public function upload(UploadedFile $file, string $folder): string
    {
        $clientName = $file->getClientOriginalName();
        $options = [
            'folder' => $folder,
            'filename_override' => $clientName,
            'use_filename' => true,
            'unique_filename' => true,
        ];

        if (strtolower($file->getMimeType()) === 'application/pdf' || self::isPdf($clientName)) {
            try {
                return $this->uploadPdf($file->getRealPath(), $options, $folder);
            } catch (Throwable $exception) {
                if (! $this->isPasswordProtectedPdfError($exception)) {
                    throw $exception;
                }

                $rawPublicId = $folder.'/'.pathinfo($clientName, PATHINFO_FILENAME).'-'.str()->uuid().'.pdf';

                return $this->secureUrl($this->cloudinary()->uploadApi()->upload(
                    $file->getRealPath(),
                    [
                        'public_id' => $rawPublicId,
                        'resource_type' => 'raw',
                    ],
                ));
            }
        }

        return $this->uploadImagePath($file->getRealPath(), ['folder' => $folder]);
    }

    public function uploadFromPath(string $absolutePath, string $publicId): string
    {
        if (self::isPdf($absolutePath)) {
            $options = [
                'public_id' => $publicId,
                'overwrite' => true,
                'resource_type' => 'image',
            ];

            try {
                return $this->secureUrl($this->cloudinary()->uploadApi()->upload($absolutePath, $options));
            } catch (Throwable $exception) {
                if (! $this->isPasswordProtectedPdfError($exception)) {
                    throw $exception;
                }

                return $this->secureUrl($this->cloudinary()->uploadApi()->upload(
                    $absolutePath,
                    [
                        'public_id' => $publicId.'.pdf',
                        'overwrite' => true,
                        'resource_type' => 'raw',
                    ],
                ));
            }
        }

        return $this->uploadImagePath($absolutePath, [
            'public_id' => $publicId,
            'overwrite' => true,
        ]);
    }

    public function delete(?string $url): void
    {
        if (! is_string($url) || ! preg_match('~^https?://~i', $url)) {
            return;
        }

        $publicId = self::publicIdFromUrl($url);

        if ($publicId === null) {
            return;
        }

        preg_match('~/((?:image|raw))/upload/~', (string) parse_url($url, PHP_URL_PATH), $matches);
        $resourceType = $matches[1] ?? 'image';

        $this->cloudinary()->uploadApi()->destroy($publicId, [
            'resource_type' => $resourceType,
            'invalidate' => true,
        ]);
    }

    public function deleteSafely(?string $url): void
    {
        try {
            $this->delete($url);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    public static function publicIdFromUrl(?string $url): ?string
    {
        if (! is_string($url) || ! preg_match('~^https?://~i', $url)) {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH);

        if (! is_string($path) || ! preg_match('~/(image|raw)/upload/(.+)$~', $path, $matches)) {
            return null;
        }

        $resourceType = $matches[1];
        $segments = array_values(array_filter(explode('/', trim($matches[2], '/')), fn (string $segment): bool => $segment !== ''));
        $versionIndex = null;

        foreach ($segments as $index => $segment) {
            if (preg_match('/^v\d+$/', $segment)) {
                $versionIndex = $index;
                break;
            }
        }

        if ($versionIndex !== null) {
            $segments = array_slice($segments, $versionIndex + 1);
        } else {
            while (isset($segments[0]) && preg_match('/^[a-z]{1,3}_[^,]+(?:,[a-z]{1,3}_[^,]+)*$/i', $segments[0])) {
                array_shift($segments);
            }
        }

        if ($segments === []) {
            return null;
        }

        $publicId = rawurldecode(implode('/', $segments));

        if ($resourceType !== 'raw' && preg_match('/\.(?:jpe?g|png|webp|gif|avif|pdf)$/i', $publicId)) {
            $publicId = preg_replace('/\.(?:jpe?g|png|webp|gif|avif|pdf)$/i', '', $publicId);
        }

        return $publicId !== '' ? $publicId : null;
    }

    public static function url(?string $value, string $transform = 'f_auto,q_auto'): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! preg_match('~^https?://~i', $value)) {
            return asset('storage/'.$value);
        }

        if (self::isPdf($value) || str_contains((string) parse_url($value, PHP_URL_PATH), '/raw/upload/')) {
            return $value;
        }

        return preg_replace('~/upload/~', '/upload/'.$transform.'/', $value, 1) ?? $value;
    }

    public static function isPdf(?string $value): bool
    {
        if ($value === null) {
            return false;
        }

        return strtolower(pathinfo((string) parse_url($value, PHP_URL_PATH), PATHINFO_EXTENSION)) === 'pdf';
    }

    public static function thumb(?string $value): ?string
    {
        return self::isPdf($value) ? null : self::url($value, 'w_150,h_150,c_fill,f_auto,q_auto');
    }

    protected function cloudinary(): Cloudinary
    {
        $url = config('services.cloudinary.url');

        if (! is_string($url) || trim($url) === '') {
            throw new RuntimeException('CLOUDINARY_URL belum diatur di konfigurasi aplikasi.');
        }

        if (! ConfigUtils::isCloudinaryUrl($url)) {
            throw new RuntimeException(
                'CLOUDINARY_URL tidak valid. Atur dengan format cloudinary://API_KEY:API_SECRET@CLOUD_NAME.',
            );
        }

        return new Cloudinary($url);
    }

    private function uploadImagePath(string $sourcePath, array $options): string
    {
        if (! function_exists('imagewebp')) {
            throw new RuntimeException('GD WebP tidak tersedia; aktifkan dukungan imagewebp() pada PHP.');
        }

        if (! is_file($sourcePath) || ! is_readable($sourcePath)) {
            throw new RuntimeException("File gambar tidak dapat dibaca: {$sourcePath}");
        }

        $temporaryPath = tempnam(sys_get_temp_dir(), 'cloudinary-');

        if ($temporaryPath === false) {
            throw new RuntimeException('File sementara untuk kompresi gambar tidak dapat dibuat.');
        }

        $webpPath = $temporaryPath.'.webp';

        try {
            $image = (new ImageManager(new GdDriver))
                ->read($sourcePath)
                ->orient()
                ->scaleDown(1600, 1600);

            $image->toWebp(80)->save($webpPath);

            return $this->secureUrl($this->cloudinary()->uploadApi()->upload(
                $webpPath,
                [...$options, 'resource_type' => 'image'],
            ));
        } finally {
            if (is_file($webpPath)) {
                unlink($webpPath);
            }

            if (is_file($temporaryPath)) {
                unlink($temporaryPath);
            }
        }
    }

    private function uploadPdf(string $sourcePath, array $options, string $folder): string
    {
        return $this->secureUrl($this->cloudinary()->uploadApi()->upload(
            $sourcePath,
            [
                ...$options,
                'folder' => $folder,
                'resource_type' => 'image',
            ],
        ));
    }

    private function secureUrl(mixed $response): string
    {
        $secureUrl = $response['secure_url'] ?? null;

        if (! is_string($secureUrl) || $secureUrl === '') {
            throw new RuntimeException('Cloudinary tidak mengembalikan secure_url untuk berkas yang diunggah.');
        }

        return $secureUrl;
    }

    private function isPasswordProtectedPdfError(Throwable $exception): bool
    {
        return preg_match('/password|encrypted|encrypt/i', $exception->getMessage()) === 1;
    }
}
