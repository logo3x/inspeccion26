<?php

use App\Domain\Imports\Actions\ProcessImportRowAction;
use App\Domain\Imports\Enums\ImportRowAction;
use App\Domain\Imports\Mappers\VehicleExcelMapper;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\Owner;
use App\Models\Vehicle;

beforeEach(function () {
    $this->batch = ImportBatch::create([
        'original_filename' => 'test.xlsx',
        'stored_path' => 'samples/test.xlsx',
        'status' => 'processing',
    ]);
    $this->mapper = new VehicleExcelMapper;
    $this->action = app(ProcessImportRowAction::class);
});

it('creates a new vehicle when placa does not exist', function () {
    $row = ImportRow::create([
        'batch_id' => $this->batch->id,
        'row_number' => 3,
        'placa' => 'NEW001',
        'raw_data' => ['B' => 'NEW001', 'G' => 'TOYOTA', 'I' => 2022],
        'action' => 'pending',
    ]);

    $mapped = $this->mapper->map($row->raw_data);
    $result = ($this->action)($row, $mapped);

    expect($result)->toBe(ImportRowAction::Created);
    expect(Vehicle::where('placa', 'NEW001')->exists())->toBeTrue();
});

it('updates an existing vehicle on second run with same placa', function () {
    Vehicle::create(['placa' => 'EXIST1', 'marca' => 'OLD']);

    $row = ImportRow::create([
        'batch_id' => $this->batch->id,
        'row_number' => 4,
        'placa' => 'EXIST1',
        'raw_data' => ['B' => 'EXIST1', 'G' => 'NEW BRAND', 'I' => 2023],
        'action' => 'pending',
    ]);

    $result = ($this->action)($row, $this->mapper->map($row->raw_data));

    expect($result)->toBe(ImportRowAction::Updated);
    expect(Vehicle::where('placa', 'EXIST1')->first()->marca)->toBe('NEW BRAND');
});

it('marks row as failed when placa is missing', function () {
    $row = ImportRow::create([
        'batch_id' => $this->batch->id,
        'row_number' => 5,
        'placa' => null,
        'raw_data' => ['G' => 'SIN PLACA'],
        'action' => 'pending',
    ]);

    $result = ($this->action)($row, $this->mapper->map($row->raw_data));

    expect($result)->toBe(ImportRowAction::Failed);
    expect($row->fresh()->error_message)->toContain('Placa');
});

it('creates owner when document_number is provided', function () {
    $row = ImportRow::create([
        'batch_id' => $this->batch->id,
        'row_number' => 6,
        'placa' => 'OWN001',
        'raw_data' => ['B' => 'OWN001', 'C' => 'Juan Perez', 'D' => '99999'],
        'action' => 'pending',
    ]);

    ($this->action)($row, $this->mapper->map($row->raw_data));

    expect(Owner::where('document_number', '99999')->exists())->toBeTrue();
});
