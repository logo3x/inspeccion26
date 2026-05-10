<?php

namespace App\Filament\Widgets;

use App\Models\Vehicle;
use Carbon\CarbonImmutable;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class RegistrationsByMonthChart extends ChartWidget
{
    protected ?string $heading = 'Vehículos registrados por mes (últimos 12)';

    protected ?string $pollingInterval = '60s';

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $start = CarbonImmutable::now()->subMonths(11)->startOfMonth();

        $rows = Vehicle::query()
            ->where('created_at', '>=', $start)
            ->select(DB::raw("DATE_FORMAT(created_at, '%Y-%m') as ym"), DB::raw('COUNT(*) as c'))
            ->groupBy('ym')
            ->orderBy('ym')
            ->pluck('c', 'ym')
            ->all();

        $labels = [];
        $values = [];
        $cursor = $start;
        for ($i = 0; $i < 12; $i++) {
            $key = $cursor->format('Y-m');
            $labels[] = $cursor->locale('es')->translatedFormat('M Y');
            $values[] = (int) ($rows[$key] ?? 0);
            $cursor = $cursor->addMonth();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Registros',
                    'data' => $values,
                    'borderColor' => 'rgb(245, 158, 11)',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.2)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
