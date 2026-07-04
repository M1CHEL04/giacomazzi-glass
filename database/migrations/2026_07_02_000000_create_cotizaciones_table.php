<?php

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
        // Registra cada "Solicitar cotización por WhatsApp" (clic en cotizar).
        // No garantiza que el mensaje se haya enviado — mide la intención de consulta.
        Schema::create('cotizaciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('cantidad_items')->default(0);
            $table->json('items')->nullable();   // snapshot del carrito al momento de cotizar
            $table->timestamps();

            $table->index('created_at');         // para stats por período (mes/día)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cotizaciones');
    }
};
