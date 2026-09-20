<?php

namespace Database\Seeders;

use App\Models\UnidadMedida;
use Illuminate\Database\Seeder;

/**
 * Unidades en las que se cotiza un producto. Es un catálogo cerrado (no hay
 * ABM), así que este seeder es la única fuente de las filas.
 *
 * `requiere_alto` / `requiere_ancho` definen qué campos de medida pide la
 * ficha del producto. Superficie (m²) = requiere ambos.
 *
 * Va por `updateOrCreate` sobre el código: se puede correr en cada deploy sin
 * duplicar filas ni cambiar los ids que ya usan los productos.
 */
class UnidadesMedidaSeeder extends Seeder
{
    public function run(): void
    {
        $unidades = [
            ['codigo' => 'unidades',   'nombre' => 'Unidades',          'simbolo' => 'u',  'requiere_alto' => false, 'requiere_ancho' => false],
            ['codigo' => 'alto',       'nombre' => 'Alto (m)',          'simbolo' => 'm',  'requiere_alto' => true,  'requiere_ancho' => false],
            ['codigo' => 'ancho',      'nombre' => 'Ancho (m)',         'simbolo' => 'm',  'requiere_alto' => false, 'requiere_ancho' => true],
            ['codigo' => 'alto_ancho', 'nombre' => 'Alto × Ancho (m²)', 'simbolo' => 'm²', 'requiere_alto' => true,  'requiere_ancho' => true],
        ];

        foreach ($unidades as $unidad) {
            UnidadMedida::updateOrCreate(['codigo' => $unidad['codigo']], $unidad);
        }
    }
}
