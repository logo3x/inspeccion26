<?php

namespace App\Filament\Resources\ImportBatches\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ImportBatchInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Resumen')
                ->columns(4)
                ->schema([
                    TextEntry::make('original_filename')
                        ->label('Archivo')
                        ->columnSpan(2),
                    TextEntry::make('status')
                        ->badge(),
                    TextEntry::make('user.name')
                        ->label('Importado por')
                        ->placeholder('—'),
                    TextEntry::make('total_rows')
                        ->label('Total filas'),
                    TextEntry::make('processed_rows')
                        ->label('Procesadas'),
                    TextEntry::make('progress')
                        ->label('Progreso')
                        ->state(fn ($record) => $record->progressPercentage().'%'),
                    TextEntry::make('started_at')
                        ->label('Inicio')
                        ->dateTime('Y-m-d H:i:s')
                        ->placeholder('—'),
                    TextEntry::make('finished_at')
                        ->label('Fin')
                        ->dateTime('Y-m-d H:i:s')
                        ->placeholder('—'),
                ]),

            Section::make('Resultados')
                ->columns(4)
                ->schema([
                    TextEntry::make('created_count')
                        ->label('Creados')
                        ->badge()
                        ->color('success'),
                    TextEntry::make('updated_count')
                        ->label('Actualizados')
                        ->badge()
                        ->color('info'),
                    TextEntry::make('failed_count')
                        ->label('Fallidos')
                        ->badge()
                        ->color('danger'),
                    TextEntry::make('skipped_count')
                        ->label('Omitidos')
                        ->badge()
                        ->color('warning'),
                ]),
        ]);
    }
}
