<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

#[Signature('inspeccion:reset-vehicle-data {--force : Saltar confirmación interactiva}')]
#[Description('Vacía las tablas de vehículos, propietarios, media, imports, exports y activity log. NO toca users ni roles.')]
class ResetVehicleData extends Command
{
    /**
     * Tablas que se truncan. NO incluye users, sessions, permissions, roles,
     * password_reset_tokens, cache, jobs, ni migrations.
     *
     * @var array<int, string>
     */
    private array $tablesToTruncate = [
        'bulk_sheet_exports',
        'activity_log',
        'import_rows',
        'import_batches',
        'media',
        'vehicles',
        'owners',
    ];

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('Esto eliminará TODOS los datos de vehículos, propietarios, fotos, imports, exports y activity log. ¿Continuar?')) {
            $this->warn('Cancelado.');

            return self::FAILURE;
        }

        $driver = DB::connection()->getDriverName();
        $missing = [];
        $truncated = [];

        DB::transaction(function () use (&$missing, &$truncated, $driver) {
            if ($driver === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=0');
            }

            foreach ($this->tablesToTruncate as $table) {
                if (! Schema::hasTable($table)) {
                    $missing[] = $table;

                    continue;
                }

                DB::table($table)->truncate();
                $truncated[] = $table;
                $this->line(' • truncated: '.$table);
            }

            if ($driver === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }
        });

        $this->newLine();
        $this->info(sprintf('Listo. %d tablas vaciadas.', count($truncated)));

        if (! empty($missing)) {
            $this->warn('Tablas no existentes (ignoradas): '.implode(', ', $missing));
        }

        $this->newLine();
        $this->line('users, permissions, roles, sessions: intactos.');
        $this->line('Siguiente paso: re-importar el Excel de vehículos desde la UI de Filament.');

        return self::SUCCESS;
    }
}
