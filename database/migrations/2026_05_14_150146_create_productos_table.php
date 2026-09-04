<?php

use App\Models\Categoria;
use App\Models\UnidadMedida;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Categoria::class)->index();
            // Nullable por los productos especiales: no se cotizan por carrito,
            // así que no tienen unidad de cotización.
            $table->foreignIdFor(UnidadMedida::class, 'unidad_id')->nullable()->index();
            $table->string('nombre');
            $table->string('descripcion');
            $table->text('descripcion_tecnica')->nullable();
            $table->string('codigo')->unique();
            $table->boolean('activo')->default(true)->index();
            // Producto a medida: mismo catálogo, otro CRUD y otra ficha.
            $table->boolean('es_especial')->default(false);
            $table->timestamps();

            // Índice compuesto para el filtro más frecuente: categoría + activo
            $table->index(['categoria_id', 'activo'], 'idx_productos_categoria_activo');
            $table->index(['es_especial', 'activo'], 'idx_productos_especial_activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
