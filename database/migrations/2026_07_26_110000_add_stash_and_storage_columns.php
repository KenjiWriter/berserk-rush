<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'stash_slots')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedInteger('stash_slots')->default(2)->after('gems');
            });
        }

        Schema::table('item_instances', function (Blueprint $table) {
            if (!Schema::hasColumn('item_instances', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('owner_character_id')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('item_instances', 'guild_id')) {
                $table->ulid('guild_id')->nullable()->after('user_id')->constrained('guilds')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('item_instances', function (Blueprint $table) {
            if (Schema::hasColumn('item_instances', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
            if (Schema::hasColumn('item_instances', 'guild_id')) {
                $table->dropForeign(['guild_id']);
                $table->dropColumn('guild_id');
            }
        });

        if (Schema::hasColumn('users', 'stash_slots')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('stash_slots');
            });
        }
    }
};
