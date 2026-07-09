<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('encounters', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('character_id');
            $table->unsignedSmallInteger('map_id');
            $table->unsignedBigInteger('monster_id');
            $table->string('state', 12)->default('ongoing'); // ongoing|win|lose
            $table->json('result')->nullable(); // xp, gold, drops summary
            $table->bigInteger('gold_reward')->default(0);
            $table->bigInteger('xp_reward')->default(0);
            $table->boolean('player_first')->default(true);
            $table->json('turns')->nullable();
            $table->json('combat_data')->nullable();
            $table->boolean('rewards_applied')->default(false);
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->foreign('character_id')->references('id')->on('characters')->cascadeOnDelete();
            $table->foreign('map_id')->references('id')->on('maps')->cascadeOnDelete();
            $table->foreign('monster_id')->references('id')->on('monsters')->cascadeOnDelete();
            $table->index(['character_id', 'state']);
            $table->index(['character_id', 'created_at']);
            $table->index(['state', 'created_at']);
            $table->index('rewards_applied');
        });

        try {
            DB::statement("ALTER TABLE encounters ADD CONSTRAINT chk_state_enc CHECK (state IN ('ongoing','win','lose'))");
        } catch (\Exception $e) {
            // Ignore if check constraints are not supported
        }

        Schema::create('turns', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->ulid('encounter_id');
            $table->unsignedInteger('turn_no');
            $table->string('attacker', 8); // char|monster
            $table->integer('damage');
            $table->boolean('crit')->default(false);
            $table->json('status')->nullable(); // np. buff/debuff
            $table->timestamps();

            $table->foreign('encounter_id')->references('id')->on('encounters')->cascadeOnDelete();
            $table->index(['encounter_id', 'turn_no']);
        });
    }

    public function down(): void
    {
        try {
            DB::statement("ALTER TABLE encounters DROP CONSTRAINT IF EXISTS chk_state_enc");
        } catch (\Exception $e) {
            // Ignore
        }

        Schema::dropIfExists('turns');
        Schema::dropIfExists('encounters');
    }
};
