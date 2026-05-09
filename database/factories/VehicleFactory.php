<?php

namespace Database\Factories;

use App\Domain\Vehicles\Enums\VehicleStatus;
use App\Models\Owner;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'placa' => Str::upper(fake()->unique()->bothify('???###')),
            'marca' => fake()->randomElement(['Chevrolet', 'Renault', 'Mazda', 'Toyota', 'Nissan']),
            'modelo' => fake()->randomElement(['Spark', 'Logan', 'CX-30', 'Corolla', 'Sentra']),
            'year' => fake()->numberBetween(2005, 2026),
            'color' => fake()->safeColorName(),
            'tipo' => fake()->randomElement(['Automóvil', 'Camioneta', 'Motocicleta', 'Camión']),
            'vin' => Str::upper(fake()->bothify('###???###???#####')),
            'engine_number' => Str::upper(fake()->bothify('???######')),
            'estado' => fake()->randomElement(VehicleStatus::cases())->value,
            'observaciones' => fake()->optional()->sentence(),
            'owner_id' => Owner::factory(),
        ];
    }
}
