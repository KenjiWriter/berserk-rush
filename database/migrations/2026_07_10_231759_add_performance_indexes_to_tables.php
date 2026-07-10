<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('item_instances', function (Blueprint $table) {
            $table->index('template_id');
        });

        Schema::table('encounters', function (Blueprint $table) {
            $table->index('map_id');
            $table->index('monster_id');
        });

        Schema::table('item_ledgers', function (Blueprint $table) {
            $table->index('item_instance_id');
        });

        Schema::table('market_listings', function (Blueprint $table) {
            $table->index('item_instance_id');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->index('listing_id');
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropIndex(['listing_id']);
        });

        Schema::table('market_listings', function (Blueprint $table) {
            $table->dropIndex(['item_instance_id']);
        });

        Schema::table('item_ledgers', function (Blueprint $table) {
            $table->dropIndex(['item_instance_id']);
        });

        Schema::table('encounters', function (Blueprint $table) {
            $table->dropIndex(['monster_id']);
            $table->dropIndex(['map_id']);
        });

        Schema::table('item_instances', function (Blueprint $table) {
            $table->dropIndex(['template_id']);
        });
    }
};
