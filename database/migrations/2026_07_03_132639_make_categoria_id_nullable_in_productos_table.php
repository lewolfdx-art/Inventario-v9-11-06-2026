<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('productos', function (Blueprint $table) {
            // ✅ Hacer nullable todas las columnas que pueden ser opcionales
            $table->unsignedBigInteger('categoria_id')->nullable()->change();
            $table->unsignedBigInteger('subcategoria_id')->nullable()->change();
            $table->unsignedBigInteger('marca_id')->nullable()->change();
            $table->unsignedBigInteger('unidad_compra_id')->nullable()->change();
            $table->unsignedBigInteger('naturaleza_id')->nullable()->change();
            $table->unsignedBigInteger('req_inventario_id')->nullable()->change();
            $table->unsignedBigInteger('req_serie_id')->nullable()->change();
            $table->unsignedBigInteger('req_lote_id')->nullable()->change();
            $table->unsignedBigInteger('req_calibracion_id')->nullable()->change();
            $table->string('serie')->nullable()->change();
            $table->string('imagen')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('productos', function (Blueprint $table) {
            // Revertir los cambios (volver a NOT NULL)
            $table->unsignedBigInteger('categoria_id')->nullable(false)->change();
            $table->unsignedBigInteger('subcategoria_id')->nullable(false)->change();
            $table->unsignedBigInteger('marca_id')->nullable(false)->change();
            $table->unsignedBigInteger('unidad_compra_id')->nullable(false)->change();
            $table->unsignedBigInteger('naturaleza_id')->nullable(false)->change();
            $table->unsignedBigInteger('req_inventario_id')->nullable(false)->change();
            $table->unsignedBigInteger('req_serie_id')->nullable(false)->change();
            $table->unsignedBigInteger('req_lote_id')->nullable(false)->change();
            $table->unsignedBigInteger('req_calibracion_id')->nullable(false)->change();
            $table->string('serie')->nullable(false)->change();
            $table->string('imagen')->nullable(false)->change();
        });
    }
};