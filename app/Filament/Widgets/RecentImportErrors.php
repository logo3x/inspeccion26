<?php

namespace App\Filament\Widgets;

use App\Domain\Imports\Enums\ImportRowAction;
use App\Models\ImportRow;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentImportErrors extends TableWidget
{
    protected static ?int $sort = 15;

    protected static ?string $heading = 'Errores recientes en importaciones';

    protected ?string $pollingInterval = '15s';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => ImportRow::query()
                ->where('action', ImportRowAction::Failed->value)
                ->latest('processed_at')
                ->limit(15))
            ->paginated(false)
            ->columns([
                TextColumn::make('batch_id')->label('Batch #'),
                TextColumn::make('row_number')->label('Fila'),
                TextColumn::make('placa')
                    ->copyable()
                    ->placeholder('—'),
                TextColumn::make('error_message')
                    ->label('Error')
                    ->wrap()
                    ->limit(120)
                    ->color('danger'),
                TextColumn::make('processed_at')
                    ->label('Cuando')
                    ->since(),
            ]);
    }
}
