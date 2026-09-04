<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\ProductoEspecial;
use App\Models\UnidadMedida;
use App\Services\MenuCategorias;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El menú de Productos lista, por rama, sólo las categorías que hoy tienen un
 * producto activo de ese tipo. Como eso depende de los productos y no sólo de
 * las categorías, lo que más se cubre acá es la invalidación del caché.
 */
class MenuProductosTest extends TestCase
{
    use RefreshDatabase;

    private function cat(string $nombre): Categoria
    {
        return Categoria::create(['nombre' => $nombre, 'activo' => true]);
    }

    private function estandar(Categoria $c, string $codigo, bool $activo = true): Producto
    {
        return Producto::create([
            'categoria_id' => $c->id,
            'unidad_id'    => UnidadMedida::where('codigo', 'unidades')->value('id'),
            'nombre'       => 'Est ' . $codigo, 'descripcion' => 'x',
            'codigo'       => $codigo, 'activo' => $activo,
        ]);
    }

    private function especial(Categoria $c, string $codigo, bool $activo = true): ProductoEspecial
    {
        return ProductoEspecial::create([
            'categoria_id' => $c->id,
            'nombre'       => 'Esp ' . $codigo, 'descripcion' => 'y',
            'codigo'       => $codigo, 'activo' => $activo,
        ]);
    }

    public function test_cada_rama_lista_solo_sus_categorias_con_producto_activo(): void
    {
        $soloEstandar = $this->cat('Aberturas');
        $soloEspecial = $this->cat('Mamparas');
        $ambas        = $this->cat('Barandas');
        $vacia        = $this->cat('Vacia');
        $inactivos    = $this->cat('Inactivos');

        $this->estandar($soloEstandar, 'A-1');
        $this->especial($soloEspecial, 'B-1');
        $this->estandar($ambas, 'C-1');
        $this->especial($ambas, 'C-2');
        $this->estandar($inactivos, 'D-1', false);
        $this->especial($inactivos, 'D-2', false);

        MenuCategorias::olvidar();

        $this->assertSame(
            ['Aberturas', 'Barandas'],
            array_column(MenuCategorias::estandar(), 'nombre')
        );
        $this->assertSame(
            ['Barandas', 'Mamparas'],
            array_column(MenuCategorias::especiales(), 'nombre')
        );
    }

    public function test_una_categoria_inactiva_no_aparece(): void
    {
        $c = $this->cat('Aberturas');
        $this->estandar($c, 'A-1');
        MenuCategorias::olvidar();
        $this->assertCount(1, MenuCategorias::estandar());

        $c->update(['activo' => false]);
        MenuCategorias::olvidar();
        $this->assertCount(0, MenuCategorias::estandar());
    }

    public function test_dar_de_baja_el_ultimo_producto_saca_la_categoria_del_menu(): void
    {
        $c = $this->cat('Mamparas');
        $e = $this->especial($c, 'B-1');
        MenuCategorias::olvidar();
        $this->assertCount(1, MenuCategorias::especiales());

        // Sin olvidar() explícito: lo tiene que hacer el controlador.
        $this->admin()->post(route('uso-interno.especiales.desactivar'), ['producto_id' => $e->id])
            ->assertRedirect();

        $this->assertCount(0, MenuCategorias::especiales(), 'el caché del menú quedó viejo');
    }

    public function test_crear_un_producto_mete_la_categoria_en_el_menu(): void
    {
        $c = $this->cat('Mamparas');
        MenuCategorias::olvidar();
        $this->assertCount(0, MenuCategorias::especiales());

        $this->admin()->post(route('uso-interno.especiales.store'), [
            'categoria_id' => $c->id, 'nombre' => 'Mampara X',
            'codigo' => 'E-1', 'descripcion' => 'd',
        ])->assertRedirect();

        $this->assertCount(1, MenuCategorias::especiales(), 'el caché del menú quedó viejo');
    }

    public function test_el_navbar_muestra_las_dos_ramas_con_sus_categorias(): void
    {
        $ab = $this->cat('Aberturas');
        $ma = $this->cat('Mamparas');
        $this->estandar($ab, 'A-1');
        $this->especial($ma, 'B-1');
        MenuCategorias::olvidar();

        $html = $this->get(route('productos.todos'))->assertOk()->getContent();

        $this->assertStringContainsString('Ver todos los productos', $html);
        $this->assertStringContainsString('nav-prod-panel-especial', $html);
        $this->assertStringContainsString('nav-prod-panel-estandar', $html);
        $this->assertStringContainsString('Ver toda la línea adapta', $html);
        $this->assertStringContainsString('Ver todos los estándar', $html);
        $this->assertStringContainsString('Línea estándar', $html);
        $this->assertStringContainsString('Línea adapta', $html);
        // "Línea estándar" va primero y "Línea adapta" después, tanto en
        // el orden del array como en el texto visible.
        $posEstandar = strpos($html, 'Línea estándar');
        $posSingular = strpos($html, 'Línea adapta');
        $this->assertNotFalse($posEstandar);
        $this->assertNotFalse($posSingular);
        $this->assertLessThan(
            $posSingular,
            $posEstandar,
            'Línea estándar debe aparecer antes que Línea adapta en el menú'
        );
        // Ninguna rama arranca desplegada: el panel sale al apuntarla.
        // (El aria-expanded="true" que hay en la página es el de los grupos
        // del sidebar de filtros, que sí arrancan abiertos.)
        $this->assertSame(2, substr_count($html, 'data-rama-wrap'));
        $this->assertSame(2, preg_match_all(
            '/class="nav-prod-rama[^"]*"\s+aria-haspopup="true" aria-expanded="false"/',
            $html
        ));
        // La categoría de cada rama apunta a su propio index
        $this->assertStringContainsString(route('productos.categoria', $ab->id), $html);
        $this->assertStringContainsString(route('productos.especial.categoria', $ma->id), $html);
    }

    private function admin(): static
    {
        \App\Models\User::create(['name' => 'A', 'email' => 'a@a.com', 'password' => 'x'])
            ->forceFill(['cambio_contraseña' => true])->save();
        return $this->withSession(['user_email' => 'a@a.com', 'rol' => 'Admin']);
    }
}
