<?php

namespace Tests\Feature;

use App\Models\Kendaraan;
use App\Models\Mileage;
use App\Services\CloudinaryService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\Fakes\FakeCloudinaryService;
use Tests\TestCase;

class CloudinaryImagesTest extends TestCase
{
    private FakeCloudinaryService $cloudinary;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cloudinary = new FakeCloudinaryService;
        $this->app->instance(CloudinaryService::class, $this->cloudinary);

        Schema::create('kendaraan', function (Blueprint $table): void {
            $table->id();
            $table->string('foto_kendaraan')->nullable();
        });

        foreach ([
            'laporan_kerusakan' => 'foto_bukti',
            'mileage' => 'foto_bukti',
            'pengajuan_servis' => 'foto_bukti_odometer',
            'riwayat_servis' => 'foto_nota',
        ] as $tableName => $imageColumn) {
            Schema::create($tableName, function (Blueprint $table) use ($imageColumn): void {
                $table->id();
                $table->foreignId('id_kendaraan')->constrained('kendaraan')->cascadeOnDelete();
                $table->string($imageColumn)->nullable();
            });
        }
    }

    protected function tearDown(): void
    {
        foreach ([
            'riwayat_servis',
            'pengajuan_servis',
            'mileage',
            'laporan_kerusakan',
            'kendaraan',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_replacing_a_model_image_deletes_the_old_cloudinary_asset_after_commit(): void
    {
        $oldUrl = 'https://res.cloudinary.com/demo/image/upload/v123/mileage/old.webp';
        [$kendaraan, $mileage] = $this->createMileage($oldUrl);

        DB::transaction(function () use ($mileage): void {
            $mileage->update([
                'foto_bukti' => 'https://res.cloudinary.com/demo/image/upload/v123/mileage/new.webp',
            ]);

            $this->assertSame([], $this->cloudinary->deletedUrls);
        });

        $this->assertSame([$oldUrl], $this->cloudinary->deletedUrls);
        $this->assertModelExists($kendaraan);
    }

    public function test_deleting_a_model_deletes_its_cloudinary_image(): void
    {
        $url = 'https://res.cloudinary.com/demo/image/upload/v123/mileage/evidence.webp';
        [, $mileage] = $this->createMileage($url);

        $mileage->delete();

        $this->assertSame([$url], $this->cloudinary->deletedUrls);
        $this->assertDatabaseMissing('mileage', ['id' => $mileage->id]);
    }

    public function test_deleting_a_vehicle_deletes_images_from_cascade_deleted_children(): void
    {
        $vehicleUrl = 'https://res.cloudinary.com/demo/image/upload/v123/kendaraan/vehicle.webp';
        $kendaraan = Kendaraan::query()->create([
            'foto_kendaraan' => $vehicleUrl,
        ]);

        $childUrls = [
            'laporan_kerusakan' => 'https://res.cloudinary.com/demo/image/upload/v123/laporan_kerusakan/report.webp',
            'mileage' => 'https://res.cloudinary.com/demo/image/upload/v123/mileage/odometer.webp',
            'pengajuan_servis' => 'https://res.cloudinary.com/demo/image/upload/v123/bukti_odometer/request.webp',
            'riwayat_servis' => 'https://res.cloudinary.com/demo/image/upload/v123/nota_servis/receipt.webp',
        ];

        $childIds = [];

        foreach ([
            'laporan_kerusakan' => 'foto_bukti',
            'mileage' => 'foto_bukti',
            'pengajuan_servis' => 'foto_bukti_odometer',
            'riwayat_servis' => 'foto_nota',
        ] as $table => $imageColumn) {
            $childIds[$table] = DB::table($table)->insertGetId([
                'id_kendaraan' => $kendaraan->id,
                $imageColumn => $childUrls[$table],
            ]);
        }

        $kendaraan->delete();

        $this->assertSame(
            [$vehicleUrl, ...array_values($childUrls)],
            $this->cloudinary->deletedUrls,
        );

        foreach ($childIds as $table => $id) {
            $this->assertDatabaseMissing($table, ['id' => $id]);
        }
    }

    /**
     * @return array{0: Kendaraan, 1: Mileage}
     */
    private function createMileage(string $photoUrl): array
    {
        $kendaraan = Kendaraan::query()->create();

        $mileage = Mileage::query()->create([
            'id_kendaraan' => $kendaraan->id,
            'foto_bukti' => $photoUrl,
        ]);

        return [$kendaraan, $mileage];
    }
}
