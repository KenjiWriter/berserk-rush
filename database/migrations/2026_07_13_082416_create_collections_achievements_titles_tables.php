<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('titles', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('prefix')->nullable();
            $table->text('description')->nullable();
            $table->json('stats_bonus')->nullable(); // np. {"str": 5, "hp": 50, "bonus_vs_demon": 15}
            $table->timestamps();
        });

        Schema::create('character_titles', function (Blueprint $table) {
            $table->id();
            $table->ulid('character_id');
            $table->ulid('title_id');
            $table->timestamp('unlocked_at');
            $table->timestamps();

            $table->foreign('character_id')->references('id')->on('characters')->cascadeOnDelete();
            $table->foreign('title_id')->references('id')->on('titles')->cascadeOnDelete();
            $table->unique(['character_id', 'title_id']);
        });

        Schema::create('achievements', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type'); // e.g., 'blacksmith_burns', 'monsters_killed'
            $table->unsignedBigInteger('target_value');
            $table->unsignedInteger('reward_points')->default(0);
            $table->ulid('reward_item_template_id')->nullable();
            $table->ulid('reward_title_id')->nullable();
            $table->timestamps();

            $table->foreign('reward_title_id')->references('id')->on('titles')->nullOnDelete();
        });

        Schema::create('character_achievements', function (Blueprint $table) {
            $table->id();
            $table->ulid('character_id');
            $table->ulid('achievement_id');
            $table->unsignedBigInteger('progress')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->boolean('rewarded')->default(false);
            $table->timestamps();

            $table->foreign('character_id')->references('id')->on('characters')->cascadeOnDelete();
            $table->foreign('achievement_id')->references('id')->on('achievements')->cascadeOnDelete();
            $table->unique(['character_id', 'achievement_id']);
        });

        Schema::create('character_bestiary', function (Blueprint $table) {
            $table->id();
            $table->ulid('character_id');
            $table->unsignedBigInteger('monster_id');
            $table->unsignedBigInteger('kills')->default(0);
            $table->timestamps();

            $table->foreign('character_id')->references('id')->on('characters')->cascadeOnDelete();
            $table->foreign('monster_id')->references('id')->on('monsters')->cascadeOnDelete();
            $table->unique(['character_id', 'monster_id']);
        });

        Schema::create('character_pokedex', function (Blueprint $table) {
            $table->id();
            $table->ulid('character_id');
            $table->ulid('item_template_id');
            $table->timestamp('discovered_at')->useCurrent();
            $table->timestamps();

            $table->foreign('character_id')->references('id')->on('characters')->cascadeOnDelete();
            $table->foreign('item_template_id')->references('id')->on('item_templates')->cascadeOnDelete();
            $table->unique(['character_id', 'item_template_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('character_pokedex');
        Schema::dropIfExists('character_bestiary');
        Schema::dropIfExists('character_achievements');
        Schema::dropIfExists('achievements');
        Schema::dropIfExists('character_titles');
        Schema::dropIfExists('titles');
    }
};
