<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\ImagenProducto;
use App\Models\Producto;
use App\Models\ProductoEspecial;
use App\Services\GestorImagenesProducto;
use App\Services\MenuCategorias;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * CRUD de productos a medida.
 *
 * Es el hermano de la parte de productos de UsoInternoController, pero más
 * corto: estos productos no se cotizan, así que no tienen unidad de medida ni
 * variantes con SKU. Todo lo demás —las 5 imágenes de galería, las 5 técnicas,
 * la portada por estrella y la baja lógica por `activo`— funciona igual, y de
 * hecho comparte el mismo servicio de imágenes y la misma tabla.
 */
class UsoInternoEspecialesController extends Controller
{
    public function __construct(private GestorImagenesProducto $gestorImagenes) {}

    public function index(Request $request)
    {
        try {
            $search      = $request->input('search');
            $categoriaId = $request->input('categoria_id');
            $activo      = $request->input('activo');

            $productos = ProductoEspecial::with('categoria')
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

            return view('UsoInterno.Especiales.indexEspeciales', compact('productos', 'categorias'));
        } catch (\Exception $e) {
            Log::error('Error al cargar productos especiales: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json(['error' => 'Error al cargar los productos especiales.'], 500);
            }

            return redirect()->back()->with('error', 'Error al cargar los productos especiales.');
        }
    }

