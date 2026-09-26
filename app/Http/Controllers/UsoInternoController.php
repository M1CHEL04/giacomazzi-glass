<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Cotizacion;
use App\Models\Producto;
use App\Models\ProductoEspecial;
use App\Models\UnidadMedida;
use App\Services\CatalogoPdfService;
use App\Services\GestorImagenesProducto;
use App\Services\ImportadorProductos;
use App\Services\MenuCategorias;
use App\Services\SincronizadorImagenesProducto;
use App\Services\SkuService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UsoInternoController extends Controller
{
    public function __construct(
        private SkuService $skuService,
        private SincronizadorImagenesProducto $sincronizadorImagenes,
        private CatalogoPdfService $catalogoPdfService,
    ) {}

    public function estadisticas()
    {
        $totalProductos     = Producto::estandar()->count();
        $productosActivos   = Producto::estandar()->where('activo', true)->count();
        $productosInactivos = $totalProductos - $productosActivos;

        $totalEspeciales   = ProductoEspecial::count();
        $especialesActivos = ProductoEspecial::where('activo', true)->count();

        // Sin imagen: cuenta los dos tipos, porque en los dos es un problema.
        $listaSinImagen = Producto::doesntHave('imagenes')
            ->with('categoria:id,nombre')
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'codigo', 'es_especial', 'categoria_id']);
        $productosSinImagen = $listaSinImagen->count();

        // Para el modal: línea → categoría → productos.
        $porCategoria = fn($lista) => $lista
            ->groupBy(fn($p) => $p->categoria->nombre ?? 'Sin categoría')
            ->sortKeys();
        $sinImagenPorLinea = [
            'Línea estándar' => $porCategoria($listaSinImagen->where('es_especial', false)),
            'Línea adapta'   => $porCategoria($listaSinImagen->where('es_especial', true)),
        ];

        $productosPorCategoria = Categoria::withCount('productos')
            ->orderByDesc('productos_count')
            ->get();

        // Consultas = clics en "Solicitar cotización por WhatsApp"
        $totalConsultas = Cotizacion::count();
        $consultasMes   = Cotizacion::where('created_at', '>=', now()->startOfMonth())->count();

        $conteoPorMes = Cotizacion::get(['created_at'])
            ->groupBy(fn($c) => $c->created_at->format('Y-m'))
            ->map->count();

        $cotizacionesMensuales = collect();
        $primeraCotizacion = Cotizacion::min('created_at');
        if ($primeraCotizacion) {
            $meses      = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
            $mesesLargos = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
            $cursor = \Illuminate\Support\Carbon::parse($primeraCotizacion)->startOfMonth();
            $fin    = now()->startOfMonth();
            while ($cursor <= $fin) {
                $cotizacionesMensuales->push([
                    'mes'      => $meses[$cursor->month - 1],
                    'mesLargo' => $mesesLargos[$cursor->month - 1],
                    'anio'     => $cursor->year,
                    'total'    => (int) ($conteoPorMes[$cursor->format('Y-m')] ?? 0),
                    'esEnero'  => $cursor->month === 1,
                ]);
                $cursor->addMonth();
            }
        }
        $maxCotizMes = max(1, (int) $cotizacionesMensuales->max('total'));

        return view('UsoInterno.estadisticas', compact(
            'totalProductos',
            'productosActivos',
            'productosInactivos',
            'totalEspeciales',
            'especialesActivos',
            'productosSinImagen',
            'sinImagenPorLinea',
            'productosPorCategoria',
            'totalConsultas',
            'consultasMes',
            'cotizacionesMensuales',
            'maxCotizMes',
        ));
    }

    public function miPerfil()
    {
        return view('UsoInterno.User.myProfile');
    }

    public function indexCategorias(Request $request)
    {
        try {
            $search = $request->input('search');

            $categorias = Categoria::withCount('productos')
                ->when($search, function ($query, $search) {
                    return $query->where('nombre', 'like', '%' . $search . '%');
                })
                ->orderBy('nombre')
                ->paginate(12)
                ->appends(['search' => $search]);

            if ($request->ajax()) {
                return response()->json([
                    'categorias' => $categorias->items(),
                    'pagination' => [
                        'current_page' => $categorias->currentPage(),
                        'last_page'    => $categorias->lastPage(),
                        'total'        => $categorias->total(),
                        'from'         => $categorias->firstItem(),
                        'to'           => $categorias->lastItem(),
                        'links'        => $categorias->links()->render(),
                    ],
                ]);
            }

            return view('UsoInterno.categorias.indexCategoria', compact('categorias'));
        } catch (\Exception $e) {
            Log::error('Error al cargar categorías: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json(['error' => 'Error al cargar las categorías.'], 500);
            }

            return redirect()->back()->with('error', 'Error al cargar las categorías.');
        }
    }

    public function createCategoria()
    {
        return view('UsoInterno.categorias.createCategoria');
    }

    public function storeCategoria(Request $request)
    {
        $request->validate(array_merge([
            'nombre'      => 'required|string|max:255|unique:categorias,nombre',
            'imagen_hero' => 'nullable|image|max:4096',
        ], $this->reglasEncuadreHero()), [
            'nombre.required'     => 'El nombre de la categoria es obligatorio.',
            'nombre.string'       => 'El nombre de la categoria debe ser un texto.',
            'nombre.max'          => 'El nombre de la categoria no puede superar los 255 caracteres.',
            'nombre.unique'       => 'Ya existe una categoria con ese nombre.',
            'imagen_hero.image'   => 'El archivo debe ser una imagen.',
            'imagen_hero.max'     => 'La imagen no puede superar los 4 MB.',
        ]);

        try {
            $rutaHero = null;
            if ($request->hasFile('imagen_hero')) {
                $rutaHero = $this->guardarImagenHero($request->file('imagen_hero'));
            }

            Categoria::create(array_merge([
                'nombre'      => $request->nombre,
                'activo'      => true,
                'imagen_hero' => $rutaHero,
            ], $rutaHero
                ? $this->encuadreHeroDesde($request)
                : $this->encuadreHeroPorDefecto()));

            MenuCategorias::olvidar();
            return redirect()->route('uso-interno.categorias.index')->with('success', 'Categoría creada exitosamente.');
        } catch (\Exception $e) {
            Log::error('Error al crear categoría: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al crear la categoría.');
        }
    }

    public function storeCategoriaJson(Request $request)
    {
        $datos = $request->validate([
            'nombre' => 'required|string|max:255|unique:categorias,nombre',
        ], [
            'nombre.required' => 'El nombre de la categoria es obligatorio.',
            'nombre.string'   => 'El nombre de la categoria debe ser un texto.',
            'nombre.max'      => 'El nombre de la categoria no puede superar los 255 caracteres.',
            'nombre.unique'   => 'Ya existe una categoria con ese nombre.',
        ]);

        try {
            $categoria = Categoria::create(array_merge([
                'nombre'      => $datos['nombre'],
                'activo'      => true,
                'imagen_hero' => null,
            ], $this->encuadreHeroPorDefecto()));

            MenuCategorias::olvidar();

            return response()->json([
                'id'     => $categoria->id,
                'nombre' => $categoria->nombre,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error al crear categoría desde el formulario de producto: ' . $e->getMessage());
            return response()->json(['message' => 'Error al crear la categoría.'], 500);
        }
    }

    public function editCategoria(int $id)
    {
        try {
            // El conteo de activos alimenta el aviso del switch: apagar la
            // categoría se lleva puestos esos productos.
            $categoria = Categoria::withCount([
                'productos as productos_activos_count' => fn($q) => $q->where('activo', true),
            ])->findOrFail($id);

            return view('UsoInterno.categorias.createCategoria', [
                'categoria'  => $categoria,
                'isEdit'     => true,
                'formAction' => route('uso-interno.categorias.update', $categoria),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error al cargar categoría para edición (id: ' . $id . '): ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al cargar la categoría.');
        }
    }

    public function updateCategoria(Request $request, int $id)
    {
        $categoria = Categoria::findOrFail($id);

        $request->validate(array_merge([
            'nombre'      => 'required|string|max:255|unique:categorias,nombre,' . $categoria->id,
            'activo'      => 'nullable|in:0,1',
            'imagen_hero' => 'nullable|image|max:4096',
        ], $this->reglasEncuadreHero()), [
            'nombre.required'   => 'El nombre de la categoria es obligatorio.',
            'nombre.string'     => 'El nombre de la categoria debe ser un texto.',
            'nombre.max'        => 'El nombre de la categoria no puede superar los 255 caracteres.',
            'nombre.unique'     => 'Ya existe una categoria con ese nombre.',
            'imagen_hero.image' => 'El archivo debe ser una imagen.',
            'imagen_hero.max'   => 'La imagen no puede superar los 4 MB.',
        ]);

        DB::beginTransaction();
        try {
            $datos = [
                'nombre' => $request->nombre,
                'activo' => (bool) $request->input('activo', 0),
            ];

            $sePasaAInactiva = $categoria->activo && ! $datos['activo'];

            if ($request->hasFile('imagen_hero')) {
                if ($categoria->imagen_hero) {
                    $this->eliminarImagenHero($categoria->imagen_hero);
                }
                $datos['imagen_hero'] = $this->guardarImagenHero($request->file('imagen_hero'));
                $datos += $this->encuadreHeroDesde($request);
            } elseif ($request->boolean('eliminar_imagen_hero')) {
                if ($categoria->imagen_hero) {
                    $this->eliminarImagenHero($categoria->imagen_hero);
                }
                $datos['imagen_hero'] = null;
                // Sin foto el encuadre no apunta a nada: vuelve al centro para
                // que la próxima que suban no herede el ajuste de la anterior.
                $datos += $this->encuadreHeroPorDefecto();
            } elseif ($categoria->imagen_hero) {
                // Misma foto, encuadre retocado: el caso más común una vez que
                // la imagen ya está cargada.
                $datos += $this->encuadreHeroDesde($request);
            }

            $categoria->update($datos);

            $productosDadosDeBaja = 0;
            if ($sePasaAInactiva) {
                $productosDadosDeBaja = Producto::where('categoria_id', $categoria->id)
                    ->where('activo', true)
                    ->update(['activo' => false]);
            }

            DB::commit();

            MenuCategorias::olvidar();

            $mensaje = 'Categoría actualizada exitosamente.';
            if ($productosDadosDeBaja > 0) {
                $mensaje .= $productosDadosDeBaja === 1
                    ? ' También se dio de baja 1 producto de la categoría.'
                    : " También se dieron de baja {$productosDadosDeBaja} productos de la categoría.";
            }

            return redirect()->route('uso-interno.categorias.index')->with('success', $mensaje);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar categoría: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al actualizar la categoría.');
        }
    }

    public function indexProductos(Request $request)
    {
        try {
            $search      = $request->input('search');
            $categoriaId = $request->input('categoria_id');
            $activo      = $request->input('activo');

            $productos = Producto::estandar()
                ->with('categoria')
                ->when($search, fn($q) => $q->where(function ($q) use ($search) {
                    $q->where('nombre', 'like', '%' . $search . '%')
                        ->orWhere('codigo', 'like', '%' . $search . '%');
                }))
                ->when($categoriaId, fn($q) => $q->where('categoria_id', $categoriaId))
                ->when($activo !== null && $activo !== '', fn($q) => $q->where('activo', (bool) $activo))
                ->orderBy('nombre')
                ->paginate(12)
                ->appends(['search' => $search, 'categoria_id' => $categoriaId, 'activo' => $activo]);

            $categorias = Categoria::orderBy('nombre')->get();

            if ($request->ajax()) {
                return response()->json([
                    'productos' => $productos->map(fn($p) => [
                        'id'          => $p->id,
                        'nombre'      => $p->nombre,
                        'codigo'      => $p->codigo,
                        'descripcion' => $p->descripcion,
                        'categoria'   => $p->categoria ? $p->categoria->nombre : '—',
                        'activo'      => (bool) $p->activo,
                    ]),
                    'pagination' => [
                        'current_page' => $productos->currentPage(),
                        'last_page'    => $productos->lastPage(),
                        'total'        => $productos->total(),
                        'from'         => $productos->firstItem(),
                        'to'           => $productos->lastItem(),
                        'links'        => $productos->links()->render(),
                    ],
                ]);
            }

            // Para el modal de carga masiva: los códigos válidos de la columna "Unidad".
            $unidades = UnidadMedida::orderBy('id')->get();

            return view('UsoInterno.Productos.indexProductos', compact('productos', 'categorias', 'unidades'));
        } catch (\Exception $e) {
            Log::error('Error al cargar productos: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json(['error' => 'Error al cargar los productos.'], 500);
            }

            return redirect()->back()->with('error', 'Error al cargar los productos.');
        }
    }

    public function catalogoPdfProductos()
    {
        try {
            $pdf = $this->catalogoPdfService->generar();

            return $pdf->stream('catalogo-productos-giacomazzi-' . now()->format('Y-m-d') . '.pdf');
        } catch (\Exception $e) {
            Log::error('Error al generar catálogo PDF: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al generar el catálogo PDF.');
        }
    }

    public function catalogoPdfProductosSinMarca()
    {
        try {
            $pdf = $this->catalogoPdfService->generarSinMarca();

            return $pdf->stream('catalogo-productos-estandar-' . now()->format('Y-m-d') . '.pdf');
        } catch (\Exception $e) {
            Log::error('Error al generar catálogo PDF sin marca: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al generar el catálogo PDF.');
        }
    }

    public function showProducto(String $id)
    {
        try {
            $producto = Producto::estandar()->with([
                'categoria',
                'unidad',
                'valoresVariantes.variante',
                'imagenes',
                'imagenesTecnicas',
                'variantes',
            ])->findOrFail($id);

            return view('UsoInterno.Productos.showProducto', compact('producto'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error al cargar producto (id: ' . $id . '): ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al cargar el producto.');
        }
    }

    public function createProducto()
    {
        try {
            $categorias       = Categoria::orderBy('nombre')->get();
            $unidades         = UnidadMedida::orderBy('id')->get();
            $initialVariantes = [];

            return view('UsoInterno.Productos.createProducto', compact('categorias', 'unidades', 'initialVariantes'));
        } catch (\Exception $e) {
            Log::error('Error al cargar formulario de creación de producto: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al cargar el formulario.');
        }
    }

    public function storeProducto(Request $request)
    {
        $request->validate([
            'categoria_id'        => 'required|exists:categorias,id',
            'unidad_id'           => 'required|exists:unidades_medida,id',
            'nombre'              => 'required|string|max:255',
            'codigo'              => 'required|string|max:100|unique:productos,codigo',
            'descripcion'         => 'required|string|max:' . Producto::MAX_DESCRIPCION,
            'descripcion_tecnica' => 'nullable|string|max:' . Producto::MAX_DESCRIPCION_TECNICA,
            'imagenes'            => 'nullable|array|max:' . Producto::MAX_IMAGENES,
            'imagenes.*'          => GestorImagenesProducto::reglasImagen(),
            'imagenes_tecnicas'   => 'nullable|array|max:' . Producto::MAX_IMAGENES_TECNICAS,
            'imagenes_tecnicas.*' => GestorImagenesProducto::reglasImagen(),
            'variantes_json'      => 'nullable|string',
            'imagen_portada'      => 'nullable|string',
            'imagenes_orden'      => 'nullable|string|max:300',
        ], [
            'categoria_id.required' => 'La categoría es obligatoria.',
            'categoria_id.exists'   => 'La categoría seleccionada no existe.',
            'unidad_id.required'    => 'La unidad de cotización es obligatoria.',
            'unidad_id.exists'      => 'La unidad de cotización seleccionada no existe.',
            'nombre.required'       => 'El nombre es obligatorio.',
            'nombre.max'            => 'El nombre no puede superar los 255 caracteres.',
            'codigo.required'       => 'El código es obligatorio.',
            'codigo.unique'         => 'Ya existe un producto con ese código.',
            'descripcion.required'  => 'La descripción es obligatoria.',
            'descripcion.max'       => 'La descripción no puede superar los ' . Producto::MAX_DESCRIPCION . ' caracteres.',
            'descripcion_tecnica.max' => 'La descripción técnica no puede superar los ' . number_format(Producto::MAX_DESCRIPCION_TECNICA, 0, ',', '.') . ' caracteres.',
            'imagenes.max'          => 'No se pueden cargar más de ' . Producto::MAX_IMAGENES . ' imágenes por producto.',
            'imagenes_tecnicas.max' => 'No se pueden cargar más de ' . Producto::MAX_IMAGENES_TECNICAS . ' imágenes técnicas por producto.',
        ]);

        DB::beginTransaction();
        try {
            $producto = Producto::create([
                'categoria_id'        => $request->categoria_id,
                'unidad_id'           => $request->unidad_id,
                'nombre'              => $request->nombre,
                'codigo'              => $request->codigo,
                'descripcion'         => $request->descripcion,
                'descripcion_tecnica' => $request->descripcion_tecnica,
                'activo'              => true,
            ]);

            $this->sincronizadorImagenes->sincronizarGaleria($producto, $request);
            $this->sincronizadorImagenes->sincronizarTecnicas($producto, $request);

            $this->skuService->sincronizarVariantes($producto, $request);

            // Puede ser el primer producto activo de su categoría, y eso hace
            // aparecer la categoría en el menú.
            MenuCategorias::olvidar();

            DB::commit();
            // A la ficha del producto y no al listado: recién creado, lo
            // primero que se quiere es ver cómo quedó.
            return redirect()->route('uso-interno.productos.show', $producto->id)
                ->with('success', 'Producto creado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear producto: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error al crear el producto.')
                ->withInput();
        }
    }

    /** Carga masiva desde Excel/CSV; el detalle de lo que falló vuelve en `importacion`. */
    public function importarProductos(Request $request, ImportadorProductos $importador)
    {
        $request->validate(
            ['archivo' => 'required|file|mimes:xlsx,xls,csv,txt|max:5120'],
            [
                'archivo.required' => 'Elegí un archivo para importar.',
                'archivo.mimes'    => 'El archivo tiene que ser Excel (.xlsx, .xls) o CSV.',
                'archivo.max'      => 'El archivo no puede superar los 5 MB.',
            ]
        );

        try {
            $resultado = $importador->importar($request->file('archivo'), false);
        } catch (\Exception $e) {
            Log::error('Error al importar productos estándar: ' . $e->getMessage());
            return redirect()->route('uso-interno.productos.index')
                ->with('error', 'No se pudo leer el archivo. Revisá que sea un Excel o CSV válido.');
        }

        return redirect()->route('uso-interno.productos.index')->with('importacion', $resultado);
    }

    public function editProducto(String $id)
    {
        try {
            $producto   = Producto::estandar()->with([
                'categoria',
                'unidad',
                'valoresVariantes.variante',
                'imagenes',
                'imagenesTecnicas',
            ])->findOrFail($id);
            $categorias = Categoria::orderBy('nombre')->get();
            $unidades   = UnidadMedida::orderBy('id')->get();

            $initialVariantes = $producto->valoresVariantes->map(fn($vv) => [
                'tipo'              => 'existente',
                'valor_variante_id' => $vv->id,
                'variante_id'       => $vv->variante_id,
                'codigo'            => $vv->codigo,
                'display'           => ($vv->variante->nombre ?? '?') . ': ' . $vv->valor,
                '_lid'              => (string) Str::uuid(),
            ])->values()->toArray();

            return view('UsoInterno.Productos.createProducto', compact('producto', 'categorias', 'unidades', 'initialVariantes'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error al cargar producto para edición (id: ' . $id . '): ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al cargar el producto para edición.');
        }
    }

    public function updateProducto(Request $request, String $id)
    {
        $producto = Producto::estandar()->findOrFail($id);

        $request->validate([
            'categoria_id'        => 'required|exists:categorias,id',
            'unidad_id'           => 'required|exists:unidades_medida,id',
            'nombre'              => 'required|string|max:255',
            'codigo'              => 'required|string|max:100|unique:productos,codigo,' . $producto->id,
            'descripcion'         => 'required|string|max:' . Producto::MAX_DESCRIPCION,
            'descripcion_tecnica' => 'nullable|string|max:' . Producto::MAX_DESCRIPCION_TECNICA,
            'activo'              => 'nullable|in:0,1',
            'imagenes'            => 'nullable|array|max:' . Producto::MAX_IMAGENES,
            'imagenes.*'          => GestorImagenesProducto::reglasImagen(),
            'imagenes_eliminar'   => 'nullable|array',
            'imagenes_eliminar.*' => 'exists:imagenes_producto,id',
            'imagenes_tecnicas'            => 'nullable|array|max:' . Producto::MAX_IMAGENES_TECNICAS,
            'imagenes_tecnicas.*'          => GestorImagenesProducto::reglasImagen(),
            'imagenes_tecnicas_eliminar'   => 'nullable|array',
            'imagenes_tecnicas_eliminar.*' => 'exists:imagenes_producto,id',
            'variantes_json'      => 'nullable|string',
            'imagen_portada'      => 'nullable|string',
            'imagenes_orden'      => 'nullable|string|max:300',
        ], [
            'categoria_id.required' => 'La categoría es obligatoria.',
            'categoria_id.exists'   => 'La categoría seleccionada no existe.',
            'unidad_id.required'    => 'La unidad de cotización es obligatoria.',
            'unidad_id.exists'      => 'La unidad de cotización seleccionada no existe.',
            'nombre.required'       => 'El nombre es obligatorio.',
            'codigo.required'       => 'El código es obligatorio.',
            'codigo.unique'         => 'Ya existe un producto con ese código.',
            'descripcion.required'  => 'La descripción es obligatoria.',
            'descripcion.max'       => 'La descripción no puede superar los ' . Producto::MAX_DESCRIPCION . ' caracteres.',
            'descripcion_tecnica.max' => 'La descripción técnica no puede superar los ' . number_format(Producto::MAX_DESCRIPCION_TECNICA, 0, ',', '.') . ' caracteres.',
            'imagenes.max'          => 'No se pueden cargar más de ' . Producto::MAX_IMAGENES . ' imágenes por producto.',
            'imagenes_tecnicas.max' => 'No se pueden cargar más de ' . Producto::MAX_IMAGENES_TECNICAS . ' imágenes técnicas por producto.',
        ]);

        DB::beginTransaction();
        try {
            $producto->update([
                'categoria_id'        => $request->categoria_id,
                'unidad_id'           => $request->unidad_id,
                'nombre'              => $request->nombre,
                'codigo'              => $request->codigo,
                'descripcion'         => $request->descripcion,
                'descripcion_tecnica' => $request->descripcion_tecnica,
                'activo'              => (bool) $request->input('activo', 0),
            ]);

            $this->sincronizadorImagenes->sincronizarGaleria($producto, $request);
            $this->sincronizadorImagenes->sincronizarTecnicas($producto, $request);

            $this->skuService->sincronizarVariantes($producto, $request);

            // Cambiar de categoría o de estado mueve al producto entre ramas
            // del menú.
            MenuCategorias::olvidar();

            DB::commit();
            return redirect()->route('uso-interno.productos.show', $producto->id)
                ->with('success', 'Producto actualizado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar producto: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error al actualizar el producto.')
                ->withInput();
        }
    }

    /**
     * Alta desde el badge de estado del listado. El id llega por el hidden
     * del modal, no por la URL.
     */
    public function activarProducto(Request $request)
    {
        return $this->cambiarEstadoProducto($request, true);
    }

    /** Baja desde el badge de estado del listado. */
    public function desactivarProducto(Request $request)
    {
        return $this->cambiarEstadoProducto($request, false);
    }

    private function cambiarEstadoProducto(Request $request, bool $activo)
    {
        $request->validate([
            'producto_id' => 'required|exists:productos,id',
        ], [
            'producto_id.required' => 'No se indicó qué producto modificar.',
            'producto_id.exists'   => 'El producto indicado no existe.',
        ]);

        $accion = $activo ? 'alta' : 'baja';

        try {
            $producto = Producto::estandar()->with('categoria')->findOrFail($request->producto_id);

            // Dos pestañas abiertas, o el listado sin refrescar: el estado que
            // vio el usuario al tocar el badge puede no ser el actual.
            if ((bool) $producto->activo === $activo) {
                return redirect()->back()
                    ->with('success', "El producto \"{$producto->nombre}\" ya estaba " . ($activo ? 'activo' : 'inactivo') . '.');
            }

            $producto->update(['activo' => $activo]);

            // La categoría puede quedarse sin productos activos, o recuperarlos.
            MenuCategorias::olvidar();

            $mensaje = "El producto \"{$producto->nombre}\" se dio de {$accion} correctamente.";

            // Un producto activo dentro de una categoría inactiva sigue sin
            // verse en el sitio; conviene decirlo o parece que el alta falló.
            if ($activo && $producto->categoria && ! $producto->categoria->activo) {
                $mensaje .= " Tené en cuenta que la categoría \"{$producto->categoria->nombre}\" está inactiva, así que todavía no se muestra en el sitio.";
            }

            return redirect()->back()->with('success', $mensaje);
        } catch (\Exception $e) {
            Log::error("Error al dar de {$accion} producto (id: " . $request->producto_id . '): ' . $e->getMessage());
            return redirect()->back()->with('error', "Error al dar de {$accion} el producto.");
        }
    }

    public function getVariantesByCategoria(String $id)
    {
        try {
            $categoria = Categoria::with(['variantes.valores'])->findOrFail($id);

            return response()->json(
                $categoria->variantes->map(fn($v) => [
                    'id'      => $v->id,
                    'nombre'  => $v->nombre,
                    'valores' => $v->valores->map(fn($vl) => [
                        'id'     => $vl->id,
                        'valor'  => $vl->valor,
                        'codigo' => $vl->codigo,
                    ]),
                ])
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error al cargar variantes de categoría (id: ' . $id . '): ' . $e->getMessage());
            return response()->json(['error' => 'Error al cargar las variantes.'], 500);
        }
    }

    private function guardarImagenHero(UploadedFile $imagen): string
    {
        $destino = public_path('images/heros');
        if (!is_dir($destino)) {
            mkdir($destino, 0755, true);
        }
        // hashName() toma la extensión del contenido, no del nombre que manda el
        // cliente: con el nombre original, un GIF llamado x.php quedaba en
        // public/ y nginx lo ejecutaba.
        $nombre = $imagen->hashName();
        $imagen->move($destino, $nombre);
        return 'images/heros/' . $nombre;
    }

    private function eliminarImagenHero(string $ruta): void
    {
        $path = public_path($ruta);
        if (file_exists($path)) {
            @unlink($path);
        }
    }

    /**
     * Reglas del encuadre del hero: punto focal en porcentaje y zoom.
     */
    private function reglasEncuadreHero(): array
    {
        $reglas = [];

        foreach (array_keys(Categoria::RECUADROS_HERO) as $recuadro) {
            $reglas["hero_{$recuadro}_x"]    = 'nullable|numeric|between:0,100';
            $reglas["hero_{$recuadro}_y"]    = 'nullable|numeric|between:0,100';
            $reglas["hero_{$recuadro}_zoom"] = 'nullable|numeric|between:1,3';
        }

        return $reglas;
    }

    /** El encuadre del formulario, armado para la columna `hero_encuadre`. */
    private function encuadreHeroDesde(Request $request): array
    {
        $encuadre = [];

        foreach (array_keys(Categoria::RECUADROS_HERO) as $recuadro) {
            foreach (Categoria::ENCUADRE_DEFECTO as $eje => $defecto) {
                $encuadre[$recuadro][$eje] = (float) $request->input("hero_{$recuadro}_{$eje}", $defecto);
            }
        }

        return ['hero_encuadre' => $encuadre];
    }

    private function encuadreHeroPorDefecto(): array
    {
        return ['hero_encuadre' => null];
    }
}
