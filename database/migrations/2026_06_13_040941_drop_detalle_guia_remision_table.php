<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('detalle_guia_remision');
    }

    public function down(): void
    {
        Schema::create('detalle_guia_remision', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guia_remision_id')->constrained('guias_remision')->onDelete('cascade');
            $table->foreignId('producto_id')->constrained('productos');
            $table->integer('cantidad')->default(1);
            $table->string('serie', 100)->nullable();
            $table->text('descripcion_completa')->nullable();
            $table->timestamps();
        });
    }
};