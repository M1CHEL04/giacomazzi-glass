<?php

namespace App\Services;

use App\Models\ImagenProducto;
use App\Models\Producto;
use Illuminate\Http\Request;

/**
 * Aplica al producto lo que el formulario dice sobre sus imágenes: bajas
 * lógicas, subidas nuevas y el orden de la galería.
 *
 * Está tipado a Producto, así que también recibe ProductoEspecial: los dos
 * comparten la tabla imagenes_producto y el mismo formulario.
 *
 * Existe porque este circuito estaba duplicado literal en cuatro métodos (store
 * y update de UsoInternoController y de UsoInternoEspecialesController) y ya
 * había empezado a divergir. Mientras siga duplicado, cada arreglo hay que
 * acordarse de hacerlo dos veces.
 *
 * Es el **único** escritor de `orden` y de `es_principal`. Eso es lo que
 * sostiene el invariante: entre las filas de galería activas de un producto,
 * `orden` es denso (0..n-1) y la de orden 0 —y sólo ella— tiene es_principal.
 * Si el flag lo escribiera además GestorImagenesProducto habría dos fuentes de
 * verdad, y la que quedara vieja mandaría la portada equivocada al catálogo.
 */
class SincronizadorImagenesProducto
{
    public function __construct(private GestorImagenesProducto $gestor) {}

    /**
     * Galería: borra lo marcado, sube lo nuevo y deja orden y portada
     * normalizados.
     *
     * Sirve igual para el alta (sin filas previas ni borrados) que para la
     * edición: en el alta el cupo arranca completo y no hay nada que borrar, y
     * el resto del recorrido es el mismo.
     */
    public function sincronizarGaleria(Producto $producto, Request $request): void
    {
        if ($request->filled('imagenes_eliminar')) {
            // El scope por producto_id y es_tecnica es la guarda de pertenencia:
            // los ids llegan del cliente, así que nunca se tocan filas de otro
            // producto ni una técnica que entre por el campo de galería.
            ImagenProducto::whereIn('id', $request->imagenes_eliminar)
                ->where('producto_id', $producto->id)
                ->where('es_tecnica', false)
                ->update(['activa' => false, 'es_principal' => false]);
        }

        // La posición de cada archivo en este array es la que el manifiesto
        // llama `nueva:<idx>`: el formulario la calcula sobre los inputs en
        // orden del DOM y acá array_filter la preserva.
        $nuevas    = array_values(array_filter($request->file('imagenes', [])));
        $idsNuevas = [];

        if (!empty($nuevas)) {
            // Cupo sobre las activas: las eliminadas siguen en la tabla con
            // activa = false y no ocupan lugar.
            $restantes = Producto::MAX_IMAGENES - $producto->fresh()->imagenes()->count();
            foreach ($nuevas as $idx => $imagen) {
                if ($restantes <= 0) break;
                $idsNuevas[$idx] = $this->gestor->guardar($producto, $imagen)->id;
                $restantes--;
            }
        }

        // Incondicional: es lo que hace que el invariante valga en todos los
        // caminos, incluso en un guardado que no tocó ninguna imagen.
        $this->normalizarOrden(
            $producto,
            $request->input('imagenes_orden'),
            $idsNuevas,
            $request->input('imagen_portada', '')
        );
    }

    /**
     * Técnicas: mismo circuito, con su propio cupo y sin portada ni orden de
     * por medio. Van por orden de carga.
     */
    public function sincronizarTecnicas(Producto $producto, Request $request): void
    {
        if ($request->filled('imagenes_tecnicas_eliminar')) {
            ImagenProducto::whereIn('id', $request->imagenes_tecnicas_eliminar)
                ->where('producto_id', $producto->id)
                ->where('es_tecnica', true)
                ->update(['activa' => false]);
        }

        $nuevas = array_values(array_filter($request->file('imagenes_tecnicas', [])));
        if (empty($nuevas)) return;

        $restantes = Producto::MAX_IMAGENES_TECNICAS - $producto->fresh()->imagenesTecnicas()->count();
        foreach ($nuevas as $imagen) {
            if ($restantes <= 0) break;
            $this->gestor->guardar($producto, $imagen, true);
            $restantes--;
        }
    }

