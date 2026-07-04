<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use App\Models\Producto;
use App\Models\ValorVariante;
use App\Services\SkuService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CarritoController extends Controller
{
    /** Tope de unidades por línea del carrito. */
    private const MAX_UNIDADES = 10;

    public function __construct(private SkuService $skuService) {}

    /** Total de unidades del carrito (suma de cantidades; ausente = 1). */
    private function totalUnidades(array $carrito): int
    {
        return array_sum(array_map(fn ($i) => $i['cantidad'] ?? 1, $carrito));
    }

    public function obtener()
    {
        try {
            $carrito = session('carrito', []);

            return response()->json([
                'cantidad' => $this->totalUnidades($carrito),
                'carrito'  => array_values($carrito),
            ]);
        } catch (\Exception $e) {
            Log::error('CarritoController::obtener - Error al leer el carrito', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['cantidad' => 0, 'carrito' => []], 500);
        }
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
            $productoId    = $request->integer('producto_id');
            $cantidadPedida = max(1, $request->integer('cantidad', 1));
            $valorIds      = array_map('intval', $request->input('valor_ids', []));
            sort($valorIds);

            $producto = Producto::select(['id', 'nombre', 'codigo'])
                ->where('activo', true)
                ->findOrFail($productoId);

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

            $key = $productoId . (empty($valorIds) ? '' : '_' . implode('_', $valorIds));

            $carrito = session('carrito', []);

            // Si la línea ya existe, sumamos las unidades (tope MAX_UNIDADES).
            $cantidadExistente = $carrito[$key]['cantidad'] ?? 0;
            $cantidad = min(self::MAX_UNIDADES, $cantidadExistente + $cantidadPedida);

            $carrito[$key] = [
                'key'         => $key,
                'producto_id' => $productoId,
                'nombre'      => $producto->nombre,
                'codigo'      => $codigo,
                'selecciones' => $selecciones,
                'cantidad'    => $cantidad,
            ];
            session(['carrito' => $carrito]);

            return response()->json([
                'ok'       => true,
                'cantidad' => $this->totalUnidades($carrito),
                'carrito'  => array_values($carrito),
            ]);
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
                'cantidad' => $this->totalUnidades($carrito),
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
                'cantidad' => $this->totalUnidades($carrito),
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
                    'cantidad_items' => $this->totalUnidades($carrito),
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
