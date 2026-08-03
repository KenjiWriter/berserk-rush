<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            // Timestamp zapisywany RAZ — w momencie osiągnięcia aktualnego poziomu.
            // Używany do sortowania rankingu: wyższy poziom wygrywa,
            // przy remisie wygrywa STARSZY timestamp (kto pierwszy wbił ten poziom).
            $table->timestamp('max_level_reached_at')->nullable()->after('xp');
            $table->index(['level', 'max_level_reached_at']);
        });
    }

    public function down(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->dropIndex(['level', 'max_level_reached_at']);
            $table->dropColumn('max_level_reached_at');
        });
    }
};
