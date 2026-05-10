<?php

namespace App\Filament\Resources\BulkSheetExports\Schemas;

use App\Models\BulkSheetExport;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BulkSheetExportInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Resumen')
                ->columns(4)
                ->schema([
                    TextEntry::make('label')->label('Descripción')->columnSpan(2),
                    TextEntry::make('status')->badge(),
                    TextEntry::make('user.name')->label('Por')->placeholder('—'),
                    TextEntry::make('total_count')->label('Total'),
                    TextEntry::make('processed_count')->label('Procesadas'),
                    TextEntry::make('failed_count')->label('Errores'),
                    TextEntry::make('progress')
                        ->label('Progreso')
                        ->state(fn (BulkSheetExport $record) => $record->progressPercentage().'%'),
                    TextEntry::make('started_at')->dateTime('Y-m-d H:i:s')->placeholder('—'),
                    TextEntry::make('finished_at')->dateTime('Y-m-d H:i:s')->placeholder('—'),
                    TextEntry::make('zip_size_bytes')
                        ->label('Tamaño ZIP')
                        ->formatStateUsing(fn (?int $state) => $state ? round($state / 1024 / 1024, 1).' MB' : '—'),
                    TextEntry::make('error_message')
                        ->label('Error')
                        ->color('danger')
                        ->columnSpanFull()
                        ->placeholder('—'),
                ]),
        ]);
    }
}
