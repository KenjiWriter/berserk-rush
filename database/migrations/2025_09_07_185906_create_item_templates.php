<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_templates', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('type'); // weapon, armor, accessory, consumable
            $table->string('slot')->nullable(); // main_hand, head, chest, feet, ring, neck
            $table->integer('level_requirement')->default(1);
            $table->jsonb('base_stats')->default(DB::raw("'{}'::jsonb"));
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->jsonb('rarity_weights')->default(DB::raw("'{\"common\":70,\"uncommon\":25,\"rare\":5}'::jsonb"));
            $table->timestamps();

            // Basic indexes
            $table->index('type');
            $table->index('slot');
            $table->index('level_requirement');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_templates');
    }
};
