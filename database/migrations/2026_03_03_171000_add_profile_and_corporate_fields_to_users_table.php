<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'gender')) {
                $table->string('gender', 20)->nullable()->after('last_name');
            }

            if (!Schema::hasColumn('users', 'gender_other')) {
                $table->string('gender_other')->nullable()->after('gender');
            }

            if (!Schema::hasColumn('users', 'has_corporate_email')) {
                $table->boolean('has_corporate_email')->default(false)->after('email');
            }

            if (!Schema::hasColumn('users', 'corporate_email')) {
                $table->string('corporate_email')->nullable()->after('has_corporate_email');
            }

            if (!Schema::hasColumn('users', 'has_corporate_phone')) {
                $table->boolean('has_corporate_phone')->default(false)->after('phone');
            }

            if (!Schema::hasColumn('users', 'corporate_phone')) {
                $table->string('corporate_phone', 20)->nullable()->after('has_corporate_phone');
            }

            if (!Schema::hasColumn('users', 'photo')) {
                $table->string('photo')->nullable()->after('corporate_phone');
            }

            if (!Schema::hasColumn('users', 'signature')) {
                $table->string('signature')->nullable()->after('photo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'signature')) {
                $table->dropColumn('signature');
            }
            if (Schema::hasColumn('users', 'photo')) {
                $table->dropColumn('photo');
            }
            if (Schema::hasColumn('users', 'corporate_phone')) {
                $table->dropColumn('corporate_phone');
            }
            if (Schema::hasColumn('users', 'has_corporate_phone')) {
                $table->dropColumn('has_corporate_phone');
            }
            if (Schema::hasColumn('users', 'corporate_email')) {
                $table->dropColumn('corporate_email');
            }
            if (Schema::hasColumn('users', 'has_corporate_email')) {
                $table->dropColumn('has_corporate_email');
            }
            if (Schema::hasColumn('users', 'gender_other')) {
                $table->dropColumn('gender_other');
            }
            if (Schema::hasColumn('users', 'gender')) {
                $table->dropColumn('gender');
            }
        });
    }
};
