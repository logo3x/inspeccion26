<?php

namespace App\Filament\Resources\Activities\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Spatie\Activitylog\Models\Activity;

class ActivitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn ($q) => $q->with('causer'))
            ->columns([
                TextColumn::make('id')->label('#'),
                TextColumn::make('created_at')
                    ->label('Cuando')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),
                TextColumn::make('log_name')
                    ->label('Log')
                    ->badge(),
                TextColumn::make('event')
                    ->badge()
                    ->color(fn (?string $s) => match ($s) {
                        'created' => 'success',
                        'updated' => 'info',
                        'deleted' => 'danger',
                        'restored' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('description')
                    ->wrap()
                    ->limit(80),
                TextColumn::make('subject_type')
                    ->label('Modelo')
                    ->formatStateUsing(fn (?string $s) => $s ? class_basename($s) : '—')
                    ->toggleable(),
                TextColumn::make('subject_id')
                    ->label('ID')
                    ->toggleable(),
                TextColumn::make('causer.name')
                    ->label('Por')
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('log_name')
                    ->options(fn () => Activity::query()
                        ->select('log_name')
                        ->distinct()
                        ->pluck('log_name', 'log_name')
                        ->all()),
                SelectFilter::make('event')
                    ->options([
                        'created' => 'Creado',
                        'updated' => 'Actualizado',
                        'deleted' => 'Eliminado',
                        'restored' => 'Restaurado',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }
}
