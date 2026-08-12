<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fabricantes')) {
            return;
        }

        Schema::create('fabricantes', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150)->unique();
            $table->text('description')->nullable();
            $table->string('contacto', 150)->nullable();
            $table->string('telefono', 60)->nullable();
            $table->string('email', 150)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('activo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fabricantes');
    }
};

