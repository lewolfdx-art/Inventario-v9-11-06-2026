<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
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
    }

    public function down()
    {
        Schema::dropIfExists('maletin_componentes');
    }
};