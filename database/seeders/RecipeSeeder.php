<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Infrastructure\Persistence\ItemTemplate;
use App\Infrastructure\Persistence\ItemRecipe;
use Illuminate\Support\Str;

class RecipeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $herb = ItemTemplate::where('name', 'Zioło Lecznika')->first();
        $bone = ItemTemplate::where('name', 'Odłamek Kości')->first();
        $pelt = ItemTemplate::where('name', 'Wilcza Skóra')->first();
        $gem = ItemTemplate::where('name', 'Klejnot Pustyni')->first();

        if (!$herb || !$bone || !$pelt || !$gem) {
            $this->command->warn('Brakuje podstawowych materiałów. Uruchom najpierw bazowy seeder (np. DatabaseSeeder -> ItemTemplateSeeder).');
            return;
        }

        $recipes = [
            [
                'result' => 'potion-hp-s',
                'ingredients' => [
                    ['template_id' => $herb->id, 'quantity' => 2]
                ],
                'gold_cost' => 50,
            ],
            [
                'result' => 'potion-hp-m',
                'ingredients' => [
                    ['template_id' => $herb->id, 'quantity' => 5],
                    ['template_id' => $gem->id, 'quantity' => 1]
                ],
                'gold_cost' => 150,
            ],
            [
                'result' => 'potion-str-s',
                'ingredients' => [
                    ['template_id' => $bone->id, 'quantity' => 2],
                    ['template_id' => $herb->id, 'quantity' => 1]
                ],
                'gold_cost' => 60,
            ],
            [
                'result' => 'potion-def-s',
                'ingredients' => [
                    ['template_id' => $pelt->id, 'quantity' => 2],
                    ['template_id' => $herb->id, 'quantity' => 1]
                ],
                'gold_cost' => 60,
            ],
        ];

        foreach ($recipes as $r) {
            ItemRecipe::firstOrCreate([
                'result_item_template_id' => $r['result']
            ], [
                'id' => (string) Str::ulid(),
                'ingredients' => $r['ingredients'],
                'gold_cost' => $r['gold_cost']
            ]);
        }
    }
}
