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
            $table->boolean('activa')->default(true);
            $table->string('ruta')->nullable();
            $table->string('nombre_imagen')->nullable();
            $table->timestamps();
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
