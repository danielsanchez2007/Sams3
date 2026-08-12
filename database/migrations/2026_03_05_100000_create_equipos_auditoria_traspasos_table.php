<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipos_auditoria_traspasos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipo_id')->constrained('equipos')->cascadeOnDelete();
            $table->string('codigo_aud', 20)->unique();
            $table->string('codigo_anterior', 50)->nullable();
            $table->foreignId('traspasado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('traspasado_en');
            $table->timestamps();

            $table->index('equipo_id');
            $table->index('traspasado_por');
            $table->index('traspasado_en');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipos_auditoria_traspasos');
    }
};
