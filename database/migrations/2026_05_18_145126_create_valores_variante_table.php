<?php

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
        Schema::create('valores_variante', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Variante::class);
            $table->string('valor');
            $table->unique(['variante_id', 'valor']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('valores_variante');
    }
};
