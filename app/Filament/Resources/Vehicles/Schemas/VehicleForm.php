<?php

namespace App\Filament\Resources\Vehicles\Schemas;

use App\Domain\Vehicles\Enums\VehicleStatus;
use App\Models\Vehicle;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\SpatieLaravelMediaLibraryPlugin\Forms\Components\SpatieMediaLibraryFileUpload;

class VehicleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make()
                ->columnSpanFull()
                ->tabs([
                    Tab::make('Datos básicos')
                        ->icon('heroicon-o-identification')
                        ->columns(2)
                        ->schema([
                            TextInput::make('placa')
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->maxLength(16)
                                ->regex('/^[A-Z]{3}-?\d{2,3}[A-Z]?$/i')
                                ->dehydrateStateUsing(fn (?string $state) => $state ? strtoupper($state) : $state)
                                ->live(onBlur: true)
                                ->placeholder('ABC123 o ABC-123'),
                            Select::make('estado')
                                ->options(VehicleStatus::class)
                                ->default(VehicleStatus::Draft->value)
                                ->required(),
                            TextInput::make('marca')->maxLength(255),
                            TextInput::make('modelo')->maxLength(255),
                            TextInput::make('year')
                                ->label('Año')
                                ->numeric()
                                ->minValue(1900)
                                ->maxValue((int) date('Y') + 1),
                            TextInput::make('color')->maxLength(50),
                            Select::make('tipo')
                                ->options([
                                    'Automóvil' => 'Automóvil',
                                    'Camioneta' => 'Camioneta',
                                    'Motocicleta' => 'Motocicleta',
                                    'Camión' => 'Camión',
                                    'Bus' => 'Bus',
                                    'Otro' => 'Otro',
                                ])
                                ->searchable(),
                            Select::make('owner_id')
                                ->label('Propietario')
                                ->relationship('owner', 'full_name')
                                ->searchable(['full_name', 'document_number'])
                                ->preload()
                                ->createOptionForm([
                                    Select::make('document_type')
                                        ->options(['CC' => 'CC', 'CE' => 'CE', 'NIT' => 'NIT', 'Pasaporte' => 'Pasaporte'])
                                        ->required(),
                                    TextInput::make('document_number')->required()->maxLength(50),
                                    TextInput::make('full_name')->required()->maxLength(255),
                                    TextInput::make('phone')->tel()->maxLength(50),
                                    TextInput::make('email')->email()->maxLength(255),
                                    TextInput::make('address')->maxLength(255),
                                ]),
                        ]),

                    Tab::make('Datos técnicos')
                        ->icon('heroicon-o-cog-6-tooth')
                        ->columns(2)
                        ->schema([
                            TextInput::make('vin')
                                ->label('VIN / Chasis')
                                ->maxLength(50),
                            TextInput::make('engine_number')
                                ->label('Número de motor')
                                ->maxLength(50),
                        ]),

                    Tab::make('Fotografías')
                        ->icon('heroicon-o-photo')
                        ->schema([
                            SpatieMediaLibraryFileUpload::make('photos')
                                ->collection(Vehicle::PHOTOS_COLLECTION)
                                ->multiple()
                                ->reorderable()
                                ->image()
                                ->imageEditor()
                                ->maxFiles(Vehicle::MAX_PHOTOS)
                                ->maxSize(5 * 1024)
                                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                ->columnSpanFull()
                                ->panelLayout('grid')
                                ->helperText('Hasta '.Vehicle::MAX_PHOTOS.' fotos. Formatos: JPG, PNG, WEBP. Máx 5 MB c/u.'),
                        ]),

                    Tab::make('Observaciones')
                        ->icon('heroicon-o-chat-bubble-bottom-center-text')
                        ->schema([
                            Textarea::make('observaciones')
                                ->rows(6)
                                ->columnSpanFull(),
                        ]),
                ]),
        ]);
    }
}
