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
        Schema::table('character_dungeon_runs', function (Blueprint $table) {
            $table->string('combat_state')->default('idle')->after('is_failed');
            $table->json('combat_data')->nullable()->after('combat_state');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('character_dungeon_runs', function (Blueprint $table) {
            $table->dropColumn(['combat_state', 'combat_data']);
        });
    }
};
