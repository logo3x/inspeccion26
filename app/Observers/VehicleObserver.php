<?php

namespace App\Observers;

use App\Domain\Vehicles\Actions\CalculateCompletionAction;
use App\Models\Vehicle;

class VehicleObserver
{
    public function __construct(
        private readonly CalculateCompletionAction $calculate,
    ) {}

    /**
     * Recalcula completion_percentage antes de cada save (insert o update),
     * para que la columna refleje siempre el estado actual.
     */
    public function saving(Vehicle $vehicle): void
    {
        $vehicle->completion_percentage = ($this->calculate)($vehicle);
    }
}
