<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * backpack_slot: pozycja itemu w plecaku postaci (0-63, null = auto)
     * material_slot: pozycja materiału w magazynie materiałów (0-99, null = auto)
     * Unikalność (character + slot) wymuszana w serwisie BackpackSlotService,
     * nie jako DB constraint (MySQL nie wspiera partial unique index).
     */
    public function up(): void
    {
        Schema::table('item_instances', function (Blueprint $table) {
            $table->unsignedSmallInteger('backpack_slot')->nullable()->after('location');
            $table->unsignedSmallInteger('material_slot')->nullable()->after('backpack_slot');

            // Indeksy dla szybkich lookupów wg slotu
            $table->index(['owner_character_id', 'backpack_slot'], 'ii_char_backpack_slot');
            $table->index(['owner_character_id', 'material_slot'], 'ii_char_material_slot');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('item_instances', function (Blueprint $table) {
            $table->dropIndex('ii_char_backpack_slot');
            $table->dropIndex('ii_char_material_slot');
            $table->dropColumn(['backpack_slot', 'material_slot']);
        });
    }
};
