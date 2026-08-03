<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dodaje "Rodzaj" (archetyp) peta: attacker|defense|support. Daje pasywny
 * bonus bojowy zależny od `fusion_count` i `tier` (patrz
 * Character::getEquipmentStats() i docs/modules/pets.md).
 *
 * Nullable - istniejące, wcześniej wyklute/zfuzjonowane pety zostają bez
 * archetypu (brak pasywki) zamiast retroaktywnie zmieniać ich tożsamość
 * po raz kolejny; nowe pety (od tego wdrożenia) dostają archetyp z puli
 * gatunków (PetTemplate).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pet_templates', function (Blueprint $table) {
            $table->string('archetype', 16)->nullable()->after('tier');
        });

        Schema::table('pets', function (Blueprint $table) {
            $table->string('archetype', 16)->nullable()->after('tier');
        });
    }

    public function down(): void
    {
        Schema::table('pet_templates', function (Blueprint $table) {
            $table->dropColumn('archetype');
        });

        Schema::table('pets', function (Blueprint $table) {
            $table->dropColumn('archetype');
        });
    }
};
