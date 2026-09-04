<?php

namespace App\Providers;

use App\Services\MenuCategorias;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewComposerServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        View::composer('layouts.app-externo', function ($view) {
            // El menú de Productos tiene una rama por tipo, y cada una lista
            // sólo las categorías con producto activo de ese tipo.
            // Ver MenuCategorias para el caché y su invalidación.
            $view->with([
                'menuEstandar'  => MenuCategorias::estandar(),
                'menuEspeciales' => MenuCategorias::especiales(),
            ]);
        });
    }
}
