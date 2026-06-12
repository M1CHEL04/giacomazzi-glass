<?php

use App\Models\Categoria;
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
            $table->string('nombre');
            $table->string('descripcion');
            $table->string('descripcion_tecnica')->nullable();
            $table->string('codigo')->unique();
            $table->boolean('activo')->default(true)->index();
            $table->timestamps();

            // Índice compuesto para el filtro más frecuente: categoría + activo
            $table->index(['categoria_id', 'activo'], 'idx_productos_categoria_activo');
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
