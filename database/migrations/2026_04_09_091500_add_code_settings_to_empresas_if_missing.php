<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('empresas', 'code_settings')) {
            Schema::table('empresas', function (Blueprint $table) {
                $table->json('code_settings')->nullable()->after('modulos');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('empresas', 'code_settings')) {
            Schema::table('empresas', function (Blueprint $table) {
                $table->dropColumn('code_settings');
            });
        }
    }
};
