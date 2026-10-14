<?php

namespace Tests\Support\Fakes;

use App\Services\CloudinaryService;
use Illuminate\Http\UploadedFile;

class FakeCloudinaryService extends CloudinaryService
{
    /**
     * @var array<int, string>
     */
    public array $deletedUrls = [];

    /**
     * @var array<int, string>
     */
    public array $uploadedUrls = [];

    private int $sequence = 0;

    public function upload(UploadedFile $file, string $folder): string
    {
        $extension = self::isPdf($file->getClientOriginalName()) ? 'pdf' : 'webp';
        $url = sprintf(
            'https://res.cloudinary.com/test/image/upload/v123/%s/%s-%d.%s',
            $folder,
            pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            ++$this->sequence,
            $extension,
        );

        $this->uploadedUrls[] = $url;

        return $url;
    }

    public function uploadFromPath(string $absolutePath, string $publicId): string
    {
        $extension = self::isPdf($absolutePath) ? 'pdf' : 'webp';
        $url = "https://res.cloudinary.com/test/image/upload/v123/{$publicId}.{$extension}";
        $this->uploadedUrls[] = $url;

        return $url;
    }

    public function deleteSafely(?string $url): void
    {
        if ($url !== null) {
            $this->deletedUrls[] = $url;
        }
    }
}
