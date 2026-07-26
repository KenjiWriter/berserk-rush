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

        $upgradeSteps = [
            0 => ['to' => 1, 'chance' => 0.95, 'gold' => 100,  'mats' => []],
            1 => ['to' => 2, 'chance' => 0.90, 'gold' => 250,  'mats' => []],
            2 => ['to' => 3, 'chance' => 0.80, 'gold' => 500,  'mats' => [['key' => 'primary', 'qty' => 1]]],
            3 => ['to' => 4, 'chance' => 0.70, 'gold' => 1000, 'mats' => [['key' => 'primary', 'qty' => 2]]],
            4 => ['to' => 5, 'chance' => 0.60, 'gold' => 2000, 'mats' => [['key' => 'primary', 'qty' => 2], ['key' => 'secondary', 'qty' => 1]]],
            5 => ['to' => 6, 'chance' => 0.50, 'gold' => 4000, 'mats' => [['key' => 'primary', 'qty' => 3], ['key' => 'secondary', 'qty' => 2]]],
            6 => ['to' => 7, 'chance' => 0.40, 'gold' => 8000, 'mats' => [['key' => 'secondary', 'qty' => 3], ['key' => 'rare', 'qty' => 1]]],
            7 => ['to' => 8, 'chance' => 0.28, 'gold' => 16000, 'mats' => [['key' => 'secondary', 'qty' => 4], ['key' => 'rare', 'qty' => 2]]],
            8 => ['to' => 9, 'chance' => 0.15, 'gold' => 32000, 'mats' => [['key' => 'secondary', 'qty' => 5], ['key' => 'rare', 'qty' => 3]]],
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

                UpgradeRule::updateOrCreate(
                    [
                        'applies_to' => 'template',
                        'applies_value' => $template->id,
                        'from_level' => $fromLevel,
                    ],
                    [
                        'to_level' => $step['to'],
                        'success_chance' => $step['chance'],
                        'on_fail' => 'nothing',
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
                        'on_fail' => 'nothing',
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
