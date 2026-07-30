<?php

use Illuminate\Database\Migrations\Migration;
use App\Infrastructure\Persistence\LootTableEntry;

return new class extends Migration
{
    public function up(): void
    {
        LootTableEntry::where('reward_type', 'item')
            ->where('weight', 0)
            ->update(['weight' => 2]);
    }

    public function down(): void
    {
        LootTableEntry::where('reward_type', 'item')
            ->where('weight', 2)
            ->update(['weight' => 0]);
    }
};
