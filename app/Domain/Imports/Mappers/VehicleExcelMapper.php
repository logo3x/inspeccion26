<?php

namespace App\Domain\Imports\Mappers;

use Carbon\CarbonImmutable;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Maps the raw Excel inventory row (column letter ⇒ value) to a normalized
 * payload ready for create/update against the Vehicle model + Owner.
 *
 * Layout from plantilla.xlsx (Hoja1):
 *   Row 1: title "INVENTARIO DTB"
 *   Row 2: headers
 *   Row 3+: data
 *
 * Header → column mapping is fixed for this template; if cabeceras changen
 * en el futuro, se pasa un $columnMap personalizado.
 */
class VehicleExcelMapper
{
    /** @var array<string, string> header label → column letter */
    public const DEFAULT_HEADER_MAP = [
        'inventario_dtb' => 'A',           // N°
        'placa' => 'B',                    // PLACA VEHICULAR
        'owner_full_name' => 'C',          // NOMBRE PROPIETARIO
        'owner_document_number' => 'D',    // CEDULA
        'vin' => 'E',                      // NUMERO DE CHASIS
        'engine_number' => 'F',            // NUMERO DE MOTOR
        'marca' => 'G',                    // MARCA
        'linea' => 'H',                    // LINEA
        'year' => 'I',                     // MODELO
        'color' => 'J',                    // COLOR
        'tipo' => 'K',                     // CLASE
        'cilindraje' => 'L',               // CILINDRAJE
        'organismo_transito' => 'M',       // ORGANISMO DE TRANSITO
        'peso_bruto' => 'N',               // PESO BRUTO
        'peso_neto' => 'O',                // PESO NETO
        'ubicacion_fisica' => 'P',         // UBICACION FISICA
        'servicio' => 'Q',                 // SERVICIO
        'fecha_ingreso' => 'R',            // FECHA DE INGRESO
        'fecha_notificacion' => 'S',       // FECHA DE NOTIFICACION
        'resolucion' => 'T',               // RESOLUCION
    ];

    /** Header row in spreadsheet (1-indexed). */
    public const HEADER_ROW = 2;

    /** First data row (1-indexed). */
    public const FIRST_DATA_ROW = 3;

    /**
     * @param  array<string, mixed>  $row  raw row from PhpSpreadsheet (column letter ⇒ value)
     * @return array{
     *     vehicle: array<string, mixed>,
     *     owner: ?array<string, mixed>,
     *     placa: ?string,
     * }
     */
    public function map(array $row): array
    {
        $get = fn (string $field) => $row[self::DEFAULT_HEADER_MAP[$field] ?? null] ?? null;

        $placa = $this->normalizePlaca($get('placa'));

        $ownerName = $this->trimOrNull($get('owner_full_name'));
        $ownerDoc = $this->trimOrNull($get('owner_document_number'));

        $owner = null;
        if ($ownerDoc !== null || $ownerName !== null) {
            $owner = [
                'document_type' => $this->guessDocumentType($ownerDoc),
                'document_number' => $ownerDoc ?? 'SIN-DOC',
                'full_name' => $ownerName ?? 'Sin nombre',
            ];
        }

        $defaults = (array) config('inspeccion.defaults', []);

        $vehicle = [
            'inventario_dtb' => $this->intOrNull($get('inventario_dtb')),
            'placa' => $placa,
            'marca' => $this->upperOrNull($get('marca')),
            'linea' => $this->upperOrNull($get('linea')),
            'year' => $this->yearOrNull($get('year')),
            'color' => $this->upperOrNull($get('color')),
            'tipo' => $this->normalizeTipo($get('tipo')) ?? ($defaults['tipo'] ?? null),
            'vin' => $this->upperOrNull($get('vin')),
            'engine_number' => $this->upperOrNull($get('engine_number')),
            'cilindraje' => $this->intOrNull($get('cilindraje')),
            'organismo_transito' => $this->trimOrNull($get('organismo_transito')) ?? ($defaults['organismo_transito'] ?? null),
            'peso_bruto' => $this->trimOrNull($get('peso_bruto')) ?? ($defaults['peso_bruto'] ?? null),
            'peso_neto' => $this->trimOrNull($get('peso_neto')) ?? ($defaults['peso_neto'] ?? null),
            'ubicacion_fisica' => $this->trimOrNull($get('ubicacion_fisica')) ?? ($defaults['ubicacion_fisica'] ?? null),
            'servicio' => $this->normalizeServicio($get('servicio')) ?? ($defaults['servicio'] ?? null),
            'fecha_ingreso' => $this->excelDateOrNull($get('fecha_ingreso')),
            'fecha_notificacion' => $this->excelDateOrNull($get('fecha_notificacion')),
            'resolucion' => $this->trimOrNull($get('resolucion')),
        ];

        return [
            'vehicle' => array_filter($vehicle, fn ($v) => $v !== null),
            'owner' => $owner,
            'placa' => $placa,
        ];
    }

