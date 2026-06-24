<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\ImagenProducto;
use App\Models\Producto;
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
    public function __construct(private SkuService $skuService) {}

    public function homeInterno()
    {
        return view('UsoInterno.index');
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
            $categoria = Categoria::findOrFail($id);

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

        try {
            $datos = [
                'nombre' => $request->nombre,
                'activo' => (bool) $request->input('activo', 0),
            ];

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

            Cache::forget('categorias_menu_externo');
            return redirect()->route('uso-interno.categorias.index')->with('success', 'Categoría actualizada exitosamente.');
        } catch (\Exception $e) {
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

            $productos = Producto::with('categoria')
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
                'valoresVariantes.variante',
                'imagenes',
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
            $initialVariantes = [];

            return view('UsoInterno.Productos.createProducto', compact('categorias', 'initialVariantes'));
        } catch (\Exception $e) {
            Log::error('Error al cargar formulario de creación de producto: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al cargar el formulario.');
        }
    }

    public function storeProducto(Request $request)
    {
        $request->validate([
            'categoria_id'        => 'required|exists:categorias,id',
            'nombre'              => 'required|string|max:255',
            'codigo'              => 'required|string|max:100|unique:productos,codigo',
            'descripcion'         => 'required|string|max:255',
            'descripcion_tecnica' => 'nullable|string|max:255',
            'imagenes'            => 'nullable|array|max:5',
            'imagenes.*'          => 'image|max:5120',
            'variantes_json'      => 'nullable|string',
            'imagen_portada'      => 'nullable|string',
        ], [
            'categoria_id.required' => 'La categoría es obligatoria.',
            'categoria_id.exists'   => 'La categoría seleccionada no existe.',
            'nombre.required'       => 'El nombre es obligatorio.',
            'nombre.max'            => 'El nombre no puede superar los 255 caracteres.',
            'codigo.required'       => 'El código es obligatorio.',
            'codigo.unique'         => 'Ya existe un producto con ese código.',
            'descripcion.required'  => 'La descripción es obligatoria.',
            'imagenes.max'          => 'Solo se permiten hasta 5 imágenes.',
            'imagenes.*.image'      => 'Cada archivo debe ser una imagen.',
            'imagenes.*.max'        => 'Cada imagen no puede superar los 5 MB.',
        ]);

        DB::beginTransaction();
        try {
            $producto = Producto::create([
                'categoria_id'        => $request->categoria_id,
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

            $this->skuService->sincronizarVariantes($producto, $request);

            DB::commit();
            return redirect()->route('uso-interno.productos.index')
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
            $producto   = Producto::with(['categoria', 'valoresVariantes.variante', 'imagenes'])->findOrFail($id);
            $categorias = Categoria::orderBy('nombre')->get();

            $initialVariantes = $producto->valoresVariantes->map(fn($vv) => [
                'tipo'              => 'existente',
                'valor_variante_id' => $vv->id,
                'variante_id'       => $vv->variante_id,
                'codigo'            => $vv->codigo,
                'display'           => ($vv->variante->nombre ?? '?') . ': ' . $vv->valor,
                '_lid'              => (string) Str::uuid(),
            ])->values()->toArray();

            return view('UsoInterno.Productos.createProducto', compact('producto', 'categorias', 'initialVariantes'));
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
            'nombre'              => 'required|string|max:255',
            'codigo'              => 'required|string|max:100|unique:productos,codigo,' . $producto->id,
            'descripcion'         => 'required|string|max:255',
            'descripcion_tecnica' => 'nullable|string|max:255',
            'activo'              => 'nullable|in:0,1',
            'imagenes'            => 'nullable|array',
            'imagenes.*'          => 'image|max:5120',
            'imagenes_eliminar'   => 'nullable|array',
            'imagenes_eliminar.*' => 'exists:imagenes_producto,id',
            'variantes_json'      => 'nullable|string',
            'imagen_portada'      => 'nullable|string',
        ], [
            'categoria_id.required' => 'La categoría es obligatoria.',
            'categoria_id.exists'   => 'La categoría seleccionada no existe.',
            'nombre.required'       => 'El nombre es obligatorio.',
            'codigo.required'       => 'El código es obligatorio.',
            'codigo.unique'         => 'Ya existe un producto con ese código.',
            'descripcion.required'  => 'La descripción es obligatoria.',
            'imagenes.*.image'      => 'Cada archivo debe ser una imagen.',
            'imagenes.*.max'        => 'Cada imagen no puede superar los 5 MB.',
        ]);

        DB::beginTransaction();
        try {
            $producto->update([
                'categoria_id'        => $request->categoria_id,
                'nombre'              => $request->nombre,
                'codigo'              => $request->codigo,
                'descripcion'         => $request->descripcion,
                'descripcion_tecnica' => $request->descripcion_tecnica,
                'activo'              => (bool) $request->input('activo', 0),
            ]);

            if ($request->filled('imagenes_eliminar')) {
                ImagenProducto::whereIn('id', $request->imagenes_eliminar)
                    ->where('producto_id', $producto->id)
                    ->update(['activa' => false, 'es_principal' => false]);
            }

            $portadaField = $request->input('imagen_portada', '');
            if (str_starts_with($portadaField, 'existente:')) {
                $portadaId = (int) substr($portadaField, 10);
                if (ImagenProducto::where('id', $portadaId)->where('producto_id', $producto->id)->exists()) {
                    $producto->imagenes()->update(['es_principal' => false]);
                    ImagenProducto::where('id', $portadaId)->update(['es_principal' => true]);
                }
            }

            $imagenesNuevas = array_values(array_filter($request->file('imagenes', [])));
            if (!empty($imagenesNuevas)) {
                $remaining  = 5 - $producto->fresh()->imagenes()->count();
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

            $producto->load('imagenes');
            if ($producto->imagenes->isNotEmpty() && $producto->imagenes->where('es_principal', true)->isEmpty()) {
                $producto->imagenes->first()->update(['es_principal' => true]);
            }

            $this->skuService->sincronizarVariantes($producto, $request);

            DB::commit();
            return redirect()->route('uso-interno.productos.index')
                ->with('success', 'Producto actualizado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar producto: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error al actualizar el producto.')
                ->withInput();
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

    private function guardarImagenes(Producto $producto, UploadedFile $imagen, bool $esPrincipal = false): ImagenProducto
    {
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
        ]);

        $imagenProducto->update([
            'nombre_imagen' => $imagenProducto->id . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $imagen->getClientOriginalName()),
        ]);

        $disk        = config('filesystems.image_disk', 'sftp');
        $rutaDisco   = 'imagenes_producto/' . $producto->id . '/' . $imagenProducto->nombre_imagen;

        try {
            Storage::disk($disk)->put($rutaDisco, $imagen->getContent());

            if (!Storage::disk($disk)->exists($rutaDisco)) {
                $imagenProducto->delete();
                throw new \Exception('La imagen no se encontró en el servidor tras subirla.');
            }

            $baseUrl = rtrim(config('filesystems.disks.' . $disk . '.url', ''), '/');
            $url     = $baseUrl ? $baseUrl . '/' . $rutaDisco : asset('storage/' . $rutaDisco);

        } catch (\Exception $e) {
            $imagenProducto->delete();
            Log::error('Error al subir imagen: ' . $e->getMessage());
            throw new \Exception('Error al subir la imagen al servidor de archivos: ' . $e->getMessage());
        }

        $imagenProducto->update(['ruta' => $url]);

        return $imagenProducto;
    }
}
