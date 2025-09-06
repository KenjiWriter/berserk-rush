<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('encounters', function (Blueprint $table) {
            $table->bigInteger('gold_reward')->default(0)->after('result');
            $table->bigInteger('xp_reward')->default(0)->after('gold_reward');
        });
    }

    public function down(): void
    {
        Schema::table('encounters', function (Blueprint $table) {
            $table->dropColumn(['gold_reward', 'xp_reward']);
        });
    }
};
