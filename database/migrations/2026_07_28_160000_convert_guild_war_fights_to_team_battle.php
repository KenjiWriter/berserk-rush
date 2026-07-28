<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Wojny Gildii (2026-07-28): przejście z modelu "5 osobnych pojedynków 1v1"
 * na JEDNO starcie drużynowe 5v5 (analogicznie do starć grupowych PvE
 * z EncounterService::simulateMultiCombat, tylko obie strony to gracze).
 *
 * `guild_war_fights` przechowywało dotąd 5 wierszy na wojnę (fight_order 1-5),
 * po jednym na każdy pojedynek. Od teraz wojna generuje JEDEN wiersz
 * (fight_order = 1) z pełnym logiem całego starcia drużynowego, a kolumny
 * `challenger_snapshot`/`defender_snapshot` przechowują TABLICĘ snapshotów
 * (do 5 postaci) zamiast pojedynczego obiektu. `challenger_character_id`/
 * `defender_character_id`/`winner_character_id` tracą sens dla starcia
 * drużynowego (nie ma jednego "przeciwnika"), więc stają się nullable.
 */
return new class extends Migration {
    public function up(): void
    {
        // Postgres: usuwamy FK + NOT NULL na kolumnach identyfikujących
        // pojedynczą postać - starcie drużynowe nie ma jednego pary graczy.
        try {
            DB::statement('ALTER TABLE guild_war_fights DROP CONSTRAINT IF EXISTS guild_war_fights_challenger_character_id_foreign');
        } catch (\Exception $e) {
            // Ignore
        }

        try {
            DB::statement('ALTER TABLE guild_war_fights DROP CONSTRAINT IF EXISTS guild_war_fights_defender_character_id_foreign');
        } catch (\Exception $e) {
            // Ignore
        }

        try {
            DB::statement('ALTER TABLE guild_war_fights ALTER COLUMN challenger_character_id DROP NOT NULL');
            DB::statement('ALTER TABLE guild_war_fights ALTER COLUMN defender_character_id DROP NOT NULL');
        } catch (\Exception $e) {
            // Ignore if already nullable / not supported
        }

        Schema::table('guild_war_fights', function (Blueprint $table) {
            $table->unsignedTinyInteger('challenger_survivors')->default(0)->after('combat_data');
            $table->unsignedTinyInteger('defender_survivors')->default(0)->after('challenger_survivors');
            $table->unsignedSmallInteger('rounds')->default(0)->after('defender_survivors');
        });
    }

    public function down(): void
    {
        Schema::table('guild_war_fights', function (Blueprint $table) {
            $table->dropColumn(['challenger_survivors', 'defender_survivors', 'rounds']);
        });

        try {
            DB::statement("UPDATE guild_war_fights SET challenger_character_id = '00000000000000000000000000' WHERE challenger_character_id IS NULL");
            DB::statement("UPDATE guild_war_fights SET defender_character_id = '00000000000000000000000000' WHERE defender_character_id IS NULL");
            DB::statement('ALTER TABLE guild_war_fights ALTER COLUMN challenger_character_id SET NOT NULL');
            DB::statement('ALTER TABLE guild_war_fights ALTER COLUMN defender_character_id SET NOT NULL');
        } catch (\Exception $e) {
            // Ignore
        }

        try {
            DB::statement('ALTER TABLE guild_war_fights ADD CONSTRAINT guild_war_fights_challenger_character_id_foreign FOREIGN KEY (challenger_character_id) REFERENCES characters(id) ON DELETE CASCADE');
            DB::statement('ALTER TABLE guild_war_fights ADD CONSTRAINT guild_war_fights_defender_character_id_foreign FOREIGN KEY (defender_character_id) REFERENCES characters(id) ON DELETE CASCADE');
        } catch (\Exception $e) {
            // Ignore
        }
    }
};
