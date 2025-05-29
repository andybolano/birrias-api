<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tournament_phases', function (Blueprint $table) {
            // Agregar nuevo campo status
            $table->enum('status', ['pending', 'active', 'completed', 'cancelled'])
                  ->default('pending')
                  ->after('type');
            
            // Migrar datos existentes
            // Las fases con is_active = true → 'active'
            // Las fases con is_completed = true → 'completed'
            // Las demás → 'pending'
        });

        // Migrar datos existentes
        DB::statement("
            UPDATE tournament_phases 
            SET status = CASE 
                WHEN is_completed = 1 THEN 'completed'
                WHEN is_active = 1 THEN 'active'
                ELSE 'pending'
            END
        ");

        Schema::table('tournament_phases', function (Blueprint $table) {
            // Remover campos booleanos antiguos
            $table->dropColumn(['is_active', 'is_completed']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tournament_phases', function (Blueprint $table) {
            // Restaurar campos booleanos
            $table->boolean('is_active')->default(false)->after('status');
            $table->boolean('is_completed')->default(false)->after('is_active');
        });

        // Migrar datos de vuelta
        DB::statement("
            UPDATE tournament_phases 
            SET 
                is_active = CASE WHEN status = 'active' THEN 1 ELSE 0 END,
                is_completed = CASE WHEN status = 'completed' THEN 1 ELSE 0 END
        ");

        Schema::table('tournament_phases', function (Blueprint $table) {
            // Remover campo status
            $table->dropColumn('status');
        });
    }
};
