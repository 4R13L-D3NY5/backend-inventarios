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
        Schema::table('items', function (Blueprint $table) {
            // Agregar nuevos campos
            $table->foreignId('laboratorio_destino_id')->nullable()->after('subcategoria_id')->constrained('laboratorios')->onDelete('set null');
            $table->foreignId('ubicacion_inicial_id')->nullable()->after('laboratorio_destino_id')->constrained('ubicaciones')->onDelete('set null');
            $table->decimal('stock_inicial', 10, 2)->default(0)->after('unidad_medida_base');
            $table->decimal('stock_minimo', 10, 2)->default(0)->after('stock_inicial');
            $table->text('especificaciones_tecnicas')->nullable()->after('descripcion');

            // Agregar índices
            $table->index('laboratorio_destino_id');
            $table->index('ubicacion_inicial_id');
            $table->index(['es_consumible', 'activo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            // Eliminar índices primero
            $table->dropIndex(['es_consumible', 'activo']);
            $table->dropIndex(['ubicacion_inicial_id']);
            $table->dropIndex(['laboratorio_destino_id']);

            // Eliminar foreign keys
            $table->dropForeign(['laboratorio_destino_id']);
            $table->dropForeign(['ubicacion_inicial_id']);

            // Eliminar columnas
            $table->dropColumn([
                'laboratorio_destino_id',
                'ubicacion_inicial_id',
                'stock_inicial',
                'stock_minimo',
                'especificaciones_tecnicas'
            ]);
        });
    }
};
