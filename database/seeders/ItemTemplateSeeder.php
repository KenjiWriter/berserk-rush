<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Infrastructure\Persistence\ItemTemplate;
use Illuminate\Support\Str;

class ItemTemplateSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing templates first
        ItemTemplate::query()->delete();

        $templates = [
            // Weapons - STR based
            [
                'id' => '01k4jpx94j70x2vv10b835prm4', // Keep this specific ID for the error
                'name' => 'Zardzewiały Miecz',
                'type' => 'weapon',
                'slot' => 'main_hand',
                'level_requirement' => 1,
                'base_stats' => [
                    'attack_min' => 2,
                    'attack_max' => 4,
                    'str_bonus' => 1,
                ],
                'description' => 'Stary, zardzewiały miecz. Mimo wieku nadal może zadać obrażenia.',
                'icon' => 'sword-rusty',
                'rarity_weights' => [
                    'common' => 70,
                    'uncommon' => 25,
                    'rare' => 5,
                ],
            ],
            [
                'id' => Str::ulid(),
                'name' => 'Bojowy Topór',
                'type' => 'weapon',
                'slot' => 'main_hand',
                'level_requirement' => 4,
                'base_stats' => [
                    'attack_min' => 5,
                    'attack_max' => 8,
                    'str_bonus' => 2,
                ],
                'description' => 'Ciężki topór wojenny. Zadaje potężne obrażenia.',
                'icon' => 'axe-battle',
                'rarity_weights' => [
                    'common' => 60,
                    'uncommon' => 30,
                    'rare' => 10,
                ],
            ],
            [
                'id' => Str::ulid(),
                'name' => 'Stalowy Miecz',
                'type' => 'weapon',
                'slot' => 'main_hand',
                'level_requirement' => 7,
                'base_stats' => [
                    'attack_min' => 8,
                    'attack_max' => 12,
                    'str_bonus' => 3,
                ],
                'description' => 'Dobrze wykuty miecz ze stali. Ostrze błyszczy niebezpiecznie.',
                'icon' => 'sword-steel',
                'rarity_weights' => [
                    'common' => 50,
                    'uncommon' => 35,
                    'rare' => 15,
                ],
            ],

            // Weapons - AGI based (ranged)
            [
                'id' => Str::ulid(),
                'name' => 'Drewniany Łuk',
                'type' => 'weapon',
                'slot' => 'main_hand',
                'level_requirement' => 2,
                'base_stats' => [
                    'attack_min' => 3,
                    'attack_max' => 6,
                    'agi_bonus' => 1,
                ],
                'description' => 'Prosty łuk wykonany z elastycznego drewna.',
                'icon' => 'bow-wooden',
                'rarity_weights' => [
                    'common' => 65,
                    'uncommon' => 30,
                    'rare' => 5,
                ],
            ],
            [
                'id' => Str::ulid(),
                'name' => 'Łuk Myśliwski',
                'type' => 'weapon',
                'slot' => 'main_hand',
                'level_requirement' => 6,
                'base_stats' => [
                    'attack_min' => 7,
                    'attack_max' => 11,
                    'agi_bonus' => 2,
                    'crit_chance' => 5,
                ],
                'description' => 'Precyzyjny łuk używany przez doświadczonych myśliwych.',
                'icon' => 'bow-hunter',
                'rarity_weights' => [
                    'common' => 45,
                    'uncommon' => 40,
                    'rare' => 15,
                ],
            ],

            // Weapons - INT based (magic)
            [
                'id' => Str::ulid(),
                'name' => 'Drewniana Różdżka',
                'type' => 'weapon',
                'slot' => 'main_hand',
                'level_requirement' => 3,
                'base_stats' => [
                    'magic_attack_min' => 4,
                    'magic_attack_max' => 7,
                    'int_bonus' => 2,
                    'mana_bonus' => 10,
                ],
                'description' => 'Prosta różdżka z magicznego drewna. Pulsuje słabą energią.',
                'icon' => 'wand-wooden',
                'rarity_weights' => [
                    'common' => 60,
                    'uncommon' => 30,
                    'rare' => 10,
                ],
            ],

            // Armor - VIT focused
            [
                'id' => Str::ulid(),
                'name' => 'Skórzana Zbroja',
                'type' => 'armor',
                'slot' => 'chest',
                'level_requirement' => 1,
                'base_stats' => [
                    'defense' => 2,
                    'hp_bonus' => 5,
                    'vit_bonus' => 1,
                ],
                'description' => 'Podstawowa ochrona ze skóry. Lekka i wygodna.',
                'icon' => 'armor-leather',
                'rarity_weights' => [
                    'common' => 75,
                    'uncommon' => 20,
                    'rare' => 5,
                ],
            ],
            [
                'id' => Str::ulid(),
                'name' => 'Żelazny Hełm',
                'type' => 'armor',
                'slot' => 'head',
                'level_requirement' => 3,
                'base_stats' => [
                    'defense' => 3,
                    'hp_bonus' => 8,
                    'vit_bonus' => 1,
                ],
                'description' => 'Solidny hełm z żelaza. Chroni głowę przed ciosami.',
                'icon' => 'helmet-iron',
                'rarity_weights' => [
                    'common' => 70,
                    'uncommon' => 25,
                    'rare' => 5,
                ],
            ],
            [
                'id' => Str::ulid(),
                'name' => 'Kolczuga',
                'type' => 'armor',
                'slot' => 'chest',
                'level_requirement' => 5,
                'base_stats' => [
                    'defense' => 6,
                    'hp_bonus' => 12,
                    'vit_bonus' => 2,
                ],
                'description' => 'Zbroja z metalowych ogniw. Zapewnia dobrą ochronę.',
                'icon' => 'armor-chainmail',
                'rarity_weights' => [
                    'common' => 55,
                    'uncommon' => 35,
                    'rare' => 10,
                ],
            ],
            [
                'id' => Str::ulid(),
                'name' => 'Skórzane Buty',
                'type' => 'armor',
                'slot' => 'feet',
                'level_requirement' => 2,
                'base_stats' => [
                    'defense' => 1,
                    'agi_bonus' => 1,
                ],
                'description' => 'Wygodne buty ze skóry. Ciche i elastyczne.',
                'icon' => 'boots-leather',
                'rarity_weights' => [
                    'common' => 80,
                    'uncommon' => 15,
                    'rare' => 5,
                ],
            ],

            // Accessories
            [
                'id' => Str::ulid(),
                'name' => 'Pierścień Siły',
                'type' => 'accessory',
                'slot' => 'ring',
                'level_requirement' => 3,
                'base_stats' => [
                    'str_bonus' => 2,
                ],
                'description' => 'Magiczny pierścień zwiększający siłę noszącego.',
                'icon' => 'ring-strength',
                'rarity_weights' => [
                    'common' => 40,
                    'uncommon' => 45,
                    'rare' => 15,
                ],
            ],
            [
                'id' => Str::ulid(),
                'name' => 'Pierścień Zręczności',
                'type' => 'accessory',
                'slot' => 'ring',
                'level_requirement' => 3,
                'base_stats' => [
                    'agi_bonus' => 2,
                ],
                'description' => 'Lekki pierścień poprawiający zręczność.',
                'icon' => 'ring-agility',
                'rarity_weights' => [
                    'common' => 40,
                    'uncommon' => 45,
                    'rare' => 15,
                ],
            ],
            [
                'id' => Str::ulid(),
                'name' => 'Amulet Żywotności',
                'type' => 'accessory',
                'slot' => 'neck',
                'level_requirement' => 4,
                'base_stats' => [
                    'vit_bonus' => 3,
                    'hp_bonus' => 15,
                ],
                'description' => 'Pradawny amulet wzmacniający życiową energię.',
                'icon' => 'amulet-vitality',
                'rarity_weights' => [
                    'common' => 35,
                    'uncommon' => 45,
                    'rare' => 20,
                ],
            ],
            [
                'id' => Str::ulid(),
                'name' => 'Amulet Mądrości',
                'type' => 'accessory',
                'slot' => 'neck',
                'level_requirement' => 4,
                'base_stats' => [
                    'int_bonus' => 3,
                    'mana_bonus' => 20,
                ],
                'description' => 'Mistyczny amulet zwiększający pojemność umysłu.',
                'icon' => 'amulet-wisdom',
                'rarity_weights' => [
                    'common' => 35,
                    'uncommon' => 45,
                    'rare' => 20,
                ],
            ],

            // Consumables
            [
                'id' => Str::ulid(),
                'name' => 'Mikstura Leczenia',
                'type' => 'consumable',
                'slot' => null,
                'level_requirement' => 1,
                'base_stats' => [
                    'heal_amount' => 25,
                ],
                'description' => 'Czerwona mikstura przywracająca zdrowie.',
                'icon' => 'potion-health',
                'rarity_weights' => [
                    'common' => 80,
                    'uncommon' => 15,
                    'rare' => 5,
                ],
            ],
            [
                'id' => Str::ulid(),
                'name' => 'Eliksir Many',
                'type' => 'consumable',
                'slot' => null,
                'level_requirement' => 2,
                'base_stats' => [
                    'mana_amount' => 30,
                ],
                'description' => 'Niebieska mikstura przywracająca energię magiczną.',
                'icon' => 'potion-mana',
                'rarity_weights' => [
                    'common' => 75,
                    'uncommon' => 20,
                    'rare' => 5,
                ],
            ],
            [
                'id' => Str::ulid(),
                'name' => 'Wielka Mikstura Leczenia',
                'type' => 'consumable',
                'slot' => null,
                'level_requirement' => 5,
                'base_stats' => [
                    'heal_amount' => 60,
                ],
                'description' => 'Potężna mikstura przywracająca znaczną ilość zdrowia.',
                'icon' => 'potion-health-greater',
                'rarity_weights' => [
                    'common' => 60,
                    'uncommon' => 30,
                    'rare' => 10,
                ],
            ],
        ];

        foreach ($templates as $template) {
            ItemTemplate::create($template);
        }

        $this->command->info('Created ' . count($templates) . ' item templates');
    }
}
