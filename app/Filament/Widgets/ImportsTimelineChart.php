<?php

namespace App\Filament\Widgets;

use App\Models\ImportRow;
use Carbon\CarbonImmutable;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ImportsTimelineChart extends ChartWidget
{
    protected ?string $heading = 'Filas importadas por día (últimos 30)';

    protected ?string $pollingInterval = '60s';

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $start = CarbonImmutable::now()->subDays(29)->startOfDay();

        $rows = ImportRow::query()
            ->where('processed_at', '>=', $start)
            ->select(
                DB::raw('DATE(processed_at) as d'),
                DB::raw("SUM(CASE WHEN action='created' THEN 1 ELSE 0 END) as created"),
                DB::raw("SUM(CASE WHEN action='updated' THEN 1 ELSE 0 END) as updated"),
                DB::raw("SUM(CASE WHEN action='failed' THEN 1 ELSE 0 END) as failed"),
            )
            ->groupBy('d')
            ->orderBy('d')
            ->get()
            ->keyBy(fn ($r) => (string) $r->d);

        $labels = [];
        $created = [];
        $updated = [];
        $failed = [];
        $cursor = $start;
        for ($i = 0; $i < 30; $i++) {
            $key = $cursor->toDateString();
            $labels[] = $cursor->format('d/m');
            $rec = $rows->get($key);
            $created[] = (int) ($rec->created ?? 0);
            $updated[] = (int) ($rec->updated ?? 0);
            $failed[] = (int) ($rec->failed ?? 0);
            $cursor = $cursor->addDay();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Creados',
                    'data' => $created,
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.15)',
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Actualizados',
                    'data' => $updated,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.15)',
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Fallidos',
                    'data' => $failed,
                    'borderColor' => 'rgb(239, 68, 68)',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.15)',
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
