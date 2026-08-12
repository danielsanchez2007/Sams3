<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('grupos')) {
            Schema::create('grupos', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->text('description')->nullable();
                $table->foreignId('leader_id')->nullable()->constrained('users')->nullOnDelete();
                $table->boolean('activo')->default(true);
                $table->timestamps();
            });
        } else {
            Schema::table('grupos', function (Blueprint $table) {
                if (!Schema::hasColumn('grupos', 'leader_id')) {
                    $table->foreignId('leader_id')->nullable()->after('description')->constrained('users')->nullOnDelete();
                }

                if (!Schema::hasColumn('grupos', 'activo')) {
                    $table->boolean('activo')->default(true)->after('leader_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('grupos')) {
            Schema::dropIfExists('grupos');
        }
    }
};
