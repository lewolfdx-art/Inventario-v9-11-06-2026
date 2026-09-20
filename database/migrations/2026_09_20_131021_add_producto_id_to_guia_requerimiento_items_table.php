<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guia_requerimiento_items', function (Blueprint $table) {
            $table->foreignId('producto_id')
                  ->nullable()
                  ->after('guia_requerimiento_id')
                  ->constrained('productos')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('guia_requerimiento_items', function (Blueprint $table) {
            $table->dropForeign(['producto_id']);
            $table->dropColumn('producto_id');
        });
    }
};