<?php

namespace Database\Seeders;

use App\Models\Cotizacion;
use App\Models\Producto;
use Illuminate\Database\Seeder;

class CotizacionesSeeder extends Seeder
{
    /**
     * Datos de prueba para el gráfico "Cotizaciones por mes".
     * Reparte cotizaciones en los últimos ~16 meses (cruza un año), con meses
     * en 0 y un mes pico, para ver la estética del gráfico y el scroll.
     *
     * Correr con: php artisan db:seed --class=CotizacionesSeeder
     */
    public function run(): void
    {
        // Usa productos reales para el snapshot; si no hay, usa un fallback.
        $productos = Producto::with('unidad')->get(['id', 'nombre', 'codigo', 'unidad_id']);
        $pool = $productos->isNotEmpty() ? $productos : collect([
            (object) ['id' => 101, 'nombre' => 'Ventana corrediza',  'codigo' => 'VC-001', 'unidad' => null],
            (object) ['id' => 102, 'nombre' => 'Puerta placa',       'codigo' => 'PP-002', 'unidad' => null],
            (object) ['id' => 103, 'nombre' => 'Mampara de ducha',   'codigo' => 'MD-003', 'unidad' => null],
            (object) ['id' => 104, 'nombre' => 'Cortina de enrollar', 'codigo' => 'CE-004', 'unidad' => null],
        ]);

        // Cantidad de cotizaciones por mes (peso: varios 0, algunos altos)
        $pesos = [0, 0, 1, 2, 3, 3, 4, 5, 6, 8, 11];

        for ($i = 16; $i >= 0; $i--) {
            $mesBase  = now()->startOfMonth()->subMonths($i);
            $cantidad = $pesos[array_rand($pesos)];

            for ($j = 0; $j < $cantidad; $j++) {
                $fecha = $mesBase->copy()
                    ->addDays(rand(0, $mesBase->daysInMonth - 1))
                    ->addHours(rand(8, 20))
                    ->addMinutes(rand(0, 59));

                $items = $pool->random(min(rand(1, 4), $pool->count()))
                    ->map(function ($p) {
                        // Misma forma que arma CarritoController::agregar.
                        $alto  = $p->unidad?->requiere_alto ? round(rand(500, 3000) / 1000, 3) : null;
                        $ancho = $p->unidad?->requiere_ancho ? round(rand(500, 3000) / 1000, 3) : null;

                        return [
                            'key'          => (string) $p->id,
                            'producto_id'  => $p->id,
                            'nombre'       => $p->nombre,
                            'codigo'       => $p->codigo,
                            'selecciones'  => [],
                            'cantidad'     => rand(1, 3),
                            'unidad'       => $p->unidad?->codigo ?? 'unidades',
                            'unidad_label' => $p->unidad?->nombre ?? 'Unidades',
                            'simbolo'      => $p->unidad?->simbolo ?? 'u',
                            'alto'         => $alto,
                            'ancho'        => $ancho,
                            'm2'           => ($alto !== null && $ancho !== null) ? round($alto * $ancho, 3) : null,
                        ];
                    })
                    ->values()
                    ->all();

                $c = new Cotizacion();
                // Igual que CarritoController::totalLineas: una fila por línea.
                $c->cantidad_items = count($items);
                $c->items          = $items;
                $c->created_at     = $fecha;
                $c->updated_at     = $fecha;
                $c->save();
            }
        }
    }
}
