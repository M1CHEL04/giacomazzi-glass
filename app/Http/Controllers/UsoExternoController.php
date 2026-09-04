<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\ProductoEspecial;
use App\Models\Variante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UsoExternoController extends Controller
{
    public function welcome()
    {
        // El ViewComposer inyecta las categorías solo en el layout (navbar);
        // acá las pasamos explícitamente para la sección "Modelos estándar".
        $categorias = Categoria::where('activo', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'imagen_hero']);

        return view('UsoExterno.welcome', compact('categorias'));
    }

    public function nosotros()
    {
        return view('UsoExterno.nosotros');
    }

    /**
     * $esLineaSingular no cambia ni la query ni los filtros disponibles —
     * sólo el hero, el breadcrumb, el renglón explicativo y a qué ruta
     * apuntan los filtros. La entrada a la línea singular (más abajo) es
     * literalmente esta misma búsqueda con el Tipo pre-armado en
     * "especial": mismo índice, mismos filtros, todos, sin excepciones.
     */
    public function indexTodos(Request $request, bool $esLineaSingular = false)
    {
        try {
            // Tipo: 'estandar' | 'especial'. Ninguno o los dos = mezclado.
            $tipos = array_values(array_intersect(
                (array) $request->input('tipos', []),
                ['estandar', 'especial']
            ));

            // El filtro de Categoría sólo debe ofrecer categorías con algún
            // producto activo DEL TIPO que se está mirando: una categoría
            // sin nada de ese tipo sería un callejón sin salida. Con los
            // dos tipos tildados (o ninguno) se listan las que tengan
            // cualquiera de los dos, que es como ya se comportaba esto.
            $todasCategorias = Categoria::where('activo', true)
                ->whereHas('productos', function ($q) use ($tipos) {
                    $q->where('activo', true);
                    if (count($tipos) === 1) {
                        $q->where('es_especial', $tipos[0] === 'especial');
                    }
                })
                ->orderBy('nombre')
                ->get(['id', 'nombre']);

            $query = Producto::with([
                'categoria:id,nombre',
                'imagenes' => fn($q) => $q->where('es_principal', true)->select(['id', 'producto_id', 'ruta', 'ruta_thumb']),
            ])
                ->select(['id', 'categoria_id', 'nombre', 'descripcion', 'es_especial'])
                ->where('activo', true)
                ->whereHas('categoria', fn($q) => $q->where('activo', true));

            if (count($tipos) === 1) {
                $query->where('es_especial', $tipos[0] === 'especial');
            }

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

            // Sólo los estándar tienen variantes: las facetas se calculan sobre ellos.
            $variantes = Variante::with(['valores' => fn($q) => $q->whereHas(
                'productos',
                fn($qp) => $qp->estandar()
                               ->where('activo', true)
                               ->whereHas('categoria', fn($qc) => $qc->where('activo', true))
            )])
                ->get()
                ->filter(fn($v) => $v->valores->count() > 0)
                ->values();

            $gridBaseUrl = $esLineaSingular ? route('productos.especiales') : route('productos.todos');

            if ($request->ajax()) {
                return response()->json([
                    'html' => view('UsoExterno.partials.productos-grid', compact(
                        'productos', 'variantes', 'filtros', 'todasCategorias', 'categoriasFiltro', 'tipos', 'gridBaseUrl', 'esLineaSingular'
                    ))->render(),
                    // El Tipo cambia qué categorías tienen sentido ofrecer, y el
                    // fetch por AJAX sólo reemplaza el grid: mandamos aparte el
                    // HTML de las opciones de Categoría para que el sidebar
                    // también quede al día sin recargar la página.
                    'categoriasHtml' => view('UsoExterno.partials.filtro-categorias-opciones', compact(
                        'todasCategorias', 'categoriasFiltro'
                    ))->render(),
                ]);
            }

            return view('UsoExterno.Indexs.todos', compact(
                'productos', 'variantes', 'filtros', 'todasCategorias', 'categoriasFiltro',
                'tipos', 'buscar', 'gridBaseUrl', 'esLineaSingular'
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

            // El grid filtrable es el catálogo estándar; los especiales van
            // aparte, en su propia franja debajo (ver $especiales).
            $query = Producto::estandar()
                ->with([
                    'imagenes' => fn($q) => $q
                        ->where('es_principal', true)
                        ->select(['id', 'producto_id', 'ruta', 'ruta_thumb']),
                ])
                // es_especial va en el select aunque acá siempre sea false:
                // la card del grid lo lee para decidir el badge y el link.
                ->select(['id', 'categoria_id', 'nombre', 'descripcion', 'es_especial'])
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
                    fn($qp) => $qp->estandar()->where('categoria_id', $id)->where('activo', true)
                )])
                ->get()
                ->filter(fn($v) => $v->valores->count() > 0)
                ->values();

            if ($request->ajax()) {
                // La franja de especiales vive fuera de #productos-container,
                // así que el filtrado por AJAX no la toca ni la recalcula.
                $gridBaseUrl = route('productos.categoria', $id);
                return response()->json([
                    'html' => view('UsoExterno.partials.productos-grid', compact('productos', 'variantes', 'filtros', 'categoria', 'gridBaseUrl'))->render(),
                ]);
            }

            // Son pocos por categoría: se traen todos, sin paginar ni filtrar.
            $especiales = ProductoEspecial::with([
                'imagenes' => fn($q) => $q
                    ->where('es_principal', true)
                    ->select(['id', 'producto_id', 'ruta', 'ruta_thumb']),
            ])
                ->select(['id', 'categoria_id', 'nombre', 'descripcion'])
                ->where('categoria_id', $id)
                ->where('activo', true)
                ->latest()
                ->get();

            return view('UsoExterno.Indexs.categoria', compact('categoria', 'productos', 'variantes', 'filtros', 'buscar', 'especiales'));
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
            // estandar(): un producto a medida tiene su propia ruta y su propia
            // ficha; acá daría 404 antes que mostrarse sin su CTA de consulta.
            $producto = Producto::estandar()->with([
                'categoria:id,nombre,activo',
                'unidad',
                'imagenes' => fn($q) => $q
                    ->orderByDesc('es_principal')
                    ->select(['id', 'producto_id', 'ruta', 'ruta_thumb', 'es_principal']),
                'imagenesTecnicas' => fn($q) => $q
                    ->select(['id', 'producto_id', 'ruta', 'ruta_thumb']),
                'valoresVariantes' => fn($q) => $q->select(['valores_variante.id', 'valores_variante.variante_id', 'valores_variante.valor']),
                'valoresVariantes.variante:id,nombre',
            ])
                ->select(['id', 'categoria_id', 'unidad_id', 'nombre', 'descripcion', 'descripcion_tecnica'])
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

            $relacionados = Producto::estandar()
                ->with([
                    'imagenes' => fn($q) => $q
                        ->where('es_principal', true)
                        ->select(['id', 'producto_id', 'ruta', 'ruta_thumb']),
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

    /**
     * Catálogo de productos a medida (línea singular).
     *
     * No es un catálogo aparte: es indexTodos() con el filtro Tipo
     * pre-armado en "especial" cuando la URL no trae uno propio. Mismo
     * índice, mismos filtros —Tipo y Categoría completos, sin recortar—,
     * la única diferencia es el valor inicial del Tipo y, en la vista, el
     * renglón que explica qué es la línea singular.
     */
    public function indexEspeciales(Request $request)
    {
        if (!$request->has('tipos')) {
            $request->merge(['tipos' => ['especial']]);
        }

        return $this->indexTodos($request, true);
    }

    /**
     * El mismo listado acotado a una categoría. Es la contraparte a medida
     * de productos.categoria, y el destino de la rama "Línea singular" del
     * menú de Productos. Acá sí hace falta una query propia —la de
     * indexCategoria() está atada a Producto::estandar()—, pero renderiza
     * la misma vista categoria.blade.php.
     */
    public function indexEspecialesCategoria(Request $request, int $id)
    {
        try {
            $categoria = Categoria::where('activo', true)->findOrFail($id);

            $query = ProductoEspecial::with([
                'categoria:id,nombre',
                'imagenes' => fn($q) => $q->where('es_principal', true)->select(['id', 'producto_id', 'ruta', 'ruta_thumb']),
            ])
                ->select(['id', 'categoria_id', 'nombre', 'descripcion', 'es_especial'])
                ->where('categoria_id', $id)
                ->where('activo', true);

            $buscar = trim($request->input('buscar', ''));
            if ($buscar !== '') {
                $query->where(fn($q) => $q
                    ->where('nombre', 'like', "%{$buscar}%")
                    ->orWhere('descripcion', 'like', "%{$buscar}%")
                );
            }

            $productos = $query->latest()->paginate(12)->withQueryString();

            // La línea singular no tiene variantes propias: filtros-categoria
            // y productos-grid las esperan igual, así que viajan vacías.
            $variantes = collect();
            $filtros   = [];
            $esLineaSingular = true;
            $gridBaseUrl = route('productos.especial.categoria', $id);

            if ($request->ajax()) {
                return response()->json([
                    'html' => view('UsoExterno.partials.productos-grid', compact(
                        'productos', 'variantes', 'filtros', 'gridBaseUrl', 'esLineaSingular'
                    ))->render(),
                ]);
            }

            return view('UsoExterno.Indexs.categoria', compact(
                'categoria', 'productos', 'variantes', 'filtros', 'buscar', 'gridBaseUrl', 'esLineaSingular'
            ));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('UsoExternoController::indexEspecialesCategoria - Error al cargar categoría ' . $id . ': ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json(['error' => 'Error al cargar los productos.'], 500);
            }

            abort(500);
        }
    }

    public function showEspecial(int $id)
    {
        try {
            $producto = ProductoEspecial::with([
                'categoria:id,nombre,activo',
                'imagenes' => fn($q) => $q
                    ->orderByDesc('es_principal')
                    ->select(['id', 'producto_id', 'ruta', 'ruta_thumb', 'es_principal']),
                'imagenesTecnicas' => fn($q) => $q
                    ->select(['id', 'producto_id', 'ruta', 'ruta_thumb']),
            ])
                ->select(['id', 'categoria_id', 'nombre', 'descripcion', 'descripcion_tecnica'])
                ->where('activo', true)
                ->findOrFail($id);

            abort_unless($producto->categoria && $producto->categoria->activo, 404);

            // Primero otros a medida de la categoría; si no hay, se completa
            // con estándar para no dejar el pie de la ficha vacío.
            $relacionados = ProductoEspecial::with([
                'imagenes' => fn($q) => $q
                    ->where('es_principal', true)
                    ->select(['id', 'producto_id', 'ruta', 'ruta_thumb']),
            ])
                ->select(['id', 'categoria_id', 'nombre'])
                ->where('categoria_id', $producto->categoria_id)
                ->where('activo', true)
                ->where('id', '!=', $producto->id)
                ->latest()
                ->limit(3)
                ->get();

            $relacionadosSonEspeciales = $relacionados->isNotEmpty();

            if (! $relacionadosSonEspeciales) {
                $relacionados = Producto::estandar()
                    ->with([
                        'imagenes' => fn($q) => $q
                            ->where('es_principal', true)
                            ->select(['id', 'producto_id', 'ruta', 'ruta_thumb']),
                    ])
                    ->select(['id', 'categoria_id', 'nombre'])
                    ->where('categoria_id', $producto->categoria_id)
                    ->where('activo', true)
                    ->latest()
                    ->limit(3)
                    ->get();
            }

            return view('UsoExterno.Shows.producto-especial', compact(
                'producto', 'relacionados', 'relacionadosSonEspeciales'
            ));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw $e;
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('UsoExternoController::showEspecial - Error al cargar especial ' . $id . ': ' . $e->getMessage());

            abort(500);
        }
    }
}
