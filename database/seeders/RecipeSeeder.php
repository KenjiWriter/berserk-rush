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
        $materials = ItemTemplate::where('type', 'material')->get()->keyBy('name');

        if ($materials->isEmpty()) {
            $this->command->warn('Brakuje materiałów w bazie. Uruchom najpierw MaterialItemSeeder.');
            return;
        }

        // Czyszczenie starych receptur dla mikstur
        $potionIds = ItemTemplate::where('type', 'consumable')->pluck('id');
        ItemRecipe::whereIn('result_item_template_id', $potionIds)->delete();

        $potionRecipes = [
            // Tier S (Poziom 1+)
            [
                'result_name' => 'Eliksir Życia (S)',
                'result_id' => 'potion-hp-s',
                'ingredients' => [
                    ['name' => 'Mroczne Zioło', 'quantity' => 2],
                    ['name' => 'Magiczny Mech', 'quantity' => 1],
                ],
                'gold_cost' => 50,
            ],
            [
                'result_name' => 'Mikstura Siły (S)',
                'result_id' => 'potion-str-s',
                'ingredients' => [
                    ['name' => 'Wilczy Kieł', 'quantity' => 2],
                    ['name' => 'Mroczne Zioło', 'quantity' => 1],
                ],
                'gold_cost' => 60,
            ],
            [
                'result_name' => 'Mikstura Obrony (S)',
                'result_id' => 'potion-def-s',
                'ingredients' => [
                    ['name' => 'Prastara Kora', 'quantity' => 2],
                    ['name' => 'Magiczny Mech', 'quantity' => 1],
                ],
                'gold_cost' => 60,
            ],
            [
                'result_name' => 'Mikstura Szału (S)',
                'result_id' => 'potion-crit-s',
                'ingredients' => [
                    ['name' => 'Słaby Kryształ Many', 'quantity' => 1],
                    ['name' => 'Błona Skrzydła', 'quantity' => 2],
                ],
                'gold_cost' => 70,
            ],
            [
                'result_name' => 'Mikstura Uniku (S)',
                'result_id' => 'potion-agi-s',
                'ingredients' => [
                    ['name' => 'Błona Skrzydła', 'quantity' => 2],
                    ['name' => 'Mroczne Zioło', 'quantity' => 1],
                ],
                'gold_cost' => 60,
            ],

            // Tier M (Poziom 10+)
            [
                'result_name' => 'Eliksir Życia (M)',
                'result_id' => 'potion-hp-m',
                'ingredients' => [
                    ['name' => 'Bagienne Zioło', 'quantity' => 3],
                    ['name' => 'Błyszczący Grzyb', 'quantity' => 2],
                    ['name' => 'Mętna Woda', 'quantity' => 1],
                ],
                'gold_cost' => 200,
            ],
            [
                'result_name' => 'Mikstura Siły (M)',
                'result_id' => 'potion-str-m',
                'ingredients' => [
                    ['name' => 'Krew Jaskiniowca', 'quantity' => 2],
                    ['name' => 'Złamany Kieł Orka', 'quantity' => 3],
                    ['name' => 'Toksyczny Śluz', 'quantity' => 1],
                ],
                'gold_cost' => 250,
            ],
            [
                'result_name' => 'Mikstura Obrony (M)',
                'result_id' => 'potion-def-m',
                'ingredients' => [
                    ['name' => 'Gruba Skóra Trolla', 'quantity' => 3],
                    ['name' => 'Skóra Pustynna', 'quantity' => 2],
                    ['name' => 'Skamieniały Torf', 'quantity' => 1],
                ],
                'gold_cost' => 250,
            ],
            [
                'result_name' => 'Mikstura Szału (M)',
                'result_id' => 'potion-crit-m',
                'ingredients' => [
                    ['name' => 'Żar Płomieni', 'quantity' => 2],
                    ['name' => 'Skrwawiony Totem', 'quantity' => 2],
                    ['name' => 'Pył Grobowy', 'quantity' => 3],
                ],
                'gold_cost' => 300,
            ],
        ];

        $generatedCount = 0;

        foreach ($potionRecipes as $r) {
            $potionTemplate = ItemTemplate::where('id', $r['result_id'])
                ->orWhere('name', $r['result_name'])
                ->first();

            if (!$potionTemplate) {
                $this->command->warn("Nie znaleziono szablonu mikstury: {$r['result_name']} ({$r['result_id']})");
                continue;
            }

            $ingredients = [];
            $missing = false;

            foreach ($r['ingredients'] as $ing) {
                if (!isset($materials[$ing['name']])) {
                    $this->command->warn("Brakuje materiału dla mikstury {$r['result_name']}: {$ing['name']}");
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

        $this->command->info("RecipeSeeder: Wygenerowano {$generatedCount} receptur mikstur alchemicznych.");
    }
}
