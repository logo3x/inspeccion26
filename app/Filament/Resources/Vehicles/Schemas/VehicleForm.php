<?php

namespace App\Filament\Resources\Vehicles\Schemas;

use App\Domain\Vehicles\Enums\VehicleStatus;
use App\Models\Vehicle;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class VehicleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Estado del registro')
                ->description('Cambia el estado del vehículo con un click. Los cambios se registran en auditoría.')
                ->icon('heroicon-o-flag')
                ->columnSpanFull()
                ->columns(3)
                ->schema([
                    ToggleButtons::make('estado')
                        ->label('Estado')
                        ->options(VehicleStatus::class)
                        ->default(VehicleStatus::Draft->value)
                        ->inline()
                        ->required()
                        ->columnSpan(2),
                    View::make('filament.forms.vehicle-photo-status')
                        ->columnSpan(1),
                ]),

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
                                ->placeholder('ABC123 o ABC-123')
                                ->columnSpanFull(),
                            TextInput::make('marca')->maxLength(255),
                            TextInput::make('linea')->label('Línea')->maxLength(100),
                            TextInput::make('modelo')->maxLength(255)->helperText('Modelo comercial (texto)'),
                            TextInput::make('year')
                                ->label('Año modelo')
                                ->numeric()
                                ->minValue(1900)
                                ->maxValue((int) date('Y') + 1),
                            TextInput::make('color')->maxLength(50),
                            Select::make('tipo')
                                ->label('Clase')
                                ->options([
                                    'AUTOMOVIL' => 'Automóvil',
                                    'CAMIONETA' => 'Camioneta',
                                    'MOTOCICLETA' => 'Motocicleta',
                                    'CAMION' => 'Camión',
                                    'BUS' => 'Bus',
                                    'BUSETA' => 'Buseta',
                                    'CUATRIMOTO' => 'Cuatrimoto',
                                    'OTRO' => 'Otro',
                                ])
                                ->searchable(),
                            TextInput::make('inventario_dtb')
                                ->label('# Inventario DTB')
                                ->numeric(),
                            Select::make('owner_id')
                                ->label('Propietario')
                                ->relationship('owner', 'full_name')
                                ->searchable(['full_name', 'document_number'])
                                ->preload()
                                ->columnSpanFull()
                                ->createOptionForm([
                                    Select::make('document_type')
                                        ->options(['CC' => 'CC', 'CE' => 'CE', 'NIT' => 'NIT', 'Pasaporte' => 'Pasaporte'])
                                        ->required()
                                        ->default('CC'),
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
                            TextInput::make('vin')->label('VIN / Chasis')->maxLength(50),
                            TextInput::make('engine_number')->label('Número de motor')->maxLength(50),
                            TextInput::make('cilindraje')
                                ->numeric()
                                ->suffix('cm³')
                                ->minValue(0)
                                ->maxValue(20000),
                            Select::make('servicio')
                                ->options([
                                    'PARTICULAR' => 'Particular',
                                    'PUBLICO' => 'Público',
                                    'OFICIAL' => 'Oficial',
                                ]),
                            TextInput::make('peso_bruto')->maxLength(30)->placeholder('p.ej. 110KG'),
                            TextInput::make('peso_neto')->maxLength(30)->placeholder('p.ej. 75KG'),
                        ]),

                    Tab::make('Inmovilización')
                        ->icon('heroicon-o-no-symbol')
                        ->columns(2)
                        ->schema([
                            TextInput::make('organismo_transito')
                                ->label('Organismo de tránsito')
                                ->maxLength(150)
                                ->columnSpanFull(),
                            TextInput::make('ubicacion_fisica')
                                ->label('Ubicación física')
                                ->maxLength(150)
                                ->columnSpanFull(),
                            TextInput::make('causal_inmovilizacion')
                                ->label('Causal')
                                ->maxLength(100),
                            TextInput::make('tiempo_inmovilizacion_dias')
                                ->label('Tiempo (días)')
                                ->numeric()
                                ->suffix('días'),
                            DatePicker::make('fecha_ingreso')
                                ->label('Fecha de ingreso')
                                ->native(false)
                                ->displayFormat('d/m/Y'),
                            DatePicker::make('fecha_notificacion')
                                ->label('Fecha de notificación')
                                ->native(false)
                                ->displayFormat('d/m/Y'),
                            TextInput::make('resolucion')
                                ->label('Resolución')
                                ->maxLength(100)
                                ->columnSpanFull(),
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
