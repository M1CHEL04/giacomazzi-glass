<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\ProductoEspecial;
use App\Models\UnidadMedida;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Los productos a medida viven en la misma tabla que los estándar, separados
 * por el flag `es_especial`. Lo que se cubre acá es justamente la frontera:
 * que un especial no se cuele en el carrito ni en la ficha estándar, y que
 * cada uno responda por su propia ruta.
 */
class ProductosEspecialesTest extends TestCase
{
    use RefreshDatabase;

    private function categoria(): Categoria
    {
        return Categoria::firstOrCreate(['nombre' => 'Test'], ['activo' => true]);
    }

    private function estandar(string $codigo = 'GG-0001'): Producto
    {
        return Producto::create([
            'categoria_id' => $this->categoria()->id,
            'unidad_id'    => UnidadMedida::where('codigo', 'unidades')->value('id'),
            'nombre'       => 'Producto estándar',
            'descripcion'  => 'Descripción de prueba',
            'codigo'       => $codigo,
            'activo'       => true,
        ]);
    }

    private function especial(string $codigo = 'ESP-0001'): ProductoEspecial
    {
        return ProductoEspecial::create([
            'categoria_id' => $this->categoria()->id,
            'nombre'       => 'Producto a medida',
            'descripcion'  => 'Se fabrica para cada obra',
            'codigo'       => $codigo,
            'activo'       => true,
        ]);
    }

    public function test_el_modelo_especial_marca_el_flag_al_crear(): void
    {
        $especial = $this->especial();

        $this->assertTrue($especial->fresh()->es_especial);
        $this->assertNull($especial->unidad_id);
    }

    public function test_los_scopes_separan_los_dos_tipos(): void
    {
        $this->estandar();
        $this->especial();

        $this->assertSame(1, Producto::estandar()->count());
        $this->assertSame(1, Producto::especial()->count());
        $this->assertSame(1, ProductoEspecial::count());
        $this->assertSame(2, Producto::count());
    }

    public function test_el_carrito_rechaza_un_producto_especial(): void
    {
        $especial = $this->especial();

        $this->postJson('/carrito/agregar', ['producto_id' => $especial->id, 'cantidad' => 1])
            ->assertNotFound();

        $this->assertEmpty(session('carrito', []));
    }

    public function test_la_ficha_estandar_no_sirve_un_especial(): void
    {
        $especial = $this->especial();

        $this->get('/productos/' . $especial->id)->assertNotFound();
        $this->get(route('productos.especial.show', $especial->id))->assertOk();
    }

    public function test_la_ficha_especial_no_sirve_un_estandar(): void
    {
        $estandar = $this->estandar();

        $this->get(route('productos.especial.show', $estandar->id))->assertNotFound();
        $this->get('/productos/' . $estandar->id)->assertOk();
    }

    public function test_el_catalogo_muestra_los_dos_tipos_y_el_filtro_acota(): void
    {
        $this->estandar();
        $this->especial();

        // Sin filtro de tipo: mezclados
        $this->get(route('productos.todos'))
            ->assertOk()
            ->assertSee('Producto estándar', false)
            ->assertSee('Producto a medida', false);

        $this->get(route('productos.todos', ['tipos' => ['especial']]))
            ->assertOk()
            ->assertSee('Producto a medida', false)
            ->assertDontSee('Producto estándar', false);

        $this->get(route('productos.todos', ['tipos' => ['estandar']]))
            ->assertOk()
            ->assertSee('Producto estándar', false)
            ->assertDontSee('Producto a medida', false);

        // Los dos tildados es lo mismo que ninguno
        $this->get(route('productos.todos', ['tipos' => ['estandar', 'especial']]))
            ->assertOk()
            ->assertSee('Producto estándar', false)
            ->assertSee('Producto a medida', false);
    }

    public function test_la_categoria_separa_el_grid_de_la_franja_a_medida(): void
    {
        $this->estandar();
        $this->especial();

        $categoriaId = $this->categoria()->id;

        // El grid paginado trae sólo el estándar; el especial llega por
        // $especiales, que alimenta la franja de abajo.
        $this->get(route('productos.categoria', $categoriaId))
            ->assertOk()
            ->assertViewHas('productos', fn ($productos) => $productos->count() === 1)
            ->assertViewHas('especiales', fn ($especiales) => $especiales->count() === 1);
    }

