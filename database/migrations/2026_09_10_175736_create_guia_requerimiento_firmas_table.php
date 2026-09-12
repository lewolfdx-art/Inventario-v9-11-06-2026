<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guia_requerimiento_firmas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('guia_requerimiento_id')
                  ->constrained('guia_requerimientos')
                  ->cascadeOnDelete();

            $table->enum('tipo', [
                'atendido_por',
                'autorizado_por',
                'recibi_conforme',
                'recepcion_devolucion',
            ]);

            $table->string('firma_path')->nullable();
            $table->date('fecha')->nullable();
            $table->string('nombre_apellidos')->nullable();
            $table->string('dni', 15)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guia_requerimiento_firmas');
    }
};