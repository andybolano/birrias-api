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
        // SQLite no soporta ALTER ENUM, por lo que necesitamos recrear la tabla
        if (DB::getDriverName() === 'sqlite') {
            // Crear tabla temporal con el nuevo ENUM
            Schema::create('tournaments_temp', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name', 255)->nullable();
                $table->date('start_date')->nullable();
                $table->decimal('inscription_fee_money', 10, 2)->nullable();
                $table->string('currency', 3)->default('USD');
                $table->uuid('owner');
                $table->enum('status', ['active', 'inactive'])->default('inactive');
                $table->enum('format', ['league', 'league_playoffs', 'groups_knockout', 'custom'])->default('custom');
                $table->integer('groups')->nullable();
                $table->integer('teams_per_group')->nullable();
                $table->integer('playoff_size')->nullable();
                $table->integer('rounds')->nullable();
                $table->boolean('home_away')->default(false);
                $table->timestamps();

                $table->foreign('owner')->references('id')->on('users')->onDelete('cascade');
            });

            // Copiar datos de la tabla original a la temporal
            DB::statement('INSERT INTO tournaments_temp SELECT * FROM tournaments');

            // Eliminar tabla original
            Schema::dropIfExists('tournaments');

            // Renombrar tabla temporal
            Schema::rename('tournaments_temp', 'tournaments');
        } else {
            // Para otros drivers que soportan ALTER ENUM
            DB::statement("ALTER TABLE tournaments MODIFY COLUMN format ENUM('league', 'league_playoffs', 'groups_knockout', 'custom') DEFAULT 'custom'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir el cambio
        if (DB::getDriverName() === 'sqlite') {
            // Crear tabla temporal con el ENUM original
            Schema::create('tournaments_temp', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name', 255)->nullable();
                $table->date('start_date')->nullable();
                $table->decimal('inscription_fee_money', 10, 2)->nullable();
                $table->string('currency', 3)->default('USD');
                $table->uuid('owner');
                $table->enum('status', ['active', 'inactive'])->default('inactive');
                $table->enum('format', ['league', 'league_playoffs', 'groups_knockout'])->default('league');
                $table->integer('groups')->nullable();
                $table->integer('teams_per_group')->nullable();
                $table->integer('playoff_size')->nullable();
                $table->integer('rounds')->nullable();
                $table->boolean('home_away')->default(false);
                $table->timestamps();

                $table->foreign('owner')->references('id')->on('users')->onDelete('cascade');
            });

            // Copiar datos (excluyendo registros con format = 'custom')
            DB::statement("INSERT INTO tournaments_temp SELECT * FROM tournaments WHERE format != 'custom'");

            // Eliminar tabla original
            Schema::dropIfExists('tournaments');

            // Renombrar tabla temporal
            Schema::rename('tournaments_temp', 'tournaments');
        } else {
            // Para otros drivers
            DB::statement("ALTER TABLE tournaments MODIFY COLUMN format ENUM('league', 'league_playoffs', 'groups_knockout') DEFAULT 'league'");
        }
    }
};
