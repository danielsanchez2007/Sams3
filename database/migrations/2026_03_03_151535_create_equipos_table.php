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
        // La tabla "equipos" se crea antes en una migración base para soportar FKs tempranas.
        // Aquí solo se crea si aún no existe.
        if (Schema::hasTable('equipos')) {
            return;
        }

        Schema::create('equipos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->string('serial', 100)->unique();
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->foreignId('tipo_equipo_id')->constrained('tipo_equipos')->onDelete('cascade');
            $table->foreignId('clase_equipo_id')->constrained('clase_equipos')->onDelete('cascade');
            $table->timestamps();
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
