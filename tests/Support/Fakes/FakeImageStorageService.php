<?php

namespace Tests\Support\Fakes;

use App\Services\ImageStorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class FakeImageStorageService extends ImageStorageService
{
    /** @var array<int, string> */
    public array $storedFiles = [];

    /** @var array<int, string> */
    public array $deletedPaths = [];

    public function store(UploadedFile $file, string $folder): string
    {
        $extension = self::isPdf($file->getClientOriginalName()) ? 'pdf' : 'webp';
        $path = trim($folder, '/').'/fake-'.Str::uuid().'.'.$extension;
        $this->storedFiles[] = $path;

        return $path;
    }

    public function storeFromPath(string $absolutePath, string $folder): string
    {
        $extension = self::isPdf($absolutePath) ? 'pdf' : 'webp';
        $path = trim($folder, '/').'/fake-'.Str::uuid().'.'.$extension;
        $this->storedFiles[] = $path;

        return $path;
    }

    public function delete(?string $path): void
    {
        if ($path !== null && $path !== '') {
            $this->deletedPaths[] = $path;
        }
    }

    public function deleteSafely(?string $path): void
    {
        $this->delete($path);
    }
}
