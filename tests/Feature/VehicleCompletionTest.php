<?php

use App\Domain\Vehicles\Actions\CalculateCompletionAction;
use App\Models\Owner;
use App\Models\Vehicle;

it('starts at 0% with only placa', function () {
    $vehicle = Vehicle::create(['placa' => 'AAA111']);

    expect($vehicle->completion_percentage)->toBe(0);
});

it('jumps to 25% when identification fields are present', function () {
    $vehicle = Vehicle::create([
        'placa' => 'BBB222',
        'marca' => 'TOYOTA',
        'linea' => 'COROLLA',
        'year' => 2020,
    ]);

    expect($vehicle->completion_percentage)->toBe(25);
});

it('reaches 80% with id + tech + immobilization + owner', function () {
    $owner = Owner::create([
        'document_type' => 'CC',
        'document_number' => '123',
        'full_name' => 'Test Owner',
    ]);

    $vehicle = Vehicle::create([
        'placa' => 'CCC333',
        'marca' => 'TOYOTA',
        'linea' => 'COROLLA',
        'year' => 2020,
        'vin' => 'V1',
        'engine_number' => 'E1',
        'color' => 'BLANCO',
        'tipo' => 'AUTOMOVIL',
        'fecha_ingreso' => '2024-01-15',
        'organismo_transito' => 'DTO',
        'ubicacion_fisica' => 'P1',
        'owner_id' => $owner->id,
    ]);

    expect($vehicle->completion_percentage)->toBe(80);
});

it('lists missing fields in spanish', function () {
    $vehicle = Vehicle::create(['placa' => 'DDD444']);
    $missing = app(CalculateCompletionAction::class)->missingFields($vehicle);

    expect($missing)->toContain('marca', 'línea', 'año', 'VIN', 'propietario', 'fotografías');
});
