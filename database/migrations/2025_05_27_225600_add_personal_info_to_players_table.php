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
            $table->string('first_name')->nullable()->after('personId');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('identification_number')->nullable()->after('last_name');
            $table->string('identification_type')->nullable()->after('identification_number'); // DNI, Passport, etc.
            
            // Add index for identification number for faster searches
            $table->index('identification_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropIndex(['identification_number']);
            $table->dropColumn(['first_name', 'last_name', 'identification_number', 'identification_type']);
        });
    }
};
