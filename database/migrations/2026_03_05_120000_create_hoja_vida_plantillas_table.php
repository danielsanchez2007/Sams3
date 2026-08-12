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
        Schema::create('hoja_vida_plantillas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tipo_equipo_id')->constrained('tipo_equipos')->cascadeOnDelete();
            $table->string('plantilla_excel_path');
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('tipo_equipo_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hoja_vida_plantillas');
    }
};
