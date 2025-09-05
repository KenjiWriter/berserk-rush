<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('crafting_recipes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('output_type', 16); // item|material
            $table->ulid('output_ref_ulid')->nullable();
            $table->unsignedBigInteger('output_qty')->default(1);
            $table->jsonb('requirements')->default(DB::raw("'{}'::jsonb")); // np. {stationLevel:2}
            $table->timestamps();
        });

        Schema::create('crafting_ingredients', function (Blueprint $table) {
            $table->id();
            $table->ulid('recipe_id');
            $table->string('ref_type', 16); // item|material
            $table->ulid('ref_ulid')->nullable();
            $table->unsignedBigInteger('qty')->default(1);
            $table->timestamps();

            $table->foreign('recipe_id')->references('id')->on('crafting_recipes')->cascadeOnDelete();
        });

        Schema::create('crafting_attempts', function (Blueprint $table) {
            $table->id();
            $table->ulid('character_id');
            $table->ulid('recipe_id');
            $table->boolean('success')->default(false);
            $table->jsonb('snapshot')->default(DB::raw("'{}'::jsonb")); // co zużyto/co powstało
            $table->string('idempotency_key', 64)->nullable()->unique();
            $table->timestamps();

            $table->foreign('character_id')->references('id')->on('characters')->cascadeOnDelete();
            $table->foreign('recipe_id')->references('id')->on('crafting_recipes')->cascadeOnDelete();
        });

        Schema::create('upgrade_rules', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('applies_to', 24); // template|slot|tier|rarity
            $table->string('applies_value')->nullable(); // np. slot=weapon
            $table->unsignedTinyInteger('from_level');
            $table->unsignedTinyInteger('to_level');
            $table->decimal('success_chance', 5, 4); // 0.0000..1.0000
            $table->string('on_fail', 12)->default('nothing'); // break|downgrade|nothing
            $table->jsonb('cost')->default(DB::raw("'{}'::jsonb")); // gold/materials
            $table->ulid('protect_item_ref_ulid')->nullable(); // opcjonalny talizman chroniący
            $table->timestamps();

            $table->index(['applies_to', 'applies_value', 'from_level']);
        });

        Schema::create('upgrade_attempts', function (Blueprint $table) {
            $table->id();
            $table->ulid('character_id');
            $table->ulid('item_instance_id');
            $table->unsignedTinyInteger('from_level');
            $table->unsignedTinyInteger('to_level');
            $table->boolean('success');
            $table->string('on_fail_applied', 12)->nullable();
            $table->string('idempotency_key', 64)->nullable()->unique();
            $table->timestamps();

            $table->foreign('character_id')->references('id')->on('characters')->cascadeOnDelete();
            $table->foreign('item_instance_id')->references('id')->on('item_instances')->cascadeOnDelete();
            $table->index(['character_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('upgrade_attempts');
        Schema::dropIfExists('upgrade_rules');
        Schema::dropIfExists('crafting_attempts');
        Schema::dropIfExists('crafting_ingredients');
        Schema::dropIfExists('crafting_recipes');
    }
};
