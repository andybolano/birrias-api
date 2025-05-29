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
        Schema::table('tournament_phases', function (Blueprint $table) {
            // Renombrar columnas existentes
            $table->renameColumn('phase_order', 'phase_number');
            $table->renameColumn('phase_type', 'type');
        });

        Schema::table('tournament_phases', function (Blueprint $table) {
            // Eliminar columnas que no necesitamos
            $table->dropColumn(['start_date', 'end_date']);
            
            // Agregar nuevas columnas
            $table->json('config')->nullable()->after('type');
            $table->boolean('home_away')->default(false)->after('config');
            $table->integer('teams_advance')->nullable()->after('home_away');
            $table->integer('groups_count')->nullable()->after('teams_advance');
            $table->integer('teams_per_group')->nullable()->after('groups_count');
            $table->boolean('is_active')->default(false)->after('teams_per_group');
            $table->boolean('is_completed')->default(false)->after('is_active');
            $table->integer('order')->default(1)->after('is_completed');
            
            // Agregar índices
            $table->unique(['tournament_id', 'phase_number']);
            $table->index(['tournament_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tournament_phases', function (Blueprint $table) {
            // Eliminar índices
            $table->dropUnique(['tournament_id', 'phase_number']);
            $table->dropIndex(['tournament_id', 'order']);
            
            // Eliminar nuevas columnas
            $table->dropColumn([
                'config', 'home_away', 'teams_advance', 'groups_count', 
                'teams_per_group', 'is_active', 'is_completed', 'order'
            ]);
            
            // Restaurar columnas originales
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
        });

        Schema::table('tournament_phases', function (Blueprint $table) {
            // Renombrar de vuelta
            $table->renameColumn('phase_number', 'phase_order');
            $table->renameColumn('type', 'phase_type');
        });
    }
};
