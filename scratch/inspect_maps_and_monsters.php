<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Infrastructure\Persistence\Map;
use App\Infrastructure\Persistence\Monster;

$maps = Map::with(['monsters.lootTable.entries.itemTemplate'])->orderBy('tier')->get();

foreach ($maps as $map) {
    echo "=========================================\n";
    echo "MAP: [Tier {$map->tier}] {$map->name} (Lvl {$map->level_min}-{$map->level_max})\n";
    echo "=========================================\n";
    
    $mapMonsters = $map->explorationMonsters()->get();
    echo "Exploration Monsters count: " . $mapMonsters->count() . "\n";
    
    $allItemsInMap = [];
    foreach ($mapMonsters as $mob) {
        echo " - Mob: {$mob->name} [Lvl {$mob->level}, Rank: {$mob->rank->value}, Avatar: {$mob->avatar}]\n";
        if ($mob->lootTable) {
            foreach ($mob->lootTable->entries as $entry) {
                if ($entry->itemTemplate) {
                    $allItemsInMap[$entry->itemTemplate->id] = [
                        'name' => $entry->itemTemplate->name,
                        'type' => $entry->reward_type,
                        'weight' => $entry->weight,
                    ];
                }
            }
        }
    }
    
    echo "   -> Total unique items & materials dropping on {$map->name}: " . count($allItemsInMap) . "\n";
    foreach ($allItemsInMap as $id => $info) {
        echo "      * [{$info['type']}] {$info['name']} (Weight: {$info['weight']})\n";
    }
    echo "\n";
}
