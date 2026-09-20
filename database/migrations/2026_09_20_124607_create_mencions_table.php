<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mencions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guia_requerimiento_id')
                  ->constrained('guia_requerimientos')
                  ->onDelete('cascade');
            $table->text('texto');                          // Texto del subtítulo/mención
            $table->integer('orden')->default(0);           // Orden de aparición
            $table->boolean('activo')->default(true);       // Mostrar u ocultar
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mencions');
    }
};