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
        Schema::create('almacenes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('codigo', 20)->unique();
            $table->enum('tipo', ['principal', 'subalmacen'])->default('principal');
            $table->foreignId('almacen_padre_id')->nullable()->constrained('almacenes')->onDelete('restrict');
            $table->foreignId('ubicacion_id')->nullable()->constrained('ubicaciones')->onDelete('set null');
            $table->foreignId('responsable_id')->nullable()->constrained('personals')->onDelete('set null');
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            
            // Índices
            $table->index('codigo');
            $table->index('tipo');
            $table->index('almacen_padre_id');
            $table->index('activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('almacenes');
    }
};
