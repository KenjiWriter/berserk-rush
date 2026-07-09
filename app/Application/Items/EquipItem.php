<?php

namespace App\Application\Items;

use App\Application\Shared\Result;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\ItemInstance;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EquipItem
{
    public function handle(Character $character, ItemInstance $item): Result
    {
        if ($item->owner_character_id !== $character->id) {
            return Result::error('NOT_OWNER', 'Nie jesteś właścicielem tego przedmiotu.');
        }

        if ($item->location !== 'inventory') {
            return Result::error('NOT_IN_INVENTORY', 'Przedmiot musi znajdować się w ekwipunku, aby go założyć.');
        }

        $template = $item->template;
        if (!$template) {
            return Result::error('INVALID_ITEM', 'Przedmiot jest uszkodzony (brak szablonu).');
        }

        if ($character->level < $template->level_requirement) {
            return Result::error('LEVEL_TOO_LOW', "Wymagany poziom do założenia to {$template->level_requirement}.");
        }

        if (!$template->slot) {
            return Result::error('NOT_EQUIPPABLE', 'Tego przedmiotu nie można założyć.');
        }

        try {
            return DB::transaction(function () use ($character, $item, $template) {
                // Find currently equipped item in the same slot
                $currentlyEquipped = ItemInstance::where('owner_character_id', $character->id)
                    ->where('location', 'equipped')
                    ->whereHas('template', function ($query) use ($template) {
                        $query->where('slot', $template->slot);
                    })
                    ->first();

                // If there's an item, unequip it
                if ($currentlyEquipped) {
                    $currentlyEquipped->update(['location' => 'inventory']);
                }

                // Equip the new item
                $item->update([
                    'location' => 'equipped',
                    'bound_to_character' => true, // Equipping usually binds the item
                ]);

                Log::info('Item equipped', [
                    'character_id' => $character->id,
                    'item_id' => $item->id,
                    'slot' => $template->slot
                ]);

                return Result::ok([
                    'unequipped' => $currentlyEquipped,
                    'equipped' => $item
                ]);
            });
        } catch (\Exception $e) {
            Log::error('EquipItem failed', [
                'character_id' => $character->id,
                'item_id' => $item->id,
                'error' => $e->getMessage()
            ]);

            return Result::error('EQUIP_FAILED', 'Wystąpił błąd podczas zakładania przedmiotu.');
        }
    }
}
