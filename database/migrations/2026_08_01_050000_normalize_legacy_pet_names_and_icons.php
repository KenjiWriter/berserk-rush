<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Ujednolica istniejące pety w bazie z aktualną pulą gatunków (`PetTemplate`).
 *
 * Przed wpięciem `PetTemplate` do wyklucia (patrz IncubatorService::pickSpecies())
 * hatch generował losowe, niepowiązane z niczym nazwy/ikony (np. "Pomocnik
 * Niedźwiedź" z ikoną `pet_dragon`/`pet_wolf`/... - to NIE są prawdziwe ikony
 * FontAwesome, więc renderują się jako puste/czarne kwadraty). Takie pety mogły
 * też "rozmnożyć" swoją starą nazwę/ikonę dalej przez Fuzję
 * (`PetFusionService::fusedPetName()` po prostu kopiuje nazwę jednego z rodziców).
 *
 * Dla KAŻDEGO istniejącego peta:
 * - Jeśli jego (tier, name) pasuje do aktualnego `PetTemplate` - tylko
 *   synchronizujemy `icon` z tym, co template ma DZIŚ (naprawia stare,
 *   nieaktualne wartości ikon nawet przy pasującej nazwie).
 * - Jeśli nie pasuje (stara losowa nazwa) - losujemy nazwę+ikonę z puli
 *   gatunków danego tieru, dokładnie jak przy wykluciu.
 *
 * Staty/poziom/fusion_count/tier peta NIE są ruszane - to czysto kosmetyczna
 * naprawa nazwy i ikony.
 */
return new class extends Migration
{
    public function up(): void
    {
        $pets = DB::table('pets')->get();
        $templatesByTier = DB::table('pet_templates')
            ->whereNotNull('tier')
            ->get()
            ->groupBy(fn ($t) => (int) $t->tier);

        foreach ($pets as $pet) {
            $templatesForTier = $templatesByTier->get((int) $pet->tier);
            if (!$templatesForTier || $templatesForTier->isEmpty()) {
                continue; // brak gatunków dla tego tieru na tej instalacji - pomiń bezpiecznie
            }

            $matching = $templatesForTier->firstWhere('name', $pet->name);

            if ($matching) {
                if ($pet->icon !== $matching->icon) {
                    DB::table('pets')->where('id', $pet->id)->update(['icon' => $matching->icon]);
                }
                continue;
            }

            $random = $templatesForTier->random();
            DB::table('pets')->where('id', $pet->id)->update([
                'name' => $random->name,
                'icon' => $random->icon,
            ]);
        }
    }

    public function down(): void
    {
        // Nieodwracalne - stare, losowe nazwy/ikony i tak nie są warte przywrócenia.
    }
};
