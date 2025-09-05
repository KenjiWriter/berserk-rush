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
            $table->string('slot'); // head|chest|legs|weapon|offhand|boots|ring|amulet|material
            $table->unsignedSmallInteger('tier')->default(1);
            $table->unsignedSmallInteger('level_min')->default(1);
            $table->unsignedSmallInteger('level_max')->default(1);
            $table->string('rarity')->default('common'); // common|uncommon|rare|epic|legendary|mythic
            $table->jsonb('base_stats')->default(DB::raw("'{}'::jsonb"));
            $table->jsonb('flags')->default(DB::raw("'{}'::jsonb")); // {bindOnEquip:true, unique:false, ...}
            $table->timestamps();

            $table->index(['slot', 'tier', 'rarity']);
        });

        Schema::create('item_instances', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('template_id');
            $table->ulid('owner_character_id')->nullable();
            $table->string('location'); // inventory|equipped|market|mail
            $table->string('rarity')->default('common');
            $table->unsignedTinyInteger('upgrade_level')->default(0); // 0..9
            $table->unsignedInteger('stack_size')->default(1);
            $table->jsonb('roll_stats')->default(DB::raw("'{}'::jsonb")); // losowe afiksy
            $table->string('seed')->nullable();
            $table->timestamp('bound_at')->nullable();
            $table->unsignedInteger('version')->default(1); // optimistic lock
            $table->timestamps();

            $table->foreign('template_id')->references('id')->on('item_templates')->cascadeOnDelete();
            $table->foreign('owner_character_id')->references('id')->on('characters')->nullOnDelete();
            $table->index(['owner_character_id', 'location']);
        });

        Schema::create('item_ledger', function (Blueprint $table) {
            $table->id();
            $table->ulid('character_id')->nullable(); // kto dostał/stracił
            $table->ulid('item_instance_id')->nullable();
            $table->bigInteger('qty')->default(1); // dla stacków
            $table->string('action', 24); // drop|craft|upgrade|sell|buy|mail|equip|unequip|destroy
            $table->string('ref_type', 32)->nullable();
            $table->ulid('ref_id')->nullable();
            $table->string('idempotency_key', 64)->nullable()->unique();
            $table->timestamps();

            $table->foreign('character_id')->references('id')->on('characters')->nullOnDelete();
            $table->foreign('item_instance_id')->references('id')->on('item_instances')->nullOnDelete();
            $table->index(['character_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_ledger');
        Schema::dropIfExists('item_instances');
        Schema::dropIfExists('item_templates');
    }
};