    /**
     * Renumera la galería activa a 0..n-1 y deja es_principal en la de orden 0.
     *
     * El orden deseado llega en `$manifiesto` (`imagenes_orden`). Si no llega
     * uno usable —JS viejo en caché, o un cliente que sólo conoce el campo
     * anterior— se cae al `imagen_portada` de siempre, que sólo dice cuál va
     * primera. En los dos casos se renumera igual, así que el invariante no
     * depende de que el formulario haya mandado algo.
     *
     * Las filas que el manifiesto no menciona no se pierden: van al final
     * conservando el orden que ya tenían.
     */
    private function normalizarOrden(
        Producto $producto,
        ?string $manifiesto,
        array $idsNuevas,
        ?string $portadaLegacy
    ): void {
        // Por la relación esto ya viene filtrado a activas y no técnicas, y
        // ordenado por orden + id.
        $filas = $producto->imagenes()->get();
        if ($filas->isEmpty()) {
            // Producto sin galería: no hay a quién darle la portada, y está
            // bien. Las vistas ya manejan el caso de cero imágenes.
            return;
        }

        $idsValidos = $filas->pluck('id')->all();

        $deseado = $this->parsearManifiesto($manifiesto, $idsValidos, $idsNuevas);

        if ($deseado === []) {
            $portadaId = $this->resolverToken($portadaLegacy, $idsValidos, $idsNuevas);
            $deseado   = $portadaId ? [$portadaId] : [];
        }

        // Lo mencionado primero, en el orden pedido; el resto detrás, como estaba.
        $final = array_merge($deseado, array_values(array_diff($idsValidos, $deseado)));

        foreach ($final as $posicion => $id) {
            $fila = $filas->firstWhere('id', $id);
            $esPortada = $posicion === 0;

            // Saltear las que ya están donde tienen que estar: en el caso normal
            // —guardar sin haber movido nada— esto deja el método en cero
            // escrituras.
            if ($fila->orden === $posicion && $fila->es_principal === $esPortada) {
                continue;
            }

            $fila->update(['orden' => $posicion, 'es_principal' => $esPortada]);
        }
    }

    /**
     * Convierte `existente:41,nueva:0,existente:38` en una lista de ids, en ese
     * orden, sin duplicados y sin nada que no pertenezca a la galería activa de
     * este producto.
     *
     * Descarta en silencio en lugar de fallar: el campo lo escribe el JS del
     * formulario, y un token raro no puede costarle al usuario el guardado
     * entero de un producto. Lo peor que pasa es que el orden quede como estaba.
     */
    private function parsearManifiesto(?string $raw, array $idsValidos, array $idsNuevas): array
    {
        if (!$raw) return [];

        $ids = [];
        foreach (explode(',', $raw) as $token) {
            $id = $this->resolverToken(trim($token), $idsValidos, $idsNuevas);
            if ($id === null || in_array($id, $ids, true)) continue;
            $ids[] = $id;
            if (count($ids) >= Producto::MAX_IMAGENES) break;
        }

        return $ids;
    }

    /**
     * Resuelve un token a un id de fila, o null si no se puede.
     *
     * `existente:<id>` es una fila ya guardada; `nueva:<idx>` es la posición de
     * un archivo de este request, que sólo tiene id si llegó a subirse (el cupo
     * puede haber descartado los últimos).
     *
     * La verificación contra $idsValidos al final es la que hace segura toda la
     * rutina: un id de otro producto, de una técnica o de una fila ya dada de
     * baja no está en esa lista y se cae acá.
     */
    private function resolverToken(?string $token, array $idsValidos, array $idsNuevas): ?int
    {
        if (!$token) return null;

        if (str_starts_with($token, 'existente:')) {
            $id = (int) substr($token, 10);
        } elseif (str_starts_with($token, 'nueva:')) {
            $id = $idsNuevas[(int) substr($token, 6)] ?? null;
        } else {
            return null;
        }

        return ($id && in_array($id, $idsValidos, true)) ? $id : null;
    }
}
