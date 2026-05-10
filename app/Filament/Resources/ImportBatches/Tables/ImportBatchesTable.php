<?php

namespace App\Filament\Resources\ImportBatches\Tables;

use App\Domain\Imports\Enums\ImportBatchStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ImportBatchesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('user')->withCount('rows'))
            ->defaultSort('created_at', 'desc')
            ->poll('5s')
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                TextColumn::make('original_filename')
                    ->label('Archivo')
                    ->limit(40)
                    ->searchable()
                    ->wrap(),
                TextColumn::make('user.name')
                    ->label('Por')
                    ->toggleable(),
                TextColumn::make('total_rows')
                    ->label('Filas')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('progress')
                    ->label('Progreso')
                    ->state(fn ($record) => $record->progressPercentage().'%')
                    ->badge()
                    ->color(fn ($record) => match (true) {
                        $record->progressPercentage() >= 100 => 'success',
                        $record->progressPercentage() > 0 => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('created_count')
                    ->label('OK')
                    ->numeric()
                    ->badge()
                    ->color('success'),
                TextColumn::make('updated_count')
                    ->label('Upd')
                    ->numeric()
                    ->badge()
                    ->color('info'),
                TextColumn::make('failed_count')
                    ->label('Err')
                    ->numeric()
                    ->badge()
                    ->color('danger'),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('created_at')
                    ->label('Iniciado')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(ImportBatchStatus::class),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
