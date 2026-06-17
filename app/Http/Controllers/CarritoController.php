<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\ValorVariante;
use App\Services\SkuService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CarritoController extends Controller
{
    public function __construct(private SkuService $skuService) {}

    public function obtener()
    {
        try {
            $carrito = session('carrito', []);

            return response()->json([
                'cantidad' => count($carrito),
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
        ]);

        try {
            $productoId = $request->integer('producto_id');
            $valorIds   = array_map('intval', $request->input('valor_ids', []));
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
            $carrito[$key] = [
                'key'         => $key,
                'producto_id' => $productoId,
                'nombre'      => $producto->nombre,
                'codigo'      => $codigo,
                'selecciones' => $selecciones,
            ];
            session(['carrito' => $carrito]);

            return response()->json([
                'ok'       => true,
                'cantidad' => count($carrito),
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
                'cantidad' => count($carrito),
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

}
