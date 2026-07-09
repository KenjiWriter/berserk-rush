<?php

namespace App\Application\Items;

use App\Application\Shared\Result;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\ItemInstance;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UnequipItem
{
    public function handle(Character $character, ItemInstance $item): Result
    {
        if ($item->owner_character_id !== $character->id) {
            return Result::error('NOT_OWNER', 'Nie jesteś właścicielem tego przedmiotu.');
        }

        if ($item->location !== 'equipped') {
            return Result::error('NOT_EQUIPPED', 'Przedmiot nie jest założony.');
        }

        try {
            return DB::transaction(function () use ($character, $item) {
                // Future expansion: check if inventory is full (e.g. limit of 25 items)
                // For now, infinite inventory

                $item->update([
                    'location' => 'inventory',
                ]);

                Log::info('Item unequipped', [
                    'character_id' => $character->id,
                    'item_id' => $item->id,
                ]);

                return Result::ok($item);
            });
        } catch (\Exception $e) {
            Log::error('UnequipItem failed', [
                'character_id' => $character->id,
                'item_id' => $item->id,
                'error' => $e->getMessage()
            ]);

            return Result::error('UNEQUIP_FAILED', 'Wystąpił błąd podczas zdejmowania przedmiotu.');
        }
    }
}
