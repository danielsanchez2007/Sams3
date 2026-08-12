<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prestamos_temporales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('to_user_id')->constrained('users')->cascadeOnDelete();
            $table->date('fecha_salida');
            $table->enum('estado', ['activo', 'pendiente_revision', 'finalizado'])->default('activo')->index();
            $table->longText('html_formulario')->nullable();
            $table->timestamp('devuelto_at')->nullable();
            $table->timestamp('finalizado_at')->nullable();
            $table->timestamps();
        });

        Schema::create('prestamo_temporal_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prestamo_id')->constrained('prestamos_temporales')->cascadeOnDelete();
            $table->foreignId('equipo_id')->constrained('equipos')->cascadeOnDelete();
            $table->enum('revision_estado', ['pendiente', 'si', 'no', 'novedad'])->default('pendiente')->index();
            $table->text('revision_novedad')->nullable();
            $table->foreignId('revision_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revision_at')->nullable();
            $table->timestamps();

            $table->unique(['prestamo_id', 'equipo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prestamo_temporal_items');
        Schema::dropIfExists('prestamos_temporales');
    }
};

