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
        Schema::table('dungeon_stages', function (Blueprint $table) {
            $table->string('stage_type')->default('single_mob')->after('stage_order');
            $table->unsignedSmallInteger('monster_count')->default(1)->after('stage_type');
            $table->unsignedSmallInteger('max_turns')->nullable()->after('monster_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dungeon_stages', function (Blueprint $table) {
            $table->dropColumn(['stage_type', 'monster_count', 'max_turns']);
        });
    }
};
