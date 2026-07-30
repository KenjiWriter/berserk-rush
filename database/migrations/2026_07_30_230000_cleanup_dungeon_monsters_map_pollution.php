<?php

use Illuminate\Database\Migrations\Migration;
use App\Infrastructure\Persistence\Monster;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    public function up(): void
    {
        // Delete high-level monsters erroneously assigned to Map 1 (Mroczny Las)
        Monster::where('map_id', 1)
            ->where('level', '>', 15)
            ->delete();

        // Re-run MonsterSeeder and DungeonSeeder to ensure clean state
        Artisan::call('db:seed', ['--class' => 'Database\Seeders\MonsterSeeder', '--force' => true]);
        Artisan::call('db:seed', ['--class' => 'Database\Seeders\MonsterLootSeeder', '--force' => true]);
        Artisan::call('db:seed', ['--class' => 'Database\Seeders\DungeonSeeder', '--force' => true]);
    }

    public function down(): void
    {
        // No reverse operation needed for cleanup
    }
};
