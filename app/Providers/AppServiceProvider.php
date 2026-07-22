<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        // Filament Shield 4 ya no registra Gate::before automáticamente.
        // Sin esto, el rol super_admin solo accede a los permisos que tenga
        // asignados explícitamente — bloqueando permisos custom como
        // 'Download:Vehicle'. Esto da bypass total.
        Gate::before(function ($user, $ability) {
            $superAdmin = config('filament-shield.super_admin.name', 'super_admin');

            return (method_exists($user, 'hasRole') && $user->hasRole($superAdmin)) ? true : null;
        });
    }
}
