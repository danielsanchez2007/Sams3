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
        Schema::create('empresas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 200);
            $table->string('nit', 50)->unique();
            $table->text('direccion')->nullable();
            $table->string('telefono', 50)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('sitio_web', 255)->nullable();
            $table->string('logo', 255)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            
            $table->index('activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empresas');
    }
};
