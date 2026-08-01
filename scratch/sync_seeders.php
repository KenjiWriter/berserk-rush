<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$newMonstersData = [
    'Mroczny Las' => [
        'seeder_mob' => "                [\n                    'name' => 'Widmowy Leśny Niedźwiedź',\n                    'type' => 'animal',\n                    'level' => 11,\n                    'rank' => 'boss',\n                    'stats' => ['hp' => 240, 'atk' => 30, 'def' => 6, 'agi' => 5, 'int' => 4, 'crit' => 0.20, 'dodge' => 0.08],\n                    'abilities' => []\n                ],",
        'loot_mob_name' => 'Widmowy Leśny Niedźwiedź',
        'materials' => ['Mroczne Zioło', 'Magiczny Mech', 'Słaby Kryształ Many', 'Kawałek Poroża', 'Wilczy Kieł', 'Błona Skrzydła', 'Prastara Kora', 'Gobliński Sztylet'],
        'items' => ['Sztylety z Kości Wilka', 'Pancerz z Wilczej Skóry', 'Maska z Czaszki Wilka', 'Miękkie Mokasyny', 'Płaszcz Liściastego Skrytobójcy', 'Dzwon Leśnego Szamana', 'Łuk z Pnia Suchodrzewu', 'Kostur z Serca Suchodrzewu', 'Zbroja z Twardej Kory', 'Hełm Leśnego Strażnika', 'Buty Tropiącego', 'Miecz Leśnego Goblina', 'Zatrute Sztylety Goblina', 'Topór Drwala z Mrocznego Lasu', 'Wzmocniony Hełm Strażnika', 'Ostrze Króla Lasu', 'Amulet Prastarego Dębu', 'Pierścień Wędrowca']
    ],
    'Stare Ruiny' => [
        'seeder_mob' => "                [\n                    'name' => 'Starożytny Golem Kamienny',\n                    'type' => 'mystical',\n                    'level' => 25,\n                    'rank' => 'boss',\n                    'stats' => ['hp' => 480, 'atk' => 68, 'def' => 18, 'agi' => 4, 'int' => 6, 'crit' => 0.15, 'dodge' => 0.05],\n                    'abilities' => []\n                ],",
        'loot_mob_name' => 'Starożytny Golem Kamienny',
        'materials' => ['Pył Grobowy', 'Zardzewiała Moneta', 'Odłamek Ruin', 'Przeklęty Onyks', 'Strzaskana Kość', 'Ektoplazma', 'Zardzewiały Grot', 'Fragment Całunu'],
        'items' => ['Zardzewiały Miecz Szkieletu', 'Zardzewiały Hełm Rycerza', 'Różdżka Potępionych Dusz', 'Kaptur Zjaw', 'Kolczuga Strażnika Ruin', 'Maska Beztwarzowego Ducha', 'Skórznia Z Grobowca', 'Cmentarne Buty', 'Łuk z Kości Zjaw', 'Buty Mgły', 'Ząbkowany Topór Upiora', 'Żelazne Sabatony', 'Dzwon Pokutny', 'Naszyjnik z Zimnej Stali', 'Zbutwiała Szata Licza', 'Pierścień Wiecznego Żalu', 'Sztylety Skrytobójcy Dusz']
    ],
    'Jaskinia Trolli' => [
        'seeder_mob' => "                [\n                    'name' => 'Mroczny Władca Trolli',\n                    'type' => 'troll',\n                    'level' => 36,\n                    'rank' => 'boss',\n                    'stats' => ['hp' => 880, 'atk' => 125, 'def' => 32, 'agi' => 6, 'int' => 8, 'crit' => 0.20, 'dodge' => 0.06],\n                    'abilities' => []\n                ],",
        'loot_mob_name' => 'Mroczny Władca Trolli',
        'materials' => ['Trolla Skóra', 'Kieł Trolla', 'Pustynna Ruda Żelaza', 'Gęsty Śluz', 'Pęd Pełzacza', 'Szamański Totem', 'Kość Ogra', 'Prastara Runa Głębin'],
        'items' => ['Maczuga Trolla Paskudnika', 'Zbroja z Grubej Skóry Trolla', 'Pancerz z Pancerza Pełzacza', 'Różdżka Mrocznej Jaskini', 'Szamański Dzwon Trolla', 'Szata Trolla Szamana', 'Wielki Topór Ogra', 'Kask z Prastarej Stali', 'Łuk z Kości Ogra', 'Płaszcz Nocnego Łowcy', 'Miecz ze Stali Trolli', 'Pierścień Siły Trolla', 'Tarcza Starożytnego Ogra', 'Sygnet Podziemi', 'Amulet Ogrzej Krwi']
    ],
    'Pustkowia Orków' => [
        'seeder_mob' => "                [\n                    'name' => 'Wojownik Cienia Orków',\n                    'type' => 'orc',\n                    'level' => 49,\n                    'rank' => 'boss',\n                    'stats' => ['hp' => 1450, 'atk' => 200, 'def' => 48, 'agi' => 10, 'int' => 10, 'crit' => 0.22, 'dodge' => 0.08],\n                    'abilities' => []\n                ],",
        'loot_mob_name' => 'Wojownik Cienia Orków',
        'materials' => ['Orczy Kieł', 'Symbol Watahy', 'Ruda Stali Orków', 'Kolcowy Pancerz Skorpiona', 'Skorpionowy Jad', 'Tymiankowy Susz', 'Runiczny Kamień Orków', 'Czaszka Wodza Orków'],
        'items' => ['Miecz Orczego Zwiadowcy', 'Lekka Kolczuga Pustkowi', 'Sztylety Skorpionowego Kła', 'Pancerz z Łusek Skorpiona', 'Topór Orczego Berserkera', 'Rozpruwacz z Pustkowi', 'Ciężki Pancerz Orków', 'Maska Berserkera', 'Kostur Szamana Krwi', 'Szata Szamana Orków', 'Wielki Topór Dowódca Watahy', 'Zbroja Płytowa Watahy', 'Miecz Niszczyciela Pustkowi', 'Naszyjnik z Kłów Orka', 'Pierścień Pustynnego Wiatru', 'Amulet Wodza Orków']
    ],
    'Bagna Grozy' => [
        'seeder_mob' => "                [\n                    'name' => 'Bagnisty Behemot Cienia',\n                    'type' => 'demon',\n                    'level' => 66,\n                    'rank' => 'boss',\n                    'stats' => ['hp' => 2250, 'atk' => 285, 'def' => 68, 'agi' => 12, 'int' => 15, 'crit' => 0.25, 'dodge' => 0.08],\n                    'abilities' => []\n                ],",
        'loot_mob_name' => 'Bagnisty Behemot Cienia',
        'materials' => ['Bagienne Zioło', 'Mętna Woda', 'Toksyczny Śluz', 'Skamieniały Torf', 'Zgniłe Mięso', 'Wiedźmi Amulet', 'Błotnisty Korzeń', 'Łuska Hydry'],
        'items' => ['Zbutwiały Topór Topielca', 'Mokre Buty Bagienne', 'Skórznia Żmijowa', 'Maska z Błota', 'Różdżka Wiedźmiej Straży', 'Kaptur Wiedźmy Zgnilizny', 'Łuk z Wierzby Płaczącej', 'Szata Tkana z Zielska', 'Dzwon Utopców', 'Buty Bagiennej Mgły', 'Ostrze z Zęba Hydry', 'Pancerz z Łusek Hydry', 'Zatrute Kły Hydry', 'Pierścień Zgniłego Mchu', 'Podeszwy Bezdźwięku', 'Zardzewiały Hełm z Głębin', 'Naszyjnik z Oka Hydry']
    ],
    'Góry Cienia' => [
        'seeder_mob' => "                [\n                    'name' => 'Wyvern Cienistego Szczytu',\n                    'type' => 'mystical',\n                    'level' => 76,\n                    'rank' => 'boss',\n                    'stats' => ['hp' => 3300, 'atk' => 395, 'def' => 92, 'agi' => 16, 'int' => 20, 'crit' => 0.28, 'dodge' => 0.10],\n                    'abilities' => []\n                ],",
        'loot_mob_name' => 'Wyvern Cienistego Szczytu',
        'materials' => ['Kryształ Cienia', 'Górska Ruda Miedzi', 'Popiół Wulkaniczny', 'Mroczne Futro', 'Pióro Harpii', 'Odłamek Bazaltu', 'Zniszczona Księga Magii', 'Łuska Smoka Cienia'],
        'items' => ['Płaszcz Górskiego Cienia', 'Maska Nocnego Drapieżnika', 'Łuk z Piór Harpii', 'Buty Sokolnika', 'Miecz Wykuty z Bazaltu', 'Pancerz Skalnego Golema', 'Ciężkie Kamienne Buty', 'Szata z Piór Harpii', 'Trzewiki Górskiego Wiatru', 'Topór Kamiennego Golema', 'Hełm z Czarnego Bazaltu', 'Różdżka z Górskiego Kryształu', 'Dzwon Górskiego Echa', 'Kaptur Burzowych Chmur', 'Sztylety Skalnego Kła', 'Pierścień Czarnego Kryształu', 'Piekielny Miecz Smoka', 'Topór Smoczego Gniewu', 'Smoczy Łuk', 'Pancerz ze Smoczych Łusek', 'Amulet Smoczego Oka']
    ],
    'Wieża Magów' => [
        'seeder_mob' => "                [\n                    'name' => 'Arcymag Pustki i Arkanów',\n                    'type' => 'mystical',\n                    'level' => 86,\n                    'rank' => 'boss',\n                    'stats' => ['hp' => 4500, 'atk' => 530, 'def' => 112, 'agi' => 18, 'int' => 45, 'crit' => 0.30, 'dodge' => 0.12],\n                    'abilities' => []\n                ],",
        'loot_mob_name' => 'Arcymag Pustki i Arkanów',
        'materials' => ['Eteryczny Pył', 'Czysta Mana', 'Czysty Pergamin', 'Odłamek Kostura Arcymaga', 'Runiczny Kamień', 'Magiczny Rdzeń', 'Żar Płomieni', 'Szkło Iluzji'],
        'items' => ['Kaptur Arcymaga', 'Buty Lewitacji', 'Miecz Runicznego Gwardzisty', 'Buty Żywiołaka Płomieni', 'Hełm Strażnika Arkanów', 'Zbroja Runiczna', 'Topór Magicznego Płomienia', 'Dzwon Oddechu Smoka', 'Sztylety z Czystej Energii', 'Skórznia Nasączona Magią', 'Dzwon Mistrza Iluzji', 'Szata Mistrza Iluzji', 'Maska Niewidzialności', 'Łuk z Eterycznej Energii', 'Naszyjnik Runicznej Energii', 'Kostur Arcymaga', 'Różdżka Smoczej Łuski', 'Pierścień Absolutu']
    ],
    'Skażone Miasto' => [
        'seeder_mob' => "                [\n                    'name' => 'Władca Skażenia i Plagi',\n                    'type' => 'demon',\n                    'level' => 99,\n                    'rank' => 'boss',\n                    'stats' => ['hp' => 6300, 'atk' => 690, 'def' => 142, 'agi' => 22, 'int' => 50, 'crit' => 0.32, 'dodge' => 0.12],\n                    'abilities' => []\n                ],",
        'loot_mob_name' => 'Władca Skażenia i Plagi',
        'materials' => ['Skażony Metal', 'Popioły Miasta', 'Czarny Kamień Dusz', 'Skażona Kość', 'Przeklęta Stal', 'Fiolka Zgnilizny', 'Jad Pająka Plagi', 'Esencja Zniszczenia'],
        'items' => ['Hełm Rycerza Skazy', 'Buty Zgnilizny', 'Ostrze Skażonego Rycerza', 'Pancerz Skażonej Stali', 'Topór Czarownicy Zgnilizny', 'Kaptur Pająka Plagi', 'Rozdzieracz Światów', 'Pancerz Absolutnego Chaosu', 'Łuk Tkany z Pajęczyny Plagi', 'Sztylety Jadu Pająka Plagi', 'Miecz Pana Zniszczenia', 'Korona Pana Zniszczenia', 'Dzwon Ostatniego Tchnienia', 'Szata Mrocznej Pustki', 'Łuk Apokalipsy', 'Serce Pana Zniszczenia', 'Sygnet Apokalipsy', 'Sztylety Ostatecznego Zniszczenia']
    ],
];

