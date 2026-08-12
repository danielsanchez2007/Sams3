<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prestamos_temporales', function (Blueprint $table) {
            $table->index(['created_by', 'estado', 'fecha_fin'], 'pt_created_estado_fechafin_idx');
            $table->index(['to_user_id', 'estado', 'fecha_fin'], 'pt_to_estado_fechafin_idx');
        });

        Schema::table('prestamo_temporal_items', function (Blueprint $table) {
            $table->index(['equipo_id', 'revision_estado'], 'pti_equipo_revision_idx');
            $table->index(['prestamo_id', 'revision_estado'], 'pti_prestamo_revision_idx');
        });

        Schema::table('aviso_empresas', function (Blueprint $table) {
            $table->index(['empresa_id', 'tipo', 'created_at'], 'aviso_empresa_tipo_fecha_idx');
        });
    }

    public function down(): void
    {
        Schema::table('prestamos_temporales', function (Blueprint $table) {
            $table->dropIndex('pt_created_estado_fechafin_idx');
            $table->dropIndex('pt_to_estado_fechafin_idx');
        });

        Schema::table('prestamo_temporal_items', function (Blueprint $table) {
            $table->dropIndex('pti_equipo_revision_idx');
            $table->dropIndex('pti_prestamo_revision_idx');
        });

        Schema::table('aviso_empresas', function (Blueprint $table) {
            $table->dropIndex('aviso_empresa_tipo_fecha_idx');
        });
    }
};

