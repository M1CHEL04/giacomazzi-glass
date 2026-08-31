<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Cotizacion;
use App\Models\ImagenProducto;
use App\Models\Producto;
use App\Models\UnidadMedida;
use App\Services\OptimizadorImagen;
use App\Services\SkuService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UsoInternoController extends Controller
{
    /**
     * Reglas de cada archivo de imagen de producto.
     *
     * `mimes` acota lo que acepta la regla `image` a secas (svg, gif y bmp
     * incluidos): GD no puede leer un SVG y la conversión a WebP explotaría.
     *
     * `dimensions` es la guarda de memoria, y va en megapíxeles porque es lo que
     * cuesta: GD descomprime a 4 bytes por píxel, así que el peso del archivo no
     * predice nada (una foto de 50 MP pesa 3,5 MB y pica en 216 MB). El tope de
     * 8000x8000 deja entrar a los celulares de 48/50 MP y pica en 280 MB contra
     * el techo de 512M que OptimizadorImagen se pone durante la conversión.
     * Si se sube este número hay que volver a medir y ajustar allá.
     */
    private const REGLAS_IMAGEN = 'image|mimes:jpg,jpeg,png,webp|max:5120|dimensions:max_width=8000,max_height=8000';

    public function __construct(
        private SkuService $skuService,
        private OptimizadorImagen $optimizador,
    ) {}

    public function estadisticas()
    {
        $totalProductos     = Producto::count();
        $productosActivos   = Producto::where('activo', true)->count();
        $productosInactivos = $totalProductos - $productosActivos;

        $totalCategorias   = Categoria::count();
        $categoriasActivas = Categoria::where('activo', true)->count();

        $productosSinImagen = Producto::doesntHave('imagenes')->count();

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
            'totalCategorias',
            'categoriasActivas',
            'productosSinImagen',
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
        $request->validate([
            'nombre'      => 'required|string|max:255|unique:categorias,nombre',
            'imagen_hero' => 'nullable|image|max:4096',
        ], [
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

            Categoria::create([
                'nombre'      => $request->nombre,
                'activo'      => true,
                'imagen_hero' => $rutaHero,
            ]);

            Cache::forget('categorias_menu_externo');
            return redirect()->route('uso-interno.categorias.index')->with('success', 'Categoría creada exitosamente.');
        } catch (\Exception $e) {
            Log::error('Error al crear categoría: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al crear la categoría.');
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

        $request->validate([
            'nombre'      => 'required|string|max:255|unique:categorias,nombre,' . $categoria->id,
            'activo'      => 'nullable|in:0,1',
            'imagen_hero' => 'nullable|image|max:4096',
        ], [
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
            } elseif ($request->boolean('eliminar_imagen_hero')) {
                if ($categoria->imagen_hero) {
                    $this->eliminarImagenHero($categoria->imagen_hero);
                }
                $datos['imagen_hero'] = null;
            }

            $categoria->update($datos);

            $productosDadosDeBaja = 0;
            if ($sePasaAInactiva) {
                $productosDadosDeBaja = Producto::where('categoria_id', $categoria->id)
                    ->where('activo', true)
                    ->update(['activo' => false]);
            }

            DB::commit();

            Cache::forget('categorias_menu_externo');

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

            $productos = Producto::with(['categoria', 'unidad'])
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
                        'unidad'      => $p->unidad ? $p->unidad->nombre : '—',
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

            return view('UsoInterno.Productos.indexProductos', compact('productos', 'categorias'));
        } catch (\Exception $e) {
            Log::error('Error al cargar productos: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json(['error' => 'Error al cargar los productos.'], 500);
            }

            return redirect()->back()->with('error', 'Error al cargar los productos.');
        }
    }

    public function showProducto(String $id)
    {
        try {
            $producto = Producto::with([
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
            'imagenes.*'          => self::REGLAS_IMAGEN,
            'imagenes_tecnicas'   => 'nullable|array|max:' . Producto::MAX_IMAGENES_TECNICAS,
            'imagenes_tecnicas.*' => self::REGLAS_IMAGEN,
            'variantes_json'      => 'nullable|string',
            'imagen_portada'      => 'nullable|string',
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
            'imagenes.*.image'      => 'Cada archivo debe ser una imagen.',
            'imagenes.*.mimes'      => 'Las imágenes deben ser JPG, PNG o WebP.',
            'imagenes.*.max'        => 'Cada imagen no puede superar los 5 MB.',
            'imagenes.*.dimensions' => 'Cada imagen no puede superar los 8000 px de ancho o alto.',
            'imagenes_tecnicas.max'     => 'No se pueden cargar más de ' . Producto::MAX_IMAGENES_TECNICAS . ' imágenes técnicas por producto.',
            'imagenes_tecnicas.*.image' => 'Cada archivo técnico debe ser una imagen.',
            'imagenes_tecnicas.*.mimes' => 'Las imágenes técnicas deben ser JPG, PNG o WebP.',
            'imagenes_tecnicas.*.max'   => 'Cada imagen técnica no puede superar los 5 MB.',
            'imagenes_tecnicas.*.dimensions' => 'Cada imagen técnica no puede superar los 8000 px de ancho o alto.',
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

            $imagenesRequest = array_values(array_filter($request->file('imagenes', [])));
            if (!empty($imagenesRequest)) {
                $portadaField = $request->input('imagen_portada', '');
                $portadaIdx   = str_starts_with($portadaField, 'nueva:')
                    ? (int) substr($portadaField, 6) : 0;
                foreach ($imagenesRequest as $idx => $imagen) {
                    $this->guardarImagenes($producto, $imagen, $idx === $portadaIdx);
                }
            }

            // Técnicas: nunca son portada, de ahí el false en el tercer argumento.
            $tecnicasRequest = array_values(array_filter($request->file('imagenes_tecnicas', [])));
            foreach ($tecnicasRequest as $imagen) {
                $this->guardarImagenes($producto, $imagen, false, true);
            }

            $this->skuService->sincronizarVariantes($producto, $request);

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

    public function editProducto(String $id)
    {
        try {
            $producto   = Producto::with([
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
        $producto = Producto::findOrFail($id);

        $request->validate([
            'categoria_id'        => 'required|exists:categorias,id',
            'unidad_id'           => 'required|exists:unidades_medida,id',
            'nombre'              => 'required|string|max:255',
            'codigo'              => 'required|string|max:100|unique:productos,codigo,' . $producto->id,
            'descripcion'         => 'required|string|max:' . Producto::MAX_DESCRIPCION,
            'descripcion_tecnica' => 'nullable|string|max:' . Producto::MAX_DESCRIPCION_TECNICA,
            'activo'              => 'nullable|in:0,1',
            'imagenes'            => 'nullable|array|max:' . Producto::MAX_IMAGENES,
            'imagenes.*'          => self::REGLAS_IMAGEN,
            'imagenes_eliminar'   => 'nullable|array',
            'imagenes_eliminar.*' => 'exists:imagenes_producto,id',
            'imagenes_tecnicas'            => 'nullable|array|max:' . Producto::MAX_IMAGENES_TECNICAS,
            'imagenes_tecnicas.*'          => self::REGLAS_IMAGEN,
            'imagenes_tecnicas_eliminar'   => 'nullable|array',
            'imagenes_tecnicas_eliminar.*' => 'exists:imagenes_producto,id',
            'variantes_json'      => 'nullable|string',
            'imagen_portada'      => 'nullable|string',
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
            'imagenes.*.image'      => 'Cada archivo debe ser una imagen.',
            'imagenes.*.mimes'      => 'Las imágenes deben ser JPG, PNG o WebP.',
            'imagenes.*.max'        => 'Cada imagen no puede superar los 5 MB.',
            'imagenes.*.dimensions' => 'Cada imagen no puede superar los 8000 px de ancho o alto.',
            'imagenes_tecnicas.max'     => 'No se pueden cargar más de ' . Producto::MAX_IMAGENES_TECNICAS . ' imágenes técnicas por producto.',
            'imagenes_tecnicas.*.image' => 'Cada archivo técnico debe ser una imagen.',
            'imagenes_tecnicas.*.mimes' => 'Las imágenes técnicas deben ser JPG, PNG o WebP.',
            'imagenes_tecnicas.*.max'   => 'Cada imagen técnica no puede superar los 5 MB.',
            'imagenes_tecnicas.*.dimensions' => 'Cada imagen técnica no puede superar los 8000 px de ancho o alto.',
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

            if ($request->filled('imagenes_eliminar')) {
                ImagenProducto::whereIn('id', $request->imagenes_eliminar)
                    ->where('producto_id', $producto->id)
                    ->where('es_tecnica', false)
                    ->update(['activa' => false, 'es_principal' => false]);
            }

            $portadaField = $request->input('imagen_portada', '');
            if (str_starts_with($portadaField, 'existente:')) {
                $portadaId = (int) substr($portadaField, 10);
                // es_tecnica false: una técnica no puede terminar de portada
                // aunque llegue su id en el hidden.
                if (ImagenProducto::where('id', $portadaId)
                    ->where('producto_id', $producto->id)
                    ->where('es_tecnica', false)
                    ->exists()
                ) {
                    $producto->imagenes()->update(['es_principal' => false]);
                    ImagenProducto::where('id', $portadaId)->update(['es_principal' => true]);
                }
            }

            $imagenesNuevas = array_values(array_filter($request->file('imagenes', [])));
            if (!empty($imagenesNuevas)) {
                // Cupo sobre las imágenes activas: las eliminadas siguen en la
                // tabla con activa = false y no ocupan lugar.
                $remaining  = Producto::MAX_IMAGENES - $producto->fresh()->imagenes()->count();
                $portadaIdx = str_starts_with($portadaField, 'nueva:')
                    ? (int) substr($portadaField, 6) : null;
                if ($portadaIdx !== null) {
                    $producto->imagenes()->update(['es_principal' => false]);
                }
                foreach ($imagenesNuevas as $idx => $imagen) {
                    if ($remaining <= 0) break;
                    $this->guardarImagenes($producto, $imagen, $portadaIdx !== null && $idx === $portadaIdx);
                    $remaining--;
                }
            }

            // ── Imágenes técnicas: mismo circuito que las de galería, pero con
            //    su propio cupo y sin portada de por medio.
            if ($request->filled('imagenes_tecnicas_eliminar')) {
                ImagenProducto::whereIn('id', $request->imagenes_tecnicas_eliminar)
                    ->where('producto_id', $producto->id)
                    ->where('es_tecnica', true)
                    ->update(['activa' => false]);
            }

            $tecnicasNuevas = array_values(array_filter($request->file('imagenes_tecnicas', [])));
            if (!empty($tecnicasNuevas)) {
                // Igual que arriba: cuentan sólo las técnicas activas, que son
                // las que el formulario muestra.
                $remainingTecnicas = Producto::MAX_IMAGENES_TECNICAS - $producto->fresh()->imagenesTecnicas()->count();
                foreach ($tecnicasNuevas as $imagen) {
                    if ($remainingTecnicas <= 0) break;
                    $this->guardarImagenes($producto, $imagen, false, true);
                    $remainingTecnicas--;
                }
            }

            $producto->load('imagenes');
            if ($producto->imagenes->isNotEmpty() && $producto->imagenes->where('es_principal', true)->isEmpty()) {
                $producto->imagenes->first()->update(['es_principal' => true]);
            }

            $this->skuService->sincronizarVariantes($producto, $request);

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
            $producto = Producto::with('categoria')->findOrFail($request->producto_id);

            // Dos pestañas abiertas, o el listado sin refrescar: el estado que
            // vio el usuario al tocar el badge puede no ser el actual.
            if ((bool) $producto->activo === $activo) {
                return redirect()->back()
                    ->with('success', "El producto \"{$producto->nombre}\" ya estaba " . ($activo ? 'activo' : 'inactivo') . '.');
            }

            $producto->update(['activo' => $activo]);

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
        $nombre = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $imagen->getClientOriginalName());
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

    private function guardarImagenes(
        Producto $producto,
        UploadedFile $imagen,
        bool $esPrincipal = false,
        bool $esTecnica = false
    ): ImagenProducto {
        if (!$imagen->isValid()) {
            Log::error('Archivo de imagen invalido en el request', [
                'producto_id'   => $producto->id,
                'error_message' => $imagen->getErrorMessage(),
            ]);
            throw new \Exception('Imagen no válida: ' . $imagen->getClientOriginalName());
        }

        $imagenProducto = ImagenProducto::create([
            'producto_id'  => $producto->id,
            'es_principal' => $esPrincipal,
            'es_tecnica'   => $esTecnica,
        ]);

        // Se descarta la extensión original: lo que se sube siempre es WebP.
        $base = $imagenProducto->id . '_' . preg_replace(
            '/[^a-zA-Z0-9._-]/',
            '_',
            pathinfo($imagen->getClientOriginalName(), PATHINFO_FILENAME)
        );

        $imagenProducto->update(['nombre_imagen' => $base . '.webp']);

        $disk       = config('filesystems.image_disk', 'sftp');
        $carpeta    = 'imagenes_producto/' . $producto->id . '/';
        $rutaDisco  = $carpeta . $base . '.webp';
        $rutaThumb  = $carpeta . $base . '-thumb.webp';

        try {
            $variantes = $this->optimizador->variantes($imagen);

            Storage::disk($disk)->put($rutaDisco, $variantes['full']);

            $url = $this->urlDelDisco($disk, $rutaDisco);
        } catch (\Exception $e) {
            $imagenProducto->delete();
            Log::error('Error al subir imagen: ' . $e->getMessage());
            throw new \Exception('Error al subir la imagen al servidor de archivos: ' . $e->getMessage());
        }

        // El thumb es una optimización, no contenido: si falla, se registra y se
        // sigue. La vista cae a la imagen grande (ImagenProducto::rutaMiniatura).
        $urlThumb = null;
        try {
            Storage::disk($disk)->put($rutaThumb, $variantes['thumb']);
            $urlThumb = $this->urlDelDisco($disk, $rutaThumb);
        } catch (\Exception $e) {
            Log::warning('No se pudo subir la miniatura de la imagen ' . $imagenProducto->id . ': ' . $e->getMessage());
        }

        $imagenProducto->update([
            'ruta'       => $url,
            'ruta_thumb' => $urlThumb,
        ]);

        return $imagenProducto;
    }

    /** URL pública de un archivo del file server, o del disco local si no hay una configurada. */
    private function urlDelDisco(string $disk, string $ruta): string
    {
        $baseUrl = rtrim(config('filesystems.disks.' . $disk . '.url', ''), '/');

        return $baseUrl ? $baseUrl . '/' . $ruta : asset('storage/' . $ruta);
    }
}
