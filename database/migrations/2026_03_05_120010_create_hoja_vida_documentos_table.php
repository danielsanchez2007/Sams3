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
        Schema::create('hoja_vida_documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipo_id')->constrained('equipos')->cascadeOnDelete();
            $table->foreignId('tipo_equipo_id')->constrained('tipo_equipos')->cascadeOnDelete();
            $table->foreignId('plantilla_id')->constrained('hoja_vida_plantillas')->cascadeOnDelete();
            $table->longText('edited_html');
            $table->json('form_data')->nullable();
            $table->string('excel_path')->nullable();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('equipo_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hoja_vida_documentos');
    }
};
