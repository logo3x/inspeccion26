<?php

namespace App\Domain\InspectionSheets\Actions;

use App\Domain\InspectionSheets\Enums\BulkSheetExportStatus;
use App\Jobs\GenerateBulkSheetsJob;
use App\Models\BulkSheetExport;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class StartBulkSheetExportAction
{
    /**
     * @param  array{kind: 'all'|'range', from?: ?int, to?: ?int}  $criteria
     */
    public function __invoke(array $criteria, ?string $label = null): BulkSheetExport
    {
        $count = $this->countMatching($criteria);

        $export = BulkSheetExport::create([
            'user_id' => Auth::id(),
            'label' => $label ?? $this->defaultLabel($criteria, $count),
            'criteria' => $criteria,
            'total_count' => $count,
            'status' => BulkSheetExportStatus::Queued->value,
        ]);

        GenerateBulkSheetsJob::dispatch($export->id);

        return $export;
    }

    /**
     * @param  array{kind: 'all'|'range', from?: ?int, to?: ?int}  $criteria
     */
    public function countMatching(array $criteria): int
    {
        return $this->buildQuery($criteria)->count();
    }

    /**
     * @param  array{kind: 'all'|'range', from?: ?int, to?: ?int}  $criteria
     * @return Builder<Vehicle>
     */
    public function buildQuery(array $criteria): Builder
    {
        $query = Vehicle::query()->orderBy('inventario_dtb')->orderBy('id');

        if (($criteria['kind'] ?? null) === 'range') {
            $from = $criteria['from'] ?? null;
            $to = $criteria['to'] ?? null;
            if ($from !== null) {
                $query->where('inventario_dtb', '>=', $from);
            }
            if ($to !== null) {
                $query->where('inventario_dtb', '<=', $to);
            }
        }

        return $query;
    }

    private function defaultLabel(array $criteria, int $count): string
    {
        if (($criteria['kind'] ?? null) === 'range') {
            $from = $criteria['from'] ?? '?';
            $to = $criteria['to'] ?? '?';

            return "Rango {$from}-{$to} ({$count} vehículos)";
        }

        return "Todos los vehículos ({$count})";
    }
}
