<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Infrastructure\Persistence\ItemTemplate;
use App\Infrastructure\Persistence\ItemRecipe;
use Illuminate\Support\Str;

class RecipeSeeder extends Seeder
{
    /**
     * Run the database seeds for consumable recipes (potions/elixirs).
     */
    public function run(): void
    {
        $this->call(PotionSeeder::class);

        $materials = ItemTemplate::where('type', 'material')->get()->keyBy('name');

        if ($materials->isEmpty()) {
            if (isset($this->command)) {
                $this->command->warn('Brakuje materiałów w bazie. Uruchom najpierw MaterialItemSeeder.');
            }
            return;
        }

        // Czyszczenie starych receptur dla mikstur
        $potionIds = ItemTemplate::where('type', 'consumable')->pluck('id');
        ItemRecipe::whereIn('result_item_template_id', $potionIds)->delete();

        $potionRecipes = [
            // --- TIER S (Poziom 1+) ---
            [
                'result_name' => 'Mały Eliksir Życia (S)',
                'result_id' => 'potion-hp-s',
                'ingredients' => [
                    ['name' => 'Mroczne Zioło', 'quantity' => 2],
                    ['name' => 'Magiczny Mech', 'quantity' => 1],
                ],
                'gold_cost' => 50,
            ],
            [
                'result_name' => 'Mały Eliksir Many (S)',
                'result_id' => 'potion-mana-s',
                'ingredients' => [
                    ['name' => 'Czysta Mana', 'quantity' => 2],
                    ['name' => 'Magiczny Mech', 'quantity' => 1],
                ],
                'gold_cost' => 50,
            ],
            [
                'result_name' => 'Mała Mikstura Siły (S)',
                'result_id' => 'potion-str-s',
                'ingredients' => [
                    ['name' => 'Wilczy Kieł', 'quantity' => 2],
                    ['name' => 'Mroczne Zioło', 'quantity' => 1],
                ],
                'gold_cost' => 60,
            ],
            [
                'result_name' => 'Mała Mikstura Zwinności (S)',
                'result_id' => 'potion-agi-s',
                'ingredients' => [
                    ['name' => 'Błona Skrzydła', 'quantity' => 2],
                    ['name' => 'Mroczne Zioło', 'quantity' => 1],
                ],
                'gold_cost' => 60,
            ],
            [
                'result_name' => 'Mała Mikstura Inteligencji (S)',
                'result_id' => 'potion-int-s',
                'ingredients' => [
                    ['name' => 'Mętna Woda', 'quantity' => 2],
                    ['name' => 'Magiczny Mech', 'quantity' => 1],
                ],
                'gold_cost' => 60,
            ],
            [
                'result_name' => 'Mała Mikstura Witalności (S)',
                'result_id' => 'potion-vit-s',
                'ingredients' => [
                    ['name' => 'Toksyczny Śluz', 'quantity' => 2],
                    ['name' => 'Mroczne Zioło', 'quantity' => 1],
                ],
                'gold_cost' => 60,
            ],
            [
                'result_name' => 'Mała Mikstura Obrony (S)',
                'result_id' => 'potion-def-s',
                'ingredients' => [
                    ['name' => 'Prastara Kora', 'quantity' => 2],
                    ['name' => 'Magiczny Mech', 'quantity' => 1],
                ],
                'gold_cost' => 60,
            ],
            [
                'result_name' => 'Mała Mikstura Szału (S)',
                'result_id' => 'potion-crit-s',
                'ingredients' => [
                    ['name' => 'Słaby Kryształ Many', 'quantity' => 1],
                    ['name' => 'Błona Skrzydła', 'quantity' => 2],
                ],
                'gold_cost' => 70,
            ],
            [
                'result_name' => 'Mała Mikstura Łowcy Potworów (S)',
                'result_id' => 'potion-monster-s',
                'ingredients' => [
                    ['name' => 'Żar Płomieni', 'quantity' => 1],
                    ['name' => 'Wilczy Kieł', 'quantity' => 2],
                ],
                'gold_cost' => 80,
            ],

            // --- TIER M (Poziom 10+) ---
            [
                'result_name' => 'Średni Eliksir Życia (M)',
                'result_id' => 'potion-hp-m',
                'ingredients' => [
                    ['name' => 'Bagienne Zioło', 'quantity' => 3],
                    ['name' => 'Błyszczący Grzyb', 'quantity' => 2],
                    ['name' => 'Mętna Woda', 'quantity' => 1],
                ],
                'gold_cost' => 200,
            ],
            [
                'result_name' => 'Średni Eliksir Many (M)',
                'result_id' => 'potion-mana-m',
                'ingredients' => [
                    ['name' => 'Czysta Mana', 'quantity' => 3],
                    ['name' => 'Błyszczący Grzyb', 'quantity' => 2],
                ],
                'gold_cost' => 200,
            ],
            [
                'result_name' => 'Średnia Mikstura Siły (M)',
                'result_id' => 'potion-str-m',
                'ingredients' => [
                    ['name' => 'Krew Jaskiniowca', 'quantity' => 2],
                    ['name' => 'Złamany Kieł Orka', 'quantity' => 3],
                    ['name' => 'Toksyczny Śluz', 'quantity' => 1],
                ],
                'gold_cost' => 250,
            ],
            [
                'result_name' => 'Średnia Mikstura Zwinności (M)',
                'result_id' => 'potion-agi-m',
                'ingredients' => [
                    ['name' => 'Błona Skrzydła', 'quantity' => 4],
                    ['name' => 'Czysta Mana', 'quantity' => 2],
                ],
                'gold_cost' => 250,
            ],
            [
                'result_name' => 'Średnia Mikstura Inteligencji (M)',
                'result_id' => 'potion-int-m',
                'ingredients' => [
                    ['name' => 'Mętna Woda', 'quantity' => 3],
                    ['name' => 'Czysta Mana', 'quantity' => 2],
                ],
                'gold_cost' => 250,
            ],
            [
                'result_name' => 'Średnia Mikstura Witalności (M)',
                'result_id' => 'potion-vit-m',
                'ingredients' => [
                    ['name' => 'Toksyczny Śluz', 'quantity' => 3],
                    ['name' => 'Bagienne Zioło', 'quantity' => 2],
                ],
                'gold_cost' => 250,
            ],
            [
                'result_name' => 'Średnia Mikstura Obrony (M)',
                'result_id' => 'potion-def-m',
                'ingredients' => [
                    ['name' => 'Gruba Skóra Trolla', 'quantity' => 3],
                    ['name' => 'Skóra Pustynna', 'quantity' => 2],
                    ['name' => 'Skamieniały Torf', 'quantity' => 1],
                ],
                'gold_cost' => 250,
            ],
            [
                'result_name' => 'Średnia Mikstura Szału (M)',
                'result_id' => 'potion-crit-m',
                'ingredients' => [
                    ['name' => 'Żar Płomieni', 'quantity' => 2],
                    ['name' => 'Skrwawiony Totem', 'quantity' => 2],
                    ['name' => 'Pył Grobowy', 'quantity' => 3],
                ],
                'gold_cost' => 300,
            ],
            [
                'result_name' => 'Średnia Mikstura Łowcy Potworów (M)',
                'result_id' => 'potion-monster-m',
                'ingredients' => [
                    ['name' => 'Żar Płomieni', 'quantity' => 3],
                    ['name' => 'Skrwawiony Totem', 'quantity' => 2],
                    ['name' => 'Krew Jaskiniowca', 'quantity' => 2],
                ],
                'gold_cost' => 350,
            ],

            // --- TIER L (Poziom 25+) ---
            [
                'result_name' => 'Duży Eliksir Życia (L)',
                'result_id' => 'potion-hp-l',
                'ingredients' => [
                    ['name' => 'Bagienne Zioło', 'quantity' => 5],
                    ['name' => 'Błyszczący Grzyb', 'quantity' => 4],
                    ['name' => 'Gruba Skóra Trolla', 'quantity' => 2],
                ],
                'gold_cost' => 1000,
            ],
            [
                'result_name' => 'Duży Eliksir Many (L)',
                'result_id' => 'potion-mana-l',
                'ingredients' => [
                    ['name' => 'Czysta Mana', 'quantity' => 5],
                    ['name' => 'Słaby Kryształ Many', 'quantity' => 3],
                ],
                'gold_cost' => 1000,
            ],
            [
                'result_name' => 'Duża Mikstura Siły (L)',
                'result_id' => 'potion-str-l',
                'ingredients' => [
                    ['name' => 'Krew Jaskiniowca', 'quantity' => 5],
                    ['name' => 'Złamany Kieł Orka', 'quantity' => 5],
                ],
                'gold_cost' => 1200,
            ],
            [
                'result_name' => 'Duża Mikstura Zwinności (L)',
                'result_id' => 'potion-agi-l',
                'ingredients' => [
                    ['name' => 'Błona Skrzydła', 'quantity' => 6],
                    ['name' => 'Czysta Mana', 'quantity' => 4],
                ],
                'gold_cost' => 1200,
            ],
            [
                'result_name' => 'Duża Mikstura Inteligencji (L)',
                'result_id' => 'potion-int-l',
                'ingredients' => [
                    ['name' => 'Mętna Woda', 'quantity' => 5],
                    ['name' => 'Słaby Kryształ Many', 'quantity' => 3],
                ],
                'gold_cost' => 1200,
            ],
            [
                'result_name' => 'Duża Mikstura Witalności (L)',
                'result_id' => 'potion-vit-l',
                'ingredients' => [
                    ['name' => 'Toksyczny Śluz', 'quantity' => 5],
                    ['name' => 'Gruba Skóra Trolla', 'quantity' => 4],
                ],
                'gold_cost' => 1200,
            ],
            [
                'result_name' => 'Duża Mikstura Obrony (L)',
                'result_id' => 'potion-def-l',
                'ingredients' => [
                    ['name' => 'Gruba Skóra Trolla', 'quantity' => 5],
                    ['name' => 'Skóra Pustynna', 'quantity' => 4],
                ],
                'gold_cost' => 1200,
            ],
            [
                'result_name' => 'Duża Mikstura Szału (L)',
                'result_id' => 'potion-crit-l',
                'ingredients' => [
                    ['name' => 'Żar Płomieni', 'quantity' => 5],
                    ['name' => 'Skrwawiony Totem', 'quantity' => 4],
                ],
                'gold_cost' => 1500,
            ],
            [
                'result_name' => 'Duża Mikstura Łowcy Potworów (L)',
                'result_id' => 'potion-monster-l',
                'ingredients' => [
                    ['name' => 'Żar Płomieni', 'quantity' => 5],
                    ['name' => 'Skrwawiony Totem', 'quantity' => 4],
                    ['name' => 'Krew Jaskiniowca', 'quantity' => 4],
                ],
                'gold_cost' => 1800,
            ],
        ];

        $generatedCount = 0;

        foreach ($potionRecipes as $r) {
            $potionTemplate = ItemTemplate::where('id', $r['result_id'])
                ->orWhere('name', $r['result_name'])
                ->first();

            if (!$potionTemplate) {
                if (isset($this->command)) {
                    $this->command->warn("Nie znaleziono szablonu mikstury: {$r['result_name']} ({$r['result_id']})");
                }
                continue;
            }

            $ingredients = [];
            $missing = false;

            foreach ($r['ingredients'] as $ing) {
                if (!isset($materials[$ing['name']])) {
                    if (isset($this->command)) {
                        $this->command->warn("Brakuje materiału dla mikstury {$r['result_name']}: {$ing['name']}");
                    }
                    $missing = true;
                    break;
                }

                $ingredients[] = [
                    'template_id' => $materials[$ing['name']]->id,
                    'quantity' => $ing['quantity'],
                ];
            }

            if (!$missing && !empty($ingredients)) {
                ItemRecipe::updateOrCreate(
                    ['result_item_template_id' => $potionTemplate->id],
                    [
                        'id' => (string) Str::ulid(),
                        'ingredients' => $ingredients,
                        'gold_cost' => $r['gold_cost'],
                    ]
                );
                $generatedCount++;
            }
        }

        if (isset($this->command)) {
            $this->command->info("RecipeSeeder: Wygenerowano {$generatedCount} receptur mikstur alchemicznych.");
        }
    }
}
