<?php

namespace App\Filament\Resources\Vehicles\Tables;

use App\Domain\Vehicles\Enums\VehicleStatus;
use App\Models\Vehicle;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

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
                TextColumn::make('modelo')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('year')
                    ->label('Año')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('owner.full_name')
                    ->label('Propietario')
                    ->searchable(['owners.full_name', 'owners.document_number'])
                    ->toggleable(),
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
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('estado')
                    ->options(VehicleStatus::class)
                    ->multiple(),
                SelectFilter::make('owner_id')
                    ->label('Propietario')
                    ->relationship('owner', 'full_name')
                    ->searchable()
                    ->preload(),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
