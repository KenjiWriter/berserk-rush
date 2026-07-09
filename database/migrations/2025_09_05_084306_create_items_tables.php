<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('item_templates', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('type'); // weapon, armor, accessory, consumable
            $table->string('slot')->nullable(); // main_hand, head, chest, feet, ring, neck
            $table->integer('level_requirement')->default(1);
            $table->json('base_stats')->nullable();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->json('rarity_weights')->nullable();
            $table->timestamps();

            $table->index('type');
            $table->index('slot');
            $table->index('level_requirement');
        });

        Schema::create('item_instances', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('template_id');
            $table->ulid('owner_character_id')->nullable();
            $table->string('location'); // inventory|equipped|market|mail
            $table->string('rarity')->default('common');
            $table->unsignedTinyInteger('upgrade_level')->default(0); // 0..9
            $table->unsignedInteger('stack_size')->default(1);
            $table->json('roll_stats')->nullable(); // losowe afiksy
            $table->string('seed')->nullable();
            $table->boolean('bound_to_character')->default(false);
            $table->unsignedInteger('version')->default(1); // optimistic lock
            $table->timestamps();

            $table->foreign('template_id')->references('id')->on('item_templates')->cascadeOnDelete();
            $table->foreign('owner_character_id')->references('id')->on('characters')->nullOnDelete();
            $table->index(['owner_character_id', 'location']);
        });

        try {
            DB::statement("ALTER TABLE item_instances ADD CONSTRAINT chk_upgrade_level CHECK (upgrade_level BETWEEN 0 AND 9)");
        } catch (\Exception $e) {
            // Ignore if check constraints are not supported by the database engine
        }

        Schema::create('item_ledgers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('character_id');
            $table->ulid('item_instance_id');
            $table->string('action'); // drop, pickup, trade, etc.
            $table->string('ref_type'); // encounter, trade, manual, etc.
            $table->string('ref_id')->nullable();
            $table->integer('quantity_change')->default(1);
            $table->string('idempotency_key')->unique();
            $table->timestamps();

            $table->foreign('character_id')->references('id')->on('characters')->cascadeOnDelete();
            $table->foreign('item_instance_id')->references('id')->on('item_instances')->cascadeOnDelete();
            $table->index(['character_id', 'created_at']);
            $table->index(['action', 'ref_type']);
        });
    }

    public function down(): void
    {
        try {
            DB::statement("ALTER TABLE item_instances DROP CONSTRAINT IF EXISTS chk_upgrade_level");
        } catch (\Exception $e) {
            // Ignore
        }

        Schema::dropIfExists('item_ledgers');
        Schema::dropIfExists('item_instances');
        Schema::dropIfExists('item_templates');
    }
};
