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
        Schema::create('inventarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('almacen_id')->constrained('almacenes')->onDelete('cascade');
            $table->foreignId('item_id')->constrained('items')->onDelete('restrict');
            
            $table->decimal('cantidad_actual', 10, 2)->default(0);
            $table->decimal('cantidad_reservada', 10, 2)->default(0);
            $table->decimal('stock_minimo', 10, 2)->default(0);
            $table->decimal('stock_maximo', 10, 2)->nullable();
            
            $table->datetime('ultima_actualizacion');
            $table->timestamps();
            
            // Índice único compuesto
            $table->unique(['almacen_id', 'item_id']);
            
            // Índices adicionales
            $table->index('almacen_id');
            $table->index('item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventarios');
    }
};
