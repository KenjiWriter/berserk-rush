<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            // Set once, the first time this character successfully links a
            // Discord account (see DiscordChatBridgeCommand::handleLinkCommand).
            // Prevents farming the one-time reward via unlink/relink.
            $table->timestamp('discord_link_reward_claimed_at')->nullable()->after('discord_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->dropColumn('discord_link_reward_claimed_at');
        });
    }
};
