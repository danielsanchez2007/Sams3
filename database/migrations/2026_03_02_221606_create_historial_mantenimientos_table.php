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
        Schema::create('historial_mantenimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipo_id')->constrained('equipos')->onDelete('cascade');
            $table->enum('tipo_mantenimiento', ['preventivo', 'correctivo', 'calibracion', 'limpieza', 'actualizacion', 'reparacion']);
            $table->text('descripcion');
            $table->date('fecha_mantenimiento');
            $table->foreignId('tecnico_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('costo', 10, 2)->nullable();
            $table->text('repuestos')->nullable();
            $table->text('observaciones')->nullable();
            $table->enum('estado_equipo', ['activo', 'mantenimiento', 'reparacion', 'baja', 'obsoleto', 'reserva']);
            $table->timestamps();
            
            $table->index('equipo_id');
            $table->index('fecha_mantenimiento');
            $table->index('tecnico_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historial_mantenimientos');
    }
};
