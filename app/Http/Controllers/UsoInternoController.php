<?php

namespace App\Http\Controllers;

use App\Jobs\UploadImagen;
use App\Models\Categoria;
use App\Models\ImagenProducto;
use App\Models\Producto;
use App\Models\ProductoVariante;
use App\Models\ValorVariante;
use App\Models\Variante;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UsoInternoController extends Controller
{
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
                    'last_page' => $categorias->lastPage(),
                    'total' => $categorias->total(),
                    'from' => $categorias->firstItem(),
                    'to' => $categorias->lastItem(),
                    'links' => $categorias->links()->render()
                ]
            ]);
        }

        return view('UsoInterno.categorias.indexCategoria', compact('categorias'));
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

    public function editCategoria($id)
    {
        $categoria = Categoria::findOrFail($id);
        return view('UsoInterno.categorias.createCategoria', [
            'categoria' => $categoria,
            'isEdit' => true,
            'formAction' => route('uso-interno.categorias.update', $categoria),
        ]);
    }

    public function updateCategoria(Request $request, $id)
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
                // Borrar la imagen anterior si existe
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

    //Productos
    public function indexProductos(Request $request)
    {
        $search = $request->input('search');
        $categoriaId = $request->input('categoria_id');
        $activo = $request->input('activo');

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
    }

    public function showProducto(String $id)
    {
        $producto = Producto::with([
            'categoria',
            'valoresVariantes.variante',
            'imagenes',
            'variantes',
        ])->findOrFail($id);

        return view('UsoInterno.Productos.showProducto', compact('producto'));
    }

    public function createProducto()
    {
        $categorias = Categoria::orderBy('nombre')->get();
        $initialVariantes = [];
        return view('UsoInterno.Productos.createProducto', compact('categorias', 'initialVariantes'));
    }

    public function storeProducto(Request $request)
    {
        $request->validate([
            'categoria_id'      => 'required|exists:categorias,id',
            'nombre'            => 'required|string|max:255',
            'codigo'            => 'required|string|max:100|unique:productos,codigo',
            'descripcion'       => 'required|string|max:255',
            'descripcion_tecnica' => 'nullable|string|max:255',
            'imagenes'          => 'nullable|array|max:5',
            'imagenes.*'        => 'image|max:5120',
            'variantes_json'    => 'nullable|string',
            'imagen_portada'    => 'nullable|string',
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
                'categoria_id'       => $request->categoria_id,
                'nombre'             => $request->nombre,
                'codigo'             => $request->codigo,
                'descripcion'        => $request->descripcion,
                'descripcion_tecnica' => $request->descripcion_tecnica,
                'activo'             => true,
            ]);

            // Imagenes
            $imagenesRequest = array_values(array_filter($request->file('imagenes', [])));
            if (!empty($imagenesRequest)) {
                $portadaField = $request->input('imagen_portada', '');
                $portadaIdx   = str_starts_with($portadaField, 'nueva:')
                    ? (int) substr($portadaField, 6) : 0;
                foreach ($imagenesRequest as $idx => $imagen) {
                    $this->guardarImagenes($producto, $imagen, $idx === $portadaIdx);
                }
            }


            // Variantes: sync asociaciones y genera combinaciones SKU
            $this->sincronizarVariantes($producto, $request);

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
        $producto = Producto::with(['categoria', 'valoresVariantes.variante', 'imagenes'])->findOrFail($id);
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
    }

    public function updateProducto(Request $request, String $id)
    {
        $producto = Producto::findOrFail($id);

        $request->validate([
            'categoria_id'      => 'required|exists:categorias,id',
            'nombre'            => 'required|string|max:255',
            'codigo'            => 'required|string|max:100|unique:productos,codigo,' . $producto->id,
            'descripcion'       => 'required|string|max:255',
            'descripcion_tecnica' => 'nullable|string|max:255',
            'activo'            => 'nullable|in:0,1',
            'imagenes'          => 'nullable|array',
            'imagenes.*'        => 'image|max:5120',
            'imagenes_eliminar' => 'nullable|array',
            'imagenes_eliminar.*' => 'exists:imagenes_producto,id',
            'variantes_json'    => 'nullable|string',
            'imagen_portada'    => 'nullable|string',
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
                'categoria_id'       => $request->categoria_id,
                'nombre'             => $request->nombre,
                'codigo'             => $request->codigo,
                'descripcion'        => $request->descripcion,
                'descripcion_tecnica' => $request->descripcion_tecnica,
                'activo'             => (bool) $request->input('activo', 0),
            ]);

            // Desactivar imágenes marcadas (verificando que pertenecen al producto)
            if ($request->filled('imagenes_eliminar')) {
                ImagenProducto::whereIn('id', $request->imagenes_eliminar)
                    ->where('producto_id', $producto->id)
                    ->update(['activa' => false, 'es_principal' => false]);
            }

            // Cambio de portada en imagen existente
            $portadaField = $request->input('imagen_portada', '');
            if (str_starts_with($portadaField, 'existente:')) {
                $portadaId = (int) substr($portadaField, 10);
                if (ImagenProducto::where('id', $portadaId)->where('producto_id', $producto->id)->exists()) {
                    $producto->imagenes()->update(['es_principal' => false]);
                    ImagenProducto::where('id', $portadaId)->update(['es_principal' => true]);
                }
            }

            // Agregar nuevas imágenes
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

            // Si ninguna imagen tiene es_principal, asignar la primera
            $producto->load('imagenes');
            if ($producto->imagenes->isNotEmpty() && $producto->imagenes->where('es_principal', true)->isEmpty()) {
                $producto->imagenes->first()->update(['es_principal' => true]);
            }

            // Variantes: sync asociaciones y regenera combinaciones SKU
            $this->sincronizarVariantes($producto, $request);

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
    }

    /**
     * Resuelve el JSON de variantes en un mapa [variante_id => [valor_variante_id, ...]].
     * Crea Variante/ValorVariante si no existen y actualiza el `codigo` del valor si cambió.
     */
    private function resolverIdsAgrupados(Request $request): array
    {
        $variantes = json_decode($request->input('variantes_json', '[]'), true) ?? [];
        $grupos    = []; // [variante_id => [valor_variante_id, ...]]

        foreach ($variantes as $item) {
            $valorVarianteId = null;
            $varianteId      = null;
            $codigoNuevo     = strtoupper(trim($item['codigo'] ?? ''));

            if ($item['tipo'] === 'existente') {
                $vv = ValorVariante::find($item['valor_variante_id']);
                if (!$vv) continue;
                if ($codigoNuevo !== '' && $vv->codigo !== $codigoNuevo) {
                    $vv->update(['codigo' => $codigoNuevo]);
                }
                $valorVarianteId = $vv->id;
                $varianteId      = $vv->variante_id;
            } elseif ($item['tipo'] === 'nuevo_valor') {
                $vv = ValorVariante::firstOrCreate(
                    ['variante_id' => $item['variante_id'], 'valor' => $item['valor']],
                    ['codigo' => $codigoNuevo]
                );
                if ($codigoNuevo !== '' && $vv->codigo !== $codigoNuevo) {
                    $vv->update(['codigo' => $codigoNuevo]);
                }
                $valorVarianteId = $vv->id;
                $varianteId      = (int) $item['variante_id'];
            } elseif ($item['tipo'] === 'nueva_variante') {
                $variante = Variante::firstOrCreate(['nombre' => $item['variante_nombre']]);
                $variante->categorias()->syncWithoutDetaching([$request->categoria_id]);
                $vv = ValorVariante::firstOrCreate(
                    ['variante_id' => $variante->id, 'valor' => $item['valor']],
                    ['codigo' => $codigoNuevo]
                );
                if ($codigoNuevo !== '' && $vv->codigo !== $codigoNuevo) {
                    $vv->update(['codigo' => $codigoNuevo]);
                }
                $valorVarianteId = $vv->id;
                $varianteId      = $variante->id;
            }

            if ($valorVarianteId && $varianteId) {
                $grupos[$varianteId][] = $valorVarianteId;
            }
        }

        return $grupos;
    }

    /**
     * Producto cartesiano de los grupos de valores.
     * Recibe  [variante_id => [id1, id2, ...]] y devuelve todas las combinaciones
     * como arrays planos de valor_variante_id, uno por columna de variante.
     *
     * Ejemplo: [[1,2],[3,4]] → [[1,3],[1,4],[2,3],[2,4]]
     */
    private function generarCombinaciones(array $grupos): array
    {
        $grupos = array_values($grupos);
        $result = [[]];

        foreach ($grupos as $grupo) {
            $paso = [];
            foreach ($result as $combo) {
                foreach ($grupo as $id) {
                    $paso[] = array_merge($combo, [$id]);
                }
            }
            $result = $paso;
        }

        return $result;
    }

    /**
     * Sincroniza los ProductoVariante del producto sin destruir registros existentes.
     *
     * Compara el conjunto de SKUs nuevo con el existente:
     *   - SKUs que siguen vigentes → se conservan intactos (mismo id).
     *   - SKUs nuevos que aún no existen → se crean.
     *   - SKUs que ya no corresponden → se eliminan.
     *
     * El SKU de cada combinación es: producto.codigo + '-' + codigo1 + '-' + codigo2 + ...
     */
    private function sincronizarProductoVariantes(Producto $producto, array $grupos): void
    {
        if (empty($grupos)) {
            ProductoVariante::where('producto_id', $producto->id)->delete();
            return;
        }

        $combinaciones = $this->generarCombinaciones($grupos);
        $codigoBase    = strtoupper($producto->codigo);
        $varianteIds   = array_keys($grupos);

        $allIds     = array_unique(array_merge(...array_values($grupos)));
        $valoresMap = ValorVariante::whereIn('id', $allIds)->pluck('codigo', 'id');

        // Prefijo de 1 letra por variante (primera consonante del nombre)
        $nombreMap   = Variante::whereIn('id', $varianteIds)->pluck('nombre', 'id');
        $prefijosMap = [];
        foreach ($varianteIds as $vid) {
            $prefijosMap[$vid] = $this->prefijoDeNombre($nombreMap[$vid] ?? '');
        }

        // Construir el conjunto completo de SKUs que deben existir
        $nuevosSkus = [];
        foreach ($combinaciones as $combo) {
            $partes = [];
            foreach ($combo as $pos => $valorId) {
                $vid      = $varianteIds[$pos];
                $partes[] = $prefijosMap[$vid] . ($valoresMap[$valorId] ?? 'X');
            }
            $nuevosSkus[] = $codigoBase . '-' . implode('-', $partes);
        }

        // SKUs actualmente guardados para este producto
        $existentes = ProductoVariante::where('producto_id', $producto->id)
            ->pluck('sku')
            ->all();

        // Eliminar solo los que ya no están en el nuevo conjunto
        $eliminar = array_diff($existentes, $nuevosSkus);
        if (!empty($eliminar)) {
            ProductoVariante::where('producto_id', $producto->id)
                ->whereIn('sku', $eliminar)
                ->delete();
        }

        // Crear solo los que todavía no existen
        $crear = array_diff($nuevosSkus, $existentes);
        foreach ($crear as $sku) {
            ProductoVariante::create([
                'producto_id' => $producto->id,
                'sku'         => $sku,
            ]);
        }
    }

    /**
     * Punto de entrada único para sincronizar variantes.
     * 1. Resuelve / crea ValorVariante según el JSON del request.
     * 2. Sincroniza la tabla productos_valores_variantes.
     * 3. Regenera las combinaciones en productos_variantes.
     */
    private function sincronizarVariantes(Producto $producto, Request $request): void
    {
        $grupos     = $this->resolverIdsAgrupados($request);
        $todosLosIds = empty($grupos) ? [] : array_unique(array_merge(...array_values($grupos)));

        $producto->valoresVariantes()->sync($todosLosIds);
        $this->sincronizarProductoVariantes($producto, $grupos);
    }

    /**
     * Devuelve la primera consonante (en mayúscula) del nombre de una variante.
     * Ej: "Color" → "C", "Material" → "M", "Ancho" → "N".
     * Se usa como prefijo de 1 carácter en cada segmento del SKU.
     */
    private function prefijoDeNombre(string $nombre): string
    {
        $map = [
            'á' => 'A',
            'é' => 'E',
            'í' => 'I',
            'ó' => 'O',
            'ú' => 'U',
            'ü' => 'U',
            'ñ' => 'N',
            'Á' => 'A',
            'É' => 'E',
            'Í' => 'I',
            'Ó' => 'O',
            'Ú' => 'U',
            'Ü' => 'U',
            'Ñ' => 'N',
        ];
        $s = strtoupper(strtr($nombre, $map));
        return preg_match('/[BCDFGHJKLMNPQRSTVWXYZ]/', $s, $m) ? $m[0] : strtoupper(substr($s, 0, 1));
    }

    private function guardarImagenHero(UploadedFile $imagen): string
    {
        $destino  = public_path('images/heros');
        if (! is_dir($destino)) {
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

        // Almacenamiento temporal en disco público (IMAGE_DISK=public)
        if (config('filesystems.image_disk', 'sftp') === 'public') {
            $carpeta = 'imagenes_producto/' . $producto->id;
            $imagen->storeAs($carpeta, $imagenProducto->nombre_imagen, 'public');
            $imagenProducto->update([
                'ruta' => asset('storage/' . $carpeta . '/' . $imagenProducto->nombre_imagen),
            ]);
            return $imagenProducto;
        }

        $path = $imagen->store('imagenes_producto_temp', 'local');
        $pathAbsoluto = storage_path('app/' . $path);

        $job = new UploadImagen($imagenProducto, $pathAbsoluto);
        Bus::dispatchSync($job);
        $url = $job->getUrl();

        if (empty($url)) {
            $imagenProducto->delete();
            throw new \Exception('No se pudo obtener la URL de la imagen subida: ' . $imagen->getClientOriginalName());
        }

        $imagenProducto->update([
            'ruta' => $url,
        ]);

        return $imagenProducto;
    }
}
