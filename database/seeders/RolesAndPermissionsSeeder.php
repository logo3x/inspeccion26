<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $allPermissions = Permission::query()->pluck('name')->all();

        // Helpers para construir conjuntos por verbo
        $viewOf = fn (string $resource) => ["View:{$resource}", "ViewAny:{$resource}"];
        $writeOf = fn (string $resource) => ["Create:{$resource}", "Update:{$resource}"];

        $vehicleRead = $viewOf('Vehicle');
        $vehicleWrite = $writeOf('Vehicle');
        $ownerRead = $viewOf('Owner');
        $ownerWrite = $writeOf('Owner');
        $importRead = $viewOf('ImportBatch');
        $importWrite = ['Create:ImportBatch'];
        $bulkExportRead = $viewOf('BulkSheetExport');
        $bulkExportWrite = ['Create:BulkSheetExport'];
        $activityRead = $viewOf('Activity');

        // 1. Administrador — todo
        $this->ensureRole('Administrador', $allPermissions);

        // 2. Operador — CRUD operativo de vehículos, propietarios, imports y exports
        $this->ensureRole('Operador', array_merge(
            $vehicleRead, $vehicleWrite,
            $ownerRead, $ownerWrite,
            $importRead, $importWrite,
            $bulkExportRead, $bulkExportWrite,
        ));

        // 3. Visualizador — solo lectura, NUNCA cambia datos
        $this->ensureRole('Visualizador', array_merge(
            $vehicleRead,
            $ownerRead,
            $importRead,
            $bulkExportRead,
            $activityRead,
        ));

        // 4. Descarga — lectura + generar exportaciones masivas (Word/ZIP)
        $this->ensureRole('Descarga', array_merge(
            $vehicleRead,
            $ownerRead,
            $bulkExportRead, $bulkExportWrite,
        ));
    }

    /**
     * @param  array<int, string>  $permissions
     */
    protected function ensureRole(string $name, array $permissions): void
    {
        $role = Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        // Intersectar para evitar errores si algún permiso aún no se generó
        $existing = Permission::query()->whereIn('name', $permissions)->pluck('name')->all();
        $role->syncPermissions($existing);
    }
}
