<?php

namespace App\Filament\Resources\Activities\Schemas;

use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ActivityInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Detalle')
                ->columns(3)
                ->schema([
                    TextEntry::make('id')->label('#'),
                    TextEntry::make('event')->badge(),
                    TextEntry::make('log_name')->badge(),
                    TextEntry::make('description')->columnSpanFull(),
                    TextEntry::make('subject_type')
                        ->label('Modelo')
                        ->formatStateUsing(fn (?string $s) => $s ? class_basename($s) : '—'),
                    TextEntry::make('subject_id')->label('ID'),
                    TextEntry::make('causer.name')->label('Realizado por')->placeholder('Sistema'),
                    TextEntry::make('created_at')->label('Cuando')->dateTime('Y-m-d H:i:s'),
                ]),

            Section::make('Cambios registrados')
                ->schema([
                    KeyValueEntry::make('properties.attributes')
                        ->label('Después')
                        ->columnSpanFull(),
                    KeyValueEntry::make('properties.old')
                        ->label('Antes')
                        ->columnSpanFull(),
                ])
                ->collapsed(false),
        ]);
    }
}
