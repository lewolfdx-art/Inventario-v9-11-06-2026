<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guia_requerimiento_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('guia_requerimiento_id')
                  ->constrained('guia_requerimientos')
                  ->cascadeOnDelete();

            $table->unsignedInteger('item');
            $table->text('descripcion');

            // SOLICITADO
            $table->decimal('cantidad_solicitada', 12, 2)->nullable();
            $table->string('unidad_solicitada', 20)->nullable();

            // ENTREGADO
            $table->boolean('entregado')->default(false);
            $table->decimal('cantidad_entregada', 12, 2)->nullable();
            $table->string('unidad_entregada', 20)->nullable();

            // DEVOLUCIÓN
            $table->boolean('devuelto')->default(false);
            $table->decimal('cantidad_devuelta', 12, 2)->nullable();
            $table->string('unidad_devuelta', 20)->nullable();

            // ORDEN
            $table->unsignedInteger('orden')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guia_requerimiento_items');
    }
};