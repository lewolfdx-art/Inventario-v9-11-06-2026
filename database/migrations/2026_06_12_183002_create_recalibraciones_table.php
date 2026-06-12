<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recalibraciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')
                  ->constrained('productos')
                  ->onDelete('cascade');

            $table->date('fecha_recalibracion')->nullable()
                  ->comment('Fecha en que se realizó la recalibración');

            $table->date('proxima_recalibracion')->nullable()
                  ->comment('Fecha estimada para la próxima recalibración');

            $table->text('observaciones')->nullable();

            $table->string('realizada_por_nombre', 100)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recalibraciones');
    }
};