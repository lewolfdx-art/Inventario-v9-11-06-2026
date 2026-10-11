<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('maletins', function (Blueprint $table) {
            $table->string('revisado_por')->nullable()->default('RED')->after('fecha_atencion');
            $table->string('version')->nullable()->default('00')->after('revisado_por');
            $table->string('aprobado_por')->nullable()->default('GG')->after('version');
            $table->string('fecha_documento')->nullable()->default('01/07/14')->after('aprobado_por');
        });
    }

    public function down()
    {
        Schema::table('maletins', function (Blueprint $table) {
            $table->dropColumn(['revisado_por', 'version', 'aprobado_por', 'fecha_documento']);
        });
    }
};