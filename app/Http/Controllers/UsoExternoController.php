<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Variante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
        try {
            $todasCategorias = Categoria::where('activo', true)->orderBy('nombre')->get(['id', 'nombre']);

            $query = Producto::with([
                'categoria:id,nombre',
                'imagenes' => fn($q) => $q->where('activa', true)->where('es_principal', true)->select(['id', 'producto_id', 'ruta']),
            ])
                ->select(['id', 'categoria_id', 'nombre', 'descripcion'])
                ->where('activo', true)
                ->whereHas('categoria', fn($q) => $q->where('activo', true));

            $categoriasFiltro = array_values(array_filter((array) $request->input('categorias', [])));
            if (!empty($categoriasFiltro)) {
                $query->whereIn('categoria_id', $categoriasFiltro);
            }

            $buscar = trim($request->input('buscar', ''));
            if ($buscar !== '') {
                $query->where(fn($q) => $q
                    ->where('nombre', 'like', "%{$buscar}%")
                    ->orWhere('descripcion', 'like', "%{$buscar}%")
                );
            }

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
                $gridBaseUrl = route('productos.todos');
                return response()->json([
                    'html' => view('UsoExterno.partials.productos-grid', compact(
                        'productos', 'variantes', 'filtros', 'todasCategorias', 'categoriasFiltro', 'gridBaseUrl'
                    ))->render(),
                ]);
            }

            return view('UsoExterno.Indexs.todos', compact(
                'productos', 'variantes', 'filtros', 'todasCategorias', 'categoriasFiltro', 'buscar'
            ));
        } catch (\Exception $e) {
            Log::error('UsoExternoController::indexTodos - Error al cargar productos: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json(['error' => 'Error al cargar los productos.'], 500);
            }

            abort(500);
        }
    }

    public function indexCategoria(Request $request, int $id)
    {
        try {
            $categoria = Categoria::where('activo', true)->findOrFail($id);

            $query = Producto::with([
                'imagenes' => fn($q) => $q
                    ->where('activa', true)
                    ->where('es_principal', true)
                    ->select(['id', 'producto_id', 'ruta']),
            ])
                ->select(['id', 'categoria_id', 'nombre', 'descripcion'])
                ->where('categoria_id', $id)
                ->where('activo', true);

            $buscar = trim($request->input('buscar', ''));
            if ($buscar !== '') {
                $query->where(fn($q) => $q
                    ->where('nombre', 'like', "%{$buscar}%")
                    ->orWhere('descripcion', 'like', "%{$buscar}%")
                );
            }

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

            $productos = $query->paginate(12)->withQueryString();

            $variantes = Variante::with(['valores' => fn($q) => $q->whereHas(
                    'productos',
                    fn($qp) => $qp->where('categoria_id', $id)->where('activo', true)
                )])
                ->get()
                ->filter(fn($v) => $v->valores->count() > 0)
                ->values();

            if ($request->ajax()) {
                $gridBaseUrl = route('productos.categoria', $id);
                return response()->json([
                    'html' => view('UsoExterno.partials.productos-grid', compact('productos', 'variantes', 'filtros', 'categoria', 'gridBaseUrl'))->render(),
                ]);
            }

            return view('UsoExterno.Indexs.categoria', compact('categoria', 'productos', 'variantes', 'filtros', 'buscar'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('UsoExternoController::indexCategoria - Error al cargar categoría ' . $id . ': ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json(['error' => 'Error al cargar los productos.'], 500);
            }

            abort(500);
        }
    }

    public function showProducto(int $id)
    {
        try {
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
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw $e;
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('UsoExternoController::showProducto - Error al cargar producto ' . $id . ': ' . $e->getMessage());

            abort(500);
        }
    }
}
