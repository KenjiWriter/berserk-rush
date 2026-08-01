<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pets', function (Blueprint $table) {
            $table->unsignedTinyInteger('tier')->default(1)->after('rarity');
            $table->unsignedTinyInteger('growth_stage')->default(0)->after('level');
            $table->unsignedTinyInteger('fusion_count')->default(0)->after('growth_stage');
            $table->json('stat_profile')->nullable()->after('stats');
            $table->ulid('collar_item_instance_id')->nullable()->after('icon');
            $table->ulid('charm_item_instance_id')->nullable()->after('collar_item_instance_id');

            $table->foreign('collar_item_instance_id')->references('id')->on('item_instances')->nullOnDelete();
            $table->foreign('charm_item_instance_id')->references('id')->on('item_instances')->nullOnDelete();
        });

        // Backfill: dawne 5 rzadkości -> nowe 6 tierów (T6 "Legendarny" staje się
        // osiągalny dopiero od teraz, m.in. przez fuzję).
        DB::table('pets')->where('rarity', 'common')->update(['tier' => 1]);
        DB::table('pets')->where('rarity', 'uncommon')->update(['tier' => 2]);
        DB::table('pets')->where('rarity', 'rare')->update(['tier' => 3]);
        DB::table('pets')->where('rarity', 'epic')->update(['tier' => 4]);
        DB::table('pets')->where('rarity', 'legendary')->update(['tier' => 5]);
    }

    public function down(): void
    {
        Schema::table('pets', function (Blueprint $table) {
            $table->dropForeign(['collar_item_instance_id']);
            $table->dropForeign(['charm_item_instance_id']);
            $table->dropColumn([
                'tier',
                'growth_stage',
                'fusion_count',
                'stat_profile',
                'collar_item_instance_id',
                'charm_item_instance_id',
            ]);
        });
    }
};
