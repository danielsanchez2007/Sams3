<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            if (!Schema::hasColumn('empresas', 'logo_principal')) {
                $table->string('logo_principal', 255)->nullable()->after('logo');
            }
            if (!Schema::hasColumn('empresas', 'logo_secundario')) {
                $table->string('logo_secundario', 255)->nullable()->after('logo_principal');
            }
            if (!Schema::hasColumn('empresas', 'foto_empresa')) {
                $table->string('foto_empresa', 255)->nullable()->after('logo_secundario');
            }
            if (!Schema::hasColumn('empresas', 'latitud')) {
                $table->decimal('latitud', 10, 7)->nullable()->after('google_maps_url');
            }
            if (!Schema::hasColumn('empresas', 'longitud')) {
                $table->decimal('longitud', 10, 7)->nullable()->after('latitud');
            }
            if (!Schema::hasColumn('empresas', 'altitud')) {
                $table->decimal('altitud', 10, 2)->nullable()->after('longitud');
            }
            if (!Schema::hasColumn('empresas', 'color_secundario_1')) {
                $table->string('color_secundario_1', 20)->nullable()->after('color_primario');
            }
            if (!Schema::hasColumn('empresas', 'color_secundario_2')) {
                $table->string('color_secundario_2', 20)->nullable()->after('color_secundario_1');
            }
            if (!Schema::hasColumn('empresas', 'color_extra_4')) {
                $table->string('color_extra_4', 20)->nullable()->after('color_secundario_2');
            }
            if (!Schema::hasColumn('empresas', 'color_extra_5')) {
                $table->string('color_extra_5', 20)->nullable()->after('color_extra_4');
            }
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            foreach ([
                'logo_principal',
                'logo_secundario',
                'foto_empresa',
                'latitud',
                'longitud',
                'altitud',
                'color_secundario_1',
                'color_secundario_2',
                'color_extra_4',
                'color_extra_5',
            ] as $column) {
                if (Schema::hasColumn('empresas', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

