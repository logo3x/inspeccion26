<?php

namespace App\Filament\Widgets;

use App\Domain\Vehicles\Enums\VehicleStatus;
use App\Models\Vehicle;
use Carbon\CarbonImmutable;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class VehicleStatusChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Estados y transiciones (últimos 30 días)';

    protected ?string $description = 'Marcadores diarios = transiciones registradas en activity log · Etiqueta = vehículos actualmente en cada estado';

    protected ?string $pollingInterval = '60s';

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $statuses = VehicleStatus::cases();
        $start = CarbonImmutable::now()->subDays(29)->startOfDay();

        $currentTotals = Vehicle::query()
            ->select('estado', DB::raw('COUNT(*) as c'))
            ->groupBy('estado')
            ->pluck('c', 'estado')
            ->all();

        $transitions = Activity::query()
            ->where('log_name', 'vehicle')
            ->where('event', 'updated')
            ->where('created_at', '>=', $start)
            ->get(['created_at', 'properties']);

        $transitionsByDay = [];
        foreach ($transitions as $activity) {
            $attrs = $activity->properties['attributes'] ?? null;
            $newEstado = is_array($attrs) ? ($attrs['estado'] ?? null) : null;
            if ($newEstado === null) {
                continue;
            }
            $day = CarbonImmutable::parse($activity->created_at)->toDateString();
            $transitionsByDay[$day][$newEstado] = ($transitionsByDay[$day][$newEstado] ?? 0) + 1;
        }

        $labels = [];
        $cursor = $start;
        for ($i = 0; $i < 30; $i++) {
            $labels[] = $cursor->format('d/m');
            $cursor = $cursor->addDay();
        }

        $colors = [
            VehicleStatus::Draft->value => 'rgb(107, 114, 128)',
            VehicleStatus::PendingReview->value => 'rgb(245, 158, 11)',
            VehicleStatus::Approved->value => 'rgb(34, 197, 94)',
            VehicleStatus::Archived->value => 'rgb(239, 68, 68)',
        ];

        $datasets = [];
        foreach ($statuses as $status) {
            $key = $status->value;
            $color = $colors[$key] ?? 'rgb(99, 102, 241)';
            $total = (int) ($currentTotals[$key] ?? 0);

            $values = [];
            $cursor = $start;
            for ($i = 0; $i < 30; $i++) {
                $day = $cursor->toDateString();
                $values[] = (int) ($transitionsByDay[$day][$key] ?? 0);
                $cursor = $cursor->addDay();
            }

            $datasets[] = [
                'label' => $status->getLabel().' (actual: '.number_format($total).')',
                'data' => $values,
                'borderColor' => $color,
                'backgroundColor' => str_replace('rgb(', 'rgba(', str_replace(')', ', 0.15)', $color)),
                'tension' => 0.25,
                'fill' => false,
                'pointRadius' => 4,
                'pointHoverRadius' => 6,
            ];
        }

        return [
            'datasets' => $datasets,
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
