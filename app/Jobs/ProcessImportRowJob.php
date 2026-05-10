<?php

namespace App\Jobs;

use App\Domain\Imports\Actions\ProcessImportRowAction;
use App\Domain\Imports\Mappers\VehicleExcelMapper;
use App\Models\ImportRow;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessImportRowJob implements ShouldQueue
{
    use Batchable, Queueable;

    public int $tries = 3;

    public int $backoff = 5;

    public function __construct(
        public int $importRowId,
    ) {
        $this->onQueue('imports');
    }

    public function handle(ProcessImportRowAction $action, VehicleExcelMapper $mapper): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $row = ImportRow::query()->find($this->importRowId);
        if ($row === null || $row->processed_at !== null) {
            return;
        }

        $mapped = $mapper->map($row->raw_data ?? []);
        $action($row, $mapped);
    }
}
