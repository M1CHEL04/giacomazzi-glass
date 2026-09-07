<?php

namespace App\Services;

use App\Models\ImagenProducto;
use App\Models\Producto;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdfDocument;
use Illuminate\Support\Collection;

/**
 * Arma y renderiza el catálogo PDF de productos estándar activos, agrupados
 * por categoría, con el mismo criterio de visibilidad que la web pública
 * (producto activo + categoría activa).
 */
class CatalogoPdfService
{
    private const MESES = [
        1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
        5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
        9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
    ];

    public function generar(): DomPdfDocument
    {
        // DomPDF descarga cada foto de portada por HTTP (viven en el file
        // server, no en public_path()); con un catálogo grande el límite
        // default de PHP se puede quedar corto.
        set_time_limit(120);

        $pdf = Pdf::loadView('UsoInterno.Productos.pdf.catalogo', [
            'capitulos' => $this->capitulos(),
            'logoPath'  => $this->logoPath(),
            'fecha'     => $this->fechaLegible(),
        ]);

        return $pdf->setPaper('a4', 'portrait');
    }

    /**
     * Un capítulo por categoría (activa, con al menos un producto activo),
     * ordenados alfabéticamente, con sus productos ya listos para la vista:
     * portada resuelta y variantes agrupadas como en la ficha pública.
     */
    private function capitulos(): Collection
    {
        return Producto::estandar()
            ->where('activo', true)
            ->whereHas('categoria', fn($q) => $q->where('activo', true))
            ->with(['categoria', 'imagenes', 'valoresVariantes.variante'])
            ->get()
            ->groupBy('categoria_id')
            ->map(fn(Collection $productos) => [
                'categoria' => $productos->first()->categoria,
                'productos' => $productos->sortBy('nombre')->values()->map(fn(Producto $producto) => [
                    'producto'  => $producto,
                    'portada'   => $this->portada($producto),
                    'variantes' => $this->agruparVariantes($producto),
                ]),
            ])
            ->sortBy(fn(array $capitulo) => $capitulo['categoria']->nombre)
            ->values();
    }

    /** Foto de portada del producto, o su primera imagen activa si ninguna está marcada como principal. */
    private function portada(Producto $producto): ?ImagenProducto
    {
        return $producto->imagenes->firstWhere('es_principal', true) ?? $producto->imagenes->first();
    }

    /**
     * Mismo agrupamiento de valoresVariantes que UsoExternoController::showProducto
     * (por variante_id, nombre + lista de valores), pero sin los ids de
     * opción: acá sólo se necesita el texto para mostrarlo estático.
     */
    private function agruparVariantes(Producto $producto): Collection
    {
        return $producto->valoresVariantes
            ->sortBy(fn($vv) => $vv->variante->nombre)
            ->groupBy('variante_id')
            ->map(fn($valores) => [
                'nombre'   => $valores->first()->variante->nombre,
                'opciones' => $valores->pluck('valor')->values(),
            ])
            ->values();
    }

    /** Ruta local del logo para el header del catálogo, o null si el usuario todavía no la subió. */
    private function logoPath(): ?string
    {
        $ruta = public_path('images/logo-catalogo.png');

        return file_exists($ruta) ? $ruta : null;
    }

    private function fechaLegible(): string
    {
        $hoy = now();

        return $hoy->day . ' de ' . self::MESES[$hoy->month] . ' de ' . $hoy->year;
    }
}
