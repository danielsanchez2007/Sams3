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
        Schema::create('auditoria_equipos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipo_id')->constrained('equipos')->onDelete('cascade');
            $table->foreignId('auditor_id')->constrained('users')->onDelete('cascade');
            $table->date('fecha_auditoria');
            $table->enum('estado_fisico', ['excelente', 'bueno', 'regular', 'malo', 'muy_malo']);
            $table->enum('estado_funcional', ['optimo', 'funcional', 'parcial', 'no_funcional', 'danado']);
            $table->boolean('ubicacion_verificada')->default(false);
            $table->boolean('responsable_verificado')->default(false);
            $table->text('observaciones')->nullable();
            $table->text('recomendaciones')->nullable();
            $table->boolean('cumple_normas')->default(false);
            $table->integer('puntuacion')->nullable(); // 1-100
            $table->timestamps();
            
            $table->index('equipo_id');
            $table->index('auditor_id');
            $table->index('fecha_auditoria');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auditoria_equipos');
    }
};
