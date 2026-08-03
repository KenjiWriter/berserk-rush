<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Ikony gatunków petów przechodzą z nazw ikon FontAwesome (`dog`, `dragon`...)
 * na prawdziwe pliki graficzne w `public/assets/items/` (`pet_wolf`,
 * `pet_dragon`...), renderowane teraz jako <img> zamiast <i class="fa-...">
 * (patrz zmiany w resources/views/livewire/city/pets.blade.php i innych).
 *
 * Ta migracja zakłada, że `PetSeeder` został już ponownie odpalony
 * (`php artisan db:seed --class=PetSeeder`) i `pet_templates.icon` ma już
 * nowe wartości - tutaj tylko synchronizujemy istniejące pety (`pets.icon`)
 * z aktualną wartością szablonu ich gatunku (dopasowanie po nazwie), tak jak
 * poprzednia migracja normalizująca (`..._normalize_legacy_pet_names_and_icons`).
 */
return new class extends Migration
{
    public function up(): void
    {
        $pets = DB::table('pets')->get();
        $templatesByName = DB::table('pet_templates')->get()->keyBy('name');

        foreach ($pets as $pet) {
            $template = $templatesByName->get($pet->name);
            if ($template && $pet->icon !== $template->icon) {
                DB::table('pets')->where('id', $pet->id)->update(['icon' => $template->icon]);
            }
        }
    }

    public function down(): void
    {
        // Nieodwracalne - poprzednie wartości ikon (FA) i tak są już nieaktualne.
    }
};
