<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Unidades en las que se cotiza un producto. Las filas las carga
 * UnidadesMedidaSeeder.
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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unidades_medida');
    }
};
