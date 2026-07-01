<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('movimientos', function (Blueprint $table) {
            $table->text('observaciones')->nullable()->after('stock_nuevo');
            $table->string('realizado_por')->nullable()->after('observaciones');
        });
    }

    public function down()
    {
        Schema::table('movimientos', function (Blueprint $table) {
            $table->dropColumn(['observaciones', 'realizado_por']);
        });
    }
};