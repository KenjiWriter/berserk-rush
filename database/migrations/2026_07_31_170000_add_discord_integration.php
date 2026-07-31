<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            // Discord user (snowflake) ID linked to this character, set once
            // the player runs "/discord" in-game and then "!link <code>" in
            // Discord. Nullable/unique: at most one character per Discord user.
            $table->string('discord_user_id')->nullable()->unique()->after('last_active_at');
        });

        Schema::create('discord_link_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 8)->unique();
            $table->ulid('character_id');
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->foreign('character_id')->references('id')->on('characters')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discord_link_codes');

        Schema::table('characters', function (Blueprint $table) {
            $table->dropColumn('discord_user_id');
        });
    }
};
