<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // ─── PvP Encounters ──────────────────────────────────────────────
        Schema::create('pvp_encounters', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('attacker_character_id');
            $table->ulid('defender_character_id');
            $table->string('state', 12)->default('pending'); // pending|calculating|finished|error
            $table->ulid('winner_character_id')->nullable();
            $table->json('attacker_snapshot'); // stats, equipment, level, name at time of challenge
            $table->json('defender_snapshot'); // stats at time of challenge
            $table->json('turns')->nullable(); // combat log
            $table->json('combat_data')->nullable(); // metadata like max HP, timing
            $table->integer('attacker_elo_change')->default(0);
            $table->integer('defender_elo_change')->default(0);
            $table->integer('arena_tokens_reward')->default(0);
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->foreign('attacker_character_id')->references('id')->on('characters')->cascadeOnDelete();
            $table->foreign('defender_character_id')->references('id')->on('characters')->cascadeOnDelete();

            $table->index(['attacker_character_id', 'state']);
            $table->index(['defender_character_id', 'state']);
            $table->index(['state', 'created_at']);
        });

        try {
            DB::statement("ALTER TABLE pvp_encounters ADD CONSTRAINT chk_state_pvp CHECK (state IN ('pending','calculating','finished','error'))");
        } catch (\Exception $e) {
            // Ignore if check constraints are not supported
        }

        // ─── Guild Wars ──────────────────────────────────────────────────
        Schema::create('guild_wars', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('challenger_guild_id');
            $table->ulid('defender_guild_id');
            $table->string('status', 16)->default('pending'); // pending|accepted|in_progress|finished|declined|expired
            $table->ulid('winner_guild_id')->nullable();
            $table->json('challenger_roster'); // array of 5 character IDs
            $table->json('defender_roster');   // array of 5 character IDs
            $table->bigInteger('gold_prize')->default(0);   // gold from loser's treasury
            $table->integer('gems_prize')->default(0);      // gems from loser's treasury
            $table->bigInteger('xp_prize')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->foreign('challenger_guild_id')->references('id')->on('guilds')->cascadeOnDelete();
            $table->foreign('defender_guild_id')->references('id')->on('guilds')->cascadeOnDelete();

            $table->index(['challenger_guild_id', 'status']);
            $table->index(['defender_guild_id', 'status']);
        });

        try {
            DB::statement("ALTER TABLE guild_wars ADD CONSTRAINT chk_status_gw CHECK (status IN ('pending','accepted','in_progress','finished','declined','expired'))");
        } catch (\Exception $e) {
            // Ignore if check constraints are not supported
        }

        // ─── Guild War Fights ────────────────────────────────────────────
        Schema::create('guild_war_fights', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->ulid('guild_war_id');
            $table->unsignedSmallInteger('fight_order'); // 1-5
            $table->ulid('challenger_character_id');
            $table->ulid('defender_character_id');
            $table->ulid('winner_character_id')->nullable();
            $table->json('challenger_snapshot');
            $table->json('defender_snapshot');
            $table->json('turns')->nullable();
            $table->json('combat_data')->nullable();
            $table->timestamps();

            $table->foreign('guild_war_id')->references('id')->on('guild_wars')->cascadeOnDelete();
            $table->foreign('challenger_character_id')->references('id')->on('characters')->cascadeOnDelete();
            $table->foreign('defender_character_id')->references('id')->on('characters')->cascadeOnDelete();

            $table->index(['guild_war_id', 'fight_order']);
        });

        // ─── Characters: PvP columns ────────────────────────────────────
        Schema::table('characters', function (Blueprint $table) {
            $table->integer('elo')->default(1000)->after('version');
            $table->string('league', 16)->default('bronze')->after('elo');
            $table->integer('arena_tokens')->default(0)->after('league');
            $table->integer('pvp_refreshes_used')->default(0)->after('arena_tokens');
            $table->timestamp('pvp_refreshes_reset_at')->nullable()->after('pvp_refreshes_used');

            $table->index('elo');
        });

        // ─── Merchant Items: currency & price columns ───────────────────
        Schema::table('merchant_items', function (Blueprint $table) {
            $table->string('currency_type', 16)->default('gold')->after('sold_quantity'); // gold|gems|arena_tokens
            $table->integer('price')->default(0)->after('currency_type');
        });

        try {
            DB::statement("ALTER TABLE merchant_items ADD CONSTRAINT chk_currency_mi CHECK (currency_type IN ('gold','gems','arena_tokens'))");
        } catch (\Exception $e) {
            // Ignore if check constraints are not supported
        }

        // ─── Guilds: war columns ────────────────────────────────────────
        Schema::table('guilds', function (Blueprint $table) {
            $table->json('war_team')->nullable()->after('bonus_gold_level'); // array of 5 character IDs
            $table->boolean('is_war_locked')->default(false)->after('war_team');
        });

        // ─── Currency Ledgers: update CHECK constraint for arena_tokens ─
        try {
            DB::statement("ALTER TABLE currency_ledgers DROP CONSTRAINT IF EXISTS chk_currency_cl");
        } catch (\Exception $e) {
            // Ignore
        }

        try {
            DB::statement("ALTER TABLE currency_ledgers ADD CONSTRAINT chk_currency_cl CHECK (currency_type IN ('gold','gems','arena_tokens'))");
        } catch (\Exception $e) {
            // Ignore if check constraints are not supported
        }

        // ─── Postgres partial indexes ───────────────────────────────────
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("CREATE INDEX IF NOT EXISTS pvp_encounters_pending_idx ON pvp_encounters (created_at) WHERE state = 'pending';");
            DB::statement("CREATE INDEX IF NOT EXISTS guild_wars_active_idx ON guild_wars (created_at) WHERE status IN ('pending','accepted','in_progress');");
        }
    }

    public function down(): void
    {
        // ─── Drop Postgres partial indexes ──────────────────────────────
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("DROP INDEX IF EXISTS guild_wars_active_idx;");
            DB::statement("DROP INDEX IF EXISTS pvp_encounters_pending_idx;");
        }

        // ─── Restore original currency_ledgers CHECK constraint ─────────
        try {
            DB::statement("ALTER TABLE currency_ledgers DROP CONSTRAINT IF EXISTS chk_currency_cl");
        } catch (\Exception $e) {
            // Ignore
        }

        try {
            DB::statement("ALTER TABLE currency_ledgers ADD CONSTRAINT chk_currency_cl CHECK (currency_type IN ('gold','gems'))");
        } catch (\Exception $e) {
            // Ignore
        }

        // ─── Remove guilds war columns ──────────────────────────────────
        Schema::table('guilds', function (Blueprint $table) {
            $table->dropColumn(['war_team', 'is_war_locked']);
        });

        // ─── Remove merchant_items columns ──────────────────────────────
        try {
            DB::statement("ALTER TABLE merchant_items DROP CONSTRAINT IF EXISTS chk_currency_mi");
        } catch (\Exception $e) {
            // Ignore
        }

        Schema::table('merchant_items', function (Blueprint $table) {
            $table->dropColumn(['currency_type', 'price']);
        });

        // ─── Remove characters PvP columns ──────────────────────────────
        Schema::table('characters', function (Blueprint $table) {
            $table->dropIndex(['elo']);
            $table->dropColumn(['elo', 'league', 'arena_tokens', 'pvp_refreshes_used', 'pvp_refreshes_reset_at']);
        });

        // ─── Drop CHECK constraints ────────────────────────────────────
        try {
            DB::statement("ALTER TABLE guild_wars DROP CONSTRAINT IF EXISTS chk_status_gw");
            DB::statement("ALTER TABLE pvp_encounters DROP CONSTRAINT IF EXISTS chk_state_pvp");
        } catch (\Exception $e) {
            // Ignore
        }

        // ─── Drop tables in reverse order ───────────────────────────────
        Schema::dropIfExists('guild_war_fights');
        Schema::dropIfExists('guild_wars');
        Schema::dropIfExists('pvp_encounters');
    }
};
