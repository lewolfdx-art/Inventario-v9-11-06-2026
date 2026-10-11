<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('maletins', function (Blueprint $table) {
            if (!Schema::hasColumn('maletins', 'fecha_solicitud')) {
                $table->date('fecha_solicitud')->nullable();
            }
            if (!Schema::hasColumn('maletins', 'responsable_solicitante')) {
                $table->string('responsable_solicitante')->nullable();
            }
            if (!Schema::hasColumn('maletins', 'proyecto_cliente')) {
                $table->string('proyecto_cliente')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('maletins', function (Blueprint $table) {
            $table->dropColumn(['responsable_solicitante', 'proyecto_cliente']);
        });
    }
};