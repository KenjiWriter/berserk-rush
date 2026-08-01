<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pet_templates', function (Blueprint $table) {
            $table->unsignedTinyInteger('tier')->nullable()->after('rarity');
        });

        // Backfill istniejących szablonów (stare rarity -> nowy tier 1-6).
        DB::table('pet_templates')->where('rarity', 'common')->update(['tier' => 1]);
        DB::table('pet_templates')->where('rarity', 'uncommon')->update(['tier' => 2]);
        DB::table('pet_templates')->where('rarity', 'rare')->update(['tier' => 3]);
        DB::table('pet_templates')->where('rarity', 'epic')->update(['tier' => 4]);
        DB::table('pet_templates')->where('rarity', 'legendary')->update(['tier' => 6]);
    }

    public function down(): void
    {
        Schema::table('pet_templates', function (Blueprint $table) {
            $table->dropColumn('tier');
        });
    }
};
