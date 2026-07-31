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
            $table->unsignedSmallInteger('key_multiplier')->default(1)->after('dungeon_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('character_dungeon_runs', function (Blueprint $table) {
            $table->dropColumn('key_multiplier');
        });
    }
};
