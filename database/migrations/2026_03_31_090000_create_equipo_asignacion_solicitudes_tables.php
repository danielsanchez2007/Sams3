<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipo_asignacion_solicitudes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('to_user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('estado', ['pendiente', 'aceptada', 'rechazada'])->default('pendiente')->index();
            $table->longText('html_formulario')->nullable();
            $table->timestamp('respondido_at')->nullable();
            $table->timestamps();
        });

        Schema::create('equipo_asignacion_solicitud_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_id')->constrained('equipo_asignacion_solicitudes')->cascadeOnDelete();
            $table->foreignId('equipo_id')->constrained('equipos')->cascadeOnDelete();
            $table->foreignId('from_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['solicitud_id', 'equipo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipo_asignacion_solicitud_items');
        Schema::dropIfExists('equipo_asignacion_solicitudes');
    }
};

