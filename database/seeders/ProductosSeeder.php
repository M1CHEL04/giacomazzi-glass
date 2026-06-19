<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Variante;
use App\Models\ValorVariante;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductosSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Variantes globales con sus valores ─────────────────────────────
        $variantesData = [
            'Espesor' => ['4 mm', '6 mm', '8 mm', '10 mm', '12 mm'],
            'Color de perfil' => ['Blanco', 'Natural', 'Negro', 'Bronce', 'Gris grafito'],
            'Tipo de vidrio' => ['Float', 'Templado', 'Laminado', 'Satinado', 'Reflectivo'],
            'Sistema de apertura' => ['Corredizo', 'Abatible', 'Batiente', 'Pivotante', 'Proyectante'],
            'Acabado' => ['Anodizado', 'Lacado', 'Termolaqueado'],
        ];

        $variantes = [];
        foreach ($variantesData as $nombre => $valores) {
            $variante = Variante::firstOrCreate(['nombre' => $nombre]);
            $variante->valores_obj = collect($valores)->map(
                fn ($v) => ValorVariante::firstOrCreate(
                    ['variante_id' => $variante->id, 'valor' => $v],
                    ['codigo' => Str::upper(Str::slug($v, '_'))]
                )
            );
            $variantes[$nombre] = $variante;
        }

        // ── 2. Categorías con sus variantes asociadas ─────────────────────────
        $categoriasConfig = [
            'Mamparas' => [
                'variantes' => ['Espesor', 'Tipo de vidrio', 'Color de perfil'],
                'productos' => [
                    ['Mampara de baño corredera', 'Panel de vidrio templado sobre bañera con sistema deslizante de perfil fino.'],
                    ['Mampara de ducha fija', 'Vidrio fijo sin marco con sellado perimetral, ideal para duchas de obra.'],
                    ['Mampara abatible doble hoja', 'Dos hojas abatibles con bisagras de acero inoxidable y junta magnética.'],
                    ['Mampara plegable 4 hojas', 'Sistema plegable compacto que permite apertura total del acceso a la ducha.'],
                    ['Mampara semircircular', 'Diseño curvo en planta para cabinas de ducha de esquina.'],
                    ['Mampara antical easy-clean', 'Tratamiento hidrofóbico en vidrio que reduce la adherencia del sarro.'],
                ],
            ],
            'Puertas' => [
                'variantes' => ['Sistema de apertura', 'Color de perfil', 'Tipo de vidrio'],
                'productos' => [
                    ['Puerta de aluminio batiente', 'Hoja única con apertura interior o exterior, marco reforzado y burlete perimetral.'],
                    ['Puerta corrediza de vidrio', 'Hoja deslizante sobre riel superior con sistema de freno suave al cierre.'],
                    ['Puerta pivotante de entrada', 'Eje central con pivote de piso y techo, cierre silencioso de alta resistencia.'],
                    ['Puerta doble hoja abatible', 'Dos hojas simétricas para vanos anchos, con manija y cerradura multipunto.'],
                    ['Puerta balcón corrediza', 'Perfil de aluminio extruido con doble junta de goma y vidrio doble contacto.'],
                ],
            ],
            'Ventanas' => [
                'variantes' => ['Sistema de apertura', 'Espesor', 'Color de perfil', 'Tipo de vidrio'],
                'productos' => [
                    ['Ventana corrediza serie 25', 'Dos hojas deslizantes con perfil de 25 mm, mosquitero retráctil incluido.'],
                    ['Ventana proyectante oscilo-batiente', 'Permite apertura en dos posiciones: basculante para ventilación y batiente total.'],
                    ['Ventana de guillotina', 'Hoja superior e inferior deslizables en plano vertical, estilo clásico.'],
                    ['Ventana fija panorámica', 'Paño fijo de gran superficie para máxima entrada de luz natural.'],
                    ['Ventana abatible con persiana integrada', 'Perfil con cajón de persiana incorporado y cinta de accionamiento manual.'],
                ],
            ],
            'Cortinas' => [
                'variantes' => ['Color de perfil', 'Acabado'],
                'productos' => [
                    ['Cortina de enrollar de PVC', 'Láminas de PVC rígido de alta densidad, accionamiento manual por cinta.'],
                    ['Cortina de seguridad articulada', 'Perfiles de aluminio articulados resistentes a la presión manual.'],
                    ['Cortina de techo motorizada', 'Sistema motorizado con control remoto para aplicaciones en techos de comercios.'],
                    ['Cortina micro-perforada', 'Permite visibilidad exterior sin perder protección contra el sol.'],
                ],
            ],
            'Persianas' => [
                'variantes' => ['Color de perfil', 'Acabado'],
                'productos' => [
                    ['Persiana de aluminio de 40 mm', 'Lamas de 40 mm de ancho, lamas reforzadas con doble pared de aluminio.'],
                    ['Persiana veneciana de 25 mm', 'Lamas delgadas orientables 180°, acabado anodizado mate premium.'],
                    ['Persiana enrollable termopanel', 'Perfil de doble cámara con relleno de espuma de poliuretano para aislación térmica.'],
                    ['Persiana canadiense de madera-aluminio', 'Lamas con núcleo de madera y cobertura de aluminio lacado exterior.'],
                ],
            ],
        ];

        // ── 3. Crear categorías, vincular variantes y cargar productos ─────────
        $codigoCounter = 1;

        foreach ($categoriasConfig as $catNombre => $config) {
            $categoria = Categoria::firstOrCreate(
                ['nombre' => $catNombre],
                ['activo' => true]
            );

            // Vincular variantes a la categoría
            $varianteIds = collect($config['variantes'])
                ->map(fn ($n) => $variantes[$n]->id)
                ->all();
            $categoria->variantes()->syncWithoutDetaching($varianteIds);

            // Recopilar todos los valores disponibles para la categoría
            $valoresPorVariante = collect($config['variantes'])->map(
                fn ($n) => $variantes[$n]->valores_obj
            );

            foreach ($config['productos'] as $idx => [$nombre, $descripcion]) {
                $codigo = 'GG-' . str_pad($codigoCounter++, 4, '0', STR_PAD_LEFT);

                $producto = Producto::firstOrCreate(
                    ['codigo' => $codigo],
                    [
                        'categoria_id'       => $categoria->id,
                        'nombre'             => $nombre,
                        'descripcion'        => $descripcion,
                        'descripcion_tecnica'=> null,
                        'activo'             => true,
                    ]
                );

                // Asignar 1 valor por variante de la categoría (rotando opciones)
                $valorIds = $valoresPorVariante->map(function ($valores) use ($idx) {
                    return $valores->get($idx % $valores->count())->id;
                })->all();

                $producto->valoresVariantes()->syncWithoutDetaching($valorIds);
            }
        }
    }
}
