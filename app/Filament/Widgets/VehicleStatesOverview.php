<?php

namespace App\Filament\Widgets;

use App\Domain\Vehicles\Enums\VehicleStatus;
use App\Models\Vehicle;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class VehicleStatesOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $heading = 'Estados de los vehículos';

    protected ?string $description = 'Distribución actual con tiempo promedio en cada estado';

    protected ?string $pollingInterval = '30s';

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $total = Vehicle::query()->count();
        $statuses = VehicleStatus::cases();

        $rows = Vehicle::query()
            ->selectRaw('estado, COUNT(*) as c, AVG(DATEDIFF(NOW(), updated_at)) as avg_days')
            ->groupBy('estado')
            ->get()
            ->keyBy('estado');

        $sevenDayRecent = Vehicle::query()
            ->where('updated_at', '>=', now()->subDays(7))
            ->selectRaw('estado, DATE(updated_at) as d, COUNT(*) as c')
            ->groupBy('estado', 'd')
            ->orderBy('d')
            ->get()
            ->groupBy('estado');

        $stats = [];
        foreach ($statuses as $status) {
            $key = $status->value;
            $row = $rows->get($key);
            $count = (int) ($row->c ?? 0);
            $avgDays = $row && $row->avg_days !== null ? (int) round((float) $row->avg_days) : null;
            $pct = $total > 0 ? round($count / $total * 100, 1) : 0;

            $trend = $sevenDayRecent->get($key, collect());
            $chart = [];
            for ($i = 6; $i >= 0; $i--) {
                $day = now()->subDays($i)->toDateString();
                $entry = $trend->firstWhere('d', $day);
                $chart[] = (int) ($entry->c ?? 0);
            }

            $description = "{$pct}% del total";
            if ($avgDays !== null && $count > 0) {
                $description .= " · ~{$avgDays}d promedio";
            }

            $stats[] = Stat::make(
                $status->getLabel(),
                number_format($count)
            )
                ->description($description)
                ->descriptionIcon($status->getIcon() ?? 'heroicon-m-tag')
                ->color($status->getColor())
                ->chart($chart);
        }

        return $stats;
    }
}
