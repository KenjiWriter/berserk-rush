<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Infrastructure\Persistence\Monster;

$mobs = Monster::whereHas('map', function($q) {
    $q->where('name', 'Skażone Miasto');
})->orderBy('level')->get();

echo "=========================================================\n";
echo "STATYSTYKI POTWORÓW - SKAŻONE MIASTO (T8)\n";
echo "=========================================================\n";

foreach ($mobs as $m) {
    $hp = $m->stats['hp'] ?? 0;
    $atk = $m->stats['atk'] ?? 0;
    $def = $m->stats['def'] ?? 0;
    
    // Szacowane hity przy dmg gracza ~13.5k
    $hitsToKill = round($hp / 13500, 1);
    
    echo sprintf(
        "%-28s | Level: %2d | Rank: %-10s | HP: %6d (%3.1f hits) | ATK: %5d | DEF: %4d\n",
        $m->name,
        $m->level,
        $m->rank->value,
        $hp,
        $hitsToKill,
        $atk,
        $def
    );
}
