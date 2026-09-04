<?php
namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\ImagenProducto;
use App\Models\ProductoEspecial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Recorre el CRUD del panel de punta a punta: alta con imágenes de galería y
 * técnicas, elección de portada, baja lógica de una imagen, baja y alta del
 * producto, y la frontera con el CRUD estándar.
 */
class EspecialesAdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): static
    {
        $u = User::create(['name' => 'A', 'email' => 'a@a.com', 'password' => 'x']);
        $u->cambio_contraseña = true;
        $u->save();
        return $this->withSession(['user_email' => 'a@a.com', 'rol' => 'Admin']);
    }

    public function test_alta_edicion_y_cambio_de_estado(): void
    {
        config(['filesystems.image_disk' => 'local']);
        Storage::fake('local');

        $cat = Categoria::create(['nombre' => 'Aberturas', 'activo' => true]);
        $this->admin();

        $this->get(route('uso-interno.especiales.index'))->assertOk();
        $this->get(route('uso-interno.especiales.create'))->assertOk();

        $resp = $this->post(route('uso-interno.especiales.store'), [
            'categoria_id' => $cat->id,
            'nombre' => 'Puerta pivotante',
            'codigo' => 'ESP-1',
            'descripcion' => 'Una puerta',
            'descripcion_tecnica' => 'Detalles',
            'imagenes' => [
                UploadedFile::fake()->image('a.jpg', 800, 600),
                UploadedFile::fake()->image('b.jpg', 800, 600),
            ],
            'imagenes_tecnicas' => [UploadedFile::fake()->image('plano.jpg', 800, 600)],
            'imagen_portada' => 'nueva:1',
        ]);
        $resp->assertRedirect();
        $resp->assertSessionHas('success');

        $p = ProductoEspecial::firstOrFail();
        $this->assertTrue($p->es_especial);
        $this->assertNull($p->unidad_id);
        $this->assertSame(2, $p->imagenes()->count());
        $this->assertSame(1, $p->imagenesTecnicas()->count());
        // La portada es la segunda imagen (nueva:1)
        $this->assertSame(
            $p->imagenes()->orderBy('id')->pluck('id')[1],
            $p->imagenes()->where('es_principal', true)->value('id')
        );

        $this->get(route('uso-interno.especiales.show', $p->id))->assertOk();
        $this->get(route('uso-interno.especiales.edit', $p->id))->assertOk();

        // Update: borra una imagen y da de baja
        $aBorrar = $p->imagenes()->orderBy('id')->value('id');
        $this->post(route('uso-interno.especiales.update', $p->id), [
            'categoria_id' => $cat->id,
            'nombre' => 'Puerta pivotante v2',
            'codigo' => 'ESP-1',
            'descripcion' => 'Una puerta',
            'activo' => '0',
            'imagenes_eliminar' => [$aBorrar],
        ])->assertRedirect()->assertSessionHas('success');

        $p->refresh();
        $this->assertSame('Puerta pivotante v2', $p->nombre);
        $this->assertFalse((bool) $p->activo);
        $this->assertSame(1, $p->imagenes()->count());
        $this->assertFalse((bool) ImagenProducto::find($aBorrar)->activa);

        // Alta desde el modal
        $this->post(route('uso-interno.especiales.activar'), ['producto_id' => $p->id])
            ->assertRedirect();
        $this->assertTrue((bool) $p->fresh()->activo);

        // El CRUD estandar no debe poder tocarlo
        $this->get(route('uso-interno.productos.edit', $p->id))->assertNotFound();
        // Y no aparece en el listado estandar
        $this->get(route('uso-interno.productos.index'))
            ->assertOk()->assertDontSee('Puerta pivotante v2', false);
    }
}
