<?php

namespace App\Console\Commands;

use App\Domain\Imports\Actions\ProcessImportRowAction;
use App\Domain\Imports\Enums\ImportBatchStatus;
use App\Domain\Imports\Enums\ImportRowAction;
use App\Domain\Imports\Mappers\VehicleExcelMapper;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('imports:process-pending {batch? : ID del batch (default: ultimo activo)} {--chunk=500 : Filas por chunk interno} {--max-rows=0 : Tope total de filas a procesar (0 = todas)}')]
#[Description('Procesa filas pending de un ImportBatch sin usar colas. Hace loop por chunks y actualiza contadores del batch en vivo.')]
class ProcessPendingImportRows extends Command
{
    /**
     * @return int
     */
    public function handle(ProcessImportRowAction $action, VehicleExcelMapper $mapper)
    {
        @set_time_limit(0);
        @ini_set('max_execution_time', '0');
        @ini_set('memory_limit', '1G');

        $batchId = $this->argument('batch');
        $chunkSize = max(1, (int) $this->option('chunk'));
        $maxRows = max(0, (int) $this->option('max-rows'));

        $batch = $batchId
            ? ImportBatch::query()->find($batchId)
            : ImportBatch::query()->whereIn('status', [ImportBatchStatus::Queued->value, ImportBatchStatus::Processing->value])->latest()->first();

        if ($batch === null) {
            $this->error('No hay batch activo.');

            return self::FAILURE;
        }

        $this->info("Batch #{$batch->id}: {$batch->original_filename}");

        $pendingTotal = $batch->rows()->where('action', ImportRowAction::Pending->value)->count();
        $this->info("Total filas: {$batch->total_rows}, Pending: {$pendingTotal}");

        if ($batch->status !== ImportBatchStatus::Processing) {
            $batch->update(['status' => ImportBatchStatus::Processing->value, 'started_at' => $batch->started_at ?? now()]);
        }

        $stats = ['created' => 0, 'updated' => 0, 'failed' => 0, 'skipped' => 0];
        $processedTotal = 0;

        while (true) {
            $pending = ImportRow::query()
                ->where('batch_id', $batch->id)
                ->where('action', ImportRowAction::Pending->value)
                ->orderBy('id')
                ->limit($chunkSize)
                ->get();

            if ($pending->isEmpty()) {
                break;
            }

            $this->info('Chunk de '.$pending->count().' filas...');
            $bar = $this->output->createProgressBar($pending->count());
            $bar->start();

            foreach ($pending as $row) {
                $mapped = $mapper->map($row->raw_data ?? []);
                $result = $action($row, $mapped);
                $stats[$result->value] = ($stats[$result->value] ?? 0) + 1;
                $processedTotal++;
                $bar->advance();
            }
            $bar->finish();
            $this->newLine();

            $this->updateBatchCounters($batch, $stats, $processedTotal);

            if ($maxRows > 0 && $processedTotal >= $maxRows) {
                $this->newLine();
                $this->warn("Tope alcanzado: --max-rows={$maxRows}. Vuelve a correr para continuar.");
                break;
            }
        }

        $this->newLine();
        foreach ($stats as $k => $v) {
            $this->line(str_pad($k, 10).": $v");
        }

        $remaining = ImportRow::query()
            ->where('batch_id', $batch->id)
            ->where('action', ImportRowAction::Pending->value)
            ->count();

        $this->newLine();
        $this->info("Pending restantes en batch: {$remaining}");

        if ($remaining === 0) {
            $this->finalize($batch);
            $this->info('Batch finalizado. Status: '.$batch->fresh()->status->value);
        }

        return self::SUCCESS;
    }

    /**
     * Refresca los contadores del batch leyendo de la BD (acumulativo
     * sobre corridas previas), para que el UI vea el total real.
     */
    private function updateBatchCounters(ImportBatch $batch, array $stats, int $processedTotal): void
    {
        $counts = $batch->rows()
            ->selectRaw('action, COUNT(*) as c')
            ->groupBy('action')
            ->pluck('c', 'action');

        $created = (int) ($counts[ImportRowAction::Created->value] ?? 0);
        $updated = (int) ($counts[ImportRowAction::Updated->value] ?? 0);
        $failed = (int) ($counts[ImportRowAction::Failed->value] ?? 0);
        $skipped = (int) ($counts[ImportRowAction::Skipped->value] ?? 0);

        $batch->forceFill([
            'created_count' => $created,
            'updated_count' => $updated,
            'failed_count' => $failed,
            'skipped_count' => $skipped,
            'processed_rows' => $created + $updated + $failed + $skipped,
        ])->save();
    }

    private function finalize(ImportBatch $batch): void
    {
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
