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
        Schema::table('players', function (Blueprint $table) {
            // Remove identification_type column
            $table->dropColumn('identification_type');
            
            // Add eps column
            $table->string('eps')->nullable()->after('identification_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            // Add back identification_type column
            $table->string('identification_type')->nullable()->after('identification_number');
            
            // Remove eps column
            $table->dropColumn('eps');
        });
    }
};
