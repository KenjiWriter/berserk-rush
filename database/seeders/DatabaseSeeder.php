<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->call([
            MapSeeder::class,
            MonsterSeeder::class,
            ItemTemplateSeeder::class,
            MaterialItemSeeder::class,
            DungeonSeeder::class,
            LootTableSeeder::class,
            MonsterLootSeeder::class,
            PetSeeder::class,
            RecipeSeeder::class,
            EquipmentRecipeSeeder::class,
        ]);
    }
}
