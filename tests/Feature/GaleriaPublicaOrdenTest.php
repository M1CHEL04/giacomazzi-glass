<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\ImagenProducto;
use App\Models\Producto;
use App\Models\UnidadMedida;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GaleriaPublicaOrdenTest extends TestCase
{
    use RefreshDatabase;

    private Producto $producto;
    private array $rutas = [];
    private array $thumbs = [];

    protected function setUp(): void
    {
        parent::setUp();

        $categoria = Categoria::create(['nombre' => 'Aberturas', 'activo' => true]);
        $unidad    = UnidadMedida::create(['codigo' => 'UN', 'nombre' => 'Unidad', 'simbolo' => 'u']);

        $this->producto = Producto::create([
            'categoria_id' => $categoria->id,
            'unidad_id'    => $unidad->id,
            'nombre'       => 'Ventana corrediza',
            'codigo'       => 'STD-1',
            'descripcion'  => 'Una ventana',
            'activo'       => true,
        ]);

        // ids 1,2,3 ↔ orden 2,0,1: el orden contradice el id a propósito.
        $ordenes = [2, 0, 1];
        foreach ($ordenes as $i => $orden) {
            $img = ImagenProducto::create([
                'producto_id'   => $this->producto->id,
                'nombre_imagen' => 'foto-' . $i . '.webp',
                'ruta'          => 'https://files.test/foto-' . $i . '.webp',
                'ruta_thumb'    => 'https://files.test/foto-' . $i . '-thumb.webp',
                'orden'         => $orden,
                'es_principal'  => $orden === 0,
            ]);
            $this->rutas[$orden]  = $img->ruta;
            $this->thumbs[$orden] = $img->ruta_thumb;
        }
    }

    /** Las imágenes grandes, de la posición 0 a la 2. */
    private function enOrden(): array
    {
        return [$this->rutas[0], $this->rutas[1], $this->rutas[2]];
    }

    /** Las miniaturas, de la posición 0 a la 2. Es lo que pintan las grillas. */
    private function enOrdenThumb(): array
    {
        return [$this->thumbs[0], $this->thumbs[1], $this->thumbs[2]];
    }

    public function test_la_ficha_publica_respeta_el_orden(): void
    {
        // Una sola afirmación fija el carrusel, la tira de miniaturas y el
        // lightbox: los tres recorren la misma colección.
        $this->get(route('productos.show', $this->producto->id))
            ->assertOk()
            ->assertSeeInOrder($this->enOrden(), false);
    }

    public function test_la_ficha_especial_respeta_el_orden(): void
    {
        $this->producto->update(['es_especial' => true, 'unidad_id' => null]);

        $this->get(route('productos.especial.show', $this->producto->id))
            ->assertOk()
            ->assertSeeInOrder($this->enOrden(), false);
    }

    public function test_la_grilla_muestra_la_portada(): void
    {
        $html = $this->get(route('productos.todos'))->assertOk()->getContent();

        // La grilla trae una sola imagen por producto —la de orden 0— y la pinta
        // como miniatura (ruta_miniatura).
        $this->assertStringContainsString($this->thumbs[0], $html);
        $this->assertStringNotContainsString($this->thumbs[1], $html);
        $this->assertStringNotContainsString($this->thumbs[2], $html);
    }

    public function test_la_ficha_interna_respeta_el_orden(): void
    {
        $u = User::create(['name' => 'A', 'email' => 'a@a.com', 'password' => 'x']);
        $u->cambio_contraseña = true;
        $u->save();
        $this->withSession(['user_email' => 'a@a.com', 'rol' => 'Admin']);

        // Cazaría una regresión del sortByDesc('es_principal') que había acá.
        // Esta vista lleva la grande en data-src, para el lightbox.
        $this->get(route('uso-interno.productos.show', $this->producto->id))
            ->assertOk()
            ->assertSeeInOrder($this->enOrden(), false);

        // Y el formulario de edición pinta las tarjetas en el mismo orden. Acá
        // sólo hay miniaturas.
        $edit = $this->get(route('uso-interno.productos.edit', $this->producto->id))->assertOk();
        $edit->assertSeeInOrder($this->enOrdenThumb(), false);

        // El badge cae entre la primera y la segunda miniatura, o sea sobre la
        // primera tarjeta: eso fija a qué imagen pertenece, no sólo que exista.
        $edit->assertSeeInOrder(
            [$this->thumbs[0], 'imagen-portada-badge', $this->thumbs[1]],
            false
        );
    }
}
