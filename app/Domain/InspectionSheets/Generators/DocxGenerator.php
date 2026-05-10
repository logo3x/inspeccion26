<?php

namespace App\Domain\InspectionSheets\Generators;

use App\Models\Vehicle;
use Illuminate\Support\Carbon;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Converter;

/**
 * Genera la ficha técnica vehicular en formato DOCX. Implementación programática
 * (sin plantilla externa) que produce un documento con encabezado, tabla de
 * datos en secciones y galería de fotos. Cuando el usuario provea una plantilla
 * .docx con placeholders, se podrá reemplazar por TemplateProcessor.
 */
class DocxGenerator
{
    public function generate(Vehicle $vehicle, string $absolutePath): string
    {
        $vehicle->loadMissing(['owner', 'media']);

        $word = new PhpWord;
        $word->setDefaultFontName('Calibri');
        $word->setDefaultFontSize(10);

        $section = $word->addSection([
            'marginTop' => Converter::cmToTwip(1.5),
            'marginBottom' => Converter::cmToTwip(1.5),
            'marginLeft' => Converter::cmToTwip(2),
            'marginRight' => Converter::cmToTwip(2),
        ]);

        $this->renderHeader($section, $vehicle);
        $this->renderIdentification($section, $vehicle);
        $this->renderTechnical($section, $vehicle);
        $this->renderImmobilization($section, $vehicle);
        $this->renderOwner($section, $vehicle);
        $this->renderObservations($section, $vehicle);
        $this->renderPhotos($section, $vehicle);
        $this->renderFooter($section, $vehicle);

        $writer = IOFactory::createWriter($word, 'Word2007');
        $writer->save($absolutePath);

        return $absolutePath;
    }

    private function renderHeader(Section $section, Vehicle $vehicle): void
    {
        $section->addText('FICHA TÉCNICA VEHICULAR', [
            'bold' => true, 'size' => 16, 'color' => '1F2937',
        ], ['alignment' => 'center']);
        $section->addText('Inventario DTB N° '.($vehicle->inventario_dtb ?? '—'), [
            'size' => 10, 'italic' => true, 'color' => '6B7280',
        ], ['alignment' => 'center']);
        $section->addText('Placa: '.$vehicle->placa, [
            'bold' => true, 'size' => 13,
        ], ['alignment' => 'center', 'spaceAfter' => 200]);
    }

    private function renderIdentification(Section $section, Vehicle $vehicle): void
    {
        $section->addText('1. IDENTIFICACIÓN', ['bold' => true, 'size' => 12, 'color' => 'F59E0B']);
        $this->renderTable($section, [
            ['Placa', $vehicle->placa ?? '—', 'Estado', $vehicle->estado?->getLabel() ?? '—'],
            ['Marca', $vehicle->marca ?? '—', 'Línea', $vehicle->linea ?? '—'],
            ['Modelo (año)', (string) ($vehicle->year ?? '—'), 'Color', $vehicle->color ?? '—'],
            ['Clase', $vehicle->tipo ?? '—', 'Servicio', $vehicle->servicio ?? '—'],
        ]);
        $section->addTextBreak(1);
    }

    private function renderTechnical(Section $section, Vehicle $vehicle): void
    {
        $section->addText('2. DATOS TÉCNICOS', ['bold' => true, 'size' => 12, 'color' => 'F59E0B']);
        $this->renderTable($section, [
            ['VIN / Chasis', $vehicle->vin ?? '—', 'Motor', $vehicle->engine_number ?? '—'],
            ['Cilindraje', $vehicle->cilindraje ? $vehicle->cilindraje.' cm³' : '—', 'Peso bruto', $vehicle->peso_bruto ?? '—'],
            ['Peso neto', $vehicle->peso_neto ?? '—', '', ''],
        ]);
        $section->addTextBreak(1);
    }

