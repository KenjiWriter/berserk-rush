<?php

namespace App\Application\Storage;

use App\Application\Shared\Result;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\CurrencyLedger;
use App\Infrastructure\Persistence\ItemInstance;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PlayerStashService
{
    public function deposit(Character $character, ItemInstance $item): Result
    {
        if ($item->owner_character_id !== $character->id) {
            return Result::error('NOT_OWNER', 'Nie jesteś właścicielem tego przedmiotu.');
        }

        $isMaterial = $item->template?->type === 'material';
        $expectedLocation = $isMaterial ? 'material_stash' : 'inventory';

        if ($item->location !== $expectedLocation) {
            return Result::error('NOT_IN_INVENTORY', 'Przedmiot nie znajduje się w plecaku ani w schowku materiałów.');
        }

        $user = $character->user()->first();
        if (!$user) {
            return Result::error('USER_NOT_FOUND', 'Błąd konta użytkownika.');
        }

        $currentCount = $user->playerStashItems()->count();
        $capacity = $user->getStashCapacity();

        if ($currentCount >= $capacity) {
            return Result::error('STASH_FULL', "Magazyn gracza jest pełny ({$currentCount}/{$capacity}). Powiększ go za diamenty!");
        }

        try {
            return DB::transaction(function () use ($item, $user) {
                $item->update([
                    'location' => 'player_stash',
                    'user_id' => $user->id,
                    'owner_character_id' => null,
                ]);

                Log::info('Item moved to player stash', [
                    'user_id' => $user->id,
                    'item_id' => $item->id,
                ]);

                return Result::ok($item);
            });
        } catch (\Exception $e) {
            Log::error('PlayerStash deposit failed', [
                'user_id' => $user->id,
                'item_id' => $item->id,
                'error' => $e->getMessage(),
            ]);

            return Result::error('STASH_DEPOSIT_FAILED', 'Wystąpił błąd podczas przenoszenia do magazynu.');
        }
    }

    public function withdraw(Character $character, ItemInstance $item): Result
    {
        $user = $character->user()->first();
        if (!$user) {
            return Result::error('USER_NOT_FOUND', 'Błąd konta użytkownika.');
        }

        if ($item->user_id !== $user->id || $item->location !== 'player_stash') {
            return Result::error('INVALID_ITEM', 'Ten przedmiot nie znajduje się w Twoim magazynie.');
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
                $item->update([
                    'location' => $targetLocation,
                    'owner_character_id' => $character->id,
                    'user_id' => null,
                ]);

                Log::info('Item withdrawn from player stash', [
                    'character_id' => $character->id,
                    'item_id' => $item->id,
                ]);

                return Result::ok($item);
            });
        } catch (\Exception $e) {
            Log::error('PlayerStash withdraw failed', [
                'character_id' => $character->id,
                'item_id' => $item->id,
                'error' => $e->getMessage(),
            ]);

            return Result::error('STASH_WITHDRAW_FAILED', 'Wystąpił błąd podczas wyciągania z magazynu.');
        }
    }

    public function expandStash(User $user): Result
    {
        $cost = 50;

        if ($user->gems < $cost) {
            return Result::error('NOT_ENOUGH_GEMS', "Brak wystarczającej liczby diamentów! Potrzebujesz {$cost} 💎.");
        }

        try {
            return DB::transaction(function () use ($user, $cost) {
                $user->gems -= $cost;
                $user->stash_slots = ($user->stash_slots ?? 2) + 1;
                $user->save();

                CurrencyLedger::create([
                    'id' => Str::ulid(),
                    'character_id' => $user->characters()->first()?->id ?? Str::ulid(),
                    'currency_type' => 'gems',
                    'amount' => -$cost,
                    'balance_after' => $user->gems,
                    'idempotency_key' => "stash_expand:user:{$user->id}:slot:{$user->stash_slots}",
                    'source_type' => 'stash_expansion',
                    'source_id' => (string) $user->id,
                    'created_at' => now(),
                ]);

                Log::info('Player stash expanded', [
                    'user_id' => $user->id,
                    'new_slots' => $user->stash_slots,
                ]);

                return Result::ok($user);
            });
        } catch (\Exception $e) {
            Log::error('PlayerStash expansion failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return Result::error('STASH_EXPAND_FAILED', 'Wystąpił błąd podczas powiększania magazynu.');
        }
    }
}
