<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Para tabla maletin_accesorios_set
        Schema::table('maletin_accesorios_set', function (Blueprint $table) {
            $table->string('descripcion')->nullable()->change();
        });

        // Para tabla maletin_accesorios_adicionales
        Schema::table('maletin_accesorios_adicionales', function (Blueprint $table) {
            $table->string('descripcion')->nullable()->change();
        });

        // Para tabla maletin_componentes
        Schema::table('maletin_componentes', function (Blueprint $table) {
            $table->string('descripcion')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('maletin_accesorios_set', function (Blueprint $table) {
            $table->string('descripcion')->nullable(false)->change();
        });

        Schema::table('maletin_accesorios_adicionales', function (Blueprint $table) {
            $table->string('descripcion')->nullable(false)->change();
        });

        Schema::table('maletin_componentes', function (Blueprint $table) {
            $table->string('descripcion')->nullable(false)->change();
        });
    }
};