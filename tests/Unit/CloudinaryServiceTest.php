<?php

namespace Tests\Unit;

use App\Services\CloudinaryService;
use Cloudinary\Api\ApiResponse;
use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Cloudinary;
use Illuminate\Http\UploadedFile;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class CloudinaryServiceTest extends TestCase
{
    public function test_public_id_is_extracted_from_urls_with_and_without_transformations(): void
    {
        $this->assertSame(
            'kendaraan/abc123',
            CloudinaryService::publicIdFromUrl(
                'https://res.cloudinary.com/demo/image/upload/w_150,h_150,c_fill,f_auto,q_auto/v123/kendaraan/abc123.webp',
            ),
        );
        $this->assertSame(
            'nota_servis/invoice',
            CloudinaryService::publicIdFromUrl(
                'https://res.cloudinary.com/demo/image/upload/v456/nota_servis/invoice.jpg',
            ),
        );
        $this->assertSame(
            'mileage/evidence',
            CloudinaryService::publicIdFromUrl(
                'https://res.cloudinary.com/demo/image/upload/w_800,c_limit,f_auto,q_auto/mileage/evidence.webp',
            ),
        );
        $this->assertSame(
            'kendaraan/legacy',
            CloudinaryService::publicIdFromUrl(
                'https://res.cloudinary.com/demo/image/upload/kendaraan/legacy.png',
            ),
        );
        $this->assertSame(
            'nota_servis/secured.pdf',
            CloudinaryService::publicIdFromUrl(
                'https://res.cloudinary.com/demo/raw/upload/v456/nota_servis/secured.pdf',
            ),
        );
        $this->assertNull(CloudinaryService::publicIdFromUrl(null));
        $this->assertNull(CloudinaryService::publicIdFromUrl('kendaraan/abc123.jpg'));
    }

    public function test_url_transforms_cloudinary_images_and_falls_back_for_legacy_paths(): void
    {
        $this->assertSame(
            'https://res.cloudinary.com/demo/image/upload/w_800,c_limit,f_auto,q_auto/v123/kendaraan/abc.webp',
            CloudinaryService::url(
                'https://res.cloudinary.com/demo/image/upload/v123/kendaraan/abc.webp',
                'w_800,c_limit,f_auto,q_auto',
            ),
        );
        $this->assertSame(
            asset('storage/kendaraan/abc.jpg'),
            CloudinaryService::url('kendaraan/abc.jpg'),
        );
        $this->assertNull(CloudinaryService::url(null));
    }

    public function test_pdf_urls_are_not_transformed_and_are_detected_by_extension(): void
    {
        $pdfUrl = 'https://res.cloudinary.com/demo/image/upload/v123/nota_servis/nota.pdf';

        $this->assertSame($pdfUrl, CloudinaryService::url($pdfUrl));
        $this->assertTrue(CloudinaryService::isPdf($pdfUrl));
        $this->assertNull(CloudinaryService::thumb($pdfUrl));
    }

    public function test_delete_uses_the_public_id_and_cloudinary_resource_type_from_the_url(): void
    {
        $uploadApi = Mockery::mock(UploadApi::class);
        $cloudinary = Mockery::mock(Cloudinary::class);

        $cloudinary->shouldReceive('uploadApi')->once()->andReturn($uploadApi);
        $uploadApi->shouldReceive('destroy')
            ->once()
            ->with('mileage/evidence', ['resource_type' => 'image', 'invalidate' => true])
            ->andReturn(new ApiResponse([], []));

        $service = new class($cloudinary) extends CloudinaryService
        {
            public function __construct(private Cloudinary $client) {}

            protected function cloudinary(): Cloudinary
            {
                return $this->client;
            }
        };

        $service->delete('https://res.cloudinary.com/demo/image/upload/v123/mileage/evidence.webp');
    }

    public function test_invalid_cloudinary_url_fails_with_actionable_configuration_message(): void
    {
        config(['services.cloudinary.url' => 'invalid-cloudinary-value']);

        $service = new class extends CloudinaryService
        {
            public function configuredClient(): Cloudinary
            {
                return $this->cloudinary();
            }
        };

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'CLOUDINARY_URL tidak valid. Atur dengan format cloudinary://API_KEY:API_SECRET@CLOUD_NAME.',
        );

        $service->configuredClient();
    }

    public function test_upload_converts_images_to_webp_before_sending_them_to_cloudinary(): void
    {
        $uploadApi = Mockery::mock(UploadApi::class);
        $cloudinary = Mockery::mock(Cloudinary::class);
        $temporaryWebpPath = null;

        $cloudinary->shouldReceive('uploadApi')->once()->andReturn($uploadApi);
        $uploadApi->shouldReceive('upload')
            ->once()
            ->withArgs(function (string $path, array $options) use (&$temporaryWebpPath): bool {
                $temporaryWebpPath = $path;
                $contents = file_get_contents($path);

                return is_string($contents)
                    && substr($contents, 0, 4) === 'RIFF'
                    && substr($contents, 8, 4) === 'WEBP'
                    && $options === ['folder' => 'kendaraan', 'resource_type' => 'image'];
            })
            ->andReturn(new ApiResponse(
                ['secure_url' => 'https://res.cloudinary.com/demo/image/upload/v123/kendaraan/test.webp'],
                [],
            ));

        $service = new class($cloudinary) extends CloudinaryService
        {
            public function __construct(private Cloudinary $client) {}

            protected function cloudinary(): Cloudinary
            {
                return $this->client;
            }
        };

        $url = $service->upload(UploadedFile::fake()->image('vehicle.jpg', 1800, 1200), 'kendaraan');

        $this->assertSame(
            'https://res.cloudinary.com/demo/image/upload/v123/kendaraan/test.webp',
            $url,
        );
        $this->assertNotNull($temporaryWebpPath);
        $this->assertFileDoesNotExist($temporaryWebpPath);
    }

    public function test_password_protected_pdf_falls_back_to_raw_cloudinary_upload(): void
    {
        $uploadApi = Mockery::mock(UploadApi::class);
        $cloudinary = Mockery::mock(Cloudinary::class);

        $cloudinary->shouldReceive('uploadApi')->twice()->andReturn($uploadApi);
        $uploadApi->shouldReceive('upload')
            ->once()
            ->withArgs(fn (string $path, array $options): bool => $options['resource_type'] === 'image')
            ->andThrow(new RuntimeException('PDF is password protected'));
        $uploadApi->shouldReceive('upload')
            ->once()
            ->withArgs(fn (string $path, array $options): bool => $options['resource_type'] === 'raw'
                && str_ends_with($options['public_id'], '.pdf'))
            ->andReturn(new ApiResponse(
                ['secure_url' => 'https://res.cloudinary.com/demo/raw/upload/v123/nota_servis/secured.pdf'],
                [],
            ));

        $service = new class($cloudinary) extends CloudinaryService
        {
            public function __construct(private Cloudinary $client) {}

            protected function cloudinary(): Cloudinary
            {
                return $this->client;
            }
        };

        $url = $service->upload(
            UploadedFile::fake()->create('secured.pdf', 20, 'application/pdf'),
            'nota_servis',
        );

        $this->assertSame(
            'https://res.cloudinary.com/demo/raw/upload/v123/nota_servis/secured.pdf',
            $url,
        );
    }
}