    public function show(string $id)
    {
        try {
            $producto = ProductoEspecial::with([
                'categoria',
                'imagenes',
                'imagenesTecnicas',
            ])->findOrFail($id);

            return view('UsoInterno.Especiales.showEspecial', compact('producto'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error al cargar producto especial (id: ' . $id . '): ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al cargar el producto especial.');
        }
    }

    public function create()
    {
        try {
            $categorias = Categoria::orderBy('nombre')->get();

            return view('UsoInterno.Especiales.createEspecial', compact('categorias'));
        } catch (\Exception $e) {
            Log::error('Error al cargar formulario de creación de producto especial: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al cargar el formulario.');
        }
    }

    public function store(Request $request)
    {
        $request->validate(
            $this->reglas(),
            $this->mensajes()
        );

        DB::beginTransaction();
        try {
            $producto = ProductoEspecial::create([
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
                    $this->gestorImagenes->guardar($producto, $imagen, $idx === $portadaIdx);
                }
            }

            // Técnicas: nunca son portada, de ahí el false en el tercer argumento.
            $tecnicasRequest = array_values(array_filter($request->file('imagenes_tecnicas', [])));
            foreach ($tecnicasRequest as $imagen) {
                $this->gestorImagenes->guardar($producto, $imagen, false, true);
            }

            // Puede ser el primer especial activo de su categoría, y eso hace
            // aparecer la categoría en la rama "a medida" del menú.
            MenuCategorias::olvidar();

            DB::commit();
            return redirect()->route('uso-interno.especiales.show', $producto->id)
                ->with('success', 'Producto especial creado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear producto especial: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error al crear el producto especial.')
                ->withInput();
        }
    }

    public function edit(string $id)
    {
        try {
            $producto   = ProductoEspecial::with(['categoria', 'imagenes', 'imagenesTecnicas'])->findOrFail($id);
            $categorias = Categoria::orderBy('nombre')->get();

            return view('UsoInterno.Especiales.createEspecial', compact('producto', 'categorias'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error al cargar producto especial para edición (id: ' . $id . '): ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al cargar el producto especial para edición.');
        }
    }

    public function update(Request $request, string $id)
    {
        $producto = ProductoEspecial::findOrFail($id);

        $request->validate(
            $this->reglas($producto->id),
            $this->mensajes()
        );

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
                    $this->gestorImagenes->guardar($producto, $imagen, $portadaIdx !== null && $idx === $portadaIdx);
                    $remaining--;
                }
            }

            if ($request->filled('imagenes_tecnicas_eliminar')) {
                ImagenProducto::whereIn('id', $request->imagenes_tecnicas_eliminar)
                    ->where('producto_id', $producto->id)
                    ->where('es_tecnica', true)
                    ->update(['activa' => false]);
            }

            $tecnicasNuevas = array_values(array_filter($request->file('imagenes_tecnicas', [])));
            if (!empty($tecnicasNuevas)) {
                $remainingTecnicas = Producto::MAX_IMAGENES_TECNICAS - $producto->fresh()->imagenesTecnicas()->count();
                foreach ($tecnicasNuevas as $imagen) {
                    if ($remainingTecnicas <= 0) break;
                    $this->gestorImagenes->guardar($producto, $imagen, false, true);
                    $remainingTecnicas--;
                }
            }

            $producto->load('imagenes');
            if ($producto->imagenes->isNotEmpty() && $producto->imagenes->where('es_principal', true)->isEmpty()) {
                $producto->imagenes->first()->update(['es_principal' => true]);
            }

            MenuCategorias::olvidar();

            DB::commit();
            return redirect()->route('uso-interno.especiales.show', $producto->id)
                ->with('success', 'Producto especial actualizado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar producto especial: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error al actualizar el producto especial.')
                ->withInput();
        }
    }

    /** Alta desde el badge de estado del listado. El id llega por el hidden del modal. */
    public function activar(Request $request)
    {
        return $this->cambiarEstado($request, true);
    }

    /** Baja desde el badge de estado del listado. */
    public function desactivar(Request $request)
    {
        return $this->cambiarEstado($request, false);
    }

    private function cambiarEstado(Request $request, bool $activo)
    {
        $request->validate([
            'producto_id' => 'required|exists:productos,id',
        ], [
            'producto_id.required' => 'No se indicó qué producto modificar.',
            'producto_id.exists'   => 'El producto indicado no existe.',
        ]);

        $accion = $activo ? 'alta' : 'baja';

        try {
            $producto = ProductoEspecial::with('categoria')->findOrFail($request->producto_id);

            // Dos pestañas abiertas, o el listado sin refrescar: el estado que
            // vio el usuario al tocar el badge puede no ser el actual.
            if ((bool) $producto->activo === $activo) {
                return redirect()->back()
                    ->with('success', "El producto \"{$producto->nombre}\" ya estaba " . ($activo ? 'activo' : 'inactivo') . '.');
            }

            $producto->update(['activo' => $activo]);

            // La categoría puede quedarse sin especiales activos, o recuperarlos.
            MenuCategorias::olvidar();

            $mensaje = "El producto \"{$producto->nombre}\" se dio de {$accion} correctamente.";

            if ($activo && $producto->categoria && ! $producto->categoria->activo) {
                $mensaje .= " Tené en cuenta que la categoría \"{$producto->categoria->nombre}\" está inactiva, así que todavía no se muestra en el sitio.";
            }

            return redirect()->back()->with('success', $mensaje);
        } catch (\Exception $e) {
            Log::error("Error al dar de {$accion} producto especial (id: " . $request->producto_id . '): ' . $e->getMessage());
            return redirect()->back()->with('error', "Error al dar de {$accion} el producto especial.");
        }
    }

    /**
     * El unique de `codigo` es sobre toda la tabla productos, así que un
     * especial tampoco puede repetir el código de uno estándar.
     */
    private function reglas(?int $ignorarId = null): array
    {
        return [
            'categoria_id'        => 'required|exists:categorias,id',
            'nombre'              => 'required|string|max:255',
            'codigo'              => 'required|string|max:100|unique:productos,codigo' . ($ignorarId ? ',' . $ignorarId : ''),
            'descripcion'         => 'required|string|max:' . Producto::MAX_DESCRIPCION,
            'descripcion_tecnica' => 'nullable|string|max:' . Producto::MAX_DESCRIPCION_TECNICA,
            'activo'              => 'nullable|in:0,1',
            'imagenes'            => 'nullable|array|max:' . Producto::MAX_IMAGENES,
            'imagenes.*'          => GestorImagenesProducto::REGLAS_IMAGEN,
            'imagenes_eliminar'   => 'nullable|array',
            'imagenes_eliminar.*' => 'exists:imagenes_producto,id',
            'imagenes_tecnicas'            => 'nullable|array|max:' . Producto::MAX_IMAGENES_TECNICAS,
            'imagenes_tecnicas.*'          => GestorImagenesProducto::REGLAS_IMAGEN,
            'imagenes_tecnicas_eliminar'   => 'nullable|array',
            'imagenes_tecnicas_eliminar.*' => 'exists:imagenes_producto,id',
            'imagen_portada'      => 'nullable|string',
        ];
    }

    private function mensajes(): array
    {
        return [
            'categoria_id.required' => 'La categoría es obligatoria.',
            'categoria_id.exists'   => 'La categoría seleccionada no existe.',
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
        ];
    }
}
