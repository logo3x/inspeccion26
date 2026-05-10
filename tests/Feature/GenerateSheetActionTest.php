<?php

use App\Domain\InspectionSheets\Actions\GenerateSheetAction;
use App\Models\Owner;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
});

it('produces a docx file at the expected path', function () {
    $owner = Owner::create([
        'document_type' => 'CC',
        'document_number' => '111',
        'full_name' => 'Test Owner',
    ]);

    $vehicle = Vehicle::create([
        'placa' => 'DOC001',
        'marca' => 'TOYOTA',
        'linea' => 'COROLLA',
        'year' => 2020,
        'tipo' => 'AUTOMOVIL',
        'owner_id' => $owner->id,
    ]);

    $path = app(GenerateSheetAction::class)($vehicle);

    expect($path)->toBeString()
        ->and(file_exists($path))->toBeTrue()
        ->and(filesize($path))->toBeGreaterThan(1000);
});

it('suggests a slug-friendly download name', function () {
    $vehicle = Vehicle::create(['placa' => 'XYZ-99']);
    $name = app(GenerateSheetAction::class)->suggestedDownloadName($vehicle);

    expect($name)->toBe('ficha_xyz_99.docx');
});
