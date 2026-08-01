<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('item_templates', function (Blueprint $table) {
            $table->unsignedTinyInteger('egg_tier')->nullable()->after('type');
        });

        Schema::table('character_incubators', function (Blueprint $table) {
            $table->unsignedTinyInteger('egg_tier')->nullable()->after('egg_rarity');
        });
    }

    public function down(): void
    {
        Schema::table('item_templates', function (Blueprint $table) {
            $table->dropColumn('egg_tier');
        });

        Schema::table('character_incubators', function (Blueprint $table) {
            $table->dropColumn('egg_tier');
        });
    }
};
