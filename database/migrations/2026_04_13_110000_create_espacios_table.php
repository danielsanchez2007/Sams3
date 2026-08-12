<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('espacios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sede_id')->constrained()->cascadeOnDelete();
            $table->string('nombre', 120);
            $table->timestamps();

            $table->unique(['sede_id', 'nombre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('espacios');
    }
};
