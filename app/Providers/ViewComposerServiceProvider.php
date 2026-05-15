<?php

namespace App\Providers;

use App\Models\Categoria;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewComposerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Compartir categorías activas con el layout externo
        View::composer('layouts.app-externo', function ($view) {
            $categorias = Categoria::where('activo', true)
                ->withCount('productos')
                ->orderBy('nombre')
                ->get();
            $view->with('categoriasMenu', $categorias);
        });
    }
}
