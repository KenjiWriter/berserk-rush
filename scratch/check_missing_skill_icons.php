<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$skills = DB::table('combat_skills')->get();
$missing = [];
$total = 0;
foreach ($skills as $s) {
    $total++;
    $iconName = $s->icon;
    $path = public_path('assets/skills/icons/' . $iconName);
    if (empty($iconName) || !file_exists($path)) {
        $missing[] = [
            'id' => $s->id,
            'name' => $s->name,
            'description' => $s->description,
            'icon' => $s->icon,
            'weapon' => $s->required_weapon_type,
            'type' => $s->type
        ];
    }
}

echo "Total skills: " . $total . "\n";
echo "Missing icons count: " . count($missing) . "\n\n";
foreach ($missing as $m) {
    echo "ID: {$m['id']} | Name: {$m['name']} | Weapon: {$m['weapon']} | Icon: {$m['icon']}\n";
    echo "  Desc: {$m['description']}\n\n";
}