// Update MonsterSeeder.php
$monsterSeederFile = base_path('database/seeders/MonsterSeeder.php');
$monsterSeederContent = file_get_contents($monsterSeederFile);

foreach ($newMonstersData as $mapName => $data) {
    if (str_contains($monsterSeederContent, $data['loot_mob_name'])) {
        continue; // already added
    }
    // find key in array: 'Mroczny Las' => [
    $pos = strpos($monsterSeederContent, "'{$mapName}' => [");
    if ($pos !== false) {
        $insertPos = $pos + strlen("'{$mapName}' => [");
        $monsterSeederContent = substr_replace($monsterSeederContent, "\n" . $data['seeder_mob'], $insertPos, 0);
    }
}
file_put_contents($monsterSeederFile, $monsterSeederContent);

// Update MonsterLootSeeder.php
$lootSeederFile = base_path('database/seeders/MonsterLootSeeder.php');
$lootSeederContent = file_get_contents($lootSeederFile);

foreach ($newMonstersData as $mapName => $data) {
    if (str_contains($lootSeederContent, $data['loot_mob_name'])) {
        continue;
    }
    $pos = strpos($lootSeederContent, "'{$mapName}' => [");
    if ($pos !== false) {
        $monstersPos = strpos($lootSeederContent, "'monsters' => [", $pos);
        if ($monstersPos !== false) {
            $insertPos = $monstersPos + strlen("'monsters' => [");
            $matsJson = json_encode($data['materials'], JSON_UNESCAPED_UNICODE);
            $itemsJson = json_encode($data['items'], JSON_UNESCAPED_UNICODE);
            // format php array
            $matsPhp = str_replace(['[', ']'], ['[', ']'], $matsJson);
            $itemsPhp = str_replace(['[', ']'], ['[', ']'], $itemsJson);
            $entry = "\n                    '{$data['loot_mob_name']}' => [\n                        'materials' => {$matsPhp},\n                        'items' => {$itemsPhp}\n                    ],";
            $lootSeederContent = substr_replace($lootSeederContent, $entry, $insertPos, 0);
        }
    }
}
file_put_contents($lootSeederFile, $lootSeederContent);

echo "Seeders synced successfully!\n";