    public function isEmptyRow(array $row): bool
    {
        $relevantCols = ['B', 'C', 'D', 'E', 'F', 'G'];
        foreach ($relevantCols as $col) {
            if (filled($row[$col] ?? null)) {
                return false;
            }
        }

        return true;
    }

    private function normalizePlaca(mixed $value): ?string
    {
        $s = $this->trimOrNull($value);
        if ($s === null) {
            return null;
        }

        $clean = strtoupper(preg_replace('/\s+/', '', $s));
        if ($clean === '' || strlen($clean) > 16) {
            return null;
        }

        return $clean;
    }

    private function normalizeTipo(mixed $value): ?string
    {
        $s = $this->upperOrNull($value);
        if ($s === null) {
            return null;
        }

        return match (true) {
            str_contains($s, 'MOTO') && ! str_contains($s, 'CUATR') => 'MOTOCICLETA',
            str_contains($s, 'CUATRI') => 'CUATRIMOTO',
            str_contains($s, 'CAMIONETA') => 'CAMIONETA',
            str_contains($s, 'CAMION') => 'CAMION',
            str_contains($s, 'BUSETA') => 'BUSETA',
            str_contains($s, 'BUS') => 'BUS',
            str_contains($s, 'AUTOMOVIL') || str_contains($s, 'AUTO') => 'AUTOMOVIL',
            default => 'OTRO',
        };
    }

    private function normalizeServicio(mixed $value): ?string
    {
        $s = $this->upperOrNull($value);
        if ($s === null) {
            return null;
        }

        return match (true) {
            str_contains($s, 'PARTICULAR') => 'PARTICULAR',
            str_contains($s, 'PUBLIC') || str_contains($s, 'PÚBLICO') || str_contains($s, 'PUBLICO') => 'PUBLICO',
            str_contains($s, 'OFICIAL') => 'OFICIAL',
            default => null,
        };
    }

    private function guessDocumentType(?string $documentNumber): string
    {
        if ($documentNumber === null) {
            return 'CC';
        }

        $digits = preg_replace('/\D/', '', $documentNumber);

        return strlen($digits ?? '') >= 9 ? 'CC' : 'CC';
    }

    private function intOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        $digits = preg_replace('/\D/', '', (string) $value);

        return $digits === '' ? null : (int) $digits;
    }

    private function yearOrNull(mixed $value): ?int
    {
        $y = $this->intOrNull($value);
        if ($y === null) {
            return null;
        }
        if ($y < 1900 || $y > (int) date('Y') + 1) {
            return null;
        }

        return $y;
    }

    private function excelDateOrNull(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            try {
                $dt = ExcelDate::excelToDateTimeObject((float) $value);

                return CarbonImmutable::instance($dt)->toDateString();
            } catch (\Throwable) {
                return null;
            }
        }

        try {
            return CarbonImmutable::parse((string) $value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function trimOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $s = trim((string) $value);

        return $s === '' ? null : $s;
    }

    private function upperOrNull(mixed $value): ?string
    {
        $s = $this->trimOrNull($value);

        return $s === null ? null : mb_strtoupper($s);
    }
}
