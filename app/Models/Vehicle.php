<?php

namespace App\Models;

use App\Domain\InspectionSheets\Support\NumberToSpanishWords;
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
    'ficha_numero',
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
    'fecha_inspeccion',
    'aviso_prensa',
    'condicion_bien',
    'tiempo_vida_util_anios',
    'estado_fisico',
    'valor_economico',
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

    public const MAX_PHOTOS = 2;

    public const IMPRONTA_CHASIS_COLLECTION = 'impronta_chasis';

    public const IMPRONTA_MOTOR_COLLECTION = 'impronta_motor';

    protected function casts(): array
    {
        return [
            'estado' => VehicleStatus::class,
            'year' => 'integer',
            'cilindraje' => 'integer',
            'tiempo_inmovilizacion_dias' => 'integer',
            'tiempo_vida_util_anios' => 'integer',
            'completion_percentage' => 'integer',
            'fecha_ingreso' => 'date',
            'fecha_notificacion' => 'date',
            'fecha_inspeccion' => 'date',
            'valor_economico' => 'decimal:2',
        ];
    }

    public function valorEconomicoEnLetras(): string
    {
        return NumberToSpanishWords::toCurrencyPesos($this->valor_economico);
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

        $this->addMediaCollection(self::IMPRONTA_CHASIS_COLLECTION)
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->singleFile();

        $this->addMediaCollection(self::IMPRONTA_MOTOR_COLLECTION)
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        // En shared hosting con proc_open deshabilitado (común en cPanel) no se
        // pueden generar conversiones de imagen. Si no está disponible, se guarda
        // solo el original y la columna foto en Filament cae al fallback.
        if (! function_exists('proc_open')) {
            return;
        }

        $this->addMediaConversion('thumb')
            ->fit(Fit::Contain, 300, 300)
            ->nonQueued();

        $this->addMediaConversion('web')
            ->fit(Fit::Max, 1280, 1280)
            ->nonQueued();
    }
}
