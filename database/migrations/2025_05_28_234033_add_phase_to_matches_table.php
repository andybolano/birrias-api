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
            $table->uuid('phase_id')->nullable()->after('tournament_id');
            $table->integer('group_number')->nullable()->after('round'); // Para fases de grupos
            $table->string('match_type')->default('regular')->after('round'); // regular, semifinal, final, etc.
            
            $table->foreign('phase_id')->references('id')->on('tournament_phases')->onDelete('cascade');
            $table->index(['phase_id', 'round']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropForeign(['phase_id']);
            $table->dropIndex(['phase_id', 'round']);
            $table->dropColumn(['phase_id', 'group_number', 'match_type']);
        });
    }
};
