<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Infrastructure\Persistence\ItemTemplate;
use App\Infrastructure\Persistence\MerchantItem;

class PotionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $potions = [
            // --- SIŁA (STR) ---
            [
                'id' => 'potion-str-s',
                'name' => 'Mała Mikstura Siły (S)',
                'type' => 'consumable',
                'level_requirement' => 1,
                'base_stats' => ['str_pct' => 5, 'duration_minutes' => 60],
                'description' => 'Zwiększa Siłę postaci o 5% na 1 godzinę.',
                'icon' => 'bagienne-ziolo.png',
                'price' => 100,
            ],
            [
                'id' => 'potion-str-m',
                'name' => 'Średnia Mikstura Siły (M)',
                'type' => 'consumable',
                'level_requirement' => 10,
                'base_stats' => ['str_pct' => 10, 'duration_minutes' => 180],
                'description' => 'Zwiększa Siłę postaci o 10% na 3 godziny.',
                'icon' => 'mroczne-ziolo.png',
                'price' => 500,
            ],
            [
                'id' => 'potion-str-l',
                'name' => 'Duża Mikstura Siły (L)',
                'type' => 'consumable',
                'level_requirement' => 25,
                'base_stats' => ['str_pct' => 15, 'duration_minutes' => 360],
                'description' => 'Zwiększa Siłę postaci o 15% na 6 godzin.',
                'icon' => 'mroczne-ziolo.png',
                'price' => 2500,
            ],

            // --- ZWINNOŚĆ (AGI) ---
            [
                'id' => 'potion-agi-s',
                'name' => 'Mała Mikstura Zwinności (S)',
                'type' => 'consumable',
                'level_requirement' => 1,
                'base_stats' => ['agi_pct' => 5, 'duration_minutes' => 60],
                'description' => 'Zwiększa Zwinność postaci o 5% na 1 godzinę.',
                'icon' => 'czysta-mana.png',
                'price' => 100,
            ],
            [
                'id' => 'potion-agi-m',
                'name' => 'Średnia Mikstura Zwinności (M)',
                'type' => 'consumable',
                'level_requirement' => 10,
                'base_stats' => ['agi_pct' => 10, 'duration_minutes' => 180],
                'description' => 'Zwiększa Zwinność postaci o 10% na 3 godziny.',
                'icon' => 'czysta-mana.png',
                'price' => 500,
            ],
            [
                'id' => 'potion-agi-l',
                'name' => 'Duża Mikstura Zwinności (L)',
                'type' => 'consumable',
                'level_requirement' => 25,
                'base_stats' => ['agi_pct' => 15, 'duration_minutes' => 360],
                'description' => 'Zwiększa Zwinność postaci o 15% na 6 godzin.',
                'icon' => 'czysta-mana.png',
                'price' => 2500,
            ],

            // --- INTELIGENCJA (INT) ---
            [
                'id' => 'potion-int-s',
                'name' => 'Mała Mikstura Inteligencji (S)',
                'type' => 'consumable',
                'level_requirement' => 1,
                'base_stats' => ['int_pct' => 5, 'duration_minutes' => 60],
                'description' => 'Zwiększa Inteligencję postaci o 5% na 1 godzinę.',
                'icon' => 'metna-woda.png',
                'price' => 100,
            ],
            [
                'id' => 'potion-int-m',
                'name' => 'Średnia Mikstura Inteligencji (M)',
                'type' => 'consumable',
                'level_requirement' => 10,
                'base_stats' => ['int_pct' => 10, 'duration_minutes' => 180],
                'description' => 'Zwiększa Inteligencję postaci o 10% na 3 godziny.',
                'icon' => 'metna-woda.png',
                'price' => 500,
            ],
            [
                'id' => 'potion-int-l',
                'name' => 'Duża Mikstura Inteligencji (L)',
                'type' => 'consumable',
                'level_requirement' => 25,
                'base_stats' => ['int_pct' => 15, 'duration_minutes' => 360],
                'description' => 'Zwiększa Inteligencję postaci o 15% na 6 godzin.',
                'icon' => 'metna-woda.png',
                'price' => 2500,
            ],

            // --- WITALNOŚĆ (VIT) ---
            [
                'id' => 'potion-vit-s',
                'name' => 'Mała Mikstura Witalności (S)',
                'type' => 'consumable',
                'level_requirement' => 1,
                'base_stats' => ['vit_pct' => 5, 'duration_minutes' => 60],
                'description' => 'Zwiększa Witalność postaci o 5% na 1 godzinę.',
                'icon' => 'toksyczny-sluz.png',
                'price' => 100,
            ],
            [
                'id' => 'potion-vit-m',
                'name' => 'Średnia Mikstura Witalności (M)',
                'type' => 'consumable',
                'level_requirement' => 10,
                'base_stats' => ['vit_pct' => 10, 'duration_minutes' => 180],
                'description' => 'Zwiększa Witalność postaci o 10% na 3 godziny.',
                'icon' => 'toksyczny-sluz.png',
                'price' => 500,
            ],
            [
                'id' => 'potion-vit-l',
                'name' => 'Duża Mikstura Witalności (L)',
                'type' => 'consumable',
                'level_requirement' => 25,
                'base_stats' => ['vit_pct' => 15, 'duration_minutes' => 360],
                'description' => 'Zwiększa Witalność postaci o 15% na 6 godzin.',
                'icon' => 'toksyczny-sluz.png',
                'price' => 2500,
            ],

            // --- OBRONA (DEF) ---
            [
                'id' => 'potion-def-s',
                'name' => 'Mała Mikstura Obrony (S)',
                'type' => 'consumable',
                'level_requirement' => 1,
                'base_stats' => ['defense_pct' => 5, 'duration_minutes' => 60],
                'description' => 'Zwiększa Pancerz postaci o 5% na 1 godzinę.',
                'icon' => 'metna-woda.png',
                'price' => 100,
            ],
            [
                'id' => 'potion-def-m',
                'name' => 'Średnia Mikstura Obrony (M)',
                'type' => 'consumable',
                'level_requirement' => 10,
                'base_stats' => ['defense_pct' => 10, 'duration_minutes' => 180],
                'description' => 'Zwiększa Pancerz postaci o 10% na 3 godziny.',
                'icon' => 'toksyczny-sluz.png',
                'price' => 500,
            ],
            [
                'id' => 'potion-def-l',
                'name' => 'Duża Mikstura Obrony (L)',
                'type' => 'consumable',
                'level_requirement' => 25,
                'base_stats' => ['defense_pct' => 15, 'duration_minutes' => 360],
                'description' => 'Zwiększa Pancerz postaci o 15% na 6 godzin.',
                'icon' => 'toksyczny-sluz.png',
                'price' => 2500,
            ],

            // --- SZAŁ / KRYTYK (CRIT) ---
            [
                'id' => 'potion-crit-s',
                'name' => 'Mała Mikstura Szału (S)',
                'type' => 'consumable',
                'level_requirement' => 1,
                'base_stats' => ['crit_chance' => 5, 'duration_minutes' => 60],
                'description' => 'Zwiększa szansę na cios krytyczny o 5% na 1 godzinę.',
                'icon' => 'zar-plomieni.png',
                'price' => 120,
            ],
            [
                'id' => 'potion-crit-m',
                'name' => 'Średnia Mikstura Szału (M)',
                'type' => 'consumable',
                'level_requirement' => 10,
                'base_stats' => ['crit_chance' => 10, 'duration_minutes' => 180],
                'description' => 'Zwiększa szansę na cios krytyczny o 10% na 3 godziny.',
                'icon' => 'zar-plomieni.png',
                'price' => 600,
            ],
            [
                'id' => 'potion-crit-l',
                'name' => 'Duża Mikstura Szału (L)',
                'type' => 'consumable',
                'level_requirement' => 25,
                'base_stats' => ['crit_chance' => 15, 'duration_minutes' => 360],
                'description' => 'Zwiększa szansę na cios krytyczny o 15% na 6 godzin.',
                'icon' => 'zar-plomieni.png',
                'price' => 3000,
            ],

            // --- MIKSTURA ŁOWCY POTWORÓW (MONSTER HUNTER) ---
            [
                'id' => 'potion-monster-s',
                'name' => 'Mała Mikstura Łowcy Potworów (S)',
                'type' => 'consumable',
                'level_requirement' => 1,
                'base_stats' => ['bonus_vs_monsters' => 5, 'duration_minutes' => 60],
                'description' => 'Zwiększa obrażenia zadawane wszystkim potworom o 5% na 1 godzinę.',
                'icon' => 'zar-plomieni.png',
                'price' => 150,
            ],
            [
                'id' => 'potion-monster-m',
                'name' => 'Średnia Mikstura Łowcy Potworów (M)',
                'type' => 'consumable',
                'level_requirement' => 10,
                'base_stats' => ['bonus_vs_monsters' => 10, 'duration_minutes' => 180],
                'description' => 'Zwiększa obrażenia zadawane wszystkim potworom o 10% na 3 godziny.',
                'icon' => 'mroczne-ziolo.png',
                'price' => 750,
            ],
            [
                'id' => 'potion-monster-l',
                'name' => 'Duża Mikstura Łowcy Potworów (L)',
                'type' => 'consumable',
                'level_requirement' => 25,
                'base_stats' => ['bonus_vs_monsters' => 15, 'duration_minutes' => 360],
                'description' => 'Zwiększa obrażenia zadawane wszystkim potworom o 15% na 6 godzin.',
                'icon' => 'mroczne-ziolo.png',
                'price' => 3500,
            ],

            // --- ELIKSIR ŻYCIA / HP ---
            [
                'id' => 'potion-hp-s',
                'name' => 'Mały Eliksir Życia (S)',
                'type' => 'consumable',
                'level_requirement' => 1,
                'base_stats' => ['hp_pct' => 5, 'heal_pct' => 5, 'duration_minutes' => 60],
                'description' => 'Zwiększa maksymalne punkty zdrowia o 5% na 1 godzinę (w lochach przywraca 5% HP).',
                'icon' => 'bagienne-ziolo.png',
                'price' => 100,
            ],
            [
                'id' => 'potion-hp-m',
                'name' => 'Średni Eliksir Życia (M)',
                'type' => 'consumable',
                'level_requirement' => 10,
                'base_stats' => ['hp_pct' => 10, 'heal_pct' => 10, 'duration_minutes' => 180],
                'description' => 'Zwiększa maksymalne punkty zdrowia o 10% na 3 godziny (w lochach przywraca 10% HP).',
                'icon' => 'mroczne-ziolo.png',
                'price' => 500,
            ],
            [
                'id' => 'potion-hp-l',
                'name' => 'Duży Eliksir Życia (L)',
                'type' => 'consumable',
                'level_requirement' => 25,
                'base_stats' => ['hp_pct' => 15, 'heal_pct' => 15, 'duration_minutes' => 360],
                'description' => 'Zwiększa maksymalne punkty zdrowia o 15% na 6 godzin (w lochach przywraca 15% HP).',
                'icon' => 'mroczne-ziolo.png',
                'price' => 2500,
            ],

            // --- ELIKSIR MANY / MANA ---
            [
                'id' => 'potion-mana-s',
                'name' => 'Mały Eliksir Many (S)',
                'type' => 'consumable',
                'level_requirement' => 1,
                'base_stats' => ['mana_pct' => 5, 'mana_heal_pct' => 5, 'duration_minutes' => 60],
                'description' => 'Zwiększa maksymalną manę o 5% na 1 godzinę.',
                'icon' => 'czysta-mana.png',
                'price' => 100,
            ],
            [
                'id' => 'potion-mana-m',
                'name' => 'Średni Eliksir Many (M)',
                'type' => 'consumable',
                'level_requirement' => 10,
                'base_stats' => ['mana_pct' => 10, 'mana_heal_pct' => 10, 'duration_minutes' => 180],
                'description' => 'Zwiększa maksymalną manę o 10% na 3 godziny.',
                'icon' => 'czysta-mana.png',
                'price' => 500,
            ],
            [
                'id' => 'potion-mana-l',
                'name' => 'Duży Eliksir Many (L)',
                'type' => 'consumable',
                'level_requirement' => 25,
                'base_stats' => ['mana_pct' => 15, 'mana_heal_pct' => 15, 'duration_minutes' => 360],
                'description' => 'Zwiększa maksymalną manę o 15% na 6 godzin.',
                'icon' => 'czysta-mana.png',
                'price' => 2500,
            ],

            // --- SPECJALNA MIKSTURA DOŚWIADCZENIA ---
            [
                'id' => 'potion-exp-special',
                'name' => 'Specjalna Mikstura Doświadczenia',
                'type' => 'consumable',
                'level_requirement' => 1,
                'base_stats' => ['exp_bonus' => 20, 'duration_minutes' => 60],
                'description' => 'Tajemniczy wywar zwiększający zdobywane doświadczenie z potworów o 20% przez 60 minut.',
                'is_tradeable' => false,
                'icon' => 'czysta-mana.png',
                'price' => 1000,
            ],
        ];

        foreach ($potions as $potionData) {
            $price = $potionData['price'] ?? 100;
            unset($potionData['price']);

            $template = ItemTemplate::updateOrCreate(['id' => $potionData['id']], $potionData);

            // Powiązanie z kupcem Czarownica (Witch)
            if ($potionData['id'] !== 'potion-exp-special') {
                MerchantItem::updateOrCreate(
                    [
                        'merchant_id' => 'witch',
                        'item_template_id' => $template->id,
                    ],
                    [
                        'required_level' => $potionData['level_requirement'] ?? 1,
                        'is_limited' => false,
                    ]
                );
            }
        }
    }
}
