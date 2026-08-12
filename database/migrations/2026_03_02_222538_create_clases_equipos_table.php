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
        Schema::create('clases_equipos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tipo_equipo_id')->constrained('tipos_equipos')->onDelete('cascade');
            $table->string('nombre', 100);
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            
            $table->index('tipo_equipo_id');
            $table->index('activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clases_equipos');
    }
};
