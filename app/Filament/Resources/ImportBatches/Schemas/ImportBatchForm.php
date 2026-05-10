<?php

namespace App\Filament\Resources\ImportBatches\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Schema;

class ImportBatchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            FileUpload::make('file')
                ->label('Archivo Excel (.xlsx)')
                ->required()
                ->disk('local')
                ->directory('imports/uploads')
                ->visibility('private')
                ->preserveFilenames()
                ->acceptedFileTypes([
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'application/vnd.ms-excel',
                ])
                ->maxSize(50 * 1024)
                ->columnSpanFull()
                ->dehydrated(true)
                ->helperText('Hasta 50 MB. Cabeceras esperadas en la fila 2 (formato plantilla DTB).')
                ->storeFileNamesIn('original_filename'),
        ]);
    }
}
