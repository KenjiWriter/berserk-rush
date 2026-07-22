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
        Schema::create('combat_skills', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type')->default('active'); // active, passive
            $table->string('required_weapon_type')->nullable(); // np. bow, sword, polearm
            $table->string('effect_type'); // np. poison, fire, buff_phys_dmg, direct_dmg
            $table->integer('base_cooldown')->default(0);
            $table->integer('base_duration')->default(0); // w turach
            $table->float('base_value')->default(0); // np. 0.2 dla 20%
            $table->float('scaling_value')->default(0); // przyrost na poziom
            $table->integer('required_level')->default(1);
            $table->integer('unlock_cost')->default(5);
            $table->timestamps();
        });

        Schema::create('character_combat_skills', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('character_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('combat_skill_id')->constrained('combat_skills')->cascadeOnDelete();
            $table->integer('level')->default(1);
            $table->boolean('is_equipped')->default(false);
            $table->integer('equip_slot')->nullable();
            $table->timestamps();

            $table->unique(['character_id', 'combat_skill_id'], 'char_combat_skill_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('character_combat_skills');
        Schema::dropIfExists('combat_skills');
    }
};
