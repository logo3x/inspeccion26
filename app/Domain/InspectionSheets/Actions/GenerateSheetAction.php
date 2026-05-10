<?php

namespace App\Domain\InspectionSheets\Actions;

use App\Domain\InspectionSheets\Generators\DocxGenerator;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GenerateSheetAction
{
    public function __construct(
        private readonly DocxGenerator $generator,
    ) {}

    /**
     * Genera el .docx para un vehículo y devuelve la ruta absoluta.
     * El archivo se guarda bajo storage/app/private/sheets/{placa}/{timestamp}_{placa}.docx
     */
    public function __invoke(Vehicle $vehicle): string
    {
        $disk = Storage::disk('local');
        $placaSlug = Str::slug($vehicle->placa ?: ('vehicle-'.$vehicle->id), '_');
        $relativeDir = "sheets/{$placaSlug}";
        $filename = sprintf('%s_%s.docx', now()->format('Ymd_His'), $placaSlug);
        $relativePath = "{$relativeDir}/{$filename}";

        $disk->makeDirectory($relativeDir);
        $absolute = $disk->path($relativePath);

        $this->generator->generate($vehicle, $absolute);

        return $absolute;
    }

    public function suggestedDownloadName(Vehicle $vehicle): string
    {
        $placaSlug = Str::slug($vehicle->placa ?: ('vehicle-'.$vehicle->id), '_');

        return "ficha_{$placaSlug}.docx";
    }
}
