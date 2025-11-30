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
        Schema::create('ordenes_compra', function (Blueprint $table) {
            $table->id();
            $table->string('numero_orden')->unique();
            $table->foreignId('solicitud_id')->nullable()->constrained('solicitudes');
            $table->foreignId('proveedor_id')->constrained('proveedores');
            $table->date('fecha_emision');
            $table->date('fecha_entrega_estimada')->nullable();
            $table->date('fecha_entrega_real')->nullable();
            $table->enum('estado', [
                'borrador',
                'enviada',
                'confirmada',
                'recibida_parcial',
                'recibida_completa',
                'cancelada'
            ])->default('borrador');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('impuestos', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->string('moneda', 10)->default('BOB');
            $table->text('condiciones_pago')->nullable();
            $table->text('observaciones')->nullable();
            $table->foreignId('usuario_creador_id')->constrained('users');
            $table->foreignId('usuario_aprobador_id')->nullable()->constrained('users');
            $table->timestamp('fecha_aprobacion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            // Índices
            $table->index('proveedor_id');
            $table->index('solicitud_id');
            $table->index('estado');
            $table->index('fecha_emision');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ordenes_compra');
    }
};
