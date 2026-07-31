<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Infrastructure\Persistence\Monster;
use App\Infrastructure\Persistence\Dungeon;
use App\Infrastructure\Persistence\DungeonStage;
use App\Infrastructure\Persistence\LootTable;
use App\Infrastructure\Persistence\LootTableEntry;
use Illuminate\Support\Facades\DB;

echo "Cleaning up monsters, dungeons, and loot tables...\n";

DB::statement('SET FOREIGN_KEY_CHECKS=0;');
LootTableEntry::query()->delete();
LootTable::query()->delete();
DungeonStage::query()->delete();
Dungeon::query()->delete();
Monster::query()->delete();
DB::statement('SET FOREIGN_KEY_CHECKS=1;');

echo "Reseeding MonsterSeeder...\n";
app(\Database\Seeders\MonsterSeeder::class)->run();

echo "Reseeding DungeonSeeder...\n";
app(\Database\Seeders\DungeonSeeder::class)->run();

echo "Reseeding MonsterLootSeeder...\n";
app(\Database\Seeders\MonsterLootSeeder::class)->run();

echo "Clean reseed completed successfully!\n";
