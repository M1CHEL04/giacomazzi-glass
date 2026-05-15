<?php

namespace Database\Seeders;

use App\Models\Categoria;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategoriasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categorias = [
            ['nombre' => 'Mamparas', 'activo' => true],
            ['nombre' => 'Puertas', 'activo' => true],
            ['nombre' => 'Ventanas', 'activo' => true],
            ['nombre' => 'Cortinas', 'activo' => true],
            ['nombre' => 'Persianas', 'activo' => true],
        ];

        foreach ($categorias as $categoria) {
            Categoria::create($categoria);
        }
    }
}
