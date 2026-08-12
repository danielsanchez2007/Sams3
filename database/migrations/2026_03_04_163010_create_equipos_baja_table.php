<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipos_baja', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipo_id')->nullable()->constrained('equipos')->nullOnDelete();
            $table->string('codigo_db', 20)->unique();
            $table->string('codigo_in', 20)->nullable()->index();

            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->date('fecha_baja')->nullable();
            $table->text('motivo_baja')->nullable();
            $table->text('observaciones_baja')->nullable();

            $table->json('equipo_snapshot')->nullable();
            $table->json('form_data')->nullable();

            $table->string('plantilla_excel_path')->nullable();
            $table->string('pdf_path')->nullable();

            $table->timestamps();

            $table->index(['fecha_baja']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipos_baja');
    }
};
