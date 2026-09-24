<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\ImagenProducto;
use App\Models\Producto;
use App\Models\UnidadMedida;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Concerns\AfirmaInvarianteGaleria;
use Tests\TestCase;

/**
 * Orden de la galería en el CRUD estándar, que hasta ahora no tenía ninguna
 * cobertura de imágenes.
 *
 * Lo que se afirma en todos lados es el invariante: `orden` denso 0..n-1 y
 * es_principal únicamente en la de orden 0. Sobre eso se apoyan las grillas, la
 * ficha pública y el PDF.
 */
class OrdenImagenesGaleriaTest extends TestCase
{
    use RefreshDatabase;
    use AfirmaInvarianteGaleria;

    private Categoria $categoria;
    private UnidadMedida $unidad;

    protected function setUp(): void
    {
        parent::setUp();
        // Disco local falso: no se toca SFTP ni se escribe nada de verdad.
        config(['filesystems.image_disk' => 'local']);
        Storage::fake('local');

        $u = User::create(['name' => 'A', 'email' => 'a@a.com', 'password' => 'x']);
        $u->cambio_contraseña = true;
        $u->save();
        $this->withSession(['user_email' => 'a@a.com', 'rol' => 'Admin']);

        $this->categoria = Categoria::create(['nombre' => 'Aberturas', 'activo' => true]);
        $this->unidad    = UnidadMedida::create([
            'codigo'  => 'UN',
            'nombre'  => 'Unidad',
            'simbolo' => 'u',
        ]);
    }

    private function datosBase(): array
    {
        return [
            'categoria_id'        => $this->categoria->id,
            'unidad_id'           => $this->unidad->id,
            'nombre'              => 'Ventana',
            'codigo'              => 'STD-1',
            'descripcion'         => 'Una ventana',
            'descripcion_tecnica' => 'Detalles',
        ];
    }

    /** Un producto con N imágenes de galería ya guardadas y bien ordenadas. */
    private function productoConGaleria(int $cantidad = 3, string $codigo = 'STD-9'): Producto
    {
        $p = Producto::create([
            'categoria_id' => $this->categoria->id,
            'unidad_id'    => $this->unidad->id,
            'nombre'       => 'Producto ' . $codigo,
            'codigo'       => $codigo,
            'descripcion'  => 'Una ventana',
            'activo'       => true,
        ]);

        for ($i = 0; $i < $cantidad; $i++) {
            ImagenProducto::create([
                'producto_id'   => $p->id,
                'nombre_imagen' => $codigo . '-' . $i . '.webp',
                'ruta'          => 'https://files.test/' . $codigo . '-' . $i . '.webp',
                'orden'         => $i,
                'es_principal'  => $i === 0,
            ]);
        }

        return $p;
    }

    private function datosUpdate(Producto $p): array
    {
        return [
            'categoria_id'        => $p->categoria_id,
            'unidad_id'           => $p->unidad_id,
            'nombre'              => $p->nombre,
            'codigo'              => $p->codigo,
            'descripcion'         => 'Una ventana',
            'descripcion_tecnica' => 'Detalles',
            'activo'              => '1',
        ];
    }

    /** El nombre guardado arranca con el id, así que se compara por el sufijo. */
    private function assertEsArchivo(string $esperado, ?string $nombreGuardado, string $mensaje = ''): void
    {
        $this->assertStringEndsWith(
            '_' . pathinfo($esperado, PATHINFO_FILENAME) . '.webp',
            (string) $nombreGuardado,
            $mensaje
        );
    }

    // ── Alta ─────────────────────────────────────────────────────

    /**
     * La prueba de correlación: `nueva:<i>` tiene que apuntar al i-ésimo archivo
     * que le llega a PHP después de array_filter, no a otro.
     *
     * Se afirma por nombre de archivo y no por id: comparar ids pasaría igual si
     * la correlación estuviera permutada.
     */
    public function test_el_manifiesto_ordena_las_subidas_nuevas(): void
    {
        $resp = $this->post(route('uso-interno.productos.store'), $this->datosBase() + [
            'imagenes' => [
                UploadedFile::fake()->image('a.jpg', 800, 600),
                UploadedFile::fake()->image('b.jpg', 800, 600),
                UploadedFile::fake()->image('c.jpg', 800, 600),
            ],
            'imagenes_orden' => 'nueva:2,nueva:0,nueva:1',
        ]);
        $resp->assertRedirect();
        $resp->assertSessionHas('success');

        $p = Producto::firstOrFail();
        $galeria = $p->imagenes()->pluck('nombre_imagen')->all();

        $this->assertCount(3, $galeria);
        $this->assertEsArchivo('c.jpg', $galeria[0], 'La portada debe ser el tercer archivo (nueva:2).');
        $this->assertEsArchivo('a.jpg', $galeria[1]);
        $this->assertEsArchivo('b.jpg', $galeria[2]);

        $this->assertInvarianteGaleria($p);
    }

