<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bodegas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('sede_id')->nullable()->constrained('sedes')->nullOnDelete();
            $table->string('nombre', 200);
            $table->string('pais', 120)->nullable();
            $table->string('departamento', 120)->nullable();
            $table->string('municipio', 120)->nullable();
            $table->string('ciudad', 120)->nullable();
            $table->text('direccion')->nullable();
            $table->string('google_maps_url', 500)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['empresa_id', 'sede_id', 'activo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bodegas');
    }
};
