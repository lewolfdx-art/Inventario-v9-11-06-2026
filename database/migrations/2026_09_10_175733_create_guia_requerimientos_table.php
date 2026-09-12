<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guia_requerimientos', function (Blueprint $table) {
            $table->id();

            $table->string('codigo', 50)->default('LOG-FOR-001');
            $table->string('version', 10)->default('02');
            $table->string('revisado_por', 100)->default('JEFE SIG');
            $table->string('aprobado_por', 100)->default('GG');
            $table->date('fecha_documento')->nullable();
            $table->unsignedInteger('pagina')->default(1);
            $table->unsignedInteger('total_paginas')->default(1);

            $table->string('nombre_proyecto')->nullable();
            $table->string('responsable_solicitante')->nullable();
            $table->date('fecha_pedido')->nullable();
            $table->string('centro_costos', 50)->nullable();
            $table->date('fecha_atencion')->nullable();

            $table->text('comentarios')->nullable();

            $table->enum('estado', ['borrador', 'pendiente', 'aprobado', 'entregado', 'devuelto'])
                  ->default('borrador');

            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guia_requerimientos');
    }
};