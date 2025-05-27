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
        Schema::create('tournaments', function (Blueprint $table) {
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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tournaments');
    }
};
