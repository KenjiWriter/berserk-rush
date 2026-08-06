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
        Schema::create('champion_skills', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('key')->unique(); // strength, wisdom, endurance, concentration, accuracy, agility, toughness, fortune, treasure_hunter, knowledge
            $table->string('name');
            $table->text('description')->nullable();
            // phys_dmg_pct, magic_dmg_pct, max_hp_pct, mana_regen_pct, dodge_pct,
            // accuracy_pct, dmg_reduction_pct, gold_pct, loot_pct, exp_pct
            $table->string('stat_type');
            $table->float('bonus_per_point')->default(1); // np. 1.0 = +1% za punkt
            $table->integer('max_points')->default(10);
            $table->string('icon')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('character_champion_skills', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('character_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('champion_skill_id')->constrained('champion_skills')->cascadeOnDelete();
            $table->integer('points')->default(0);
            $table->timestamps();

            $table->unique(['character_id', 'champion_skill_id'], 'char_champion_skill_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('character_champion_skills');
        Schema::dropIfExists('champion_skills');
    }
};
