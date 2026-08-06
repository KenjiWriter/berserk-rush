<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Infrastructure\Persistence\Map;
use App\Infrastructure\Persistence\Monster;

$map = Map::where('name', 'Epicentrum Apokalipsy')->first();
$mobs = Monster::where('map_id', $map->id)->orderBy('level')->get();
foreach ($mobs as $m) {
    echo "{$m->name} => {$m->avatar}\n";
}
