<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Http\Request;

class UsoExternoController extends Controller
{
    public function indexCategoria(Request $request, int $id)
    {
        $categoria = Categoria::with(['variantes.valores'])
            ->where('activo', true)
            ->findOrFail($id);

        $query = Producto::with([
            'imagenes' => fn($q) => $q->where('activa', true)->where('es_principal', true),
        ])
            ->where('categoria_id', $id)
            ->where('activo', true);

        // Aplicar filtros por valores de variante
        $filtros = $request->get('variantes', []);
        foreach ($filtros as $varianteId => $valores) {
            $valores = array_filter((array) $valores);
            if (!empty($valores)) {
                $query->whereHas('valoresVariantes', function ($q) use ($varianteId, $valores) {
                    $q->where('variante_id', $varianteId)
                        ->whereIn('id', $valores);
                });
            }
        }

        $productos = $query->paginate(12)->withQueryString();

        // Sólo cargar las variantes que tienen valores usados en productos de esta categoría
        $variantes = $categoria->variantes()
            ->with(['valores' => fn($q) => $q->whereHas(
                'productos',
                fn($qp) => $qp->where('categoria_id', $id)->where('activo', true)
            )])
            ->get()
            ->filter(fn($v) => $v->valores->count() > 0)
            ->values();

        return view('UsoExterno.Indexs.categoria', compact('categoria', 'productos', 'variantes', 'filtros'));
    }
}
