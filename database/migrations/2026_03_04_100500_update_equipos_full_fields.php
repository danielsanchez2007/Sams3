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
        Schema::table('equipos', function (Blueprint $table) {
            $table->string('codigo', 20)->nullable()->unique()->after('id');
            $table->integer('vida_util')->nullable()->after('codigo');

            $table->string('estado_item', 30)->nullable()->after('vida_util');
            $table->text('observacion')->nullable()->after('estado_item');

            $table->foreignId('fabricante_id')->nullable()->after('clase_equipo_id')->constrained('fabricantes')->nullOnDelete();

            $table->foreignId('empresa_id')->nullable()->after('fabricante_id')->constrained('empresas')->nullOnDelete();
            $table->foreignId('sede_id')->nullable()->after('empresa_id')->constrained('sedes')->nullOnDelete();
            $table->foreignId('bodega_id')->nullable()->after('sede_id')->constrained('bodegas')->nullOnDelete();

            $table->date('fecha_fabricacion')->nullable()->after('bodega_id');
            $table->date('fecha_uso')->nullable()->after('fecha_fabricacion');
            $table->string('tipo_uso', 100)->nullable()->after('fecha_uso');

            $table->boolean('es_kit')->default(false)->after('tipo_uso');
            $table->string('kit_nombre', 200)->nullable()->after('es_kit');
            $table->unsignedInteger('kit_cantidad')->nullable()->after('kit_nombre');

            $table->text('certificacion_descripcion')->nullable()->after('kit_cantidad');
            $table->text('especificaciones_tecnicas')->nullable()->after('certificacion_descripcion');

            $table->decimal('valor_equipo', 12, 2)->nullable()->after('especificaciones_tecnicas');
            $table->string('numero_factura', 100)->nullable()->after('valor_equipo');
            $table->date('fecha_compra')->nullable()->after('numero_factura');
            $table->string('lote', 120)->nullable()->after('fecha_compra');

            $table->boolean('tiene_resistencia')->default(false)->after('lote');
            $table->string('resistencia_descripcion', 500)->nullable()->after('tiene_resistencia');

            $table->boolean('tiene_manual_fabricante')->default(false)->after('resistencia_descripcion');
            $table->boolean('tiene_certificacion_fabricante')->default(false)->after('tiene_manual_fabricante');

            $table->index('codigo');
            $table->index('estado_item');
            $table->index(['empresa_id', 'sede_id', 'bodega_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            $table->dropIndex(['codigo']);
            $table->dropIndex(['estado_item']);
            $table->dropIndex(['empresa_id', 'sede_id', 'bodega_id']);

            $table->dropForeign(['bodega_id']);
            $table->dropForeign(['sede_id']);
            $table->dropForeign(['empresa_id']);
            $table->dropForeign(['fabricante_id']);

            $table->dropUnique(['codigo']);

            $table->dropColumn([
                'codigo',
                'vida_util',
                'estado_item',
                'observacion',
                'fabricante_id',
                'empresa_id',
                'sede_id',
                'bodega_id',
                'fecha_fabricacion',
                'fecha_uso',
                'tipo_uso',
                'es_kit',
                'kit_nombre',
                'kit_cantidad',
                'certificacion_descripcion',
                'especificaciones_tecnicas',
                'valor_equipo',
                'numero_factura',
                'fecha_compra',
                'lote',
                'tiene_resistencia',
                'resistencia_descripcion',
                'tiene_manual_fabricante',
                'tiene_certificacion_fabricante',
            ]);
        });
    }
};
