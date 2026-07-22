<?php

namespace App\Filament\Widgets;

use App\Domain\Vehicles\Enums\VehicleStatus;
use App\Filament\Resources\Vehicles\VehicleResource;
use App\Models\Vehicle;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;

class RecentStateChanges extends TableWidget
{
    protected static ?int $sort = 13;

    protected static ?string $heading = 'Cambios de estado recientes';

    protected ?string $pollingInterval = '20s';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Activity::query()
                ->where('log_name', 'vehicle')
                ->where('event', 'updated')
                ->whereJsonLength('properties->attributes->estado', '>', 0)
                ->with('causer')
                ->latest('id')
                ->limit(15))
            ->paginated(false)
            ->columns([
                TextColumn::make('created_at')
                    ->label('Cuando')
                    ->dateTime('Y-m-d H:i')
                    ->since(),
                TextColumn::make('placa')
                    ->label('Placa')
                    ->state(fn (Activity $record) => optional(Vehicle::query()->withTrashed()->find($record->subject_id))->placa ?? '#'.$record->subject_id)
                    ->copyable()
                    ->weight('bold'),
                TextColumn::make('from')
                    ->label('Antes')
                    ->state(fn (Activity $record) => self::stateLabel($record->properties['old']['estado'] ?? null))
                    ->badge()
                    ->color(fn (Activity $record) => self::stateColor($record->properties['old']['estado'] ?? null)),
                TextColumn::make('arrow')
                    ->label('')
                    ->state(fn () => '→'),
                TextColumn::make('to')
                    ->label('Después')
                    ->state(fn (Activity $record) => self::stateLabel($record->properties['attributes']['estado'] ?? null))
                    ->badge()
                    ->color(fn (Activity $record) => self::stateColor($record->properties['attributes']['estado'] ?? null)),
                TextColumn::make('causer.name')
                    ->label('Por')
                    ->placeholder('Sistema'),
            ])
            ->recordActions([
                Action::make('open')
                    ->label('Abrir')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Activity $record) => $record->subject_id ? VehicleResource::getUrl('edit', ['record' => $record->subject_id]) : null)
                    ->visible(fn (Activity $record) => $record->subject_id !== null),
            ]);
    }

    private static function stateLabel(?string $value): string
    {
        if ($value === null) {
            return '—';
        }
        $case = VehicleStatus::tryFrom($value);

        return $case?->getLabel() ?? $value;
    }

    private static function stateColor(?string $value): string
    {
        if ($value === null) {
            return 'gray';
        }
        $case = VehicleStatus::tryFrom($value);

        return $case?->getColor() ?? 'gray';
    }
}
