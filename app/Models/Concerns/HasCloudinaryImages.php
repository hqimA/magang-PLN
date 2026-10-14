<?php

namespace App\Models\Concerns;

use App\Services\CloudinaryService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

trait HasCloudinaryImages
{
    protected array $cloudinaryImagesToDelete = [];

    protected static function bootHasCloudinaryImages(): void
    {
        static::updated(function (Model $model): void {
            foreach ($model->cloudinaryImages as $column) {
                if (! $model->wasChanged($column)) {
                    continue;
                }

                $oldUrl = $model->getOriginal($column);

                if (! is_string($oldUrl) || $oldUrl === '') {
                    continue;
                }

                DB::afterCommit(fn () => app(CloudinaryService::class)->deleteSafely($oldUrl));
            }
        });

        static::deleting(function (Model $model): void {
            $model->cloudinaryImagesToDelete = [];

            foreach ($model->cloudinaryImages as $column) {
                $url = $model->getAttribute($column);

                if (is_string($url) && $url !== '') {
                    $model->cloudinaryImagesToDelete[] = $url;
                }
            }

            foreach ($model->cloudinaryChildren ?? [] as $table => [$foreignKey, $imageColumn]) {
                $model->cloudinaryImagesToDelete = [
                    ...$model->cloudinaryImagesToDelete,
                    ...DB::table($table)
                        ->where($foreignKey, $model->getKey())
                        ->whereNotNull($imageColumn)
                        ->pluck($imageColumn)
                        ->filter(fn (mixed $url): bool => is_string($url) && $url !== '')
                        ->all(),
                ];
            }
        });

        static::deleted(function (Model $model): void {
            $urls = $model->cloudinaryImagesToDelete;

            if ($urls === []) {
                return;
            }

            DB::afterCommit(function () use ($urls): void {
                foreach ($urls as $url) {
                    app(CloudinaryService::class)->deleteSafely($url);
                }
            });
        });
    }
}
