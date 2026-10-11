<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea las tablas reales que usan los modelos MaletinAccesorioSet y
     * MaletinAccesorioAdicional. Las migraciones originales crearon por error
     * "maletin_accesorio_sets" / "maletin_accesorio_adicionals" (sin la "s"
     * interior en "accesorios"), nombres que ningún modelo usa.
     */
    public function up(): void
    {
        if (!Schema::hasTable('maletin_accesorios_set')) {
            Schema::create('maletin_accesorios_set', function (Blueprint $table) {
                $table->id();
                $table->foreignId('maletin_id')->constrained('maletins')->cascadeOnDelete();
                $table->integer('item_numero')->default(0);
                $table->integer('cantidad')->default(1);
                $table->string('descripcion')->nullable();
                $table->boolean('incluido')->default(true);
                $table->string('estado_devolucion')->nullable()->default('ok');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('maletin_accesorios_adicionales')) {
            Schema::create('maletin_accesorios_adicionales', function (Blueprint $table) {
                $table->id();
                $table->foreignId('maletin_id')->constrained('maletins')->cascadeOnDelete();
                $table->integer('item_numero')->default(0);
                $table->integer('cantidad')->default(1);
                $table->string('descripcion')->nullable();
                $table->boolean('incluido')->default(true);
                $table->string('estado_devolucion')->nullable()->default('ok');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // No se eliminan: en bases ya existentes estas tablas son las de trabajo.
    }
};