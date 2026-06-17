<?php

namespace App\Services;

use App\Models\Producto;
use App\Models\ProductoVariante;
use App\Models\ValorVariante;
use App\Models\Variante;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SkuService
{
    /**
     * Busca el SKU exacto que corresponde a la combinación de ValorVariante seleccionada.
     * Compara segmentos ordenados para ser independiente del orden de selección.
     */
    public function buscarSku(int $productoId, Collection $valores): ?string
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
            $partes = array_slice(explode('-', $pvSku), 1);
            sort($partes);
            if ($partes === $segmentos) {
                return $pvSku;
            }
        }

        return null;
    }

    /**
     * Sincroniza los ValorVariante asociados al producto y regenera sus SKUs.
     * Punto de entrada único desde los controladores.
     */
    public function sincronizarVariantes(Producto $producto, Request $request): void
    {
        $grupos      = $this->resolverIdsAgrupados($request);
        $todosLosIds = empty($grupos) ? [] : array_unique(array_merge(...array_values($grupos)));

        $producto->valoresVariantes()->sync($todosLosIds);
        $this->sincronizarProductoVariantes($producto, $grupos);
    }

    /**
     * Primera consonante (en mayúscula) del nombre de una variante.
     * Se usa como prefijo de 1 carácter en cada segmento del SKU.
     * Ej: "Color" → "C", "Material" → "M", "Ancho" → "N".
     */
    public function prefijoDeNombre(string $nombre): string
    {
        $map = [
            'á' => 'A', 'é' => 'E', 'í' => 'I', 'ó' => 'O', 'ú' => 'U',
            'ü' => 'U', 'ñ' => 'N', 'Á' => 'A', 'É' => 'E', 'Í' => 'I',
            'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U', 'Ñ' => 'N',
        ];
        $s = strtoupper(strtr($nombre, $map));
        return preg_match('/[BCDFGHJKLMNPQRSTVWXYZ]/', $s, $m) ? $m[0] : strtoupper(substr($s, 0, 1));
    }

    // ── Helpers privados ──────────────────────────────────────────────────────

    /**
     * Resuelve el JSON de variantes en un mapa [variante_id => [valor_variante_id, ...]].
     * Crea Variante/ValorVariante si no existen y actualiza el `codigo` del valor si cambió.
     */
    private function resolverIdsAgrupados(Request $request): array
    {
        $variantes = json_decode($request->input('variantes_json', '[]'), true) ?? [];
        $grupos    = [];

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
     * Recibe [variante_id => [id1, id2, ...]] y devuelve todas las combinaciones posibles.
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
     * Sincroniza los ProductoVariante (SKUs) sin destruir registros existentes.
     * SKUs que siguen vigentes se conservan, nuevos se crean, obsoletos se eliminan.
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

        $nombreMap   = Variante::whereIn('id', $varianteIds)->pluck('nombre', 'id');
        $prefijosMap = [];
        foreach ($varianteIds as $vid) {
            $prefijosMap[$vid] = $this->prefijoDeNombre($nombreMap[$vid] ?? '');
        }

        $nuevosSkus = [];
        foreach ($combinaciones as $combo) {
            $partes = [];
            foreach ($combo as $pos => $valorId) {
                $vid      = $varianteIds[$pos];
                $partes[] = $prefijosMap[$vid] . ($valoresMap[$valorId] ?? 'X');
            }
            $nuevosSkus[] = $codigoBase . '-' . implode('-', $partes);
        }

        $existentes = ProductoVariante::where('producto_id', $producto->id)->pluck('sku')->all();

        $eliminar = array_diff($existentes, $nuevosSkus);
        if (!empty($eliminar)) {
            ProductoVariante::where('producto_id', $producto->id)
                ->whereIn('sku', $eliminar)
                ->delete();
        }

        $crear = array_diff($nuevosSkus, $existentes);
        foreach ($crear as $sku) {
            ProductoVariante::create(['producto_id' => $producto->id, 'sku' => $sku]);
        }
    }
}
