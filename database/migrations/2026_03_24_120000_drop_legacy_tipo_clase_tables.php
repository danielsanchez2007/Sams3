<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('clases_equipos')) {
            Schema::drop('clases_equipos');
        }

        if (Schema::hasTable('tipos_equipos')) {
            Schema::drop('tipos_equipos');
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('tipos_equipos')) {
            Schema::create('tipos_equipos', function (Blueprint $table) {
                $table->id();
                $table->string('nombre');
                $table->text('descripcion')->nullable();
                $table->boolean('activo')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('clases_equipos')) {
            Schema::create('clases_equipos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tipo_equipo_id')->constrained('tipos_equipos')->onDelete('cascade');
                $table->string('nombre');
                $table->text('descripcion')->nullable();
                $table->boolean('activo')->default(true);
                $table->timestamps();
            });
        }
    }
};
