<?php

namespace App\Filament\Resources\Owners\RelationManagers;

use App\Domain\Vehicles\Enums\VehicleStatus;
use App\Filament\Resources\Vehicles\VehicleResource;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class VehiclesRelationManager extends RelationManager
{
    protected static string $relationship = 'vehicles';

    protected static ?string $relatedResource = VehicleResource::class;

    protected static ?string $title = 'Vehículos asociados';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('placa')
            ->defaultSort('placa')
            ->modifyQueryUsing(fn ($query) => $query->with('media'))
            ->columns([
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
                TextColumn::make('fecha_ingreso')
                    ->date('Y-m-d')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('estado')
                    ->options(VehicleStatus::class)
                    ->multiple(),
            ])
            ->headerActions([])
            ->recordActions([
                Action::make('open')
                    ->label('Abrir')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn ($record) => VehicleResource::getUrl('edit', ['record' => $record]))
                    ->openUrlInNewTab(false),
            ])
            ->toolbarActions([]);
    }
}
