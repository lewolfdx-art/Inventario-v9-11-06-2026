<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // ✅ FECHA DE DEVOLUCIÓN en la tabla principal
        Schema::table('maletins', function (Blueprint $table) {
            $table->date('fecha_devolucion')->nullable()->after('observaciones');
        });

        // ✅ ESTADO DE DEVOLUCIÓN por ítem (componentes, accesorios set, adicionales)
        Schema::table('maletin_componentes', function (Blueprint $table) {
            $table->string('estado_devolucion')->nullable()->default('ok')->after('incluido');
        });

        Schema::table('maletin_accesorios_set', function (Blueprint $table) {
            $table->string('estado_devolucion')->nullable()->default('ok')->after('incluido');
        });

        Schema::table('maletin_accesorios_adicionales', function (Blueprint $table) {
            $table->string('estado_devolucion')->nullable()->default('ok')->after('incluido');
        });
    }

    public function down()
    {
        Schema::table('maletins', function (Blueprint $table) {
            $table->dropColumn('fecha_devolucion');
        });

        Schema::table('maletin_componentes', function (Blueprint $table) {
            $table->dropColumn('estado_devolucion');
        });

        Schema::table('maletin_accesorios_set', function (Blueprint $table) {
            $table->dropColumn('estado_devolucion');
        });

        Schema::table('maletin_accesorios_adicionales', function (Blueprint $table) {
            $table->dropColumn('estado_devolucion');
        });
    }
};