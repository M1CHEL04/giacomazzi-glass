<?php

namespace App\Providers;

use App\Models\Categoria;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewComposerServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        View::composer('layouts.app-externo', function ($view) {
            // Se almacena como array plano para evitar problemas de deserialización
            // de Eloquent Collections al leer del caché de archivo/base de datos.
            $categorias = Cache::remember('categorias_menu_externo', 1800, function () {
                return Categoria::where('activo', true)
                    ->orderBy('nombre')
                    ->get(['id', 'nombre'])
                    ->map(fn($c) => ['id' => $c->id, 'nombre' => $c->nombre])
                    ->all();
            });
            $view->with('categoriasMenu', $categorias);
        });
    }
}
