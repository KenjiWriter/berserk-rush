<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Rozszerzenie systemu skilli bojowych (2026-07-28):
     * - `is_magic`: oznacza, że umiejętność należy do szkoły magii (Różdżka/Dzwon).
     *   Obrażenia takiej umiejętności są liczone i wyświetlane jako `magicDamage`
     *   zamiast `baseDamage`/`bonusDamage` w logu walki (patrz EncounterService::playerAttack()),
     *   zamiast wprowadzać osobną "obronę magiczną" - zgodnie z celową uproszczoną
     *   mechaniką mitygacji opisaną w docs/modules/combat.md.
     * - `is_aoe`: oznacza, że umiejętność bije obszarowo - w starciach grupowych
     *   (over-level, 3-4 potworów, `EncounterService::simulateMultiCombat()`) trafia
     *   WSZYSTKICH żywych przeciwników zamiast jednego, wytypowanego przez taktykę celu.
     *   W starciach 1 na 1 (PvE standard, PvP) nie ma efektu poza zwykłym trafieniem
     *   pojedynczego celu.
     *
     * Nowe wartości `effect_type` obsługiwane przez silnik (nie wymagają nowych kolumn,
     * korzystają z istniejących base_value/scaling_value/base_duration):
     * - `heal`: leczy postać gracza o % many HP (base_value, skalowane scaling_value/poziom).
     * - `freeze` / `stun`: zadaje obrażenia bezpośrednie (jak direct_dmg, mnożnik base_value)
     *   i unieruchamia cel na `base_duration` tur (traci turę/tury ataku).
     * - `aoe_dmg`: obrażenia bezpośrednie biją wszystkich przeciwników w starciu grupowym
     *   (wymaga is_aoe = true, ale wartość jest też przydatna jako semantyczny znacznik).
     * - `passive_aura_dmg`: pasywna, stale aktywna aura zwiększająca obrażenia fizyczne
     *   o % (bez konieczności "rzucenia" - aktywna cały czas, gdy skill jest wyposażony).
     * - `passive_extra_attack`: pasywna szansa (base_value, 0..1) na natychmiastowy dodatkowy
     *   atak po trafieniu.
     */
    public function up(): void
    {
        Schema::table('combat_skills', function (Blueprint $table) {
            $table->boolean('is_magic')->default(false)->after('effect_type');
            $table->boolean('is_aoe')->default(false)->after('is_magic');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('combat_skills', function (Blueprint $table) {
            $table->dropColumn(['is_magic', 'is_aoe']);
        });
    }
};
