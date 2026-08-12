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
        Schema::table('hoja_vida_documentos', function (Blueprint $table) {
            if (!Schema::hasColumn('hoja_vida_documentos', 'selected_equipo_imagen_ids')) {
                $table->json('selected_equipo_imagen_ids')->nullable()->after('form_data');
            }

            if (!Schema::hasColumn('hoja_vida_documentos', 'signature_user_id')) {
                $table->foreignId('signature_user_id')->nullable()->after('selected_equipo_imagen_ids')->constrained('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('hoja_vida_documentos', 'pdf_path')) {
                $table->string('pdf_path')->nullable()->after('excel_path');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hoja_vida_documentos', function (Blueprint $table) {
            if (Schema::hasColumn('hoja_vida_documentos', 'signature_user_id')) {
                $table->dropForeign(['signature_user_id']);
                $table->dropColumn('signature_user_id');
            }

            if (Schema::hasColumn('hoja_vida_documentos', 'selected_equipo_imagen_ids')) {
                $table->dropColumn('selected_equipo_imagen_ids');
            }

            if (Schema::hasColumn('hoja_vida_documentos', 'pdf_path')) {
                $table->dropColumn('pdf_path');
            }
        });
    }
};