    /** Sin manifiesto, el campo viejo sigue eligiendo la portada. */
    public function test_sin_manifiesto_cae_al_campo_de_portada(): void
    {
        $this->post(route('uso-interno.productos.store'), $this->datosBase() + [
            'imagenes' => [
                UploadedFile::fake()->image('a.jpg', 800, 600),
                UploadedFile::fake()->image('b.jpg', 800, 600),
            ],
            'imagen_portada' => 'nueva:1',
        ])->assertRedirect();

        $p = Producto::firstOrFail();
        $this->assertEsArchivo('b.jpg', $p->imagenes()->value('nombre_imagen'));
        $this->assertInvarianteGaleria($p);
    }

    /** Sin manifiesto ni portada, la primera subida es la portada. */
    public function test_sin_nada_la_primera_subida_es_portada(): void
    {
        $this->post(route('uso-interno.productos.store'), $this->datosBase() + [
            'imagenes' => [
                UploadedFile::fake()->image('a.jpg', 800, 600),
                UploadedFile::fake()->image('b.jpg', 800, 600),
            ],
        ])->assertRedirect();

        $p = Producto::firstOrFail();
        $this->assertEsArchivo('a.jpg', $p->imagenes()->value('nombre_imagen'));
        $this->assertInvarianteGaleria($p);
    }

    // ── Edición ──────────────────────────────────────────────────

    public function test_reordena_solo_filas_guardadas(): void
    {
        $p = $this->productoConGaleria(3);
        [$a, $b, $c] = $p->imagenes()->pluck('id')->all();

        $this->post(route('uso-interno.productos.update', $p->id), $this->datosUpdate($p) + [
            'imagenes_orden' => "existente:$c,existente:$a,existente:$b",
        ])->assertRedirect();

        $this->assertSame([$c, $a, $b], $p->fresh()->imagenes()->pluck('id')->all());
        $this->assertTrue((bool) ImagenProducto::find($c)->es_principal);
        $this->assertFalse((bool) ImagenProducto::find($a)->es_principal);
        $this->assertInvarianteGaleria($p);
    }

    public function test_una_subida_nueva_puede_quedar_de_portada(): void
    {
        $p = $this->productoConGaleria(2);
        [$a, $b] = $p->imagenes()->pluck('id')->all();

        $this->post(route('uso-interno.productos.update', $p->id), $this->datosUpdate($p) + [
            'imagenes'       => [UploadedFile::fake()->image('nueva.jpg', 800, 600)],
            'imagenes_orden' => "nueva:0,existente:$b,existente:$a",
        ])->assertRedirect();

        $galeria = $p->fresh()->imagenes()->get();
        $this->assertCount(3, $galeria);
        $this->assertEsArchivo('nueva.jpg', $galeria->first()->nombre_imagen);
        $this->assertSame([$b, $a], $galeria->slice(1)->pluck('id')->values()->all());
        $this->assertInvarianteGaleria($p);
    }

    // ── Robustez del manifiesto ──────────────────────────────────

    /**
     * Un id de otro producto no puede tocar nada. Es la guarda que reemplaza al
     * exists() previo que tenía el código viejo.
     */
    public function test_ignora_una_imagen_de_otro_producto(): void
    {
        $p     = $this->productoConGaleria(2, 'STD-A');
        $otro  = $this->productoConGaleria(2, 'STD-B');
        $ajena = $otro->imagenes()->where('es_principal', false)->value('id');

        $this->post(route('uso-interno.productos.update', $p->id), $this->datosUpdate($p) + [
            'imagenes_orden' => "existente:$ajena,existente:" . $p->imagenes()->value('id'),
        ])->assertRedirect();

        $fila = ImagenProducto::find($ajena);
        $this->assertSame($otro->id, $fila->producto_id);
        $this->assertFalse((bool) $fila->es_principal, 'La imagen ajena no debería haber cambiado.');

        $this->assertInvarianteGaleria($p);
        $this->assertInvarianteGaleria($otro);
    }

