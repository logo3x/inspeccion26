<?php

namespace App\Filament\Widgets;

use App\Models\Vehicle;
use Filament\Widgets\ChartWidget;

class VehiclesByTypeChart extends ChartWidget
{
    protected ?string $heading = 'Distribución por clase';

    protected ?string $pollingInterval = '60s';

    protected int|string|array $columnSpan = 1;

    protected function getData(): array
    {
        $rows = Vehicle::query()
            ->selectRaw('COALESCE(tipo, "Sin clasificar") as tipo, COUNT(*) as c')
            ->groupBy('tipo')
            ->orderByDesc('c')
            ->limit(8)
            ->pluck('c', 'tipo')
            ->all();

        return [
            'datasets' => [
                [
                    'label' => 'Vehículos',
                    'data' => array_values($rows),
                    'backgroundColor' => [
                        '#f59e0b', '#3b82f6', '#10b981', '#ef4444',
                        '#8b5cf6', '#ec4899', '#14b8a6', '#6b7280',
                    ],
                ],
            ],
            'labels' => array_keys($rows),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
