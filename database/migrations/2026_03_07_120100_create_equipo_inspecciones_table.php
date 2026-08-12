<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('equipo_inspecciones')) {
            return;
        }
        Schema::create('equipo_inspecciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipo_id')->constrained('equipos')->cascadeOnDelete();
            $table->date('fecha_inspeccion');
            $table->date('validez_hasta');
            $table->longText('edited_html');
            $table->string('pdf_path')->nullable();
            $table->boolean('inspeccion_obligatoria')->default(false);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipo_inspecciones');
    }
};
