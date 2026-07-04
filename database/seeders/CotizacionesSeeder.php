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
        $productos = Producto::get(['id', 'nombre', 'codigo']);
        $pool = $productos->isNotEmpty() ? $productos : collect([
            (object) ['id' => 101, 'nombre' => 'Ventana corrediza',  'codigo' => 'VC-001'],
            (object) ['id' => 102, 'nombre' => 'Puerta placa',       'codigo' => 'PP-002'],
            (object) ['id' => 103, 'nombre' => 'Mampara de ducha',   'codigo' => 'MD-003'],
            (object) ['id' => 104, 'nombre' => 'Cortina de enrollar', 'codigo' => 'CE-004'],
        ]);

        // Cantidad de cotizaciones por mes (peso: varios 0, algunos altos)
        $pesos = [0, 0, 1, 2, 3, 3, 4, 5, 6, 8, 11];

        for ($i = 16; $i >= 0; $i--) {
            $mesBase  = now()->startOfMonth()->subMonths($i);
            $cantidad = fake()->randomElement($pesos);

            for ($j = 0; $j < $cantidad; $j++) {
                $fecha = $mesBase->copy()
                    ->addDays(rand(0, $mesBase->daysInMonth - 1))
                    ->addHours(rand(8, 20))
                    ->addMinutes(rand(0, 59));

                $items = $pool->random(min(rand(1, 4), $pool->count()))
                    ->map(fn ($p) => [
                        'key'         => (string) $p->id,
                        'producto_id' => $p->id,
                        'nombre'      => $p->nombre,
                        'codigo'      => $p->codigo,
                        'selecciones' => [],
                    ])
                    ->values()
                    ->all();

                $c = new Cotizacion();
                $c->cantidad_items = count($items);
                $c->items          = $items;
                $c->created_at     = $fecha;
                $c->updated_at     = $fecha;
                $c->save();
            }
        }
    }
}
