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
        Schema::table('tournament_team', function (Blueprint $table) {
            // Drop the existing primary key
            $table->dropPrimary(['id']);
            // Drop the id column
            $table->dropColumn('id');
            // Create a composite primary key
            $table->primary(['team_id', 'tournament_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tournament_team', function (Blueprint $table) {
            // Drop the composite primary key
            $table->dropPrimary(['team_id', 'tournament_id']);
            // Add back the id column
            $table->uuid('id')->primary()->first();
        });
    }
};
