<?php

namespace App\Filament\Widgets;

use App\Models\ImportBatch;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentImportsTable extends TableWidget
{
    protected static ?string $heading = 'Últimas importaciones';

    protected ?string $pollingInterval = '15s';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => ImportBatch::query()
                ->with('user')
                ->latest()
                ->limit(10))
            ->paginated(false)
            ->columns([
                TextColumn::make('id')->label('#'),
                TextColumn::make('original_filename')
                    ->label('Archivo')
                    ->wrap()
                    ->limit(40),
                TextColumn::make('user.name')->label('Por')->placeholder('—'),
                TextColumn::make('total_rows')->label('Filas')->numeric(),
                TextColumn::make('created_count')->label('OK')->badge()->color('success'),
                TextColumn::make('updated_count')->label('Upd')->badge()->color('info'),
                TextColumn::make('failed_count')->label('Err')->badge()->color('danger'),
                TextColumn::make('status')->badge(),
                TextColumn::make('created_at')
                    ->label('Iniciado')
                    ->since(),
            ]);
    }
}
