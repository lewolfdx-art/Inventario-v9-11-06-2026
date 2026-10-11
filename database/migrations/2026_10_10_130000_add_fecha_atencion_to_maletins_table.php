<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('maletins', function (Blueprint $table) {
            $table->date('fecha_atencion')->nullable()->after('fecha_devolucion');
        });
    }

    public function down()
    {
        Schema::table('maletins', function (Blueprint $table) {
            $table->dropColumn('fecha_atencion');
        });
    }
};