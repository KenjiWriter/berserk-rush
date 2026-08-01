<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Naprawia i przeprojektowuje loot chowańców z bossów lochów po reworku
 * tierów Petów:
 *
 * 1. FIX: `DungeonSeeder::$dungeonsConfig` odwoływał się do jajek po starych
 *    nazwach ("Zwykłe/Rzadkie/Epickie Jajo Chowańca"), których szablony
 *    zostały usunięte migracją `2026_08_01_030000_...` (scalone z nowym
 *    systemem tierów). Wpisy `loot_table_entries.ref_ulid` z tamtego seeda
 *    wskazują więc teraz donikąd (martwe ID typu `egg-common`) - taki wpis,
 *    jeśli wylosowany przez `WeightedPicker`, wysadziłby drop (próba insertu
 *    `item_instances` z nieistniejącym `template_id`).
 * 2. FEATURE: pety wypadają teraz WYŁĄCZNIE z bossów lochów poziomu 50+
 *    (Pustkowia Zarazy/Cytadela Cienia/Otchłań Zniszczenia) - niższe lochy
 *    (Katakumby/Krypta) tracą loot chowańców całkowicie. Im wyższy loch, tym
 *    lepszy tier jajek (i pojawia się szansa na ekwipunek peta).
 */
return new class extends Migration
{
    // Stare, martwe referencje do usunięcia wszędzie tam, gdzie występują.
    private const DEAD_EGG_IDS = ['egg-common', 'egg-rare', 'egg-epic'];

    private function lootTableId(string $dungeonName): ?int
    {
        $name = 'boss_' . Str::slug($dungeonName) . '_loot';
        return DB::table('loot_tables')->where('name', $name)->value('id');
    }

    public function up(): void
    {
        // 1. Usuń martwe wpisy jajek ze WSZYSTKICH lochów (w tym tych <50 lvl,
        // które od teraz w ogóle nie dropią petów).
        DB::table('loot_table_entries')
            ->where('reward_type', 'item')
            ->whereIn('ref_ulid', self::DEAD_EGG_IDS)
            ->delete();

        // 2. Nowy, przeskalowany loot petów - tylko dla lochów 50+.
        $petLoot = [
            'Pustkowia Zarazy' => [ // lvl 50 - wejściowy loch z petami
                ['ref_ulid' => 'egg-t3', 'weight' => 220],
                ['ref_ulid' => 'egg-t4', 'weight' => 200],
                ['ref_ulid' => 'egg-t5', 'weight' => 60],
                ['ref_ulid' => 'pet-collar-basic', 'weight' => 20],
            ],
            'Cytadela Cienia' => [ // lvl 70
                ['ref_ulid' => 'egg-t4', 'weight' => 300],
                ['ref_ulid' => 'egg-t5', 'weight' => 250],
                ['ref_ulid' => 'egg-t6', 'weight' => 30],
                ['ref_ulid' => 'pet-collar-silver', 'weight' => 30],
                ['ref_ulid' => 'pet-charm-amulet', 'weight' => 20],
            ],
            'Otchłań Zniszczenia' => [ // lvl 88 - najlepszy loot petów w grze
                ['ref_ulid' => 'egg-t5', 'weight' => 350],
                ['ref_ulid' => 'egg-t6', 'weight' => 200],
                ['ref_ulid' => 'pet-charm-amulet', 'weight' => 70],
                ['ref_ulid' => 'pet-charm-bag', 'weight' => 50],
            ],
        ];

        foreach ($petLoot as $dungeonName => $entries) {
            $lootTableId = $this->lootTableId($dungeonName);
            if (!$lootTableId) {
                continue; // loch nie istnieje na tej instalacji - pomiń bezpiecznie
            }

            foreach ($entries as $entry) {
                DB::table('loot_table_entries')->insert([
                    'loot_table_id' => $lootTableId,
                    'reward_type' => 'item',
                    'ref_ulid' => $entry['ref_ulid'],
                    'min_qty' => 1,
                    'max_qty' => 1,
                    'weight' => $entry['weight'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Nieodwracalne - usunięte martwe referencje i tak nie da się przywrócić sensownie.
    }
};
