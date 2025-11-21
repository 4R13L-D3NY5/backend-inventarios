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
        Schema::create('historial_precios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->onDelete('cascade');
            $table->foreignId('proveedor_id')->constrained('proveedores')->onDelete('cascade');
            $table->decimal('precio', 12, 2);
            $table->date('fecha_vigencia');
            $table->string('moneda', 3)->default('BOB');
            $table->text('observaciones')->nullable();
            $table->timestamps();

            // Índices
            $table->index('item_id');
            $table->index('proveedor_id');
            $table->index('fecha_vigencia');
            $table->index(['item_id', 'proveedor_id', 'fecha_vigencia']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historial_precios');
    }
};
