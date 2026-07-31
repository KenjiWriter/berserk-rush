<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$monsters = \App\Infrastructure\Persistence\Monster::with('map')->get();

foreach ($monsters as $m) {
    $rank = is_object($m->rank) ? $m->rank->value : (string)$m->rank;
    if ($rank === 'boss' || $rank === 'worldboss' || str_contains($m->name, 'Dowódca') || str_contains($m->name, 'Władca Krypty')) {
        echo "ID: {$m->id} | Name: {$m->name} | Rank: {$rank} | Map: " . ($m->map ? $m->map->name : 'N/A') . " | HP: " . ($m->stats['hp'] ?? 0) . "\n";
    }
}
