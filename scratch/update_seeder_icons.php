<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$skills = DB::table('combat_skills')->get()->keyBy('name');

$seederPath = base_path('database/seeders/CombatSkillSeeder.php');
$content = file_get_contents($seederPath);

foreach ($skills as $name => $s) {
    if (!empty($s->icon)) {
        // Find pattern like 'name' => 'Kojący Dźwięk', ... 'icon' => '' or missing icon
        $namePattern = preg_quote($name, '/');
        $pattern = "/('name'\s*=>\s*'{$namePattern}',[\s\S]*?'icon'\s*=>\s*')([^']*)(')/u";
        if (preg_match($pattern, $content)) {
            $content = preg_replace($pattern, "$1{$s->icon}$3", $content);
        } else {
            // If 'icon' field is missing before unlock_cost
            $pattern2 = "/('name'\s*=>\s*'{$namePattern}',[\s\S]*?'unlock_cost'\s*=>\s*\d+,)/u";
            if (preg_match($pattern2, $content)) {
                $replacement = "$1\n                'icon' => '{$s->icon}',";
                $content = preg_replace($pattern2, $replacement, $content);
            }
        }
    }
}

file_put_contents($seederPath, $content);
echo "Updated CombatSkillSeeder.php icons.\n";