    private function renderImmobilization(Section $section, Vehicle $vehicle): void
    {
        if (
            blank($vehicle->fecha_ingreso)
            && blank($vehicle->organismo_transito)
            && blank($vehicle->ubicacion_fisica)
            && blank($vehicle->causal_inmovilizacion)
        ) {
            return;
        }

        $section->addText('3. INMOVILIZACIÓN', ['bold' => true, 'size' => 12, 'color' => 'F59E0B']);
        $this->renderTable($section, [
            [
                'Fecha de ingreso',
                $vehicle->fecha_ingreso instanceof Carbon ? $vehicle->fecha_ingreso->format('d/m/Y') : ($vehicle->fecha_ingreso ?? '—'),
                'Tiempo (días)',
                (string) ($vehicle->tiempo_inmovilizacion_dias ?? '—'),
            ],
            ['Organismo de tránsito', $vehicle->organismo_transito ?? '—', 'Causal', $vehicle->causal_inmovilizacion ?? '—'],
            ['Ubicación física', $vehicle->ubicacion_fisica ?? '—', 'Resolución', $vehicle->resolucion ?? '—'],
        ]);
        $section->addTextBreak(1);
    }

    private function renderOwner(Section $section, Vehicle $vehicle): void
    {
        if ($vehicle->owner === null) {
            return;
        }

        $section->addText('4. PROPIETARIO', ['bold' => true, 'size' => 12, 'color' => 'F59E0B']);
        $this->renderTable($section, [
            [
                'Documento',
                trim(($vehicle->owner->document_type ?? '').' '.($vehicle->owner->document_number ?? '—')),
                'Nombre',
                $vehicle->owner->full_name ?? '—',
            ],
            ['Teléfono', $vehicle->owner->phone ?? '—', 'Email', $vehicle->owner->email ?? '—'],
            ['Dirección', $vehicle->owner->address ?? '—', '', ''],
        ]);
        $section->addTextBreak(1);
    }

    private function renderObservations(Section $section, Vehicle $vehicle): void
    {
        if (blank($vehicle->observaciones)) {
            return;
        }

        $section->addText('5. OBSERVACIONES', ['bold' => true, 'size' => 12, 'color' => 'F59E0B']);
        $section->addText($vehicle->observaciones, [], ['spaceAfter' => 200]);
    }

    private function renderPhotos(Section $section, Vehicle $vehicle): void
    {
        $photos = $vehicle->getMedia(Vehicle::PHOTOS_COLLECTION);
        if ($photos->isEmpty()) {
            return;
        }

        $section->addPageBreak();
        $section->addText('6. FOTOGRAFÍAS', ['bold' => true, 'size' => 12, 'color' => 'F59E0B']);
        $section->addTextBreak(1);

        foreach ($photos as $i => $photo) {
            $path = $photo->getPath();
            if (! is_file($path)) {
                continue;
            }

            $section->addText('Foto '.($i + 1).' — '.$photo->file_name, ['italic' => true, 'size' => 9]);
            $section->addImage($path, [
                'width' => Converter::cmToPoint(14),
                'alignment' => 'center',
            ]);
            $section->addTextBreak(1);
        }
    }

    private function renderFooter(Section $section, Vehicle $vehicle): void
    {
        $section->addTextBreak(2);
        $section->addText('Documento generado el '.now()->format('d/m/Y H:i'), [
            'size' => 8, 'italic' => true, 'color' => '9CA3AF',
        ], ['alignment' => 'right']);
    }

    /**
     * @param  array<int, array<int, string>>  $rows  4 columns per row (label | value | label | value)
     */
    private function renderTable(Section $section, array $rows): void
    {
        $table = $section->addTable([
            'borderSize' => 4,
            'borderColor' => 'D1D5DB',
            'cellMargin' => 80,
            'width' => 100 * 50, // 100% in fifty-twentieths
            'unit' => 'pct',
        ]);

        foreach ($rows as $row) {
            $table->addRow();
            $table->addCell(2500, ['bgColor' => 'F3F4F6'])->addText($row[0] ?? '', ['bold' => true, 'size' => 9]);
            $table->addCell(3000)->addText($row[1] ?? '', ['size' => 9]);
            $table->addCell(2500, ['bgColor' => 'F3F4F6'])->addText($row[2] ?? '', ['bold' => true, 'size' => 9]);
            $table->addCell(3000)->addText($row[3] ?? '', ['size' => 9]);
        }
    }
}
