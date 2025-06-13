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
        // Verificar si la columna substitute_player_id ya existe
        if (!Schema::hasColumn('match_events', 'substitute_player_id')) {
            Schema::table('match_events', function (Blueprint $table) {
                $table->uuid('substitute_player_id')->nullable()->after('player_id');
                $table->foreign('substitute_player_id')->references('id')->on('players')->onDelete('cascade');
            });
        }

        // Para SQLite, necesitamos recrear la tabla para agregar el campo type
        if (DB::getDriverName() === 'sqlite') {
            // Crear tabla temporal con la estructura completa
            Schema::create('match_events_temp', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('match_id');
                $table->uuid('player_id');
                $table->uuid('substitute_player_id')->nullable();
                $table->enum('type', ['goal', 'yellow_card', 'red_card', 'blue_card', 'substitution'])->default('goal');
                $table->integer('minute');
                $table->text('description')->nullable();
                $table->timestamps();

                $table->foreign('match_id')->references('id')->on('matches')->onDelete('cascade');
                $table->foreign('player_id')->references('id')->on('players')->onDelete('cascade');
                $table->foreign('substitute_player_id')->references('id')->on('players')->onDelete('cascade');
            });

            // Copiar datos existentes (asignando 'goal' como tipo por defecto)
            DB::statement('INSERT INTO match_events_temp (id, match_id, player_id, substitute_player_id, type, minute, description, created_at, updated_at) 
                          SELECT id, match_id, player_id, substitute_player_id, "goal", minute, description, created_at, updated_at FROM match_events');

            // Eliminar tabla original
            Schema::dropIfExists('match_events');

            // Renombrar tabla temporal
            Schema::rename('match_events_temp', 'match_events');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            // Crear tabla temporal sin type y substitute_player_id
            Schema::create('match_events_temp', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('match_id');
                $table->uuid('player_id');
                $table->integer('minute');
                $table->text('description')->nullable();
                $table->timestamps();

                $table->foreign('match_id')->references('id')->on('matches')->onDelete('cascade');
                $table->foreign('player_id')->references('id')->on('players')->onDelete('cascade');
            });

            // Copiar datos (excluyendo sustituciones)
            DB::statement('INSERT INTO match_events_temp (id, match_id, player_id, minute, description, created_at, updated_at) 
                          SELECT id, match_id, player_id, minute, description, created_at, updated_at FROM match_events WHERE type != "substitution"');

            Schema::dropIfExists('match_events');
            Schema::rename('match_events_temp', 'match_events');
        }
    }
};
