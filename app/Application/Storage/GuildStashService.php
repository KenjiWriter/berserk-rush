<?php

namespace App\Application\Storage;

use App\Application\Shared\Result;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\ItemInstance;
use App\Models\Guild;
use App\Models\GuildLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GuildStashService
{
    public function deposit(Character $character, ItemInstance $item): Result
    {
        if (!$character->guild_id) {
            return Result::error('NO_GUILD', 'Nie należysz do żadnej gildii.');
        }

        $guild = Guild::find($character->guild_id);
        if (!$guild) {
            return Result::error('GUILD_NOT_FOUND', 'Nie odnaleziono gildii.');
        }

        if ($item->owner_character_id !== $character->id) {
            return Result::error('NOT_OWNER', 'Nie jesteś właścicielem tego przedmiotu.');
        }

        $isMaterial = $item->template?->type === 'material';
        $expectedLocation = $isMaterial ? 'material_stash' : 'inventory';

        if ($item->location !== $expectedLocation) {
            return Result::error('NOT_IN_INVENTORY', 'Przedmiot nie znajduje się w plecaku ani w schowku materiałów.');
        }

        if ($item->isBound()) {
            return Result::error('BOUND_ITEM', 'Nie można przekazać przypisanego przedmiotu do magazynu gildii.');
        }

        $currentCount = $guild->guildStashItems()->count();
        $capacity = $guild->getStashCapacity();

        if ($currentCount >= $capacity) {
            return Result::error('GUILD_STASH_FULL', "Magazyn gildii jest pełny ({$currentCount}/{$capacity}).");
        }

        try {
            return DB::transaction(function () use ($item, $character, $guild) {
                $item->update([
                    'location' => 'guild_stash',
                    'guild_id' => $guild->id,
                    'owner_character_id' => null,
                ]);

                GuildLog::create([
                    'guild_id' => $guild->id,
                    'character_id' => $character->id,
                    'action' => "Zdeponowano przedmiot: {$item->template->name}",
                    'amount' => 1,
                ]);

                Log::info('Item deposited to guild stash', [
                    'guild_id' => $guild->id,
                    'character_id' => $character->id,
                    'item_id' => $item->id,
                ]);

                return Result::ok($item);
            });
        } catch (\Exception $e) {
            Log::error('GuildStash deposit failed', [
                'guild_id' => $guild->id,
                'character_id' => $character->id,
                'item_id' => $item->id,
                'error' => $e->getMessage(),
            ]);

            return Result::error('GUILD_STASH_DEPOSIT_FAILED', 'Wystąpił błąd podczas deponowania przedmiotu w magazynie gildii.');
        }
    }

    public function withdraw(Character $character, ItemInstance $item): Result
    {
        if (!$character->guild_id) {
            return Result::error('NO_GUILD', 'Nie należysz do żadnej gildii.');
        }

        if ($item->guild_id !== $character->guild_id || $item->location !== 'guild_stash') {
            return Result::error('INVALID_ITEM', 'Ten przedmiot nie znajduje się w magazynie Twojej gildii.');
        }

        $isMaterial = $item->template?->type === 'material';
        $targetLocation = $isMaterial ? 'material_stash' : 'inventory';

        if ($isMaterial) {
            if ($character->isMaterialStashFull()) {
                $count = $character->getMaterialStashCount();
                $max = $character->getMaterialStashCapacity();
                return Result::error('MATERIAL_STASH_FULL', "Twój schowek materiałów jest pełny ({$count}/{$max}). Zwolnij miejsce!");
            }
        } else {
            if ($character->isBackpackFull()) {
                $count = $character->getBackpackCount();
                $max = $character->getBackpackCapacity();
                return Result::error('INVENTORY_FULL', "Twój plecak jest pełny ({$count}/{$max}). Zwolnij miejsce w plecaku!");
            }
        }

        try {
            return DB::transaction(function () use ($item, $character, $targetLocation) {
                $guildId = $item->guild_id;
                $itemName = $item->template->name;

                $item->update([
                    'location' => $targetLocation,
                    'owner_character_id' => $character->id,
                    'guild_id' => null,
                ]);

                GuildLog::create([
                    'guild_id' => $guildId,
                    'character_id' => $character->id,
                    'action' => "Wyciągnięto przedmiot: {$itemName}",
                    'amount' => 1,
                ]);

                Log::info('Item withdrawn from guild stash', [
                    'guild_id' => $guildId,
                    'character_id' => $character->id,
                    'item_id' => $item->id,
                ]);

                return Result::ok($item);
            });
        } catch (\Exception $e) {
            Log::error('GuildStash withdraw failed', [
                'character_id' => $character->id,
                'item_id' => $item->id,
                'error' => $e->getMessage(),
            ]);

            return Result::error('GUILD_STASH_WITHDRAW_FAILED', 'Wystąpił błąd podczas wyciągania przedmiotu z magazynu gildii.');
        }
    }
}
