<?php

namespace App\Providers;

use App\Models\Nota;
use App\Observers\NotaObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
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
        // Registrar o Observer para monitorar alterações em notas
        Nota::observe(NotaObserver::class); 
        Gate::before(function (User $user, string $ability) {
            return $user->role?->hasPermission($ability) ? true : null;
        });
    }
}