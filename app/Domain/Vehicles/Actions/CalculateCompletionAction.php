<?php

namespace App\Domain\Vehicles\Actions;

use App\Models\Vehicle;

/**
 * Calcula el porcentaje de completitud de la ficha técnica.
 *
 * Pesos:
 *  - Identificación básica (placa, marca, linea, year)        25%
 *  - Datos técnicos (vin, engine_number, color, tipo)          25%
 *  - Inmovilización (fecha_ingreso, organismo, ubicacion)      20%
 *  - Propietario asociado                                       10%
 *  - Al menos 1 fotografía                                      15%
 *  - Firma asociada (si aplica — Fase futura)                    5%
 */
class CalculateCompletionAction
{
    public const WEIGHT_IDENTIFICATION = 25;

    public const WEIGHT_TECHNICAL = 25;

    public const WEIGHT_IMMOBILIZATION = 20;

    public const WEIGHT_OWNER = 10;

    public const WEIGHT_PHOTOS = 15;

    public const WEIGHT_SIGNATURE = 5;

    public function __invoke(Vehicle $vehicle): int
    {
        $score = 0;

        if (
            filled($vehicle->placa)
            && filled($vehicle->marca)
            && filled($vehicle->linea)
            && filled($vehicle->year)
        ) {
            $score += self::WEIGHT_IDENTIFICATION;
        }

        if (
            filled($vehicle->vin)
            && filled($vehicle->engine_number)
            && filled($vehicle->color)
            && filled($vehicle->tipo)
        ) {
            $score += self::WEIGHT_TECHNICAL;
        }

        if (
            filled($vehicle->fecha_ingreso)
            && filled($vehicle->organismo_transito)
            && filled($vehicle->ubicacion_fisica)
        ) {
            $score += self::WEIGHT_IMMOBILIZATION;
        }

        if ($vehicle->owner_id !== null) {
            $score += self::WEIGHT_OWNER;
        }

        if ($vehicle->relationLoaded('media')
            ? $vehicle->media->where('collection_name', Vehicle::PHOTOS_COLLECTION)->count() > 0
            : $vehicle->getMedia(Vehicle::PHOTOS_COLLECTION)->count() > 0
        ) {
            $score += self::WEIGHT_PHOTOS;
        }

        // La firma vendrá en una fase futura (Signature model). Por ahora se
        // suma SIGNATURE solo si tiene firma del usuario que creó la ficha.
        // De momento dejamos esto reservado y no se otorga.
        $score += 0;

        return min(100, max(0, $score));
    }

    /**
     * @return array<int, string> lista de campos faltantes con etiqueta legible
     */
    public function missingFields(Vehicle $vehicle): array
    {
        $missing = [];

        foreach (['placa' => 'placa', 'marca' => 'marca', 'linea' => 'línea', 'year' => 'año'] as $key => $label) {
            if (blank($vehicle->{$key})) {
                $missing[] = $label;
            }
        }
        foreach (['vin' => 'VIN', 'engine_number' => 'motor', 'color' => 'color', 'tipo' => 'clase'] as $key => $label) {
            if (blank($vehicle->{$key})) {
                $missing[] = $label;
            }
        }
        foreach (['fecha_ingreso' => 'fecha de ingreso', 'organismo_transito' => 'organismo', 'ubicacion_fisica' => 'ubicación'] as $key => $label) {
            if (blank($vehicle->{$key})) {
                $missing[] = $label;
            }
        }
        if ($vehicle->owner_id === null) {
            $missing[] = 'propietario';
        }

        $hasPhotos = $vehicle->relationLoaded('media')
            ? $vehicle->media->where('collection_name', Vehicle::PHOTOS_COLLECTION)->count() > 0
            : $vehicle->getMedia(Vehicle::PHOTOS_COLLECTION)->count() > 0;

        if (! $hasPhotos) {
            $missing[] = 'fotografías';
        }

        return $missing;
    }
}
