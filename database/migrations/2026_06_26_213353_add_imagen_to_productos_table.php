<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            // Agregar campo imagen después de descripcion
            if (!Schema::hasColumn('productos', 'imagen')) {
                $table->string('imagen')->nullable()->after('descripcion');
            }
            
            // Si quieres agregar otros campos relacionados con imagen
            // $table->string('imagen_public_id')->nullable()->after('imagen'); // Para Cloudinary
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            // Eliminar los campos en orden inverso
            $table->dropColumn('imagen');
            // $table->dropColumn('imagen_public_id');
        });
    }
};