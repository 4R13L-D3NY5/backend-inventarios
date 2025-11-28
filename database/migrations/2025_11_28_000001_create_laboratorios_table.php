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
        Schema::create('laboratorios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100)->unique();
            $table->string('codigo', 20)->unique()->nullable();
            $table->text('descripcion')->nullable();
            $table->foreignId('responsable_id')->nullable()->constrained('personals')->onDelete('set null');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            // Índices
            $table->index('nombre');
            $table->index('codigo');
            $table->index('activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laboratorios');
    }
};
