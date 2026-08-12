<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('inspeccion_plantillas')) {
            return;
        }
        Schema::create('inspeccion_plantillas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tipo_equipo_id')->constrained('tipo_equipos')->cascadeOnDelete();
            $table->string('plantilla_excel_path');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspeccion_plantillas');
    }
};
