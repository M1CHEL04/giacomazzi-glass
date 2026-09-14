<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\ProductoEspecial;
use Illuminate\Database\Seeder;

class ProductosEspecialesSeeder extends Seeder
{
    public function run(): void
    {
        $especiales = [
            ['Mamparas', 'GGE-0001', 'Mampara a medida en L', 'Diseño a medida para baños con geometría irregular, cierre hermético perimetral.'],
            ['Puertas', 'GGE-0002', 'Puerta de aluminio a medida', 'Fabricada según plano del cliente, incluye herrajes y vidrio a elección.'],
            ['Ventanas', 'GGE-0003', 'Ventanal fijo a medida', 'Paño único de gran formato, cálculo estructural incluido.'],
            ['Cortinas', 'GGE-0004', 'Cortina de enrollar a medida', 'Ancho y alto según vano del cliente, accionamiento manual o motorizado.'],
            ['Persianas', 'GGE-0005', 'Persiana a medida para local', 'Sistema reforzado para vanos comerciales de gran dimensión.'],
        ];

        foreach ($especiales as [$catNombre, $codigo, $nombre, $descripcion]) {
            $categoria = Categoria::firstOrCreate(
                ['nombre' => $catNombre],
                ['activo' => true]
            );

            // es_especial se fuerza acá porque DatabaseSeeder corre con
            // WithoutModelEvents, que desactiva el evento `creating` del
            // modelo que normalmente lo setea.
            ProductoEspecial::firstOrCreate(
                ['codigo' => $codigo],
                [
                    'categoria_id' => $categoria->id,
                    'nombre'       => $nombre,
                    'descripcion'  => $descripcion,
                    'activo'       => true,
                    'es_especial'  => true,
                ]
            );
        }
    }
}
