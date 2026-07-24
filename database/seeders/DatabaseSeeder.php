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
        User::firstOrCreate([
            'email' => 'test@example.com',
        ], [
            'name' => 'Test User',
            'password' => bcrypt('password'),
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
            PotionSeeder::class,
            RecipeSeeder::class,
            EquipmentRecipeSeeder::class,
            ShopEquipmentSeeder::class,
            NewsSeeder::class,
            TitleSeeder::class,
            AchievementSeeder::class,
            QuestSeeder::class,
            CombatSkillSeeder::class,
        ]);
    }
}
