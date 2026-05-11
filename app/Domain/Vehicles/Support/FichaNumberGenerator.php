<?php

namespace App\Domain\Vehicles\Support;

use App\Models\Vehicle;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Genera el siguiente número de ficha técnica como secuencia anual
 * independiente. Formato: "YYYY-NNNNNN" (ej. "2026-000001").
 *
 * Es transaccional y seguro contra concurrencia (lockForUpdate) para
 * evitar colisiones cuando varios operadores crean vehículos al mismo
 * tiempo.
 */
class FichaNumberGenerator
{
    public function next(?int $year = null): string
    {
        $year ??= (int) CarbonImmutable::now()->year;
        $prefix = (string) $year;
        $padding = (int) config('inspeccion.ficha.padding', 6);

        return DB::transaction(function () use ($prefix, $padding): string {
            $last = Vehicle::query()
                ->where('ficha_numero', 'LIKE', "{$prefix}-%")
                ->lockForUpdate()
                ->orderByDesc('ficha_numero')
                ->value('ficha_numero');

            $nextSeq = 1;
            if ($last !== null && preg_match('/-(\d+)$/', $last, $m)) {
                $nextSeq = ((int) $m[1]) + 1;
            }

            return $prefix.'-'.str_pad((string) $nextSeq, $padding, '0', STR_PAD_LEFT);
        });
    }
}
