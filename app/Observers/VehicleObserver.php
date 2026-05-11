<?php

namespace App\Observers;

use App\Domain\Vehicles\Actions\CalculateCompletionAction;
use App\Domain\Vehicles\Support\FichaNumberGenerator;
use App\Models\Vehicle;

class VehicleObserver
{
    public function __construct(
        private readonly CalculateCompletionAction $calculate,
        private readonly FichaNumberGenerator $fichaGenerator,
    ) {}

    /**
     * Antes de cada save:
     *  - Si es creating y no tiene ficha_numero, asigna el siguiente de la secuencia anual.
     *  - Recalcula completion_percentage.
     */
    public function saving(Vehicle $vehicle): void
    {
        if (! $vehicle->exists && blank($vehicle->ficha_numero)) {
            $vehicle->ficha_numero = $this->fichaGenerator->next();
        }

        $vehicle->completion_percentage = ($this->calculate)($vehicle);
    }
}
