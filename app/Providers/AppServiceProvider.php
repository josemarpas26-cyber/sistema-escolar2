<?php

namespace App\Providers;

use App\Models\Nota;
use App\Observers\NotaObserver;
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
        // Registrar o Observer para monitorar alterações em notas
        Nota::observe(NotaObserver::class);
    }
}