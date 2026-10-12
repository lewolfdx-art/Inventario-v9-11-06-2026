<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('producto_unidades', function (Blueprint $table) {
            $table->id();

            $table->foreignId('producto_id')
                ->constrained('productos')
                ->cascadeOnDelete();

            // Código de barras único de la unidad física. Ej: 0TAL-01-001
            $table->string('codigo', 100)->unique();

            $table->string('serie', 100)->nullable();
            $table->string('estado', 20)->default('disponible'); // disponible/salido/mantenimiento/baja
            $table->string('ubicacion')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('producto_unidades');
    }
};
