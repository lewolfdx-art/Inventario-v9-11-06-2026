<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // ✅ TABLA PRINCIPAL CON NOMBRE
        Schema::create('maletins', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->nullable(); // ✅ NUEVO CAMPO
            $table->string('responsable_solicitante')->nullable();
            $table->text('observaciones')->nullable();
            $table->string('estado')->default('activo');
            $table->timestamps();
        });

        // ✅ COMPONENTES DEL EQUIPO
        Schema::create('maletin_componentes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('maletin_id');
            $table->integer('item_numero')->default(0);
            $table->integer('cantidad')->default(1);
            $table->string('descripcion');
            $table->boolean('incluido')->default(true);
            $table->timestamps();

            $table->foreign('maletin_id')
                  ->references('id')
                  ->on('maletins')
                  ->onDelete('cascade');
        });

        // ✅ CONTENIDO DE LA MALETA
        Schema::create('maletin_accesorios_set', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('maletin_id');
            $table->integer('item_numero')->default(0);
            $table->integer('cantidad')->default(1);
            $table->string('descripcion');
            $table->boolean('incluido')->default(true);
            $table->timestamps();

            $table->foreign('maletin_id')
                  ->references('id')
                  ->on('maletins')
                  ->onDelete('cascade');
        });

        // ✅ PAQUETE ADICIONAL DE ACCESORIOS
        Schema::create('maletin_accesorios_adicionales', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('maletin_id');
            $table->integer('item_numero')->default(0);
            $table->integer('cantidad')->default(1);
            $table->string('descripcion');
            $table->boolean('incluido')->default(true);
            $table->timestamps();

            $table->foreign('maletin_id')
                  ->references('id')
                  ->on('maletins')
                  ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('maletin_accesorios_adicionales');
        Schema::dropIfExists('maletin_accesorios_set');
        Schema::dropIfExists('maletin_componentes');
        Schema::dropIfExists('maletins');
    }
};