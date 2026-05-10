<?php

namespace App\Jobs;

use App\Domain\Imports\Enums\ImportBatchStatus;
use App\Domain\Imports\Enums\ImportRowAction;
use App\Models\ImportBatch;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class FinalizeImportBatchJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $batchId,
    ) {
        $this->onQueue('imports');
    }

    public function handle(): void
    {
        $batch = ImportBatch::query()->find($this->batchId);
        if ($batch === null) {
            return;
        }

        $counts = $batch->rows()
            ->selectRaw('action, COUNT(*) as c')
            ->groupBy('action')
            ->pluck('c', 'action');

        $created = (int) ($counts[ImportRowAction::Created->value] ?? 0);
        $updated = (int) ($counts[ImportRowAction::Updated->value] ?? 0);
        $failed = (int) ($counts[ImportRowAction::Failed->value] ?? 0);
        $skipped = (int) ($counts[ImportRowAction::Skipped->value] ?? 0);
        $processed = $created + $updated + $failed + $skipped;

        $status = match (true) {
            $failed > 0 && ($created + $updated) === 0 => ImportBatchStatus::Failed,
            $failed > 0 => ImportBatchStatus::Partial,
            default => ImportBatchStatus::Completed,
        };

        $batch->forceFill([
            'created_count' => $created,
            'updated_count' => $updated,
            'failed_count' => $failed,
            'skipped_count' => $skipped,
            'processed_rows' => $processed,
            'status' => $status->value,
            'finished_at' => now(),
        ])->save();
    }
}
