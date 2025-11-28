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
        Schema::create('movimientos_inventario', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', ['entrada', 'salida', 'traspaso', 'ajuste']);
            $table->foreignId('item_id')->constrained('items')->onDelete('restrict');
            
            $table->decimal('cantidad', 10, 2);
            $table->string('unidad_medida', 20);
            
            $table->foreignId('almacen_origen_id')->nullable()->constrained('almacenes')->onDelete('restrict');
            $table->foreignId('almacen_destino_id')->nullable()->constrained('almacenes')->onDelete('restrict');
            
            $table->foreignId('responsable_id')->constrained('users')->onDelete('restrict');
            // $table->foreignId('orden_compra_id')->nullable()->constrained('ordenes_compra')->onDelete('set null');
            $table->unsignedBigInteger('orden_compra_id')->nullable(); // Temporal hasta crear tabla ordenes_compra
            $table->foreignId('solicitud_id')->nullable()->constrained('solicitudes')->onDelete('set null');
            
            $table->text('motivo');
            $table->text('observaciones')->nullable();
            $table->datetime('fecha_movimiento');
            
            $table->timestamps();
            
            // Índices
            $table->index('tipo');
            $table->index('item_id');
            $table->index('almacen_origen_id');
            $table->index('almacen_destino_id');
            $table->index('fecha_movimiento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimientos_inventario');
    }
};
