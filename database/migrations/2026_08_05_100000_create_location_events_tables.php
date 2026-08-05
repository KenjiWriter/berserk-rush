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
        Schema::create('location_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('rank')->unique();
            $table->string('name');
            $table->unsignedTinyInteger('spawn_chance_pct');
            $table->unsignedTinyInteger('monster_count_min');
            $table->unsignedTinyInteger('monster_count_max');
            $table->decimal('attack_multiplier', 4, 2)->default(1.00);
            $table->decimal('reward_multiplier', 4, 2)->default(1.00);
            $table->unsignedTinyInteger('group_chance_pct')->default(0);
            $table->unsignedTinyInteger('group_max_size')->default(0);
            $table->unsignedTinyInteger('chest_min')->default(0);
            $table->unsignedTinyInteger('chest_max')->default(0);
            $table->timestamps();
        });

        Schema::create('location_event_upgrade_levels', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('level')->unique();
            $table->unsignedTinyInteger('roll_chance_pct');
            $table->unsignedTinyInteger('monster_count_delta_min')->default(0);
            $table->unsignedTinyInteger('monster_count_delta_max')->default(0);
            $table->decimal('attack_multiplier', 4, 2)->default(1.00);
            $table->decimal('hp_multiplier', 4, 2)->default(1.00);
            $table->decimal('exp_multiplier', 4, 2)->default(1.00);
            $table->decimal('drop_multiplier', 4, 2)->default(1.00);
            $table->unsignedTinyInteger('bonus_group_chance_pct')->default(0);
            $table->unsignedTinyInteger('chest_bonus_min')->default(0);
            $table->unsignedTinyInteger('chest_bonus_max')->default(0);
            $table->timestamps();
        });

        Schema::create('character_location_event_runs', function (Blueprint $table) {
            $table->id();
            $table->ulid('character_id');
            $table->foreign('character_id')->references('id')->on('characters')->cascadeOnDelete();
            $table->unsignedSmallInteger('map_id');
            $table->foreign('map_id')->references('id')->on('maps')->cascadeOnDelete();
            $table->foreignId('location_event_id')->constrained('location_events')->cascadeOnDelete();
            $table->unsignedTinyInteger('upgrade_level')->default(0);
            $table->boolean('is_hardcore')->default(false);
            $table->json('monsters_queue');
            $table->unsignedTinyInteger('current_monster_index')->default(0);
            $table->unsignedTinyInteger('total_monsters');
            $table->bigInteger('current_hp');
            $table->bigInteger('current_mana')->nullable();
            $table->boolean('is_completed')->default(false);
            $table->boolean('is_failed')->default(false);
            $table->string('combat_state')->default('idle');
            $table->json('combat_data')->nullable();
            $table->json('accumulated_loot')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('character_location_event_runs');
        Schema::dropIfExists('location_event_upgrade_levels');
        Schema::dropIfExists('location_events');
    }
};
