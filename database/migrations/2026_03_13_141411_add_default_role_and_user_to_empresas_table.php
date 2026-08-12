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
        Schema::table('empresas', function (Blueprint $table) {
            if (!Schema::hasColumn('empresas', 'default_role_id')) {
                $table->unsignedBigInteger('default_role_id')->nullable()->after('modulos');
            }
            if (!Schema::hasColumn('empresas', 'default_user_id')) {
                $table->unsignedBigInteger('default_user_id')->nullable()->after('default_role_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            if (Schema::hasColumn('empresas', 'default_user_id')) {
                $table->dropColumn('default_user_id');
            }
            if (Schema::hasColumn('empresas', 'default_role_id')) {
                $table->dropColumn('default_role_id');
            }
        });
    }
};
