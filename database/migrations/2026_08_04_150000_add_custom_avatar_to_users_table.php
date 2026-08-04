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
        Schema::table('users', function (Blueprint $table) {
            // URL lub ścieżka do indywidualnego avatara, który admin może ustawić
            // dla specjalnych kont (np. youtuberów/streamerów)
            $table->string('custom_avatar_url', 500)->nullable()->after('unlocked_avatars');
            $table->string('custom_avatar_label', 100)->nullable()->after('custom_avatar_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['custom_avatar_url', 'custom_avatar_label']);
        });
    }
};
