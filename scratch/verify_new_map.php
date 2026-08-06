<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Infrastructure\Persistence\Map;
use App\Infrastructure\Persistence\Monster;

$map = Map::where('name', 'Epicentrum Apokalipsy')->first();
echo "MAP: id={$map->id} tier={$map->tier} level_min={$map->level_min} level_max={$map->level_max} image={$map->image_path}\n\n";

$mobs = Monster::where('map_id', $map->id)->orderBy('level')->get();
foreach ($mobs as $m) {
    $s = $m->stats;
    printf(
        "%-30s | lvl %3d | %-8s | %-6s | HP=%6d ATK=%4d DEF=%4d AGI=%3d INT=%3d | skills=%s\n",
        $m->name, $m->level, $m->rank->value, $m->type->value, $s['hp'], $s['atk'], $s['def'], $s['agi'], $s['int'] ?? 0,
        json_encode($m->getCombatSkills() ?? [])
    );
}

echo "\n--- Loot check (Herold Apokalipsy) ---\n";
$h = Monster::where('name', 'Herold Apokalipsy')->first();
foreach ($h->lootTable->entries as $e) {
    $ref = $e->reward_type === 'item' || $e->reward_type === 'material'
        ? \App\Infrastructure\Persistence\ItemTemplate::find($e->ref_ulid)?->name
        : null;
    echo "  {$e->reward_type} weight={$e->weight} qty={$e->min_qty}-{$e->max_qty} " . ($ref ? "-> {$ref}" : '') . "\n";
}

echo "\n--- Boss chest check (Nieumarły Jeździec Zagłady) ---\n";
$b = Monster::where('name', 'Nieumarły Jeździec Zagłady')->first();
foreach ($b->lootTable->entries as $e) {
    $ref = $e->reward_type === 'item' || $e->reward_type === 'material'
        ? \App\Infrastructure\Persistence\ItemTemplate::find($e->ref_ulid)?->name
        : null;
    echo "  {$e->reward_type} weight={$e->weight} qty={$e->min_qty}-{$e->max_qty} " . ($ref ? "-> {$ref}" : '') . "\n";
}
