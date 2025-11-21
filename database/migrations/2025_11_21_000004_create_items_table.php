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
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 100)->unique();
            $table->string('nombre', 255);
            $table->foreignId('categoria_id')->constrained('categorias')->onDelete('restrict');
            $table->foreignId('subcategoria_id')->nullable()->constrained('subcategorias')->onDelete('restrict');
            $table->string('marca', 100)->nullable();
            $table->string('modelo', 100)->nullable();
            $table->string('unidad_medida_base', 20); // g, mL, UN, etc.
            $table->boolean('es_consumible')->default(true);
            $table->boolean('es_peligroso')->default(false);
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('codigo');
            $table->index('categoria_id');
            $table->index('subcategoria_id');
            $table->index('nombre');
            $table->index('activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
