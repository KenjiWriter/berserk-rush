<?php

namespace App\Application\Pets;

use App\Application\Shared\Result;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\ItemInstance;
use App\Infrastructure\Persistence\MarketListing;
use App\Infrastructure\Persistence\Pet;
use Illuminate\Support\Facades\DB;

class PetEquipmentService
{
    private const SLOTS = ['collar', 'charm'];

    private function hasActiveListing(Pet $pet): bool
    {
        return MarketListing::active()->where('pet_id', $pet->id)->exists();
    }

    /**
     * Zakłada przedmiot ekwipunku peta (obroża/charm) w jednym z 2 slotów.
     * Jeśli slot jest już zajęty, poprzedni przedmiot wraca do plecaka.
     */
    public function equipGear(Character $character, int $petId, string $slot, string $itemInstanceId): Result
    {
        if (!in_array($slot, self::SLOTS, true)) {
            return Result::error('INVALID_SLOT', 'Nieprawidłowy slot ekwipunku peta.');
        }

        return DB::transaction(function () use ($character, $petId, $slot, $itemInstanceId) {
            $pet = Pet::where('id', $petId)->where('character_id', $character->id)->first();
            if (!$pet) {
                return Result::error('NO_PET', 'Nie posiadasz tego peta.');
            }

            if ($this->hasActiveListing($pet)) {
                return Result::error('PET_LISTED', 'Nie można zmieniać ekwipunku chowańca wystawionego na Rynku.');
            }

            $item = ItemInstance::with('template')
                ->where('id', $itemInstanceId)
                ->where('owner_character_id', $character->id)
                ->where('location', 'inventory')
                ->first();

            if (!$item) {
                return Result::error('NO_ITEM', 'Nie posiadasz tego przedmiotu w plecaku.');
            }

            $expectedType = config("pets.gear_types.{$slot}");
            if (!$item->template || $item->template->type !== $expectedType) {
                return Result::error('WRONG_ITEM_TYPE', 'Ten przedmiot nie pasuje do wybranego slotu.');
            }

            $column = "{$slot}_item_instance_id";
            $currentGear = $pet->{$column} ? ItemInstance::find($pet->{$column}) : null;
            if ($currentGear) {
                $currentGear->update(['location' => 'inventory', 'bound_to_character' => false]);
            }

            $item->update(['location' => 'pet_equipped', 'bound_to_character' => true]);
            $pet->update([$column => $item->id]);

            if ($pet->is_equipped) {
                $character->clearStatsCache();
            }

            return Result::ok(['pet' => $pet->fresh(), 'unequipped' => $currentGear]);
        });
    }

    public function unequipGear(Character $character, int $petId, string $slot): Result
    {
        if (!in_array($slot, self::SLOTS, true)) {
            return Result::error('INVALID_SLOT', 'Nieprawidłowy slot ekwipunku peta.');
        }

        return DB::transaction(function () use ($character, $petId, $slot) {
            $pet = Pet::where('id', $petId)->where('character_id', $character->id)->first();
            if (!$pet) {
                return Result::error('NO_PET', 'Nie posiadasz tego peta.');
            }

            $column = "{$slot}_item_instance_id";
            if (!$pet->{$column}) {
                return Result::error('SLOT_EMPTY', 'Ten slot jest już pusty.');
            }

            if ($character->isBackpackFull()) {
                return Result::error('INVENTORY_FULL', 'Twój plecak jest pełny. Zwolnij miejsce przed zdjęciem ekwipunku peta.');
            }

            $item = ItemInstance::find($pet->{$column});
            if ($item) {
                $item->update(['location' => 'inventory', 'bound_to_character' => false]);
            }

            $pet->update([$column => null]);

            if ($pet->is_equipped) {
                $character->clearStatsCache();
            }

            return Result::ok(['pet' => $pet->fresh()]);
        });
    }

    /**
     * Ustawia (lub zdejmuje) peta jako aktywnego towarzysza - tylko 1 może
     * być aktywny naraz, dokładnie jak dawne IncubatorService::toggleEquipPet.
     */
    public function toggleActiveCompanion(Character $character, int $petId): Result
    {
        return DB::transaction(function () use ($character, $petId) {
            $pet = Pet::where('id', $petId)->where('character_id', $character->id)->first();

            if (!$pet) {
                return Result::error('NO_PET', 'Nie posiadasz tego peta.');
            }

            if ($pet->is_equipped) {
                $pet->update(['is_equipped' => false]);
                $character->clearStatsCache();
                return Result::ok(['action' => 'unequipped', 'pet' => $pet]);
            }

            if ($this->hasActiveListing($pet)) {
                return Result::error('PET_LISTED', 'Nie można przywołać chowańca wystawionego na Rynku.');
            }

            Pet::where('character_id', $character->id)
                ->where('is_equipped', true)
                ->update(['is_equipped' => false]);

            $pet->update(['is_equipped' => true]);
            $character->clearStatsCache();

            return Result::ok(['action' => 'equipped', 'pet' => $pet]);
        });
    }
}
