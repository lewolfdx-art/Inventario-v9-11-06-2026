<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('imagens', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');           // Nombre descriptivo
            $table->string('archivo');          // Ruta del archivo
            $table->string('tipo')->default('logo'); // logo, firma, sello, otro
            $table->string('mime_type')->nullable();
            $table->integer('tamaño')->nullable(); // en bytes
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imagens');
    }
};