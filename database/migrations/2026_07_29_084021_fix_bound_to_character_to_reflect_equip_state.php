<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Data-fix (nie zmiana schematu): bound_to_character ma teraz odzwierciedlać
 * WYŁĄCZNIE aktualny stan założenia przedmiotu (patrz EquipItem/UnequipItem) -
 * wcześniej był to permanentny znacznik nadawany raz przy stworzeniu instancji
 * (starterowy miecz z CreateCharacter, nagrody z TutorialOverlay). Ta migracja
 * ujednolica istniejące dane w bazie z nową semantyką:
 * - Przedmioty aktualnie założone (location='equipped') -> bound_to_character=true.
 * - Wszystkie pozostałe (plecak, magazyny, rynek, poczta itd.) -> bound_to_character=false.
 * Trwałą ochronę startowych/samouczkowych przedmiotów przed handlem przejmuje
 * teraz ItemTemplate::is_tradeable (patrz ItemTemplateSeeder) - stąd rollback
 * tej migracji jest celowo no-opem, nie da się jednoznacznie odtworzyć starego stanu.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('item_instances')->where('location', 'equipped')->where('bound_to_character', false)->update(['bound_to_character' => true]);
        DB::table('item_instances')->where('location', '!=', 'equipped')->where('bound_to_character', true)->update(['bound_to_character' => false]);
    }

    public function down(): void
    {
        // Celowo brak odwrócenia - patrz komentarz wyżej.
    }
};
