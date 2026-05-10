<?php

namespace App\Jobs;

use App\Domain\InspectionSheets\Actions\GenerateSheetAction;
use App\Domain\InspectionSheets\Actions\StartBulkSheetExportAction;
use App\Domain\InspectionSheets\Enums\BulkSheetExportStatus;
use App\Models\BulkSheetExport;
use App\Models\Vehicle;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;
use ZipArchive;

/**
 * Genera el ZIP de fichas técnicas para un BulkSheetExport. Procesa los vehículos
 * en chunks (500) para no saturar memoria, escribe cada DOCX al disco y los
 * añade incrementalmente a un ZIP. Actualiza processed_count para mostrar
 * progreso al usuario en la vista del export.
 */
class GenerateBulkSheetsJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $timeout = 1800; // 30 min

    public int $tries = 1;

    public function __construct(
        public int $exportId,
    ) {
        $this->onQueue('generation');
    }

    public function uniqueId(): string
    {
        return (string) $this->exportId;
    }

    public function handle(StartBulkSheetExportAction $starter, GenerateSheetAction $sheet): void
    {
        @ini_set('memory_limit', '1024M');
        @set_time_limit(0);

        $export = BulkSheetExport::query()->find($this->exportId);
        if ($export === null || $export->status->isDownloadable()) {
            return;
        }

        $export->forceFill([
            'status' => BulkSheetExportStatus::Processing->value,
            'started_at' => now(),
            'processed_count' => 0,
            'failed_count' => 0,
        ])->save();

        $disk = Storage::disk('local');
        $batchId = now()->format('Ymd_His').'_'.Str::random(6);
        $relativeDir = "sheets/_bulk/{$batchId}";
        $zipRelative = "{$relativeDir}/{$batchId}.zip";
        $disk->makeDirectory($relativeDir);
        $zipAbsolute = $disk->path($zipRelative);

        $zip = new ZipArchive;
        if ($zip->open($zipAbsolute, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->fail($export, 'No se pudo crear el archivo ZIP.');

            return;
        }

        $usedNames = [];
        $processed = 0;
        $failed = 0;

        try {
            $starter->buildQuery($export->criteria)
                ->with(['owner', 'media'])
                ->chunkById(50, function ($chunk) use ($zip, $sheet, &$usedNames, &$processed, &$failed, $export) {
                    foreach ($chunk as $vehicle) {
                        try {
                            /** @var Vehicle $vehicle */
                            $sheetPath = $sheet($vehicle);
                            $entryName = $this->uniqueEntryName($sheet->suggestedDownloadName($vehicle), $usedNames);
                            $zip->addFile($sheetPath, $entryName);
                            $usedNames[$entryName] = true;
                            $processed++;
                        } catch (Throwable $e) {
                            $failed++;
                            logger()->error('GenerateBulkSheetsJob row failure', [
                                'vehicle_id' => $vehicle->id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }

                    DB::table('bulk_sheet_exports')
                        ->where('id', $export->id)
                        ->update([
                            'processed_count' => $processed,
                            'failed_count' => $failed,
                        ]);
                });
        } catch (Throwable $e) {
            $zip->close();
            $this->fail($export, 'Fallo durante la generación: '.$e->getMessage());

            return;
        }

        $zip->close();

        $size = is_file($zipAbsolute) ? filesize($zipAbsolute) : 0;

        $export->forceFill([
            'status' => BulkSheetExportStatus::Completed->value,
            'zip_path' => $zipRelative,
            'zip_size_bytes' => $size,
            'processed_count' => $processed,
            'failed_count' => $failed,
            'finished_at' => now(),
        ])->save();
    }

    private function fail(BulkSheetExport $export, string $message): void
    {
        $export->forceFill([
            'status' => BulkSheetExportStatus::Failed->value,
            'error_message' => $message,
            'finished_at' => now(),
        ])->save();
    }

    /**
     * @param  array<string, true>  $used
     */
    private function uniqueEntryName(string $candidate, array $used): string
    {
        if (! isset($used[$candidate])) {
            return $candidate;
        }

        $info = pathinfo($candidate);
        $base = $info['filename'];
        $ext = isset($info['extension']) ? '.'.$info['extension'] : '';

        $i = 2;
        while (isset($used["{$base}_{$i}{$ext}"])) {
            $i++;
        }

        return "{$base}_{$i}{$ext}";
    }
}
