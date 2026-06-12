<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subcategorias', function (Blueprint $table) {
            $table->string('nombre', 50)->change(); // Aumentar a 50
        });
    }

    public function down(): void
    {
        Schema::table('subcategorias', function (Blueprint $table) {
            $table->string('nombre', 10)->change();
        });
    }
};