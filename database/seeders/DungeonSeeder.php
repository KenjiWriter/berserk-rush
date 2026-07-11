<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Infrastructure\Persistence\Dungeon;
use App\Infrastructure\Persistence\DungeonStage;
use App\Infrastructure\Persistence\Monster;

class DungeonSeeder extends Seeder
{
    public function run(): void
    {
        Dungeon::query()->delete();

        $dungeon = Dungeon::create([
            'name' => 'Zapomniane Katakumby',
            'min_level' => 8,
            'entry_item_template_id' => '01k4jpx94j70x2vv10b835key1',
        ]);

        $monsters = Monster::where('level', '>=', 8)->where('level', '<=', 15)->orderBy('level')->get();

        if ($monsters->isNotEmpty()) {
            for ($i = 0; $i < 5; $i++) {
                $monster = $monsters->random();
                DungeonStage::create([
                    'dungeon_id' => $dungeon->id,
                    'monster_id' => $monster->id,
                    'stage_order' => $i + 1,
                ]);
            }
        }

        $this->command->info('Dungeon seeder completed.');
    }
}
