<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Variante;
use Illuminate\Http\Request;

class UsoExternoController extends Controller
{
    public function welcome()
    {
        return view('UsoExterno.welcome');
    }

    public function contacto()
    {
        return view('UsoExterno.contacto');
    }

    public function indexTodos(Request $request)
    {
        $todasCategorias = Categoria::where('activo', true)->orderBy('nombre')->get(['id', 'nombre']);

        $query = Producto::with([
            'categoria:id,nombre',
            'imagenes' => fn($q) => $q->where('activa', true)->where('es_principal', true)->select(['id', 'producto_id', 'ruta']),
        ])
            ->select(['id', 'categoria_id', 'nombre', 'descripcion'])
            ->where('activo', true)
            ->whereHas('categoria', fn($q) => $q->where('activo', true));

        // Filtro por categorías
        $categoriasFiltro = array_values(array_filter((array) $request->input('categorias', [])));
        if (!empty($categoriasFiltro)) {
            $query->whereIn('categoria_id', $categoriasFiltro);
        }

        // Filtros por variantes
        $filtros = $request->input('variantes', []);
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

        $productos = $query->latest()->paginate(12)->withQueryString();

        $variantes = Variante::with(['valores' => fn($q) => $q->whereHas(
            'productos',
            fn($qp) => $qp->where('activo', true)
                           ->whereHas('categoria', fn($qc) => $qc->where('activo', true))
        )])
            ->get()
            ->filter(fn($v) => $v->valores->count() > 0)
            ->values();

        if ($request->ajax()) {
            return response()->json([
                'html' => view('UsoExterno.partials.productos-grid-todos', compact(
                    'productos', 'variantes', 'filtros', 'todasCategorias', 'categoriasFiltro'
                ))->render(),
            ]);
        }

        return view('UsoExterno.Indexs.todos', compact(
            'productos', 'variantes', 'filtros', 'todasCategorias', 'categoriasFiltro'
        ));
    }

    public function indexCategoria(Request $request, int $id)
    {
        $categoria = Categoria::with(['variantes.valores'])
            ->where('activo', true)
            ->findOrFail($id);

        $query = Producto::with([
            'imagenes' => fn($q) => $q
                ->where('activa', true)
                ->where('es_principal', true)
                ->select(['id', 'producto_id', 'ruta']),
        ])
            ->select(['id', 'categoria_id', 'nombre', 'descripcion'])
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

        if ($request->ajax()) {
            return response()->json([
                'html' => view('UsoExterno.partials.productos-grid', compact('productos', 'variantes', 'filtros', 'categoria'))->render(),
            ]);
        }

        return view('UsoExterno.Indexs.categoria', compact('categoria', 'productos', 'variantes', 'filtros'));
    }

    public function showProducto(int $id)
    {
        $producto = Producto::with([
            'categoria:id,nombre,activo',
            'imagenes' => fn($q) => $q
                ->where('activa', true)
                ->orderByDesc('es_principal')
                ->select(['id', 'producto_id', 'ruta', 'es_principal']),
            'valoresVariantes' => fn($q) => $q->select(['valores_variante.id', 'valores_variante.variante_id', 'valores_variante.valor']),
            'valoresVariantes.variante:id,nombre',
        ])
            ->select(['id', 'categoria_id', 'nombre', 'descripcion', 'descripcion_tecnica'])
            ->where('activo', true)
            ->findOrFail($id);

        abort_unless($producto->categoria && $producto->categoria->activo, 404);

        // Agrupar únicamente los valores que tiene ESTE producto, por variante
        $selectorVariantes = $producto->valoresVariantes
            ->sortBy(fn($vv) => $vv->variante->nombre)
            ->groupBy('variante_id')
            ->map(fn($valores) => [
                'nombre'   => $valores->first()->variante->nombre,
                'opciones' => $valores->map(fn($v) => [
                    'id'    => $v->id,
                    'valor' => $v->valor,
                ])->values(),
            ])
            ->values();

        // Relacionados: otros productos activos de la misma categoría
        $relacionados = Producto::with([
            'imagenes' => fn($q) => $q
                ->where('activa', true)
                ->where('es_principal', true)
                ->select(['id', 'producto_id', 'ruta']),
        ])
            ->select(['id', 'categoria_id', 'nombre'])
            ->where('categoria_id', $producto->categoria_id)
            ->where('activo', true)
            ->where('id', '!=', $producto->id)
            ->latest()
            ->limit(3)
            ->get();

        return view('UsoExterno.Shows.producto', compact('producto', 'selectorVariantes', 'relacionados'));
    }
}
