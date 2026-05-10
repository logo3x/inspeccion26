<?php

namespace App\Filament\Resources\ImportBatches\RelationManagers;

use App\Domain\Imports\Enums\ImportRowAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class RowsRelationManager extends RelationManager
{
    protected static string $relationship = 'rows';

    protected static ?string $title = 'Filas procesadas';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('row_number')
            ->defaultSort('row_number')
            ->poll('5s')
            ->columns([
                TextColumn::make('row_number')
                    ->label('Fila')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('placa')
                    ->searchable()
                    ->copyable()
                    ->weight('bold'),
                TextColumn::make('action')
                    ->label('Resultado')
                    ->badge(),
                TextColumn::make('vehicle.id')
                    ->label('Vehículo ID')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('error_message')
                    ->label('Error')
                    ->wrap()
                    ->limit(120)
                    ->color('danger')
                    ->placeholder('—'),
                TextColumn::make('processed_at')
                    ->label('Procesado')
                    ->dateTime('Y-m-d H:i:s')
                    ->toggleable()
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->label('Resultado')
                    ->options(ImportRowAction::class)
                    ->multiple(),
            ])
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
