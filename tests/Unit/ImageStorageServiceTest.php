<?php

namespace Tests\Unit;

use App\Services\ImageStorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageStorageServiceTest extends TestCase
{
    public function test_url_generates_correct_storage_urls(): void
    {
        $this->assertSame(
            asset('storage/kendaraan/test.webp'),
            ImageStorageService::url('kendaraan/test.webp'),
        );
        $this->assertNull(ImageStorageService::url(null));
        $this->assertNull(ImageStorageService::url(''));
    }

    public function test_is_pdf_detects_pdf_extension(): void
    {
        $this->assertTrue(ImageStorageService::isPdf('nota_servis/invoice.pdf'));
        $this->assertFalse(ImageStorageService::isPdf('kendaraan/photo.webp'));
        $this->assertFalse(ImageStorageService::isPdf(null));
    }

    public function test_delete_removes_file_from_public_disk(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('kendaraan/test.webp', 'dummy');

        $service = new ImageStorageService;
        $service->delete('kendaraan/test.webp');

        Storage::disk('public')->assertMissing('kendaraan/test.webp');
    }

    public function test_store_pdf_saves_directly_to_public_disk(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->create('nota.pdf', 100, 'application/pdf');

        $service = new ImageStorageService;
        $path = $service->store($file, 'nota_servis');

        $this->assertStringStartsWith('nota_servis/', $path);
        $this->assertStringEndsWith('.pdf', $path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_store_image_optimizes_and_saves_webp_to_public_disk(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('mobil.jpg', 600, 400);

        $service = new ImageStorageService;
        $path = $service->store($file, 'kendaraan');

        $this->assertStringStartsWith('kendaraan/', $path);
        $this->assertStringEndsWith('.webp', $path);
        Storage::disk('public')->assertExists($path);
    }
}
