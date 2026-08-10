<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Unidades en las que se cotiza un producto. Es un catálogo cerrado (no hay ABM),
 * por eso las filas se siembran acá y no en un seeder: así existen sí o sí en
 * cualquier entorno apenas se corren las migraciones.
 *
 * `requiere_alto` / `requiere_ancho` definen qué campos de medida pide la ficha
 * del producto. Superficie (m²) = requiere ambos.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('unidades_medida', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->string('nombre');
            $table->string('simbolo', 4);
            $table->boolean('requiere_alto')->default(false);
            $table->boolean('requiere_ancho')->default(false);
            $table->timestamps();
        });

        $ahora = now();

        DB::table('unidades_medida')->insert([
            [
                'codigo'         => 'unidades',
                'nombre'         => 'Unidades',
                'simbolo'        => 'u',
                'requiere_alto'  => false,
                'requiere_ancho' => false,
                'created_at'     => $ahora,
                'updated_at'     => $ahora,
            ],
            [
                'codigo'         => 'alto',
                'nombre'         => 'Alto (m)',
                'simbolo'        => 'm',
                'requiere_alto'  => true,
                'requiere_ancho' => false,
                'created_at'     => $ahora,
                'updated_at'     => $ahora,
            ],
            [
                'codigo'         => 'ancho',
                'nombre'         => 'Ancho (m)',
                'simbolo'        => 'm',
                'requiere_alto'  => false,
                'requiere_ancho' => true,
                'created_at'     => $ahora,
                'updated_at'     => $ahora,
            ],
            [
                'codigo'         => 'alto_ancho',
                'nombre'         => 'Alto × Ancho (m²)',
                'simbolo'        => 'm²',
                'requiere_alto'  => true,
                'requiere_ancho' => true,
                'created_at'     => $ahora,
                'updated_at'     => $ahora,
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unidades_medida');
    }
};