    public function test_el_index_de_especiales_lista_solo_especiales(): void
    {
        $this->estandar();
        $this->especial();

        $this->get(route('productos.especiales'))
            ->assertOk()
            ->assertSee('Producto a medida', false)
            ->assertDontSee('Producto estándar', false);
    }

    public function test_el_codigo_es_unico_entre_los_dos_tipos(): void
    {
        $this->estandar('DUP-001');

        $this->expectException(\Illuminate\Database\QueryException::class);
        $this->especial('DUP-001');
    }

    public function test_el_index_de_especiales_por_categoria_acota_a_esa_categoria(): void
    {
        $ab = Categoria::create(['nombre' => 'Aberturas', 'activo' => true]);
        $ma = Categoria::create(['nombre' => 'Mamparas', 'activo' => true]);

        ProductoEspecial::create(['categoria_id' => $ab->id, 'nombre' => 'Puerta pivot',
            'descripcion' => 'd', 'codigo' => 'E-1', 'activo' => true]);
        ProductoEspecial::create(['categoria_id' => $ma->id, 'nombre' => 'Mampara curva',
            'descripcion' => 'd', 'codigo' => 'E-2', 'activo' => true]);
        // Un estandar en la misma categoria no debe colarse
        Producto::create(['categoria_id' => $ab->id,
            'unidad_id' => UnidadMedida::where('codigo', 'unidades')->value('id'),
            'nombre' => 'Ventana comun', 'descripcion' => 'd', 'codigo' => 'A-1', 'activo' => true]);

        $html = $this->get(route('productos.especial.categoria', $ab->id))
            ->assertOk()
            // $enCategoria se calcula dentro del blade (@php), ya no viaja
            // como variable del controlador: se verifica por el contenido.
            ->assertViewHas('categoria', fn ($c) => $c->id === $ab->id)
            ->getContent();

        $this->assertStringContainsString('Puerta pivot', $html);
        $this->assertStringNotContainsString('Mampara curva', $html);
        $this->assertStringNotContainsString('Ventana comun', $html);
        // El hero y el breadcrumb toman el nombre de la categoria
        $this->assertStringContainsString('g-hero-title">Aberturas', $html);
        // Misma vista que la categoría estándar (categoria.blade.php), sin
        // ninguna plantilla paralela: como la línea singular nunca tiene
        // variantes, el mismo @if($variantes->count() > 0) que ya oculta el
        // sidebar en una categoría estándar sin facetas lo oculta acá
        // también —sidebar, botón de filtrar y buscador incluidos—, y el
        // grid pasa a ocupar el ancho completo.
        $this->assertStringNotContainsString('filtro-grupo-categorias', $html);
        $this->assertStringNotContainsString('filtros-buscar-input', $html);
        $this->assertStringNotContainsString('btn-filtros-mobile', $html);
        $this->assertStringContainsString('col-12" id="productos-container"', $html);
        // El renglón de la línea singular sí aparece: es la única diferencia
        // de contenido frente a la misma vista del lado estándar.
        $this->assertStringContainsString('singular-linea-alert', $html);
        $this->assertStringContainsString('Productos que no se fabrican en serie', $html);
    }

    public function test_la_categoria_de_especiales_exige_categoria_activa(): void
    {
        $c = Categoria::create(['nombre' => 'Oculta', 'activo' => false]);

        $this->get(route('productos.especial.categoria', $c->id))->assertNotFound();
        $this->get(route('productos.especial.categoria', 99999))->assertNotFound();
    }

    public function test_la_busqueda_funciona_dentro_de_una_categoria_a_medida(): void
    {
        $ab = Categoria::create(['nombre' => 'Aberturas', 'activo' => true]);
        ProductoEspecial::create(['categoria_id' => $ab->id, 'nombre' => 'Puerta pivot',
            'descripcion' => 'd', 'codigo' => 'E-1', 'activo' => true]);
        ProductoEspecial::create(['categoria_id' => $ab->id, 'nombre' => 'Baranda recta',
            'descripcion' => 'd', 'codigo' => 'E-2', 'activo' => true]);

        $html = $this->get(route('productos.especial.categoria', [$ab->id, 'buscar' => 'pivot']))
            ->assertOk()->getContent();

        $this->assertStringContainsString('Puerta pivot', $html);
        $this->assertStringNotContainsString('Baranda recta', $html);
    }
}
