<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->string('nombre', 100)->change();  // Cambiar a 100 caracteres
            $table->string('modelo', 50)->change();   // Cambiar a 50 caracteres
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->string('nombre', 10)->change();
            $table->string('modelo', 10)->change();
        });
    }
};