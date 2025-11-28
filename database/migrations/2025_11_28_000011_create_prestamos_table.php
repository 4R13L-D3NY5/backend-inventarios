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
        Schema::create('prestamos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->unique();
            
            // Equipo y ubicación
            $table->foreignId('item_id')->constrained('items')->onDelete('restrict');
            $table->foreignId('custodio_id')->constrained('personals')->onDelete('restrict');
            $table->foreignId('autorizado_por')->constrained('users')->onDelete('restrict');
            $table->foreignId('almacen_id')->constrained('almacenes')->onDelete('restrict');
            $table->foreignId('laboratorio_id')->nullable()->constrained('laboratorios')->onDelete('set null');
            
            // Fechas
            $table->date('fecha_prestamo');
            $table->date('fecha_vencimiento');
            $table->datetime('fecha_devolucion_real')->nullable();
            
            // Estado
            $table->enum('estado', ['activo', 'vencido', 'devuelto', 'cancelado'])->default('activo');
            
            // Estado del equipo
            $table->enum('estado_equipo_prestamo', ['bueno', 'regular', 'malo'])->default('bueno');
            $table->enum('estado_equipo_devolucion', ['bueno', 'regular', 'malo'])->nullable();
            
            // Observaciones
            $table->text('observaciones_prestamo')->nullable();
            $table->text('observaciones_devolucion')->nullable();
            
            // Devolución
            $table->foreignId('recibido_por')->nullable()->constrained('users')->onDelete('set null');
            
            // Recordatorios
            $table->boolean('recordatorio_enviado')->default(false);
            $table->datetime('fecha_recordatorio')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index('codigo');
            $table->index('item_id');
            $table->index('custodio_id');
            $table->index('estado');
            $table->index('fecha_vencimiento');
            $table->index('fecha_prestamo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prestamos');
    }
};
