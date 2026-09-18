<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categorias', function (Blueprint $table) {
            // x/y en porcentaje (0–100); 50/50 es el centro, o sea el
            // comportamiento que tenía el hero antes de esta columna.
            $table->decimal('hero_movil_x', 5, 2)->default(50);
            $table->decimal('hero_movil_y', 5, 2)->default(50);
            // zoom 1 = la foto apenas cubre el recuadro (object-fit: cover).
            $table->decimal('hero_movil_zoom', 4, 2)->default(1);

            $table->decimal('hero_escritorio_x', 5, 2)->default(50);
            $table->decimal('hero_escritorio_y', 5, 2)->default(50);
            $table->decimal('hero_escritorio_zoom', 4, 2)->default(1);
        });
    }

    public function down(): void
    {
        Schema::table('categorias', function (Blueprint $table) {
            $table->dropColumn([
                'hero_movil_x',
                'hero_movil_y',
                'hero_movil_zoom',
                'hero_escritorio_x',
                'hero_escritorio_y',
                'hero_escritorio_zoom',
            ]);
        });
    }
};
