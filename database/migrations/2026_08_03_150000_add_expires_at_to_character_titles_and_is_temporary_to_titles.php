<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('titles', function (Blueprint $table) {
            $table->boolean('is_temporary')->default(false)->after('stats_bonus');
        });

        Schema::table('character_titles', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->after('unlocked_at');
        });
    }

    public function down(): void
    {
        Schema::table('titles', function (Blueprint $table) {
            $table->dropColumn('is_temporary');
        });

        Schema::table('character_titles', function (Blueprint $table) {
            $table->dropColumn('expires_at');
        });
    }
};
