<?php

namespace Tests\Feature\Concerns;

use App\Models\ImagenProducto;
use App\Models\Producto;

/**
 * El contrato entero de la feature en una sola afirmación.
 *
 * Toda prueba que escriba imágenes cierra con esto: si el invariante se rompe,
 * el catálogo empieza a mostrar la portada equivocada o a repetir posiciones, y
 * eso es un bug que desde el formulario no se ve.
 */
trait AfirmaInvarianteGaleria
{
    protected function assertInvarianteGaleria(Producto $producto): void
    {
        $activas = ImagenProducto::where('producto_id', $producto->id)
            ->where('es_tecnica', false)
            ->where('activa', true)
            ->orderBy('orden')
            ->get();

        // 1. Denso: 0..n-1, sin huecos ni repetidos.
        $this->assertSame(
            range(0, max(0, $activas->count() - 1)),
            $activas->isEmpty() ? [0] : $activas->pluck('orden')->all(),
            'El orden de la galería no es denso 0..n-1.'
        );

        if ($activas->isEmpty()) {
            // Sin galería no hay portada, y está bien.
            $this->assertSame(
                0,
                ImagenProducto::where('producto_id', $producto->id)->where('es_principal', true)->count(),
                'Un producto sin galería activa no debería tener portada.'
            );
            return;
        }

        // 2. Exactamente una portada.
        $portadas = $activas->where('es_principal', true);
        $this->assertCount(1, $portadas, 'Debe haber exactamente una portada en la galería activa.');

        // 3. Y es la primera.
        $this->assertSame(0, $portadas->first()->orden, 'La portada tiene que ser la de orden 0.');

        // 4. Ni las técnicas ni las dadas de baja pueden reclamar el flag.
        $this->assertSame(
            0,
            ImagenProducto::where('producto_id', $producto->id)
                ->where('es_principal', true)
                ->where(fn($q) => $q->where('es_tecnica', true)->orWhere('activa', false))
                ->count(),
            'Una técnica o una imagen dada de baja no puede ser portada.'
        );
    }
}
