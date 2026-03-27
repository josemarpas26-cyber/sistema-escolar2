<?php

namespace App\Providers;

use App\Models\Nota;
use App\Observers\NotaObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
use App\Policies\NotaPolicy;
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
            
        Gate::policy(\App\Models\Nota::class, \App\Policies\NotaPolicy::class);
 
        Gate::before(function (User $user, string $ability) {
            if ($user->isAdmin()) {
                return true;
            }

            return null;
        });
    }
}
