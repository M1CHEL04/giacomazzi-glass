<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
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
        // El proyecto usa Bootstrap (no Tailwind). Sin esto, links() renderiza la
        // vista Tailwind por defecto y los chevrons SVG salen a tamaño natural.
        Paginator::useBootstrapFive();
    }
}
