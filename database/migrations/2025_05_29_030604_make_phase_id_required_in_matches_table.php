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
        Schema::table('matches', function (Blueprint $table) {
            // Hacer phase_id requerido (NOT NULL)
            $table->uuid('phase_id')->nullable(false)->change();
            
            // Agregar índice para mejorar performance en consultas
            $table->index('phase_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            // Remover índice
            $table->dropIndex(['phase_id']);
            
            // Hacer phase_id opcional nuevamente
            $table->uuid('phase_id')->nullable()->change();
        });
    }
};
