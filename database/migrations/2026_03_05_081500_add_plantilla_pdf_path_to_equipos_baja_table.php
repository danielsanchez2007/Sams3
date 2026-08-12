<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipos_baja', function (Blueprint $table) {
            if (!Schema::hasColumn('equipos_baja', 'plantilla_pdf_path')) {
                $table->string('plantilla_pdf_path')->nullable()->after('plantilla_excel_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('equipos_baja', function (Blueprint $table) {
            if (Schema::hasColumn('equipos_baja', 'plantilla_pdf_path')) {
                $table->dropColumn('plantilla_pdf_path');
            }
        });
    }
};
