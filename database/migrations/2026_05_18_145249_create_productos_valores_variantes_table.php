<?php

use App\Models\Producto;
use App\Models\ValorVariante;
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
        Schema::create('productos_valores_variantes', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Producto::class);
            $table->foreignIdFor(ValorVariante::class);
            $table->unique(['producto_id', 'valor_variante_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos_valores_variantes');
    }
};
