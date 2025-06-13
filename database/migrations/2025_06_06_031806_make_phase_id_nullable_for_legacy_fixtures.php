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
            // Hacer phase_id nullable para mantener compatibilidad con fixtures legados
            $table->uuid('phase_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            // Revertir a NOT NULL (solo si no hay matches con phase_id NULL)
            $table->uuid('phase_id')->nullable(false)->change();
        });
    }
};
