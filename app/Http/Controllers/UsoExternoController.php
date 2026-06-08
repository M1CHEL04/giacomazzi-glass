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
                $query->whereIn('productos.id', function ($sub) use ($varianteId, $valores) {
                    $sub->select('pvv.producto_id')
                        ->from('productos_valores_variantes as pvv')
                        ->join('valores_variante as vv', 'vv.id', '=', 'pvv.valor_variante_id')
                        ->where('vv.variante_id', $varianteId)
                        ->whereIn('vv.id', $valores);
                });
            }
        }

        $productos = $query->paginate(2)->withQueryString();

        // Sólo cargar las variantes que tienen valores usados en productos de esta categoría
        $variantes = $categoria->variantes()
            ->with(['valores' => fn($q) => $q->whereHas(
                'productos',
                fn($qp) => $qp->where('categoria_id', $id)->where('activo', true)
            )])
            ->get()
            ->filter(fn($v) => $v->valores->count() > 0)
            ->values();

        if ($request->ajax()) {
            return response()->json([
                'html' => view('UsoExterno.partials.productos-grid', compact('productos', 'variantes', 'filtros', 'categoria'))->render(),
            ]);
        }

        return view('UsoExterno.Indexs.categoria', compact('categoria', 'productos', 'variantes', 'filtros'));
    }
}
