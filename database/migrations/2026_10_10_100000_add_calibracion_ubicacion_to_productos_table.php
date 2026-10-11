<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            if (!Schema::hasColumn('productos', 'fecha_ultima_calibracion')) {
                $table->date('fecha_ultima_calibracion')->nullable();
            }
            if (!Schema::hasColumn('productos', 'fecha_ultimo_mantenimiento')) {
                $table->date('fecha_ultimo_mantenimiento')->nullable();
            }
            if (!Schema::hasColumn('productos', 'ubicacion')) {
                $table->enum('ubicacion', ['almacen', 'alquilado', 'campo'])->default('almacen');
            }
            if (!Schema::hasColumn('productos', 'frecuencia_mantenimiento')) {
                $table->enum('frecuencia_mantenimiento', ['1_anio', '2_anios', 'vida_util_unica'])->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn([
                'fecha_ultima_calibracion',
                'fecha_ultimo_mantenimiento',
                'ubicacion',
                'frecuencia_mantenimiento',
            ]);
        });
    }
};