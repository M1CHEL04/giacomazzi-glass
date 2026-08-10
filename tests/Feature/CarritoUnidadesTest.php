<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Cotizacion;
use App\Models\Producto;
use App\Models\UnidadMedida;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cubre el carrito según la unidad de cotización del producto: unidades,
 * medida lineal (alto / ancho) y superficie (alto × ancho).
 */
class CarritoUnidadesTest extends TestCase
{
    use RefreshDatabase;

    private function producto(string $codigoUnidad, string $codigo = 'GG-0001'): Producto
    {
        $categoria = Categoria::firstOrCreate(['nombre' => 'Test'], ['activo' => true]);

        return Producto::create([
            'categoria_id' => $categoria->id,
            'unidad_id'    => UnidadMedida::where('codigo', $codigoUnidad)->value('id'),
            'nombre'       => 'Producto ' . $codigoUnidad,
            'descripcion'  => 'Descripción de prueba',
            'codigo'       => $codigo,
            'activo'       => true,
        ]);
    }

    public function test_la_migracion_siembra_las_cuatro_unidades(): void
    {
        $this->assertSame(
            ['unidades', 'alto', 'ancho', 'alto_ancho'],
            UnidadMedida::orderBy('id')->pluck('codigo')->all()
        );
    }

    public function test_producto_por_unidades_no_guarda_medidas(): void
    {
        $producto = $this->producto('unidades');

        $this->postJson('/carrito/agregar', ['producto_id' => $producto->id, 'cantidad' => 3])
            ->assertOk()
            ->assertJsonPath('cantidad', 1)                 // una línea
            ->assertJsonPath('carrito.0.cantidad', 3)
            ->assertJsonPath('carrito.0.unidad', 'unidades')
            ->assertJsonPath('carrito.0.alto', null)
            ->assertJsonPath('carrito.0.ancho', null);
    }

    public function test_producto_por_unidades_rechaza_medidas(): void
    {
        $producto = $this->producto('unidades');

        $this->postJson('/carrito/agregar', ['producto_id' => $producto->id, 'alto' => 2])
            ->assertStatus(422)
            ->assertJsonValidationErrors('alto');
    }

    public function test_medida_lineal_acepta_coma_decimal_y_tres_decimales(): void
    {
        $producto = $this->producto('alto');

        $this->postJson('/carrito/agregar', ['producto_id' => $producto->id, 'alto' => '5,546'])
            ->assertOk()
            ->assertJsonPath('carrito.0.unidad', 'alto')
            ->assertJsonPath('carrito.0.alto', 5.546)
            ->assertJsonPath('carrito.0.ancho', null)
            ->assertJsonPath('carrito.0.m2', null);
    }

    public function test_superficie_calcula_los_metros_cuadrados(): void
    {
        $producto = $this->producto('alto_ancho');

        $this->postJson('/carrito/agregar', [
            'producto_id' => $producto->id,
            'alto'        => '5,546',
            'ancho'       => '2,3',
            'cantidad'    => 3,
        ])
            ->assertOk()
            ->assertJsonPath('carrito.0.alto', 5.546)
            ->assertJsonPath('carrito.0.ancho', 2.3)
            ->assertJsonPath('carrito.0.m2', 12.756)
            ->assertJsonPath('carrito.0.cantidad', 3);
    }

    public function test_superficie_exige_ambas_medidas(): void
    {
        $producto = $this->producto('alto_ancho');

        $this->postJson('/carrito/agregar', ['producto_id' => $producto->id, 'alto' => '2'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('ancho');
    }

    public function test_rechaza_medidas_fuera_de_rango(): void
    {
        $producto = $this->producto('alto');

        $this->postJson('/carrito/agregar', ['producto_id' => $producto->id, 'alto' => '0'])
            ->assertStatus(422);

        $this->postJson('/carrito/agregar', ['producto_id' => $producto->id, 'alto' => '250'])
            ->assertStatus(422);
    }

    public function test_medidas_distintas_son_lineas_separadas_y_las_iguales_suman_piezas(): void
    {
        $producto = $this->producto('alto_ancho');

        $this->postJson('/carrito/agregar', [
            'producto_id' => $producto->id, 'alto' => '2', 'ancho' => '1',
        ])->assertOk();

        $this->postJson('/carrito/agregar', [
            'producto_id' => $producto->id, 'alto' => '3', 'ancho' => '1',
        ])->assertJsonPath('cantidad', 2);

        // Misma medida que la primera línea: suma piezas, no crea una nueva.
        $this->postJson('/carrito/agregar', [
            'producto_id' => $producto->id, 'alto' => '2', 'ancho' => '1', 'cantidad' => 2,
        ])
            ->assertJsonPath('cantidad', 2)
            ->assertJsonPath('carrito.0.cantidad', 3);
    }

    public function test_cotizar_guarda_el_snapshot_con_unidad_y_medidas(): void
    {
        $producto = $this->producto('alto_ancho');

        $this->postJson('/carrito/agregar', [
            'producto_id' => $producto->id, 'alto' => '2', 'ancho' => '1,5',
        ])->assertOk();

        $this->postJson('/carrito/cotizar')->assertOk()->assertJsonPath('cantidad', 0);

        $cotizacion = Cotizacion::sole();

        $this->assertSame(1, $cotizacion->cantidad_items);   // una línea
        $this->assertSame('alto_ancho', $cotizacion->items[0]['unidad']);
        // 3.0 vuelve del JSON como 3: la comparación es por valor, no por tipo.
        $this->assertEquals(3, $cotizacion->items[0]['m2']);
    }
}
