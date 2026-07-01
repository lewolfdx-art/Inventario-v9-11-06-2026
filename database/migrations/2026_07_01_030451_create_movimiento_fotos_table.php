<?php
// database/migrations/2024_01_01_create_movimiento_fotos_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('movimiento_fotos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movimiento_id')->constrained()->onDelete('cascade');
            $table->string('ruta_imagen');
            $table->string('descripcion')->nullable();
            $table->string('tipo')->default('salida'); // salida o entrada
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('movimiento_fotos');
    }
};