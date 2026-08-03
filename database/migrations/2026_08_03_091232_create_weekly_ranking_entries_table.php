<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weekly_ranking_entries', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('character_id');
            $table->date('week_start'); // zawsze poniedziałek
            $table->string('category', 50); // monsters_killed, dungeons_completed, world_boss_damage, levels_gained, map_bosses_killed, arena_wins
            $table->unsignedBigInteger('score')->default(0);
            $table->timestamps();

            $table->foreign('character_id')
                  ->references('id')
                  ->on('characters')
                  ->cascadeOnDelete();

            // jeden wpis per postać, per tydzień, per kategoria
            $table->unique(['character_id', 'week_start', 'category']);

            // index do szybkiego sortowania rankingów
            $table->index(['week_start', 'category', 'score']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weekly_ranking_entries');
    }
};
