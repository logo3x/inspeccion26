<?php

namespace App\Domain\InspectionSheets\Generators;

use App\Domain\InspectionSheets\Support\NumberToSpanishWords;
use App\Models\Vehicle;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\TemplateProcessor;

/**
 * Generador basado en la plantilla oficial .docx con placeholders `${campo}`.
 * Usa PhpWord TemplateProcessor::setValue() para llenar texto, y opcionalmente
 * setImageValue() para fotos e improntas si los placeholders existen.
 *
 * La plantilla debe vivir en storage/app/private/templates/ficha.docx.
 */
class OfficialTemplateGenerator
{
    public const TEMPLATE_PATH = 'templates/ficha.docx';

    public static function isAvailable(): bool
    {
        return Storage::disk('local')->exists(self::TEMPLATE_PATH);
    }

    public function generate(Vehicle $vehicle, string $absolutePath): string
    {
        $vehicle->loadMissing(['owner', 'media']);

        $templatePath = Storage::disk('local')->path(self::TEMPLATE_PATH);
        $tp = new TemplateProcessor($templatePath);

        // Valor económico = peso_neto * 300 (cálculo por chatarra).
        $pesoNetoKg = $this->parseWeightInt($vehicle->peso_neto);
        $valorEconomico = $pesoNetoKg > 0 ? $pesoNetoKg * 300 : 0;

        // Placeholders de texto. Cualquier valor null se reemplaza por '—'.
        $values = [
            'ficha_numero' => $vehicle->ficha_numero ?? '—',
            // Fecha de inspección = día en que se imprime la ficha
            'fecha_inspeccion' => Carbon::now()->format('d/m/Y'),
            'fecha_ingreso' => $this->formatDate($vehicle->fecha_ingreso),
            // Notificación va vacía en la ficha
            'fecha_notificacion' => '',

            'placa' => $vehicle->placa ?? '—',
            'marca' => $vehicle->marca ?? '—',
            'linea' => $vehicle->linea ?? '—',
            'year' => (string) ($vehicle->year ?? '—'),
            'color' => $vehicle->color ?? '—',
            'tipo' => $vehicle->tipo ?? '—',
            'vin' => $vehicle->vin ?? '—',
            'engine_number' => $vehicle->engine_number ?? '—',
            'peso_bruto' => $this->parseWeight($vehicle->peso_bruto),
            'peso_neto' => $this->parseWeight($vehicle->peso_neto),

            'servicio' => $this->humanServicio($vehicle->servicio),
            'ubicacion_fisica' => $vehicle->ubicacion_fisica ?? '—',
            'organismo_transito' => $vehicle->organismo_transito ?? '—',

            'owner_name' => $vehicle->owner?->full_name ?? '—',
            'owner_document' => $vehicle->owner?->document_number ?? '—',

            // Aviso de prensa y resolución van vacíos en la ficha
            'aviso_prensa' => '',
            'resolucion' => '',

            'condicion_bien' => $vehicle->condicion_bien ?? '—',
            'tiempo_vida_util' => $vehicle->tiempo_vida_util_anios
                ? $vehicle->tiempo_vida_util_anios.' AÑOS'
                : '—',
            'estado_fisico' => $vehicle->estado_fisico ?? '—',
            'valor_economico' => $valorEconomico > 0
                ? number_format($valorEconomico, 0, ',', '.')
                : '—',
            'valor_economico_letras' => $valorEconomico > 0
                ? NumberToSpanishWords::toCurrencyPesos($valorEconomico)
                : '—',

            'tecnico_nombre' => (string) config('inspeccion.tecnico_avaluador.nombre', 'Luis A. Fuentes'),
            'tecnico_cargo' => (string) config('inspeccion.tecnico_avaluador.cargo', 'Técnico Automotores'),
        ];

        foreach ($values as $key => $value) {
            $tp->setValue($key, htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8'));
        }

        // Imágenes: fotos del vehículo (${foto_1}..${foto_2}) + improntas en página 2
        // El recuadro de fotos mide ~13 cm de ancho x ~9.6 cm de alto (7425x5463 twips).
        // Cada una de las 2 fotos ocupa la mitad de esa altura, a todo lo ancho.
        $vars = $tp->getVariables();
        $photos = $vehicle->getMedia(Vehicle::PHOTOS_COLLECTION);
        foreach (range(1, Vehicle::MAX_PHOTOS) as $i) {
            $placeholder = "foto_{$i}";
            if (! in_array($placeholder, $vars, true)) {
                continue;
            }
            $photo = $photos[$i - 1] ?? null;
            if ($photo && is_file($photo->getPath())) {
                $tp->setImageValue($placeholder, [
                    'path' => $photo->getPath(),
                    'width' => 480,
                    'height' => 175,
                    'ratio' => true,
                ]);
            } else {
                $tp->setValue($placeholder, '');
            }
        }

        $tp->saveAs($absolutePath);

        return $absolutePath;
    }

    private function formatDate(mixed $date): string
    {
        if ($date === null) {
            return '—';
        }
        if ($date instanceof Carbon) {
            return $date->format('d/m/Y');
        }
        try {
            return Carbon::parse((string) $date)->format('d/m/Y');
        } catch (\Throwable) {
            return (string) $date;
        }
    }

    private function parseWeight(?string $raw): string
    {
        if ($raw === null) {
            return '—';
        }
        // "110KG" → "110"; "110 KG" → "110"; "110" → "110"
        if (preg_match('/(\d+)/', $raw, $m)) {
            return $m[1];
        }

        return $raw;
    }

    private function parseWeightInt(?string $raw): int
    {
        if ($raw === null) {
            return 0;
        }
        if (preg_match('/(\d+)/', $raw, $m)) {
            return (int) $m[1];
        }

        return 0;
    }

    private function humanServicio(?string $servicio): string
    {
        return match ($servicio) {
            'PARTICULAR' => 'Particular',
            'PUBLICO' => 'Público',
            'OFICIAL' => 'Oficial',
            null => '—',
            default => $servicio,
        };
    }
}
