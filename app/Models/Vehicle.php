<?php

namespace App\Models;

use App\Domain\Vehicles\Enums\VehicleStatus;
use App\Observers\VehicleObserver;
use Database\Factories\VehicleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[ObservedBy([VehicleObserver::class])]
#[Fillable([
    'inventario_dtb',
    'placa',
    'marca',
    'modelo',
    'linea',
    'year',
    'color',
    'tipo',
    'vin',
    'engine_number',
    'cilindraje',
    'organismo_transito',
    'peso_bruto',
    'peso_neto',
    'ubicacion_fisica',
    'servicio',
    'tiempo_inmovilizacion_dias',
    'causal_inmovilizacion',
    'fecha_ingreso',
    'fecha_notificacion',
    'resolucion',
    'estado',
    'observaciones',
    'owner_id',
    'created_by',
    'completion_percentage',
])]
class Vehicle extends Model implements HasMedia
{
    /** @use HasFactory<VehicleFactory> */
    use HasFactory, InteractsWithMedia, LogsActivity, SoftDeletes;

    public const PHOTOS_COLLECTION = 'photos';

    public const MAX_PHOTOS = 5;

    protected function casts(): array
    {
        return [
            'estado' => VehicleStatus::class,
            'year' => 'integer',
            'cilindraje' => 'integer',
            'tiempo_inmovilizacion_dias' => 'integer',
            'completion_percentage' => 'integer',
            'fecha_ingreso' => 'date',
            'fecha_notificacion' => 'date',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'placa', 'marca', 'linea', 'year', 'color', 'tipo',
                'estado', 'observaciones', 'owner_id',
                'fecha_ingreso', 'organismo_transito', 'ubicacion_fisica',
                'completion_percentage',
            ])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => match ($eventName) {
                'created' => 'Vehículo registrado',
                'updated' => 'Vehículo actualizado',
                'deleted' => 'Vehículo eliminado',
                'restored' => 'Vehículo restaurado',
                default => "Vehículo: {$eventName}",
            })
            ->useLogName('vehicle');
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
