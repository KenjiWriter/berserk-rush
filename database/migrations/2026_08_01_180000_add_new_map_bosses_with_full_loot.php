<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Infrastructure\Persistence\Map;
use App\Infrastructure\Persistence\Monster;
use App\Infrastructure\Persistence\LootTable;
use App\Infrastructure\Persistence\LootTableEntry;

return new class extends Migration
{
    public function up(): void
    {
        $newBossesConfig = [
            'Mroczny Las' => [
                'name' => 'Widmowy Leśny Niedźwiedź',
                'type' => 'animal',
                'rank' => 'boss',
                'level' => 11,
                'avatar' => 'monsters/avatars/widmowy-lesny-niedzwiedz.png',
                'stats' => ['hp' => 240, 'atk' => 30, 'def' => 6, 'agi' => 5, 'int' => 4, 'crit' => 0.20, 'dodge' => 0.08],
            ],
            'Stare Ruiny' => [
                'name' => 'Starożytny Golem Kamienny',
                'type' => 'mystical',
                'rank' => 'boss',
                'level' => 25,
                'avatar' => 'monsters/avatars/starozytny-golem-kamienny.png',
                'stats' => ['hp' => 480, 'atk' => 68, 'def' => 18, 'agi' => 4, 'int' => 6, 'crit' => 0.15, 'dodge' => 0.05],
            ],
            'Jaskinia Trolli' => [
                'name' => 'Mroczny Władca Trolli',
                'type' => 'troll',
                'rank' => 'boss',
                'level' => 36,
                'avatar' => 'monsters/avatars/mroczny-wladca-trolli.png',
                'stats' => ['hp' => 880, 'atk' => 125, 'def' => 32, 'agi' => 6, 'int' => 8, 'crit' => 0.20, 'dodge' => 0.06],
            ],
            'Pustkowia Orków' => [
                'name' => 'Wojownik Cienia Orków',
                'type' => 'orc',
                'rank' => 'boss',
                'level' => 49,
                'avatar' => 'monsters/avatars/wojownik-cienia-orkow.png',
                'stats' => ['hp' => 1450, 'atk' => 200, 'def' => 48, 'agi' => 10, 'int' => 10, 'crit' => 0.22, 'dodge' => 0.08],
            ],
            'Bagna Grozy' => [
                'name' => 'Bagnisty Behemot Cienia',
                'type' => 'demon',
                'rank' => 'boss',
                'level' => 66,
                'avatar' => 'monsters/avatars/bagnisty-behemot-cienia.png',
                'stats' => ['hp' => 2250, 'atk' => 285, 'def' => 68, 'agi' => 12, 'int' => 15, 'crit' => 0.25, 'dodge' => 0.08],
            ],
            'Góry Cienia' => [
                'name' => 'Wyvern Cienistego Szczytu',
                'type' => 'mystical',
                'rank' => 'boss',
                'level' => 76,
                'avatar' => 'monsters/avatars/wyvern-cienistego-szczytu.png',
                'stats' => ['hp' => 8250, 'atk' => 790, 'def' => 276, 'agi' => 16, 'int' => 20, 'crit' => 0.28, 'dodge' => 0.10],
            ],
            'Wieża Magów' => [
                'name' => 'Arcymag Pustki i Arkanów',
                'type' => 'mystical',
                'rank' => 'boss',
                'level' => 86,
                'avatar' => 'monsters/avatars/arcymag-pustki-i-arkanow.png',
                'stats' => ['hp' => 4500, 'atk' => 530, 'def' => 112, 'agi' => 18, 'int' => 45, 'crit' => 0.30, 'dodge' => 0.12],
            ],
            'Skażone Miasto' => [
                'name' => 'Władca Skażenia i Plagi',
                'type' => 'demon',
                'rank' => 'boss',
                'level' => 99,
                'avatar' => 'monsters/avatars/wladca-skazenia-i-plagi.png',
                'stats' => ['hp' => 6300, 'atk' => 690, 'def' => 142, 'agi' => 22, 'int' => 50, 'crit' => 0.32, 'dodge' => 0.12],
            ],
        ];

        foreach ($newBossesConfig as $mapName => $bossInfo) {
            $map = Map::where('name', $mapName)->first();
            if (!$map) continue;

            // Gather all item templates & materials from existing monsters on this map
            $mapMonsters = $map->explorationMonsters()->with('lootTable.entries')->get();
            $uniqueDrops = []; // [ref_ulid => ['reward_type' => ..., 'weight' => ...]]

            foreach ($mapMonsters as $mob) {
                if ($mob->lootTable) {
                    foreach ($mob->lootTable->entries as $entry) {
                        if (!empty($entry->ref_ulid)) {
                            $uniqueDrops[$entry->ref_ulid] = [
                                'reward_type' => $entry->reward_type,
                                'weight' => max(5, $entry->weight),
                                'min_qty' => $entry->min_qty,
                                'max_qty' => $entry->max_qty,
                            ];
                        }
                    }
                }
            }

            // Create or retrieve dedicated LootTable for new boss
            $lootTable = LootTable::firstOrCreate([
                'name' => "Tabela Łupów - {$bossInfo['name']}",
            ], [
                'description' => "Pełny loot ze wszystkich potworów lokacji {$mapName}",
            ]);

            // Clear old entries if re-running
            LootTableEntry::where('loot_table_id', $lootTable->id)->delete();

            // Add entries to loot table
            foreach ($uniqueDrops as $refUlid => $dropInfo) {
                LootTableEntry::create([
                    'loot_table_id' => $lootTable->id,
                    'reward_type' => $dropInfo['reward_type'],
                    'ref_ulid' => $refUlid,
                    'weight' => $dropInfo['weight'],
                    'min_qty' => $dropInfo['min_qty'],
                    'max_qty' => $dropInfo['max_qty'],
                ]);
            }

            // Create Monster
            Monster::updateOrCreate(
                ['map_id' => $map->id, 'name' => $bossInfo['name']],
                [
                    'type' => $bossInfo['type'],
                    'rank' => $bossInfo['rank'],
                    'level' => $bossInfo['level'],
                    'stats' => $bossInfo['stats'],
                    'abilities' => [],
                    'loot_table_id' => $lootTable->id,
                    'avatar' => $bossInfo['avatar'],
                ]
            );
        }
    }

    public function down(): void
    {
        $names = [
            'Widmowy Leśny Niedźwiedź',
            'Starożytny Golem Kamienny',
            'Mroczny Władca Trolli',
            'Wojownik Cienia Orków',
            'Bagnisty Behemot Cienia',
            'Wyvern Cienistego Szczytu',
            'Arcymag Pustki i Arkanów',
            'Władca Skażenia i Plagi',
        ];

        foreach ($names as $name) {
            $mob = Monster::where('name', $name)->first();
            if ($mob) {
                if ($mob->loot_table_id) {
                    LootTableEntry::where('loot_table_id', $mob->loot_table_id)->delete();
                    LootTable::where('id', $mob->loot_table_id)->delete();
                }
                $mob->delete();
            }
        }
    }
};
