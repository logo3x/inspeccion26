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

        $vehicleRead = ['View:Vehicle', 'ViewAny:Vehicle'];
        $vehicleWrite = ['Create:Vehicle', 'Update:Vehicle'];

        $ownerRead = ['View:Owner', 'ViewAny:Owner'];
        $ownerWrite = ['Create:Owner', 'Update:Owner'];

        $this->ensureRole('Administrador', $allPermissions);

        $this->ensureRole('Operador', array_merge(
            $vehicleRead,
            $vehicleWrite,
            $ownerRead,
            $ownerWrite,
        ));

        $this->ensureRole('Visualizador', array_merge(
            $vehicleRead,
            $ownerRead,
        ));

        $this->ensureRole('Descarga', array_merge(
            $vehicleRead,
            $ownerRead,
        ));
    }

    /**
     * @param  array<int, string>  $permissions
     */
    protected function ensureRole(string $name, array $permissions): void
    {
        $role = Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        $role->syncPermissions($permissions);
    }
}
