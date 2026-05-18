<?php

use App\Models\Categoria;
use App\Models\Variante;
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
        Schema::create('categorias_variantes', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Categoria::class);
            $table->foreignIdFor(Variante::class);
            $table->unique(['categoria_id', 'variante_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categorias_variantes');
    }
};
