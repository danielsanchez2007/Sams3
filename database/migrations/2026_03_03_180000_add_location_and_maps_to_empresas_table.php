<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            if (!Schema::hasColumn('empresas', 'pais')) {
                $table->string('pais', 120)->nullable()->after('nombre');
            }
            if (!Schema::hasColumn('empresas', 'departamento')) {
                $table->string('departamento', 120)->nullable()->after('pais');
            }
            if (!Schema::hasColumn('empresas', 'municipio')) {
                $table->string('municipio', 120)->nullable()->after('departamento');
            }
            if (!Schema::hasColumn('empresas', 'ciudad')) {
                $table->string('ciudad', 120)->nullable()->after('municipio');
            }
            if (!Schema::hasColumn('empresas', 'google_maps_url')) {
                $table->string('google_maps_url', 500)->nullable()->after('direccion');
            }
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            if (Schema::hasColumn('empresas', 'google_maps_url')) {
                $table->dropColumn('google_maps_url');
            }
            if (Schema::hasColumn('empresas', 'ciudad')) {
                $table->dropColumn('ciudad');
            }
            if (Schema::hasColumn('empresas', 'municipio')) {
                $table->dropColumn('municipio');
            }
            if (Schema::hasColumn('empresas', 'departamento')) {
                $table->dropColumn('departamento');
            }
            if (Schema::hasColumn('empresas', 'pais')) {
                $table->dropColumn('pais');
            }
        });
    }
};
