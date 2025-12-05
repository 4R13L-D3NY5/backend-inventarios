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
        // Update 'permisos' table
        if (Schema::hasTable('permisos')) {
            Schema::table('permisos', function (Blueprint $table) {
                if (!Schema::hasColumn('permisos', 'nombre')) {
                    $table->string('nombre');
                }
                if (!Schema::hasColumn('permisos', 'clave')) {
                    $table->string('clave')->unique();
                }
                if (!Schema::hasColumn('permisos', 'descripcion')) {
                    $table->text('descripcion')->nullable();
                }
                if (!Schema::hasColumn('permisos', 'grupo')) {
                    $table->string('grupo')->nullable(); // For grouping in UI (Inventario, Compras, etc.)
                }
                if (!Schema::hasColumn('permisos', 'estado')) {
                    $table->boolean('estado')->default(true);
                }
            });
        } else {
            Schema::create('permisos', function (Blueprint $table) {
                $table->id();
                $table->string('nombre');
                $table->string('clave')->unique();
                $table->text('descripcion')->nullable();
                $table->string('grupo')->nullable();
                $table->boolean('estado')->default(true);
                $table->timestamps();
            });
        }

        // Create pivot table 'rol_permiso'
        if (!Schema::hasTable('rol_permiso')) {
            Schema::create('rol_permiso', function (Blueprint $table) {
                $table->id();
                $table->foreignId('rol_id')->constrained('rols')->onDelete('cascade');
                $table->foreignId('permiso_id')->constrained('permisos')->onDelete('cascade');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rol_permiso');
        // We don't drop 'permisos' because it might have existed before,
        // but we could drop columns if we wanted to be strict.
        // For now, just dropping the pivot is enough for rollback of the relationship.
    }
};
