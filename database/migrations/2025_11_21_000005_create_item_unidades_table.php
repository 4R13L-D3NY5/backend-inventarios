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
        Schema::create('item_unidades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->onDelete('cascade');
            $table->string('unidad_medida', 20); // kg, L, caja, etc.
            $table->decimal('factor_a_base', 10, 4); // Factor de conversión a UM base
            $table->timestamps();

            // Índices
            $table->index('item_id');
            
            // Constraint: unidad única por ítem
            $table->unique(['item_id', 'unidad_medida']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_unidades');
    }
};
