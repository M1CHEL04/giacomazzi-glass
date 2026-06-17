<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\ProductoVariante;
use App\Models\ValorVariante;
use Illuminate\Http\Request;

class CarritoController extends Controller
{
    public function obtener()
    {
        $carrito = session('carrito', []);

        return response()->json([
            'cantidad' => count($carrito),
            'carrito'  => array_values($carrito),
        ]);
    }

    public function agregar(Request $request)
    {
        $request->validate([
            'producto_id' => 'required|integer|exists:productos,id',
            'valor_ids'   => 'array',
            'valor_ids.*' => 'integer|exists:valores_variante,id',
        ]);

        $productoId = $request->integer('producto_id');
        $valorIds   = array_map('intval', $request->input('valor_ids', []));
        sort($valorIds);

        $producto = Producto::select(['id', 'nombre', 'codigo'])
            ->where('activo', true)
            ->findOrFail($productoId);

        $valores     = ValorVariante::with('variante:id,nombre')
            ->whereIn('id', $valorIds)
            ->get();

        $selecciones = [];
        foreach ($valores as $v) {
            $selecciones[] = [
                'variante' => $v->variante?->nombre ?? '—',
                'valor'    => $v->valor,
            ];
        }

        // Buscar el SKU específico de la combinación seleccionada
        $sku    = $this->buscarSku($productoId, $valores);
        $codigo = $sku ?? $producto->codigo ?? '';

        // Clave única: producto_id + valor_ids ordenados
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
    }

    public function eliminar(Request $request)
    {
        $request->validate(['key' => 'required|string']);

        $carrito = session('carrito', []);
        unset($carrito[$request->input('key')]);
        session(['carrito' => $carrito]);

        return response()->json([
            'ok'       => true,
            'cantidad' => count($carrito),
            'carrito'  => array_values($carrito),
        ]);
    }

    public function vaciar()
    {
        session(['carrito' => []]);

        return response()->json([
            'ok'       => true,
            'cantidad' => 0,
            'carrito'  => [],
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Busca en productos_variantes el SKU que corresponde exactamente
     * a la combinación de ValorVariante seleccionada.
     * Compara los segmentos del SKU (prefijo + codigo) sin importar el orden.
     */
    private function buscarSku(int $productoId, \Illuminate\Support\Collection $valores): ?string
    {
        if ($valores->isEmpty()) {
            return null;
        }

        $segmentos = $valores->map(
            fn($v) => $this->prefijoDeNombre($v->variante?->nombre ?? '') . strtoupper($v->codigo ?? 'X')
        )->all();
        sort($segmentos);

        $pvs = ProductoVariante::where('producto_id', $productoId)->pluck('sku')->all();
        foreach ($pvs as $pvSku) {
            // El primer segmento es el código del producto; los siguientes son las variantes
            $partes = array_slice(explode('-', $pvSku), 1);
            sort($partes);
            if ($partes === $segmentos) {
                return $pvSku;
            }
        }

        return null;
    }

    /**
     * Primera consonante (en mayúscula) del nombre de una variante.
     * Replica la lógica de UsoInternoController para que los segmentos coincidan.
     */
    private function prefijoDeNombre(string $nombre): string
    {
        $map = [
            'á' => 'A', 'é' => 'E', 'í' => 'I', 'ó' => 'O', 'ú' => 'U',
            'ü' => 'U', 'ñ' => 'N', 'Á' => 'A', 'É' => 'E', 'Í' => 'I',
            'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U', 'Ñ' => 'N',
        ];
        $s = strtoupper(strtr($nombre, $map));
        return preg_match('/[BCDFGHJKLMNPQRSTVWXYZ]/', $s, $m) ? $m[0] : strtoupper(substr($s, 0, 1));
    }
}