    /** Una técnica no puede colarse en la galería ni quedar de portada. */
    public function test_ignora_una_tecnica_en_el_manifiesto(): void
    {
        $p = $this->productoConGaleria(2);
        $tecnica = ImagenProducto::create([
            'producto_id'   => $p->id,
            'nombre_imagen' => 'plano.webp',
            'ruta'          => 'https://files.test/plano.webp',
            'es_tecnica'    => true,
        ]);

        $this->post(route('uso-interno.productos.update', $p->id), $this->datosUpdate($p) + [
            'imagenes_orden' => "existente:{$tecnica->id},existente:" . $p->imagenes()->value('id'),
        ])->assertRedirect();

        $this->assertFalse((bool) $tecnica->fresh()->es_principal);
        $this->assertSame(2, $p->fresh()->imagenes()->count());
        $this->assertInvarianteGaleria($p);
    }

    /** Basura, duplicados e índices inexistentes: se descartan sin explotar. */
    public function test_un_manifiesto_basura_no_rompe_el_guardado(): void
    {
        $p  = $this->productoConGaleria(3);
        $id = $p->imagenes()->pluck('id')->last();

        $this->post(route('uso-interno.productos.update', $p->id), $this->datosUpdate($p) + [
            'imagenes_orden' => "existente:$id,existente:$id,nueva:99,,basura,existente:999999",
        ])->assertRedirect()->assertSessionHas('success');

        // El token válido manda; el resto se cae y las no nombradas van detrás.
        $this->assertSame($id, $p->fresh()->imagenes()->value('id'));
        $this->assertSame(3, $p->fresh()->imagenes()->count());
        $this->assertInvarianteGaleria($p);
    }

    // ── Borrados y cupo ──────────────────────────────────────────

    public function test_borrar_la_portada_la_pasa_a_la_siguiente(): void
    {
        $p = $this->productoConGaleria(3);
        [$a, $b, $c] = $p->imagenes()->pluck('id')->all();

        $this->post(route('uso-interno.productos.update', $p->id), $this->datosUpdate($p) + [
            'imagenes_eliminar' => [$a],
        ])->assertRedirect();

        $borrada = ImagenProducto::find($a);
        $this->assertFalse((bool) $borrada->activa);
        $this->assertFalse((bool) $borrada->es_principal);

        $this->assertSame([$b, $c], $p->fresh()->imagenes()->pluck('id')->all());
        $this->assertTrue((bool) ImagenProducto::find($b)->es_principal);
        $this->assertInvarianteGaleria($p);
    }

    public function test_borrar_todas_no_deja_portada_ni_explota(): void
    {
        $p = $this->productoConGaleria(2);

        $this->post(route('uso-interno.productos.update', $p->id), $this->datosUpdate($p) + [
            'imagenes_eliminar' => $p->imagenes()->pluck('id')->all(),
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame(0, $p->fresh()->imagenes()->count());
        $this->assertInvarianteGaleria($p);
    }

    /**
     * Con el cupo lleno, la subida se descarta y su token queda sin fila. Las
     * sobrevivientes tienen que salir densas igual.
     */
    public function test_el_cupo_descarta_la_subida_y_el_orden_queda_denso(): void
    {
        $p   = $this->productoConGaleria(Producto::MAX_IMAGENES);
        $ids = $p->imagenes()->pluck('id')->all();

        $this->post(route('uso-interno.productos.update', $p->id), $this->datosUpdate($p) + [
            'imagenes'       => [UploadedFile::fake()->image('sobra.jpg', 800, 600)],
            'imagenes_orden' => 'nueva:0,' . implode(',', array_map(fn($id) => "existente:$id", $ids)),
        ])->assertRedirect();

        $this->assertSame(Producto::MAX_IMAGENES, $p->fresh()->imagenes()->count());
        // El token nueva:0 no resolvió, así que manda el resto del manifiesto.
        $this->assertSame($ids, $p->fresh()->imagenes()->pluck('id')->all());
        $this->assertInvarianteGaleria($p);
    }

    /** Las técnicas no se ordenan ni participan de la portada. */
    public function test_las_tecnicas_quedan_al_margen(): void
    {
        $p = $this->productoConGaleria(2);
        $tecnicas = collect(['t1', 't2'])->map(fn($n) => ImagenProducto::create([
            'producto_id'   => $p->id,
            'nombre_imagen' => $n . '.webp',
            'ruta'          => 'https://files.test/' . $n . '.webp',
            'es_tecnica'    => true,
        ]));

        $this->post(route('uso-interno.productos.update', $p->id), $this->datosUpdate($p) + [
            'imagenes_orden' => 'existente:' . $p->imagenes()->pluck('id')->last(),
        ])->assertRedirect();

        // Siguen en orden de id y sin tocar su orden.
        $this->assertSame($tecnicas->pluck('id')->all(), $p->fresh()->imagenesTecnicas()->pluck('id')->all());
        $this->assertSame(0, $tecnicas->first()->fresh()->orden);
        $this->assertInvarianteGaleria($p);
    }
}
