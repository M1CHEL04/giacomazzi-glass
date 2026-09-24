<?php

use App\Models\Producto;
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
        Schema::create('imagenes_producto', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Producto::class)->constrained()->onDelete('cascade');
            $table->boolean('es_principal')->default(false);
            $table->boolean('es_tecnica')->default(false);
            $table->boolean('activa')->default(true);
            // Posición en la galería, densa (0..n-1) entre las filas activas y no
            // técnicas de un producto. La fila con orden 0 es la portada, y
            // es_principal es su reflejo denormalizado: existe para que las grillas
            // sigan filtrando por un booleano en vez de buscar un MIN(orden).
            // Los dos los escribe únicamente SincronizadorImagenesProducto.
            // En técnicas y en filas con activa = false, orden no significa nada.
            $table->unsignedSmallInteger('orden')->default(0);
            $table->string('ruta')->nullable();
            // Variante reducida (~600px) que usan grids y miniaturas. Nullable
            // porque el thumb es una optimización: si su subida falla, la imagen
            // igual queda usable y las vistas caen a `ruta`.
            $table->string('ruta_thumb')->nullable();
            $table->string('nombre_imagen')->nullable();
            $table->timestamps();

            // Índice compuesto para el eager load filtrado por activa y es_principal
            $table->index(['producto_id', 'activa', 'es_principal'], 'idx_imagenes_producto_activa_principal');

            // El de arriba sirve a las grillas, que filtran por es_principal. Este
            // cubre el otro acceso: traer la galería completa de un producto ya
            // ordenada, que es lo que hacen las relaciones de Producto.
            $table->index(['producto_id', 'activa', 'es_tecnica', 'orden'], 'idx_imagenes_producto_orden');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('imagenes_producto');
    }
};
