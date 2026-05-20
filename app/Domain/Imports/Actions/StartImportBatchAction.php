<?php

namespace App\Domain\Imports\Actions;

use App\Domain\Imports\Enums\ImportBatchStatus;
use App\Domain\Imports\Enums\ImportRowAction;
use App\Domain\Imports\Mappers\VehicleExcelMapper;
use App\Domain\Imports\Support\ChunkReadFilter;
use App\Jobs\FinalizeImportBatchJob;
use App\Jobs\ProcessImportRowJob;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

/**
 * Lee el archivo subido en chunks (para no saturar memoria con miles de filas),
 * materializa una ImportRow por cada fila de datos y dispara un Bus::batch con
 * un Job por fila ("uno por uno") para máxima trazabilidad.
 */
class StartImportBatchAction
{
    public const CHUNK_SIZE = 500;

    public function __invoke(ImportBatch $batch): void
    {
        // Shared hosting (LiteSpeed) limita a 30s por request; el parseo + materialización
        // de Excels grandes excede ese límite. Quitamos el tope mientras dura el job.
        @set_time_limit(0);
        @ini_set('max_execution_time', '0');
        @ini_set('memory_limit', '512M');

        $batch->forceFill([
            'status' => ImportBatchStatus::Processing->value,
            'started_at' => now(),
        ])->save();

        $absolutePath = Storage::disk('local')->path($batch->stored_path);

        $totalRows = $this->detectHighestRow($absolutePath);
        $highestColIndex = $this->detectHighestColumnIndex($absolutePath);

        $insertedCount = $this->materializeRowsInChunks($batch, $absolutePath, $totalRows, $highestColIndex);

        DB::table('import_batches')
            ->where('id', $batch->id)
            ->update(['total_rows' => $insertedCount]);

        $rowIds = ImportRow::query()
            ->where('batch_id', $batch->id)
            ->orderBy('id')
            ->pluck('id')
            ->all();

        if ($rowIds === []) {
            $batch->forceFill([
                'status' => ImportBatchStatus::Completed->value,
                'finished_at' => now(),
            ])->save();

            return;
        }

        // En shared hosting (QUEUE_CONNECTION=sync) Bus::batch ejecutaría TODOS los jobs
        // dentro de esta request HTTP, lo que excede el max_execution_time del servidor.
        // En ese caso dejamos las filas como Pending para que se procesen vía el
        // comando `imports:process-pending` (botón en setup.php / cron).
        if (config('queue.default') === 'sync') {
            return;
        }

        $jobs = array_map(fn (int $id) => new ProcessImportRowJob($id), $rowIds);

        Bus::batch($jobs)
            ->name("Import batch #{$batch->id}")
            ->onQueue('imports')
            ->allowFailures()
            ->finally(fn () => FinalizeImportBatchJob::dispatch($batch->id))
            ->dispatch();
    }

    private function detectHighestRow(string $path): int
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        // Lee la primera columna (la del índice) en todas las filas para descubrir el rango.
        $reader->setReadFilter(new class implements IReadFilter
        {
            public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool
            {
                return $columnAddress === 'A' || $columnAddress === 'B';
            }
        });
        $ss = $reader->load($path);
        $highest = $ss->getActiveSheet()->getHighestRow();
        $ss->disconnectWorksheets();
        unset($ss);

        return (int) $highest;
    }

    private function detectHighestColumnIndex(string $path): int
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $reader->setReadFilter(new ChunkReadFilter(VehicleExcelMapper::HEADER_ROW, VehicleExcelMapper::HEADER_ROW));
        $ss = $reader->load($path);
        $highest = $ss->getActiveSheet()->getHighestColumn();
        $ss->disconnectWorksheets();
        unset($ss);

        return Coordinate::columnIndexFromString($highest);
    }

    private function materializeRowsInChunks(ImportBatch $batch, string $absolutePath, int $totalRows, int $highestColIndex): int
    {
        $mapper = new VehicleExcelMapper;
        $count = 0;
        $start = VehicleExcelMapper::FIRST_DATA_ROW;

        while ($start <= $totalRows) {
            $end = min($start + self::CHUNK_SIZE - 1, $totalRows);

            $reader = IOFactory::createReaderForFile($absolutePath);
            $reader->setReadDataOnly(true);
            $reader->setReadFilter(new ChunkReadFilter($start, $end));
            $ss = $reader->load($absolutePath);
            $sheet = $ss->getActiveSheet();

            $payload = [];
            for ($r = $start; $r <= $end; $r++) {
                $raw = [];
                for ($c = 1; $c <= $highestColIndex; $c++) {
                    $letter = Coordinate::stringFromColumnIndex($c);
                    $value = $sheet->getCell("{$letter}{$r}")->getValue();
                    if ($value !== null && $value !== '') {
                        $raw[$letter] = $value;
                    }
                }

                if ($mapper->isEmptyRow($raw)) {
                    continue;
                }

                $count++;
                $payload[] = [
                    'batch_id' => $batch->id,
                    'row_number' => $r,
                    'placa' => $this->placaFromRaw($raw),
                    'raw_data' => json_encode($raw, JSON_UNESCAPED_UNICODE),
                    'action' => ImportRowAction::Pending->value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if ($payload !== []) {
                ImportRow::insert($payload);
            }

            $ss->disconnectWorksheets();
            unset($ss, $sheet, $reader);
            gc_collect_cycles();

            $start = $end + 1;
        }

        return $count;
    }

    private function placaFromRaw(array $raw): ?string
    {
        $val = $raw[VehicleExcelMapper::DEFAULT_HEADER_MAP['placa']] ?? null;
        if ($val === null) {
            return null;
        }

        $clean = strtoupper(preg_replace('/\s+/', '', (string) $val));

        return $clean === '' ? null : mb_substr($clean, 0, 16);
    }
}
