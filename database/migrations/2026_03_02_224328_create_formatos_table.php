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
        Schema::create('formatos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 200);
            $table->text('descripcion')->nullable();
            $table->enum('tipo', ['hoja_vida', 'inspeccion', 'exportacion']);
            $table->text('contenido')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            
            $table->index('tipo');
            $table->index('activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('formatos');
    }
};
