<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Infrastructure\Persistence\Achievement;
use App\Infrastructure\Persistence\Title;
use App\Infrastructure\Persistence\Map;

class AchievementSeeder extends Seeder
{
    public function run(): void
    {
        // Helper function to resolve Title ID by name
        $getTitleId = function (string $titleName): ?string {
            return Title::where('name', $titleName)->first()?->id;
        };

        // Helper function to resolve Map ID by name
        $getMapId = function (string $mapName): ?string {
            return Map::where('name', $mapName)->first()?->id;
        };

        // Define achievement chains
        $chains = [
            // ====================================================
            // 1. ŁOWCA POTWORÓW (OGÓLNE ZABÓJSTWA)
            // ====================================================
            'monsters_killed_chain' => [
                [
                    'name' => 'Nowicjusz Łowiectwa',
                    'description' => 'Zgładź 50 potworów w krainie.',
                    'type' => 'monsters_killed',
                    'conditions' => null,
                    'target_value' => 50,
                    'reward_points' => 10,
                    'reward_gold' => 100,
                    'reward_exp' => 200,
                    'reward_title' => null,
                ],
                [
                    'name' => 'Doświadczony Łowca',
                    'description' => 'Zgładź 250 potworów w krainie.',
                    'type' => 'monsters_killed',
                    'conditions' => null,
                    'target_value' => 250,
                    'reward_points' => 25,
                    'reward_gold' => 500,
                    'reward_exp' => 1000,
                    'reward_title' => null,
                ],
                [
                    'name' => 'Mistrz Polowania',
                    'description' => 'Zgładź 1,000 potworów w krainie.',
                    'type' => 'monsters_killed',
                    'conditions' => null,
                    'target_value' => 1000,
                    'reward_points' => 50,
                    'reward_gold' => 2000,
                    'reward_exp' => 5000,
                    'reward_title' => 'Łowca Potworów',
                ],
                [
                    'name' => 'Legendarny Pogromca',
                    'description' => 'Zgładź 5,000 potworów w krainie.',
                    'type' => 'monsters_killed',
                    'conditions' => null,
                    'target_value' => 5000,
                    'reward_points' => 100,
                    'reward_gold' => 10000,
                    'reward_exp' => 25000,
                    'reward_title' => 'Pogromca Potworów',
                ],
            ],

            // ====================================================
            // 2. WORLD BOSSOWIE
            // ====================================================
            'worldboss_chain' => [
                [
                    'name' => 'Pierwsza Krew Bossa',
                    'description' => 'Zgładź swojego pierwszego World Bossa.',
                    'type' => 'monsters_killed',
                    'conditions' => ['monster_rank' => 'worldboss'],
                    'target_value' => 1,
                    'reward_points' => 20,
                    'reward_gold' => 500,
                    'reward_exp' => 1000,
                    'reward_title' => null,
                ],
                [
                    'name' => 'Kat Zgudzeń',
                    'description' => 'Zgładź 10 World Bossów.',
                    'type' => 'monsters_killed',
                    'conditions' => ['monster_rank' => 'worldboss'],
                    'target_value' => 10,
                    'reward_points' => 60,
                    'reward_gold' => 5000,
                    'reward_exp' => 10000,
                    'reward_title' => 'Zabójca Bossów',
                ],
                [
                    'name' => 'Pogromca Legend',
                    'description' => 'Zgładź 50 World Bossów.',
                    'type' => 'monsters_killed',
                    'conditions' => ['monster_rank' => 'worldboss'],
                    'target_value' => 50,
                    'reward_points' => 150,
                    'reward_gold' => 25000,
                    'reward_exp' => 50000,
                    'reward_title' => 'Zagłada Bossów',
                ],
            ],

            // ====================================================
            // 3. NIEUMARLI (UNDEAD)
            // ====================================================
            'undead_chain' => [
                [
                    'name' => 'Łowca Szkieletów',
                    'description' => 'Pokonaj 25 nieumarłych stworów.',
                    'type' => 'monsters_killed',
                    'conditions' => ['monster_type' => 'undead'],
                    'target_value' => 25,
                    'reward_points' => 10,
                    'reward_gold' => 150,
                    'reward_exp' => 300,
                    'reward_title' => null,
                ],
                [
                    'name' => 'Wędrowny Egzorcysta',
                    'description' => 'Pokonaj 150 nieumarłych potworów.',
                    'type' => 'monsters_killed',
                    'conditions' => ['monster_type' => 'undead'],
                    'target_value' => 150,
                    'reward_points' => 30,
                    'reward_gold' => 1000,
                    'reward_exp' => 2500,
                    'reward_title' => 'Egzorcysta',
                ],
                [
                    'name' => 'Światłość w Cieniu',
                    'description' => 'Pokonaj 500 nieumarłych stworów.',
                    'type' => 'monsters_killed',
                    'conditions' => ['monster_type' => 'undead'],
                    'target_value' => 500,
                    'reward_points' => 75,
                    'reward_gold' => 5000,
                    'reward_exp' => 12000,
                    'reward_title' => 'Świetlisty Strażnik',
                ],
            ],

            // ====================================================
            // 4. BESTIE I ZWIERZĘTA (ANIMAL)
            // ====================================================
            'animal_chain' => [
                [
                    'name' => 'Wilczy Tropizm',
                    'description' => 'Zgładź 30 zwierząt i dzikich bestii.',
                    'type' => 'monsters_killed',
                    'conditions' => ['monster_type' => 'animal'],
                    'target_value' => 30,
                    'reward_points' => 10,
                    'reward_gold' => 150,
                    'reward_exp' => 300,
                    'reward_title' => null,
                ],
                [
                    'name' => 'Władca Bestii',
                    'description' => 'Zgładź 200 zwierząt i dzikich bestii.',
                    'type' => 'monsters_killed',
                    'conditions' => ['monster_type' => 'animal'],
                    'target_value' => 200,
                    'reward_points' => 40,
                    'reward_gold' => 1500,
                    'reward_exp' => 3500,
                    'reward_title' => 'Władca Bestii',
                ],
            ],

            // ====================================================
            // 5. ORKI (ORC)
            // ====================================================
            'orc_chain' => [
                [
                    'name' => 'Pogromca Zielonoskórych',
                    'description' => 'Zgładź 50 orków na pustkowiach.',
                    'type' => 'monsters_killed',
                    'conditions' => ['monster_type' => 'orc'],
                    'target_value' => 50,
                    'reward_points' => 15,
                    'reward_gold' => 300,
                    'reward_exp' => 600,
                    'reward_title' => null,
                ],
                [
                    'name' => 'Niszczyciel Watahy',
                    'description' => 'Zgładź 300 orków.',
                    'type' => 'monsters_killed',
                    'conditions' => ['monster_type' => 'orc'],
                    'target_value' => 300,
                    'reward_points' => 50,
                    'reward_gold' => 3000,
                    'reward_exp' => 7000,
                    'reward_title' => 'Niszczyciel Orków',
                ],
            ],

            // ====================================================
            // 6. DEMONY (DEMON)
            // ====================================================
            'demon_chain' => [
                [
                    'name' => 'Czyściciel Otchłani',
                    'description' => 'Pokonaj 30 demonów.',
                    'type' => 'monsters_killed',
                    'conditions' => ['monster_type' => 'demon'],
                    'target_value' => 30,
                    'reward_points' => 20,
                    'reward_gold' => 500,
                    'reward_exp' => 1000,
                    'reward_title' => null,
                ],
                [
                    'name' => 'Pogromca Otchłani',
                    'description' => 'Pokonaj 200 demonicznych istot.',
                    'type' => 'monsters_killed',
                    'conditions' => ['monster_type' => 'demon'],
                    'target_value' => 200,
                    'reward_points' => 60,
                    'reward_gold' => 5000,
                    'reward_exp' => 12000,
                    'reward_title' => 'Łowca Demonów',
                ],
            ],

            // ====================================================
            // 7. SMOKI (DRAGON)
            // ====================================================
            'dragon_chain' => [
                [
                    'name' => 'Krew Smoka',
                    'description' => 'Pokonaj 5 smoków w Górach Cienia.',
                    'type' => 'monsters_killed',
                    'conditions' => ['monster_type' => 'dragon'],
                    'target_value' => 5,
                    'reward_points' => 30,
                    'reward_gold' => 2000,
                    'reward_exp' => 5000,
                    'reward_title' => null,
                ],
                [
                    'name' => 'Smokobójca',
                    'description' => 'Pokonaj 25 smoków.',
                    'type' => 'monsters_killed',
                    'conditions' => ['monster_type' => 'dragon'],
                    'target_value' => 25,
                    'reward_points' => 100,
                    'reward_gold' => 15000,
                    'reward_exp' => 30000,
                    'reward_title' => 'Pogromca Smoków',
                ],
            ],

            // ====================================================
            // 8. ODKRYWCA / KOLEKCJONER (ITEMS DISCOVERED)
            // ====================================================
            'collection_chain' => [
                [
                    'name' => 'Ciekawski Wędrowiec',
                    'description' => 'Odkryj 10 unikalnych przedmiotów w krainie.',
                    'type' => 'items_discovered',
                    'conditions' => null,
                    'target_value' => 10,
                    'reward_points' => 10,
                    'reward_gold' => 100,
                    'reward_exp' => 200,
                    'reward_title' => null,
                ],
                [
                    'name' => 'Zapalony Kolekcjoner',
                    'description' => 'Odkryj 50 unikalnych przedmiotów.',
                    'type' => 'items_discovered',
                    'conditions' => null,
                    'target_value' => 50,
                    'reward_points' => 30,
                    'reward_gold' => 1000,
                    'reward_exp' => 2000,
                    'reward_title' => 'Kolekcjoner',
                ],
                [
                    'name' => 'Poszukiwacz Skarbów',
                    'description' => 'Odkryj 150 unikalnych przedmiotów.',
                    'type' => 'items_discovered',
                    'conditions' => null,
                    'target_value' => 150,
                    'reward_points' => 80,
                    'reward_gold' => 5000,
                    'reward_exp' => 10000,
                    'reward_title' => 'Poszukiwacz Skarbów',
                ],
            ],
        ];

        $totalSeeded = 0;

        foreach ($chains as $chainName => $steps) {
            $parentId = null;

            foreach ($steps as $stepData) {
                $rewardTitleId = $stepData['reward_title'] ? $getTitleId($stepData['reward_title']) : null;

                $achievement = Achievement::updateOrCreate(
                    ['name' => $stepData['name']],
                    [
                        'parent_achievement_id' => $parentId,
                        'description' => $stepData['description'],
                        'type' => $stepData['type'],
                        'conditions' => $stepData['conditions'],
                        'target_value' => $stepData['target_value'],
                        'reward_points' => $stepData['reward_points'],
                        'reward_gold' => $stepData['reward_gold'],
                        'reward_exp' => $stepData['reward_exp'],
                        'reward_title_id' => $rewardTitleId,
                    ]
                );

                // Pass current achievement ID to next step as parent_achievement_id
                $parentId = $achievement->id;
                $totalSeeded++;
            }
        }

        // ====================================================
        // 9. EKSPLORACJA SPECIFIKOWANYCH MAP (MAP HUNTER)
        // ====================================================
        $mapAchievements = [
            [
                'name' => 'Obrońca Mrocznego Lasu',
                'map_name' => 'Mroczny Las',
                'target_value' => 100,
                'reward_points' => 20,
                'reward_gold' => 300,
                'reward_exp' => 600,
                'reward_title' => 'Obrońca Mrocznego Lasu',
            ],
            [
                'name' => 'Badacz Starych Ruin',
                'map_name' => 'Stare Ruiny',
                'target_value' => 100,
                'reward_points' => 30,
                'reward_gold' => 600,
                'reward_exp' => 1200,
                'reward_title' => null,
            ],
            [
                'name' => 'Pogromca Jaskini Trolli',
                'map_name' => 'Jaskinia Trolli',
                'target_value' => 100,
                'reward_points' => 40,
                'reward_gold' => 1000,
                'reward_exp' => 2000,
                'reward_title' => null,
            ],
            [
                'name' => 'Czyszczenie Wieży Magów',
                'map_name' => 'Wieża Magów',
                'target_value' => 100,
                'reward_points' => 60,
                'reward_gold' => 3000,
                'reward_exp' => 6000,
                'reward_title' => 'Mistrz Arkanów',
            ],
            [
                'name' => 'Wyzwoliciel Skażonego Miasta',
                'map_name' => 'Skażone Miasto',
                'target_value' => 100,
                'reward_points' => 80,
                'reward_gold' => 5000,
                'reward_exp' => 10000,
                'reward_title' => 'Oswobodziciel Skażonego Miasta',
            ],
        ];

        foreach ($mapAchievements as $mapAch) {
            $mapId = $getMapId($mapAch['map_name']);
            if (!$mapId) {
                continue;
            }

            $rewardTitleId = $mapAch['reward_title'] ? $getTitleId($mapAch['reward_title']) : null;

            Achievement::updateOrCreate(
                ['name' => $mapAch['name']],
                [
                    'parent_achievement_id' => null,
                    'description' => "Zgładź {$mapAch['target_value']} potworów na terenie lokacji {$mapAch['map_name']}.",
                    'type' => 'monsters_killed',
                    'conditions' => ['map_id' => $mapId],
                    'target_value' => $mapAch['target_value'],
                    'reward_points' => $mapAch['reward_points'],
                    'reward_gold' => $mapAch['reward_gold'],
                    'reward_exp' => $mapAch['reward_exp'],
                    'reward_title_id' => $rewardTitleId,
                ]
            );
            $totalSeeded++;
        }

        $this->command->info('Achievement seeder completed - created ' . $totalSeeded . ' achievements across multiple tiers.');
    }
}
