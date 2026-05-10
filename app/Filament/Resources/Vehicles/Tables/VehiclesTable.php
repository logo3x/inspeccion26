<?php

namespace App\Filament\Resources\Vehicles\Tables;

use App\Domain\InspectionSheets\Actions\GenerateBulkZipAction;
use App\Domain\InspectionSheets\Actions\GenerateSheetAction;
use App\Domain\Vehicles\Enums\VehicleStatus;
use App\Models\Vehicle;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class VehiclesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['owner', 'createdBy', 'media']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                SpatieMediaLibraryImageColumn::make('photo')
                    ->label('')
                    ->collection(Vehicle::PHOTOS_COLLECTION)
                    ->conversion('thumb')
                    ->circular()
                    ->limit(1),
                TextColumn::make('placa')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),
                TextColumn::make('marca')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('linea')
                    ->label('Línea')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('year')
                    ->label('Año')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('tipo')
                    ->label('Clase')
                    ->badge()
                    ->toggleable(),
                TextColumn::make('owner.full_name')
                    ->label('Propietario')
                    ->searchable(['owners.full_name', 'owners.document_number'])
                    ->toggleable()
                    ->wrap(),
                TextColumn::make('servicio')
                    ->badge()
                    ->color(fn (?string $s) => match ($s) {
                        'PARTICULAR' => 'gray',
                        'PUBLICO' => 'info',
                        'OFICIAL' => 'warning',
                        default => 'gray',
                    })
                    ->toggleable(),
                TextColumn::make('ubicacion_fisica')
                    ->label('Ubicación')
                    ->toggleable()
                    ->wrap()
                    ->limit(40),
                TextColumn::make('fecha_ingreso')
                    ->date('Y-m-d')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('tiempo_inmovilizacion_dias')
                    ->label('Días inmov.')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('estado')
                    ->badge()
                    ->sortable(),
                TextColumn::make('completion_percentage')
                    ->label('% Compl.')
                    ->numeric()
                    ->suffix('%')
                    ->sortable()
                    ->color(fn (int $state) => match (true) {
                        $state >= 100 => 'success',
                        $state >= 60 => 'warning',
                        default => 'danger',
                    }),
                TextColumn::make('createdBy.name')
                    ->label('Creado por')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('estado')
                    ->options(VehicleStatus::class)
                    ->multiple(),
                SelectFilter::make('tipo')
                    ->label('Clase')
                    ->options([
                        'AUTOMOVIL' => 'Automóvil',
                        'CAMIONETA' => 'Camioneta',
                        'MOTOCICLETA' => 'Motocicleta',
                        'CAMION' => 'Camión',
                        'BUS' => 'Bus',
                        'BUSETA' => 'Buseta',
                    ])
                    ->multiple(),
                SelectFilter::make('servicio')
                    ->options([
                        'PARTICULAR' => 'Particular',
                        'PUBLICO' => 'Público',
                        'OFICIAL' => 'Oficial',
                    ]),
                SelectFilter::make('owner_id')
                    ->label('Propietario')
                    ->relationship('owner', 'full_name')
                    ->searchable()
                    ->preload(),
                Filter::make('fecha_ingreso')
                    ->schema([
                        DatePicker::make('desde')->native(false),
                        DatePicker::make('hasta')->native(false),
                    ])
                    ->query(fn ($query, array $data) => $query
                        ->when($data['desde'] ?? null, fn ($q, $d) => $q->whereDate('fecha_ingreso', '>=', $d))
                        ->when($data['hasta'] ?? null, fn ($q, $d) => $q->whereDate('fecha_ingreso', '<=', $d))
                    ),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('downloadSheet')
                    ->label('Ficha')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(function (Vehicle $record, GenerateSheetAction $action) {
                        $path = $action($record);

                        return response()->download($path, $action->suggestedDownloadName($record))
                            ->deleteFileAfterSend(false);
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkAction::make('downloadSheetsZip')
                    ->label('Descargar fichas (ZIP)')
                    ->icon('heroicon-o-archive-box-arrow-down')
                    ->color('success')
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records, GenerateBulkZipAction $bulk) {
                        try {
                            $zipPath = $bulk($records);
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Error generando ZIP')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();

                            return null;
                        }

                        return response()->download($zipPath, $bulk->suggestedZipName())
                            ->deleteFileAfterSend(false);
                    }),
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
