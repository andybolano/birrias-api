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
        Schema::table('match_events', function (Blueprint $table) {
            // Cambiar event_type a type y actualizar los valores permitidos
            $table->dropColumn('event_type');
        });

        Schema::table('match_events', function (Blueprint $table) {
            $table->enum('type', ['goal', 'yellow_card', 'red_card', 'blue_card'])->after('player_id');
        });

        Schema::table('match_events', function (Blueprint $table) {
            // Hacer player_id requerido (no nullable)
            $table->uuid('player_id')->nullable(false)->change();
            
            // Eliminar team_id ya que lo obtendremos de la alineación
            $table->dropForeign(['team_id']);
            $table->dropColumn('team_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('match_events', function (Blueprint $table) {
            // Restaurar team_id
            $table->uuid('team_id')->after('player_id');
            $table->foreign('team_id')->references('id')->on('teams')->onDelete('cascade');
            
            // Hacer player_id nullable
            $table->uuid('player_id')->nullable()->change();
            
            // Cambiar type de vuelta a event_type
            $table->dropColumn('type');
        });

        Schema::table('match_events', function (Blueprint $table) {
            $table->enum('event_type', ['goal', 'yellow_card', 'red_card', 'substitution'])->after('player_id');
        });
    }
};
