<?php

namespace App\Filament\Resources\Owners\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OwnerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Datos del propietario')
                ->columns(3)
                ->schema([
                    TextEntry::make('document_type')
                        ->label('Tipo')
                        ->badge(),
                    TextEntry::make('document_number')
                        ->label('Documento')
                        ->copyable(),
                    TextEntry::make('vehicles_count')
                        ->label('Vehículos asociados')
                        ->state(fn ($record) => $record->vehicles()->count())
                        ->badge()
                        ->color('primary'),
                    TextEntry::make('full_name')
                        ->label('Nombre')
                        ->columnSpanFull()
                        ->weight('bold')
                        ->size('lg'),
                    TextEntry::make('phone')
                        ->label('Teléfono')
                        ->icon('heroicon-o-phone')
                        ->placeholder('—'),
                    TextEntry::make('email')
                        ->icon('heroicon-o-envelope')
                        ->copyable()
                        ->placeholder('—'),
                    TextEntry::make('address')
                        ->label('Dirección')
                        ->placeholder('—'),
                    TextEntry::make('created_at')
                        ->label('Registrado')
                        ->dateTime('Y-m-d H:i')
                        ->columnSpan(2),
                    TextEntry::make('updated_at')
                        ->label('Última actualización')
                        ->dateTime('Y-m-d H:i'),
                ]),
        ]);
    }
}
