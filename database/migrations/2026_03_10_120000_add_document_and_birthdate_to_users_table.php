<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'document_type')) {
                $table->string('document_type', 10)->nullable()->after('password');
            }
            if (!Schema::hasColumn('users', 'document_number')) {
                $table->string('document_number', 50)->nullable()->after('document_type');
            }
            if (!Schema::hasColumn('users', 'birth_date')) {
                $table->date('birth_date')->nullable()->after('signature');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'birth_date')) {
                $table->dropColumn('birth_date');
            }
            if (Schema::hasColumn('users', 'document_number')) {
                $table->dropColumn('document_number');
            }
            if (Schema::hasColumn('users', 'document_type')) {
                $table->dropColumn('document_type');
            }
        });
    }
};
