<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Infrastructure\Persistence\ItemTemplate;
use App\Infrastructure\Persistence\UpgradeRule;

class UpgradeRuleSeeder extends Seeder
{
    public function run(): void
    {
        $materialMap = ItemTemplate::where('type', 'material')->pluck('id', 'name')->toArray();

        $tierMaterials = [
            1  => ['primary' => 'Wilczy Kieł',        'secondary' => 'Prastara Kora',       'rare' => 'Magiczny Mech'],
            2  => ['primary' => 'Prastara Kora',       'secondary' => 'Strzaskana Kość',     'rare' => 'Ektoplazma'],
            3  => ['primary' => 'Strzaskana Kość',     'secondary' => 'Fragment Całunu',     'rare' => 'Przeklęty Onyks'],
            4  => ['primary' => 'Gruba Skóra Trolla',  'secondary' => 'Ogrzy Pazur',         'rare' => 'Ruda Żelaza'],
            5  => ['primary' => 'Złamany Kieł Orka',   'secondary' => 'Twarde Rzemienie',    'rare' => 'Symbol Wodza'],
            6  => ['primary' => 'Błotnisty Korzeń',    'secondary' => 'Łuska Hydry',         'rare' => 'Toksyczny Śluz'],
            7  => ['primary' => 'Odłamek Bazaltu',     'secondary' => 'Łuska Smoka Cienia',  'rare' => 'Kryształ Cienia'],
            8  => ['primary' => 'Runiczny Kamień',     'secondary' => 'Magiczny Rdzeń',      'rare' => 'Eteryczny Pył'],
            9  => ['primary' => 'Skażona Kość',        'secondary' => 'Przeklęta Stal',      'rare' => 'Esencja Zniszczenia'],
            10 => ['primary' => 'Skażona Kość',        'secondary' => 'Czarny Kamień Dusz',  'rare' => 'Esencja Zniszczenia'],
        ];

        // Faza 5 rebalansu (2026-08-05): nowe szanse powodzenia podane wprost przez
        // gracza (100/100/95/90/85/75/65/55/45), i kara za porażkę - od +6 wzwyż
        // nieudane ulepszenie obniża przedmiot o 1 poziom ('downgrade'), do +5 bez
        // kary (tylko strata surowców/złota, jak dotychczas). Materiały ("ulepszacze"):
        // krok do +3 celowo BEZ materiałów (tylko złoto) - próg +3 daje darmowy bonus
        // (patrz UpgradeService::syncThresholdBonus()), więc sam koszt wejścia na niego
        // ma zostać niski. Materiały wracają od kroku do +4 wzwyż.
        $upgradeSteps = [
            0 => ['to' => 1, 'chance' => 1.00, 'onFail' => 'nothing',   'gold' => 100,  'mats' => []],
            1 => ['to' => 2, 'chance' => 1.00, 'onFail' => 'nothing',   'gold' => 250,  'mats' => []],
            2 => ['to' => 3, 'chance' => 0.95, 'onFail' => 'nothing',   'gold' => 500,  'mats' => []],
            3 => ['to' => 4, 'chance' => 0.90, 'onFail' => 'nothing',   'gold' => 1000, 'mats' => [['key' => 'primary', 'qty' => 2]]],
            4 => ['to' => 5, 'chance' => 0.85, 'onFail' => 'nothing',   'gold' => 2000, 'mats' => [['key' => 'primary', 'qty' => 2], ['key' => 'secondary', 'qty' => 1]]],
            5 => ['to' => 6, 'chance' => 0.75, 'onFail' => 'downgrade', 'gold' => 4000, 'mats' => [['key' => 'primary', 'qty' => 3], ['key' => 'secondary', 'qty' => 2]]],
            6 => ['to' => 7, 'chance' => 0.65, 'onFail' => 'downgrade', 'gold' => 8000, 'mats' => [['key' => 'secondary', 'qty' => 3], ['key' => 'rare', 'qty' => 1]]],
            7 => ['to' => 8, 'chance' => 0.55, 'onFail' => 'downgrade', 'gold' => 16000, 'mats' => [['key' => 'secondary', 'qty' => 4], ['key' => 'rare', 'qty' => 2]]],
            8 => ['to' => 9, 'chance' => 0.45, 'onFail' => 'downgrade', 'gold' => 32000, 'mats' => [['key' => 'secondary', 'qty' => 5], ['key' => 'rare', 'qty' => 3]]],
        ];

        $templates = ItemTemplate::whereIn('type', ['weapon', 'armor', 'accessory'])->get();
        $createdCount = 0;

        foreach ($templates as $template) {
            $level = max(1, (int) $template->level_requirement);
            
            $tierIndex = match (true) {
                $level <= 10 => 1,
                $level <= 20 => 2,
                $level <= 30 => 3,
                $level <= 40 => 4,
                $level <= 50 => 5,
                $level <= 60 => 6,
                $level <= 70 => 7,
                $level <= 80 => 8,
                $level <= 90 => 9,
                default      => 10,
            };

            $tierMatNames = $tierMaterials[$tierIndex];

            foreach ($upgradeSteps as $fromLevel => $step) {
                $goldCost = (int) round($step['gold'] * max(1, $level * 0.4));
                $materialsReq = [];

                foreach ($step['mats'] as $mConfig) {
                    $matName = $tierMatNames[$mConfig['key']];
                    $matId = $materialMap[$matName] ?? null;

                    if ($matId) {
                        $materialsReq[] = [
                            'template_id' => $matId,
                            'quantity' => $mConfig['qty'],
                        ];
                    }
                }

                // Wszystkie przedmioty od +6 do +9 wymagają Runicznych Odłamków - skalowane z poziomem wymaganym przedmiotu
                if ($fromLevel >= 5) {
                    $runicMatId = $materialMap['Runiczny Odłamek'] ?? null;

                    // Mnożnik progu poziomu przedmiotu (Niski / Średni / Wysoki / Endgame)
                    $levelFactor = match (true) {
                        $level <= 30 => 0.1,  // Lvl 1 - 30
                        $level <= 60 => 0.4,  // Lvl 31 - 60
                        $level <= 80 => 1.0,  // Lvl 61 - 80
                        default      => 2.0,  // Lvl 81 - 99
                    };

                    $stepBase = match ($fromLevel) {
                        5 => 40,   // -> +6
                        6 => 100,  // -> +7
                        7 => 220,  // -> +8
                        8 => 450,  // -> +9
                        default => 0,
                    };

                    $runicQty = (int) max(5, round($stepBase * $levelFactor));

                    if ($runicMatId && $runicQty > 0) {
                        $materialsReq[] = [
                            'template_id' => $runicMatId,
                            'quantity'   => $runicQty,
                        ];
                    }
                }

                UpgradeRule::updateOrCreate(
                    [
                        'applies_to' => 'template',
                        'applies_value' => $template->id,
                        'from_level' => $fromLevel,
                    ],
                    [
                        'to_level' => $step['to'],
                        'success_chance' => $step['chance'],
                        'on_fail' => $step['onFail'],
                        'cost' => [
                            'gold' => $goldCost,
                            'materials' => $materialsReq,
                        ],
                    ]
                );

                $createdCount++;
            }
        }

        // Generic fallback rules for types: weapon, armor, accessory
        $types = ['weapon', 'armor', 'accessory'];
        $defaultMatId = $materialMap['Wilczy Kieł'] ?? null;

        foreach ($types as $type) {
            foreach ($upgradeSteps as $fromLevel => $step) {
                $materialsReq = [];
                if (!empty($step['mats']) && $defaultMatId) {
                    $totalQty = array_sum(array_column($step['mats'], 'qty'));
                    $materialsReq[] = [
                        'template_id' => $defaultMatId,
                        'quantity' => max(1, $totalQty),
                    ];
                }

                UpgradeRule::updateOrCreate(
                    [
                        'applies_to' => 'type',
                        'applies_value' => $type,
                        'from_level' => $fromLevel,
                    ],
                    [
                        'to_level' => $step['to'],
                        'success_chance' => $step['chance'],
                        'on_fail' => $step['onFail'],
                        'cost' => [
                            'gold' => $step['gold'],
                            'materials' => $materialsReq,
                        ],
                    ]
                );
            }
        }

        $this->command->info("UpgradeRuleSeeder completed. Seeded {$createdCount} template-specific upgrade rules.");
    }
}
