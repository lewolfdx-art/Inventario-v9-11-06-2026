<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            
            // Campos principales
            $table->string('sku', 10)->unique(); // SKU único
            $table->string('modelo', 10);
            $table->string('nombre', 10);
            
            // Llaves foráneas (para seleccionar de las otras tablas)
            $table->foreignId('unidad_compra_id')->constrained('unidad_compras');
            $table->foreignId('naturaleza_id')->constrained('naturalezas');
            $table->foreignId('req_inventario_id')->constrained('requerimiento_inventarios');
            $table->foreignId('req_serie_id')->constrained('requerimiento_series');
            $table->foreignId('req_lote_id')->constrained('requerimiento_lotes');
            $table->foreignId('req_calibracion_id')->constrained('requerimiento_calibraciones');
            $table->foreignId('estado_id')->constrained('estados');
            $table->foreignId('categoria_id')->constrained('categorias');
            $table->foreignId('subcategoria_id')->constrained('subcategorias');
            $table->foreignId('marca_id')->constrained('marcas');
            
            $table->text('descripcion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};