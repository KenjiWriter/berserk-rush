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
        Schema::table('achievements', function (Blueprint $table) {
            $table->ulid('parent_achievement_id')->nullable()->after('id');
            $table->json('stats_bonus')->nullable()->after('reward_title_id');
            $table->unsignedInteger('reward_gold')->default(0)->after('stats_bonus');
            $table->unsignedInteger('reward_exp')->default(0)->after('reward_gold');

            $table->foreign('parent_achievement_id')->references('id')->on('achievements')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('achievements', function (Blueprint $table) {
            $table->dropForeign(['parent_achievement_id']);
            $table->dropColumn(['parent_achievement_id', 'stats_bonus', 'reward_gold', 'reward_exp']);
        });
    }
};
