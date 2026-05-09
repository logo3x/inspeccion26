<?php

namespace App\Filament\Resources\Owners\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class OwnerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('document_type')
                    ->options(['CC' => 'CC', 'CE' => 'CE', 'NIT' => 'NIT', 'Pasaporte' => 'Pasaporte'])
                    ->required(),
                TextInput::make('document_number')
                    ->required()
                    ->maxLength(50),
                TextInput::make('full_name')
                    ->label('Nombre completo')
                    ->required()
                    ->columnSpanFull()
                    ->maxLength(255),
                TextInput::make('phone')
                    ->tel()
                    ->maxLength(50),
                TextInput::make('email')
                    ->email()
                    ->maxLength(255),
                TextInput::make('address')
                    ->label('Dirección')
                    ->columnSpanFull()
                    ->maxLength(255),
            ]);
    }
}
