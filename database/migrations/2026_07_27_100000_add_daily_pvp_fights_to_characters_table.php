<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->unsignedInteger('daily_pvp_fights_used')->default(0)->after('pvp_refreshes_reset_at');
            $table->timestamp('daily_pvp_fights_last_reset_at')->nullable()->after('daily_pvp_fights_used');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->dropColumn(['daily_pvp_fights_used', 'daily_pvp_fights_last_reset_at']);
        });
    }
};
