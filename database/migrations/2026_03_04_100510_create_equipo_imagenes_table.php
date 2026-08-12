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
        Schema::create('equipo_imagenes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipo_id')->constrained('equipos')->onDelete('cascade');
            $table->string('tipo', 50);
            $table->string('path', 500);
            $table->timestamps();

            $table->index(['equipo_id', 'tipo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipo_imagenes');
    }
};
