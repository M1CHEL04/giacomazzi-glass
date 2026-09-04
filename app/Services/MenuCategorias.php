<?php

namespace App\Services;

use App\Models\Categoria;
use Illuminate\Support\Facades\Cache;

/**
 * Categorías que alimentan el menú de Productos del sitio público.
 *
 * El menú tiene dos ramas —a medida y de catálogo— y cada una lista sólo las
 * categorías que hoy tienen al menos un producto activo de ese tipo: una
 * categoría que lleva a un listado vacío es una promesa incumplida.
 *
 * Por eso el caché no depende sólo de las categorías: dar de alta o de baja
 * un producto puede hacer aparecer o desaparecer una rama entera. Todas las
 * mutaciones que pueden cambiarlo llaman a olvidar().
 */
class MenuCategorias
{
    private const TTL = 1800;

    private const CLAVE_ESTANDAR = 'menu_categorias_estandar';
    private const CLAVE_ESPECIAL = 'menu_categorias_especial';

    /** @return array<int, array{id:int, nombre:string}> */
    public static function estandar(): array
    {
        return Cache::remember(
            self::CLAVE_ESTANDAR,
            self::TTL,
            fn () => self::consultar(false)
        );
    }

    /** @return array<int, array{id:int, nombre:string}> */
    public static function especiales(): array
    {
        return Cache::remember(
            self::CLAVE_ESPECIAL,
            self::TTL,
            fn () => self::consultar(true)
        );
    }

    public static function olvidar(): void
    {
        Cache::forget(self::CLAVE_ESTANDAR);
        Cache::forget(self::CLAVE_ESPECIAL);
    }

    /**
     * Array plano y no una Collection: el caché puede estar en archivo o en
     * base de datos, y deserializar modelos de Eloquent desde ahí trae
     * problemas que un array no tiene.
     *
     * @return array<int, array{id:int, nombre:string}>
     */
    private static function consultar(bool $especiales): array
    {
        return Categoria::where('activo', true)
            ->whereHas('productos', fn ($q) => $q
                ->where('es_especial', $especiales)
                ->where('activo', true))
            ->orderBy('nombre')
            ->get(['id', 'nombre'])
            ->map(fn ($c) => ['id' => $c->id, 'nombre' => $c->nombre])
            ->all();
    }
}
