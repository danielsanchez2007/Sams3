<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('codigos_reutilizables', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->unique();
            $table->string('estado', 20)->default('disponible'); // disponible|reservado|usado
            $table->foreignId('reservado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reservado_en')->nullable();
            $table->timestamp('usado_en')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('codigos_reutilizables');
    }
};
