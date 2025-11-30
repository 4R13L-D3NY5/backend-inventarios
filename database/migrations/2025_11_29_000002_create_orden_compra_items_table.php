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
        Schema::create('orden_compra_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_compra_id')->constrained('ordenes_compra')->onDelete('cascade');
            $table->foreignId('item_id')->constrained('items');
            $table->decimal('cantidad_solicitada', 10, 2);
            $table->decimal('cantidad_recibida', 10, 2)->default(0);
            $table->string('unidad_medida', 50);
            $table->decimal('precio_unitario', 10, 2);
            $table->decimal('subtotal', 12, 2);
            $table->text('observaciones')->nullable();
            $table->timestamps();

            // Índices
            $table->index('orden_compra_id');
            $table->index('item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orden_compra_items');
    }
};
