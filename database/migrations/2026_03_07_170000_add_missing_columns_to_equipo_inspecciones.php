<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('equipo_inspecciones')) {
            return;
        }

        $table = 'equipo_inspecciones';

        if (!Schema::hasColumn($table, 'validez_hasta')) {
            Schema::table($table, function (Blueprint $t) {
                $t->date('validez_hasta')->nullable();
            });
        }
        if (!Schema::hasColumn($table, 'fecha_inspeccion')) {
            Schema::table($table, function (Blueprint $t) {
                $t->date('fecha_inspeccion')->nullable();
            });
        }
        if (!Schema::hasColumn($table, 'edited_html')) {
            Schema::table($table, function (Blueprint $t) {
                $t->longText('edited_html')->nullable();
            });
        }
        if (!Schema::hasColumn($table, 'pdf_path')) {
            Schema::table($table, function (Blueprint $t) {
                $t->string('pdf_path')->nullable();
            });
        }
        if (!Schema::hasColumn($table, 'inspeccion_obligatoria')) {
            Schema::table($table, function (Blueprint $t) {
                $t->boolean('inspeccion_obligatoria')->default(false);
            });
        }
        if (!Schema::hasColumn($table, 'user_id')) {
            Schema::table($table, function (Blueprint $t) {
                $t->unsignedBigInteger('user_id')->nullable();
                if (Schema::hasTable('users')) {
                    $t->foreign('user_id')->references('id')->on('users')->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        //
    }
};
