<?php

namespace App\Filament\Widgets;

use App\Domain\Imports\Enums\ImportBatchStatus;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ImportsStatsOverview extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $totalBatches = ImportBatch::query()->count();
        $processing = ImportBatch::query()
            ->whereIn('status', [ImportBatchStatus::Queued->value, ImportBatchStatus::Processing->value])
            ->count();
        $totalRows = ImportRow::query()->count();
        $failedRows = ImportRow::query()->where('action', 'failed')->count();
        $successRate = $totalRows > 0
            ? round(($totalRows - $failedRows) / $totalRows * 100, 1)
            : 0;

        return [
            Stat::make('Importaciones totales', number_format($totalBatches))
                ->description('Lotes históricos')
                ->descriptionIcon('heroicon-m-arrow-up-tray')
                ->color('primary'),

            Stat::make('En proceso', number_format($processing))
                ->description($processing > 0 ? 'Procesando ahora' : 'Sin actividad')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color($processing > 0 ? 'info' : 'gray'),

            Stat::make('Filas procesadas', number_format($totalRows))
                ->description("Errores: {$failedRows}")
                ->descriptionIcon('heroicon-m-document-text')
                ->color('gray'),

            Stat::make('Tasa de éxito', $successRate.'%')
                ->description($totalRows > 0 ? "{$failedRows} fallidas de {$totalRows}" : 'Sin datos')
                ->descriptionIcon($successRate >= 95 ? 'heroicon-m-check-badge' : 'heroicon-m-exclamation-triangle')
                ->color(match (true) {
                    $successRate >= 95 => 'success',
                    $successRate >= 80 => 'warning',
                    default => 'danger',
                }),
        ];
    }
}
