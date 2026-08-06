<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Infrastructure\Persistence\ItemTemplate;
use Illuminate\Support\Facades\DB;

$candidates = [
    'Różdżka Zmutowanego Czarownika','Buty Kwasu','Maska Cienia Skazy','Skórznia Upadłego Zabójcy',
    'Podeszwy Trucizny','Amulet Zmutowanego Oka','Pierścień Zgnilizny',
    'Dzwon Sądu Ostatecznego','Kostur Władcy Mroku','Buty Deptania Światów','Kaptur Pożeracza Dusz',
    'Buty Otchłani','Maska Bezwzględnego Zniszczenia','Płaszcz Końca Czasu','Ciche Podeszwy Zmierzchu',
];

foreach ($candidates as $name) {
    $t = ItemTemplate::where('name', $name)->first();
    if (!$t) { echo "MISSING TEMPLATE: $name\n"; continue; }
    $usedIn = DB::table('loot_table_entries')->where('ref_ulid', $t->id)->count();
    echo sprintf("%-35s id=%s used_in_loot_entries=%d\n", $name, $t->id, $usedIn);
}
