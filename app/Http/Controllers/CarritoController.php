<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use App\Models\Producto;
use App\Models\ValorVariante;
use App\Services\SkuService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CarritoController extends Controller
{
    /** Tope de piezas por línea del carrito. */
    private const MAX_UNIDADES = 10;

    /** Tope de una medida en metros (guarda contra errores de tipeo). */
    private const MAX_METROS = 100;

    /** Decimales admitidos en las medidas (ej. 5,546 m). */
    private const DECIMALES_METROS = 3;

    public function __construct(private SkuService $skuService) {}

    /**
     * Cantidad de líneas del carrito. Es lo que muestra el badge y lo que se
     * guarda en cotizaciones.cantidad_items: con unidades y metros conviviendo,
     * sumar las cantidades no representaría nada.
     */
    private function totalLineas(array $carrito): int
    {
        return count($carrito);
    }

    public function obtener()
    {
        try {
            $carrito = session('carrito', []);

            return response()->json([
                'cantidad' => $this->totalLineas($carrito),
                'carrito'  => array_values($carrito),
            ]);
        } catch (\Exception $e) {
            Log::error('CarritoController::obtener - Error al leer el carrito', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['cantidad' => 0, 'carrito' => []], 500);
        }
    }

    /**
     * Valida alto/ancho según la unidad del producto y los normaliza a metros
     * con DECIMALES_METROS decimales. Devuelve [alto, ancho], null cuando la
     * unidad no pide esa medida.
     *
     * Acepta coma o punto decimal: el input de la ficha es de texto libre.
     */
    private function validarMedidas(Request $request, Producto $producto): array
    {
        $unidad        = $producto->unidad;
        $requiereAlto  = (bool) ($unidad?->requiere_alto);
        $requiereAncho = (bool) ($unidad?->requiere_ancho);

        $normalizar = fn ($v) => is_string($v) ? str_replace(',', '.', trim($v)) : $v;

        $datos = [
            'alto'  => $normalizar($request->input('alto')),
            'ancho' => $normalizar($request->input('ancho')),
        ];

        $reglaMedida = 'required|numeric|min:0.001|max:' . self::MAX_METROS;

        Validator::make($datos, [
            'alto'  => $requiereAlto ? $reglaMedida : 'nullable|prohibited',
            'ancho' => $requiereAncho ? $reglaMedida : 'nullable|prohibited',
        ], [
            'alto.required'  => 'El alto es obligatorio para este producto.',
            'ancho.required' => 'El ancho es obligatorio para este producto.',
            'alto.prohibited'  => 'Este producto no se cotiza por alto.',
            'ancho.prohibited' => 'Este producto no se cotiza por ancho.',
            'numeric'          => 'La medida debe ser un número en metros.',
            'min'              => 'La medida debe ser mayor a 0.',
            'max'              => 'La medida no puede superar los :max metros.',
        ])->validate();

        return [
            $requiereAlto ? round((float) $datos['alto'], self::DECIMALES_METROS) : null,
            $requiereAncho ? round((float) $datos['ancho'], self::DECIMALES_METROS) : null,
        ];
    }

    /**
     * Sufijo determinístico de la clave de línea a partir de las medidas.
     * Vacío cuando el producto se cotiza por unidades.
     */
    private function sufijoMedidas(?float $alto, ?float $ancho): string
    {
        if ($alto === null && $ancho === null) {
            return '';
        }

        $fmt = fn (?float $v) => $v === null ? '' : number_format($v, self::DECIMALES_METROS, '.', '');

        return '_' . $fmt($alto) . 'x' . $fmt($ancho);
    }

    public function agregar(Request $request)
    {
        $request->validate([
            'producto_id' => 'required|integer|exists:productos,id',
            'valor_ids'   => 'array',
            'valor_ids.*' => 'integer|exists:valores_variante,id',
            'cantidad'    => 'nullable|integer|min:1|max:' . self::MAX_UNIDADES,
        ]);

        try {
            $productoId     = $request->integer('producto_id');
            $cantidadPedida = max(1, $request->integer('cantidad', 1));
            $valorIds       = array_map('intval', $request->input('valor_ids', []));
            sort($valorIds);

            $producto = Producto::select(['id', 'nombre', 'codigo', 'unidad_id'])
                ->with('unidad')
                ->where('activo', true)
                ->findOrFail($productoId);

            // Las medidas dependen de la unidad del producto, así que recién se
            // pueden validar una vez que sabemos cuál es.
            [$alto, $ancho] = $this->validarMedidas($request, $producto);

            $valores = ValorVariante::with('variante:id,nombre')
                ->whereIn('id', $valorIds)
                ->get();

            $selecciones = [];
            foreach ($valores as $v) {
                $selecciones[] = [
                    'variante' => $v->variante?->nombre ?? '—',
                    'valor'    => $v->valor,
                ];
            }

            $sku    = $this->skuService->buscarSku($productoId, $valores);
            $codigo = $sku ?? $producto->codigo ?? '';

            // Las medidas forman parte de la clave: dos medidas distintas del
            // mismo producto son dos líneas separadas del carrito.
            $key = $productoId
                . (empty($valorIds) ? '' : '_' . implode('_', $valorIds))
                . $this->sufijoMedidas($alto, $ancho);

            $carrito = session('carrito', []);

            // Si la línea ya existe (mismo producto, variantes y medidas),
            // sumamos las piezas (tope MAX_UNIDADES).
            $cantidadExistente = $carrito[$key]['cantidad'] ?? 0;
            $cantidad = min(self::MAX_UNIDADES, $cantidadExistente + $cantidadPedida);

            $carrito[$key] = [
                'key'          => $key,
                'producto_id'  => $productoId,
                'nombre'       => $producto->nombre,
                'codigo'       => $codigo,
                'selecciones'  => $selecciones,
                'cantidad'     => $cantidad,
                'unidad'       => $producto->unidad?->codigo ?? 'unidades',
                'unidad_label' => $producto->unidad?->nombre ?? 'Unidades',
                'simbolo'      => $producto->unidad?->simbolo ?? 'u',
                'alto'         => $alto,
                'ancho'        => $ancho,
                'm2'           => ($alto !== null && $ancho !== null)
                    ? round($alto * $ancho, self::DECIMALES_METROS)
                    : null,
            ];
            session(['carrito' => $carrito]);

            return response()->json([
                'ok'       => true,
                'cantidad' => $this->totalLineas($carrito),
                'carrito'  => array_values($carrito),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('CarritoController::agregar - Error al agregar producto al carrito', [
                'producto_id' => $request->input('producto_id'),
                'valor_ids'   => $request->input('valor_ids'),
                'error'       => $e->getMessage(),
            ]);

            return response()->json(['ok' => false, 'message' => 'No se pudo agregar el producto al carrito.'], 500);
        }
    }

    public function eliminar(Request $request)
    {
        $request->validate(['key' => 'required|string']);

        try {
            $carrito = session('carrito', []);
            unset($carrito[$request->input('key')]);
            session(['carrito' => $carrito]);

            return response()->json([
                'ok'       => true,
                'cantidad' => $this->totalLineas($carrito),
                'carrito'  => array_values($carrito),
            ]);
        } catch (\Exception $e) {
            Log::error('CarritoController::eliminar - Error al eliminar ítem del carrito', [
                'key'   => $request->input('key'),
                'error' => $e->getMessage(),
            ]);

            return response()->json(['ok' => false, 'message' => 'No se pudo eliminar el producto del carrito.'], 500);
        }
    }

    /**
     * Setea la cantidad ABSOLUTA de una línea del carrito (stepper del panel).
     * A diferencia de agregar (que suma), acá se fija el valor exacto.
     */
    public function actualizarCantidad(Request $request)
    {
        $request->validate([
            'key'      => 'required|string',
            'cantidad' => 'required|integer|min:1|max:' . self::MAX_UNIDADES,
        ]);

        try {
            $carrito = session('carrito', []);
            $key     = $request->input('key');

            if (isset($carrito[$key])) {
                $carrito[$key]['cantidad'] = $request->integer('cantidad');
                session(['carrito' => $carrito]);
            }

            return response()->json([
                'ok'       => true,
                'cantidad' => $this->totalLineas($carrito),
                'carrito'  => array_values($carrito),
            ]);
        } catch (\Exception $e) {
            Log::error('CarritoController::actualizarCantidad - Error al actualizar la cantidad', [
                'key'   => $request->input('key'),
                'error' => $e->getMessage(),
            ]);

            return response()->json(['ok' => false, 'message' => 'No se pudo actualizar la cantidad.'], 500);
        }
    }

    public function vaciar()
    {
        try {
            session(['carrito' => []]);

            return response()->json([
                'ok'       => true,
                'cantidad' => 0,
                'carrito'  => [],
            ]);
        } catch (\Exception $e) {
            Log::error('CarritoController::vaciar - Error al vaciar el carrito', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['ok' => false, 'message' => 'No se pudo vaciar el carrito.'], 500);
        }
    }

    /**
     * Registra una solicitud de cotización (clic en "Solicitar cotización por
     * WhatsApp") con un snapshot del carrito, y luego lo vacía.
     *
     * No garantiza que el mensaje se haya enviado — mide la intención de consulta.
     * Si el registro falla, igual se vacía el carrito para no romper la UX.
     */
    public function cotizar()
    {
        $carrito = session('carrito', []);

        if (! empty($carrito)) {
            try {
                Cotizacion::create([
                    'cantidad_items' => $this->totalLineas($carrito),
                    'items'          => array_values($carrito),
                ]);
            } catch (\Exception $e) {
                Log::error('CarritoController::cotizar - No se pudo registrar la cotización', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        session(['carrito' => []]);

        return response()->json([
            'ok'       => true,
            'cantidad' => 0,
            'carrito'  => [],
        ]);
    }

}
