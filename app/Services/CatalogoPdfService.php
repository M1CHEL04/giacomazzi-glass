<?php

namespace App\Services;

use App\Models\ImagenProducto;
use App\Models\Producto;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdfDocument;
use Illuminate\Support\Collection;


class CatalogoPdfService
{
    private const MESES = [
        1 => 'enero',
        2 => 'febrero',
        3 => 'marzo',
        4 => 'abril',
        5 => 'mayo',
        6 => 'junio',
        7 => 'julio',
        8 => 'agosto',
        9 => 'septiembre',
        10 => 'octubre',
        11 => 'noviembre',
        12 => 'diciembre',
    ];

    public function generar(): DomPdfDocument
    {
        set_time_limit(120);

        $pdf = Pdf::loadView('UsoInterno.Productos.pdf.catalogo', [
            'capitulos' => $this->capitulos(),
            'logoPath'  => $this->logoPath(),
            'fecha'     => $this->fechaLegible(),
            'contacto'  => $this->contacto(),
        ]);

        $pdf->setOption('isPhpEnabled', true);

        return $pdf->setPaper('a4', 'portrait');
    }

    /** Mismo contenido que generar(), pero en una plantilla sin logos ni nombre de marca. */
    public function generarSinMarca(): DomPdfDocument
    {
        set_time_limit(120);

        $pdf = Pdf::loadView('UsoInterno.Productos.pdf.catalogo-sin-marca', [
            'capitulos' => $this->capitulos(),
            'fecha'     => $this->fechaLegible(),
        ]);

        $pdf->setOption('isPhpEnabled', true);

        return $pdf->setPaper('a4', 'portrait');
    }

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


    private function contacto(): array
    {
        $whatsapp = preg_replace('/\D/', '', config('app.whatsapp_number', ''));

        return [
            'telefono'  => $whatsapp ? substr($whatsapp, 0, 4) . ' ' . substr($whatsapp, 4) : null,
            'email'     => 'presupuestos@aberturasgiacomazzi.com.ar',
            'direccion' => 'San Juan 1978, Quilmes Oeste',
        ];
    }

    private function fechaLegible(): string
    {
        $hoy = now();

        return $hoy->day . ' de ' . self::MESES[$hoy->month] . ' de ' . $hoy->year;
    }
}
