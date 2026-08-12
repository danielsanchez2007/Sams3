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
        // Se crea una versión base de "equipos" temprano porque otras migraciones del mismo día
        // (p. ej. historial_mantenimientos) la referencian. Las relaciones/columnas extra se
        // agregan en migraciones posteriores.
        if (Schema::hasTable('equipos')) {
            return;
        }

        Schema::create('equipos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->string('serial', 100)->unique();
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);

            // En este punto aún pueden no existir tipo_equipos/clase_equipos, así que se crean
            // las columnas sin constraints. Migraciones posteriores normalizan el esquema.
            $table->unsignedBigInteger('tipo_equipo_id')->nullable();
            $table->unsignedBigInteger('clase_equipo_id')->nullable();

            $table->timestamps();

            $table->index(['tipo_equipo_id', 'clase_equipo_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipos');
    }
};
