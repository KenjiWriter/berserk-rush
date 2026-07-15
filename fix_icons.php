<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$updates = [
    "material-pelt" => "mroczne-futro.png",
    "material-bone" => "strzaskana-kosc.png",
    "material-herb" => "bagienne-ziolo.png",
    "potion-health" => "amulet-zywotnosci.png",
    "potion-mana" => "slaby-krysztal-many.png",
    "potion-health-greater" => "amulet-zywotnosci.png",
    "material-gem" => "odlamek-skarbu.png",
    "??" => "zardzewiala-moneta.png"
];
foreach(\App\Infrastructure\Persistence\ItemTemplate::where("icon", "not like", "%.png%")->get() as $item) {
    if(isset($updates[$item->icon])) {
        $item->icon = $updates[$item->icon];
        $item->save();
        echo "Updated " . $item->name . " to " . $item->icon . PHP_EOL;
    } else {
        $item->icon = "zardzewialy-miecz.png";
        $item->save();
        echo "Fallback updated " . $item->name . PHP_EOL;
    }
}
