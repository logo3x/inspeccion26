<?php

namespace App\Filament\Widgets;

use App\Models\Vehicle;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class VehiclesStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 10;

    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $total = Vehicle::query()->count();
        $today = Vehicle::query()->whereDate('created_at', today())->count();
        $week = Vehicle::query()->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count();
        $complete = Vehicle::query()->where('completion_percentage', 100)->count();
        $immobilized = Vehicle::query()->whereNotNull('fecha_ingreso')->count();

        return [
            Stat::make('Total vehículos', number_format($total))
                ->description('Registros activos')
                ->descriptionIcon('heroicon-m-truck')
                ->color('primary'),

            Stat::make('Inmovilizados', number_format($immobilized))
                ->description('Con fecha de ingreso')
                ->descriptionIcon('heroicon-m-no-symbol')
                ->color('warning'),

            Stat::make('Creados hoy', number_format($today))
                ->description("Esta semana: {$week}")
                ->descriptionIcon('heroicon-m-plus-circle')
                ->color('success'),

            Stat::make('Fichas completas', number_format($complete))
                ->description($total > 0 ? round($complete / $total * 100, 1).'% del total' : 'Sin datos')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color($total > 0 && $complete / $total > 0.5 ? 'success' : 'gray'),
        ];
    }
}
