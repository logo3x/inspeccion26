<?php

namespace App\Models;

use App\Domain\Vehicles\Enums\VehicleStatus;
use Database\Factories\VehicleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[Fillable([
    'placa',
    'marca',
    'modelo',
    'year',
    'color',
    'tipo',
    'vin',
    'engine_number',
    'estado',
    'observaciones',
    'owner_id',
    'created_by',
    'completion_percentage',
])]
class Vehicle extends Model implements HasMedia
{
    /** @use HasFactory<VehicleFactory> */
    use HasFactory, InteractsWithMedia, SoftDeletes;

    public const PHOTOS_COLLECTION = 'photos';

    public const MAX_PHOTOS = 5;

    protected function casts(): array
    {
        return [
            'estado' => VehicleStatus::class,
            'year' => 'integer',
            'completion_percentage' => 'integer',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::PHOTOS_COLLECTION)
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->onlyKeepLatest(self::MAX_PHOTOS);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(Fit::Contain, 300, 300)
            ->nonQueued();

        $this->addMediaConversion('web')
            ->fit(Fit::Max, 1280, 1280)
            ->nonQueued();
    }
}
