<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\ImagenProducto;
use App\Models\Producto;
use App\Models\ValorVariante;
use App\Models\Variante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UsoInternoController extends Controller
{
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
            'nombre' => 'required|string|max:255|unique:categorias,nombre',
        ], [
            'nombre.required' => 'El nombre de la categoria es obligatorio.',
            'nombre.string' => 'El nombre de la categoria debe ser un texto.',
            'nombre.max' => 'El nombre de la categoria no puede superar los 255 caracteres.',
            'nombre.unique' => 'Ya existe una categoria con ese nombre.',
        ]);

        try {

            Categoria::create([
                'nombre' => $request->nombre,
                'activo' => true,
            ]);

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
            'nombre' => 'required|string|max:255|unique:categorias,nombre,' . $categoria->id,
            'activo' => 'nullable|in:0,1',
        ], [
            'nombre.required' => 'El nombre de la categoria es obligatorio.',
            'nombre.string' => 'El nombre de la categoria debe ser un texto.',
            'nombre.max' => 'El nombre de la categoria no puede superar los 255 caracteres.',
            'nombre.unique' => 'Ya existe una categoria con ese nombre.',
        ]);

        try {
            $categoria->update([
                'nombre' => $request->nombre,
                'activo' => (bool) $request->input('activo', 0),
            ]);

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

            // Imágenes
            /* if ($request->hasFile('imagenes')) {
                foreach ($request->file('imagenes') as $imagen) {
                    $ruta = $imagen->store('productos', 'public');
                    ImagenProducto::create([
                        'producto_id'   => $producto->id,
                        'ruta'          => $ruta,
                        'nombre_imagen' => $imagen->getClientOriginalName(),
                    ]);
                }
            } */

            // Variantes
            $this->procesarVariantes($producto, $request);

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
            'tipo'             => 'existente',
            'valor_variante_id' => $vv->id,
            'display'          => ($vv->variante->nombre ?? '?') . ': ' . $vv->valor,
            '_lid'             => (string) Str::uuid(),
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

            // Eliminar imágenes marcadas (verificando que pertenecen al producto)
            if ($request->filled('imagenes_eliminar')) {
                $imgs = ImagenProducto::whereIn('id', $request->imagenes_eliminar)
                    ->where('producto_id', $producto->id)->get();
                foreach ($imgs as $img) {
                    Storage::disk('public')->delete($img->ruta);
                    $img->delete();
                }
            }

            // Agregar nuevas imágenes
            /*  if ($request->hasFile('imagenes')) {
                $remaining = 5 - $producto->imagenes()->count();
                foreach ($request->file('imagenes') as $imagen) {
                    if ($remaining <= 0) break;
                    $ruta = $imagen->store('productos', 'public');
                    ImagenProducto::create([
                        'producto_id'   => $producto->id,
                        'ruta'          => $ruta,
                        'nombre_imagen' => $imagen->getClientOriginalName(),
                    ]);
                    $remaining--;
                }
            } */

            // Variantes: diff eficiente — adjunta nuevas, desasocia las quitadas, no toca las que no cambiaron
            $producto->valoresVariantes()->sync($this->resolverIdsVariantes($request));

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
                'id'     => $v->id,
                'nombre' => $v->nombre,
                'valores' => $v->valores->map(fn($vl) => ['id' => $vl->id, 'valor' => $vl->valor]),
            ])
        );
    }

    /**
     * Resuelve los IDs de ValorVariante a partir del JSON del request,
     * creando Variante/ValorVariante nuevos si corresponde.
     */
    private function resolverIdsVariantes(Request $request): array
    {
        $variantes = json_decode($request->input('variantes_json', '[]'), true) ?? [];
        $ids = [];

        foreach ($variantes as $item) {
            $valorVarianteId = null;

            if ($item['tipo'] === 'existente') {
                $valorVarianteId = $item['valor_variante_id'];
            } elseif ($item['tipo'] === 'nuevo_valor') {
                $vv = ValorVariante::firstOrCreate([
                    'variante_id' => $item['variante_id'],
                    'valor'       => $item['valor'],
                ]);
                $valorVarianteId = $vv->id;
            } elseif ($item['tipo'] === 'nueva_variante') {
                $variante = Variante::firstOrCreate(['nombre' => $item['variante_nombre']]);
                $variante->categorias()->syncWithoutDetaching([$request->categoria_id]);
                $vv = ValorVariante::firstOrCreate([
                    'variante_id' => $variante->id,
                    'valor'       => $item['valor'],
                ]);
                $valorVarianteId = $vv->id;
            }

            if ($valorVarianteId) {
                $ids[] = $valorVarianteId;
            }
        }

        return $ids;
    }

    private function procesarVariantes(Producto $producto, Request $request): void
    {
        $ids = $this->resolverIdsVariantes($request);
        if (!empty($ids)) {
            $producto->valoresVariantes()->syncWithoutDetaching($ids);
        }
    }
}
