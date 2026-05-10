<?php

namespace App\Domain\InspectionSheets\Actions;

use App\Models\Vehicle;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class GenerateBulkZipAction
{
    public const MAX_SYNC_BATCH = 100;

    public function __construct(
        private readonly GenerateSheetAction $sheet,
    ) {}

    /**
     * Genera un ZIP con la ficha técnica de cada vehículo dado.
     * Devuelve la ruta absoluta del ZIP. Para >MAX_SYNC_BATCH se debería
     * usar una versión queue-based; aquí lanzamos excepción.
     *
     * @param  Collection<int, Vehicle>  $vehicles
     */
    public function __invoke(Collection $vehicles): string
    {
        if ($vehicles->isEmpty()) {
            throw new \RuntimeException('No hay vehículos seleccionados para generar.');
        }

        if ($vehicles->count() > self::MAX_SYNC_BATCH) {
            throw new \RuntimeException(sprintf(
                'Generación masiva limitada a %d vehículos por lote (recibidos: %d). Filtra la selección.',
                self::MAX_SYNC_BATCH,
                $vehicles->count()
            ));
        }

        @ini_set('memory_limit', '512M');
        @set_time_limit(300);

        $disk = Storage::disk('local');
        $batchId = now()->format('Ymd_His').'_'.Str::random(6);
        $relativeDir = "sheets/_bulk/{$batchId}";
        $disk->makeDirectory($relativeDir);

        $zipRelative = "{$relativeDir}/fichas_{$batchId}.zip";
        $zipAbsolute = $disk->path($zipRelative);

        $zip = new ZipArchive;
        if ($zip->open($zipAbsolute, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('No se pudo crear el archivo ZIP en '.$zipAbsolute);
        }

        $usedNames = [];
        foreach ($vehicles as $vehicle) {
            $sheetPath = ($this->sheet)($vehicle);
            $entryName = $this->uniqueEntryName($this->sheet->suggestedDownloadName($vehicle), $usedNames);
            $zip->addFile($sheetPath, $entryName);
            $usedNames[$entryName] = true;
        }

        $zip->close();

        return $zipAbsolute;
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

    public function suggestedZipName(): string
    {
        return 'fichas_'.now()->format('Ymd_His').'.zip';
    }
}
