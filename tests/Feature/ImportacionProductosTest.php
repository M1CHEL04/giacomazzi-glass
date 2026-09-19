<?php
namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\ProductoEspecial;
use App\Models\UnidadMedida;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Carga masiva desde CSV (el xlsx pasa por el mismo lector de PhpSpreadsheet,
 * pero necesita ext-zip y el entorno local no siempre la tiene).
 */
class ImportacionProductosTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): static
    {
        $u = User::create(['name' => 'A', 'email' => 'a@a.com', 'password' => 'x']);
        $u->cambio_contraseña = true;
        $u->save();
        return $this->withSession(['user_email' => 'a@a.com', 'rol' => 'Admin']);
    }

    private function csv(string $contenido): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('productos.csv', $contenido);
    }

    public function test_linea_estandar_crea_las_filas_validas_y_reporta_las_demas(): void
    {
        $categoria = Categoria::create(['nombre' => 'Puertas metálicas', 'activo' => true]);
        Producto::create([
            'categoria_id' => $categoria->id, 'unidad_id' => 1,
            'nombre' => 'Existente', 'codigo' => 'EX-1', 'descripcion' => 'x',
        ]);

        $archivo = $this->csv(
            "Nombre;Código de producto;Categoría;Unidad;Descripción;Descripción técnica\n" .
            "Puerta A;PA-1;  PUERTAS   METÁLICAS ;unidades;Desc A;Técnica A\n" .
            "Puerta B;PB-1;puertas metalicas; Alto Ancho ;Desc B;\n" .
            ";;;;;\n" .
            "Ventana;VE-1;Ventanas;unidades;Desc V;\n" .
            "Repetida;PA-1;Puertas metálicas;unidades;Desc;\n" .
            "Ya cargada;EX-1;Puertas metálicas;unidades;Desc;\n" .
            "Sin unidad válida;SU-1;Puertas metálicas;metros;Desc;\n" .
            "Puerta C;PC-1;Puertas metálicas;ALTO-ANCHO;Desc C;\n"
        );

        $this->admin()
            ->post(route('uso-interno.productos.importar'), ['archivo' => $archivo])
            ->assertRedirect(route('uso-interno.productos.index'))
            ->assertSessionHas('importacion', function ($r) {
                $this->assertSame(['PA-1', 'PB-1', 'PC-1'], array_column($r['creados'], 'codigo'));
                $this->assertSame(['fila' => 2, 'codigo' => 'PA-1', 'nombre' => 'Puerta A'], $r['creados'][0]);
                $this->assertSame([5, 6, 7, 8], array_column($r['errores'], 'fila'));
                $this->assertStringContainsString('Ventanas', $r['errores'][0]['motivo']);
                $this->assertStringContainsString('metros', $r['errores'][3]['motivo']);
                return true;
            });

        $superficie = UnidadMedida::where('codigo', 'alto_ancho')->value('id');

        $a = Producto::where('codigo', 'PA-1')->firstOrFail();
        $this->assertSame($categoria->id, $a->categoria_id);
        $this->assertSame(UnidadMedida::where('codigo', 'unidades')->value('id'), $a->unidad_id);
        $this->assertFalse($a->es_especial);
        $this->assertSame('Técnica A', $a->descripcion_tecnica);
        $this->assertNull(Producto::where('codigo', 'PB-1')->value('descripcion_tecnica'));
        $this->assertSame($superficie, Producto::where('codigo', 'PB-1')->value('unidad_id'));
        $this->assertSame($superficie, Producto::where('codigo', 'PC-1')->value('unidad_id'));
    }

    public function test_linea_estandar_exige_la_columna_unidad(): void
    {
        Categoria::create(['nombre' => 'Portones', 'activo' => true]);

        $this->admin()
            ->post(route('uso-interno.productos.importar'), [
                'archivo' => $this->csv("Nombre;Código;Categoría;Descripción\nX;X-1;Portones;Desc\n"),
            ])
            ->assertSessionHas('importacion', function ($r) {
                $this->assertSame([], $r['creados']);
                $this->assertStringContainsString('unidad', $r['errores'][0]['motivo']);
                return true;
            });

        $this->assertSame(0, Producto::count());
    }

    public function test_linea_adapta_crea_productos_especiales_sin_unidad(): void
    {
        Categoria::create(['nombre' => 'Portones', 'activo' => true]);

        $archivo = $this->csv(
            "nombre,codigo,categoria,descripcion,descripcion tecnica\n" .
            "Portón corredizo,AD-1,portones,A medida,\n"
        );

        $this->admin()
            ->post(route('uso-interno.especiales.importar'), ['archivo' => $archivo])
            ->assertRedirect(route('uso-interno.especiales.index'));

        $p = ProductoEspecial::where('codigo', 'AD-1')->firstOrFail();
        $this->assertTrue($p->es_especial);
        $this->assertNull($p->unidad_id);
    }

    public function test_sin_columnas_obligatorias_no_crea_nada(): void
    {
        Categoria::create(['nombre' => 'Portones', 'activo' => true]);

        $this->admin()
            ->post(route('uso-interno.productos.importar'), [
                'archivo' => $this->csv("Nombre;Categoría\nX;Portones\n"),
            ])
            ->assertSessionHas('importacion', function ($r) {
                $this->assertSame([], $r['creados']);
                $this->assertStringContainsString('codigo', $r['errores'][0]['motivo']);
                return true;
            });

        $this->assertSame(0, Producto::count());
    }
}
