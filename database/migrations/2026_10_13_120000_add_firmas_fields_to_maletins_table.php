<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('maletins', function (Blueprint $table) {
            if (! Schema::hasColumn('maletins', 'entregado_por_nombres')) {
                $table->string('entregado_por_nombres', 150)->nullable()->after('fecha_documento');
            }
            if (! Schema::hasColumn('maletins', 'entregado_por_dni')) {
                $table->string('entregado_por_dni', 8)->nullable()->after('entregado_por_nombres');
            }
            if (! Schema::hasColumn('maletins', 'entregado_por_firma')) {
                $table->string('entregado_por_firma')->nullable()->after('entregado_por_dni');
            }
            if (! Schema::hasColumn('maletins', 'recibido_por_nombres')) {
                $table->string('recibido_por_nombres', 150)->nullable()->after('entregado_por_firma');
            }
            if (! Schema::hasColumn('maletins', 'recibido_por_dni')) {
                $table->string('recibido_por_dni', 8)->nullable()->after('recibido_por_nombres');
            }
            if (! Schema::hasColumn('maletins', 'recibido_por_firma')) {
                $table->string('recibido_por_firma')->nullable()->after('recibido_por_dni');
            }
        });
    }

    public function down()
    {
        Schema::table('maletins', function (Blueprint $table) {
            $table->dropColumn([
                'entregado_por_nombres',
                'entregado_por_dni',
                'entregado_por_firma',
                'recibido_por_nombres',
                'recibido_por_dni',
                'recibido_por_firma',
            ]);
        });
    }
};
