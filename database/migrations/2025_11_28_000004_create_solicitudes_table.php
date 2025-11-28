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
        Schema::create('solicitudes', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->unique();
            $table->enum('tipo', ['reposicion', 'compra_nueva']);
            $table->enum('prioridad', ['baja', 'media', 'alta'])->default('media');
            
            // Origen de la solicitud
            $table->foreignId('solicitante_id')->constrained('personals')->onDelete('restrict');
            $table->foreignId('laboratorio_id')->constrained('laboratorios')->onDelete('restrict');
            $table->foreignId('ubicacion_id')->nullable()->constrained('ubicaciones')->onDelete('set null');
            
            $table->text('observaciones')->nullable();
            $table->date('fecha_solicitud');
            $table->datetime('fecha_completada')->nullable();
            
            // Estados por nivel
            $table->enum('estado_subalmacen', ['pendiente', 'aprobado', 'denegado'])->default('pendiente');
            $table->enum('estado_almacen', ['pendiente', 'aprobado', 'denegado'])->nullable();
            $table->enum('estado_adquisicion', ['pendiente', 'aprobado', 'denegado'])->nullable();
            
            // Estado general
            $table->enum('estado_general', [
                'pendiente_subalmacen',
                'pendiente_almacen', 
                'en_adquisiciones',
                'completada',
                'denegada'
            ])->default('pendiente_subalmacen');
            
            // Aprobadores
            $table->foreignId('aprobado_subalmacen_por')->nullable()->constrained('users')->onDelete('set null');
            $table->datetime('aprobado_subalmacen_fecha')->nullable();
            $table->foreignId('aprobado_almacen_por')->nullable()->constrained('users')->onDelete('set null');
            $table->datetime('aprobado_almacen_fecha')->nullable();
            $table->foreignId('aprobado_adquisicion_por')->nullable()->constrained('users')->onDelete('set null');
            $table->datetime('aprobado_adquisicion_fecha')->nullable();
            
            // Denegación
            $table->text('motivo_denegacion')->nullable();
            $table->foreignId('denegado_por')->nullable()->constrained('users')->onDelete('set null');
            $table->datetime('denegado_fecha')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index('codigo');
            $table->index('tipo');
            $table->index('prioridad');
            $table->index('estado_general');
            $table->index('solicitante_id');
            $table->index('laboratorio_id');
            $table->index('fecha_solicitud');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitudes');
    }
};
