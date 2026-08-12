<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipo_asignacion_solicitudes', function (Blueprint $table) {
            $table->string('workflow_step', 40)->default('pendiente_usuario')->after('estado')->index();
            $table->text('comentario_revision')->nullable()->after('html_formulario');
        });
    }

    public function down(): void
    {
        Schema::table('equipo_asignacion_solicitudes', function (Blueprint $table) {
            $table->dropIndex(['workflow_step']);
            $table->dropColumn(['workflow_step', 'comentario_revision']);
        });
    }
};

