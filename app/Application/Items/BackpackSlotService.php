<?php

namespace App\Application\Items;

use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\ItemInstance;
use Illuminate\Support\Facades\DB;

class BackpackSlotService
{
    /**
     * Maksymalna liczba slotów w plecaku (VIP ma 64, zwykły 32).
     * Używane tylko do walidacji – rzeczywisty limit pobierany z Character.
     */
    public const MAX_BACKPACK_SLOTS = 64;
    public const MAX_MATERIAL_SLOTS = 100;

    /**
     * Przenieś item z plecaka do wskazanego slotu.
     * Jeśli slot docelowy jest zajęty – wykonaj swap (zamień pozycje).
     *
     * @param  Character    $character
     * @param  string       $itemId       ULID itemu do przeniesienia
     * @param  int          $targetSlot   0-based index slotu docelowego
     * @param  string       $slotColumn   'backpack_slot' lub 'material_slot'
     * @param  string       $location     'inventory' lub 'material_stash'
     * @return bool true = OK, false = błąd walidacji
     */
    public function moveItem(
        Character $character,
        string $itemId,
        int $targetSlot,
        string $slotColumn = 'backpack_slot',
        string $location = 'inventory'
    ): bool {
        $maxSlot = $slotColumn === 'material_slot'
            ? self::MAX_MATERIAL_SLOTS - 1
            : self::MAX_BACKPACK_SLOTS - 1;

        if ($targetSlot < 0 || $targetSlot > $maxSlot) {
            return false;
        }

        return DB::transaction(function () use ($character, $itemId, $targetSlot, $slotColumn, $location) {
            // Pobierz źródłowy item – musi należeć do tej postaci i być w odpowiedniej lokacji
            $sourceItem = ItemInstance::where('id', $itemId)
                ->where('owner_character_id', $character->id)
                ->where('location', $location)
                ->lockForUpdate()
                ->first();

            if (!$sourceItem) {
                return false;
            }

            $sourceSlot = $sourceItem->$slotColumn;

            // Jeśli to ten sam slot – nic do roboty
            if ($sourceSlot === $targetSlot) {
                return true;
            }

            // Sprawdź czy slot docelowy jest zajęty
            $targetItem = ItemInstance::where('owner_character_id', $character->id)
                ->where('location', $location)
                ->where($slotColumn, $targetSlot)
                ->where('id', '!=', $itemId)
                ->lockForUpdate()
                ->first();

            if ($targetItem) {
                // SWAP: przesuń target do pozycji source (lub null jeśli source nie miał slotu)
                $targetItem->$slotColumn = $sourceSlot; // może być null – OK
                $targetItem->save();
            }

            // Przesuń source do pozycji docelowej
            $sourceItem->$slotColumn = $targetSlot;
            $sourceItem->save();

            return true;
        });
    }

    /**
     * Przydziel pierwszy wolny slot jeśli item nie ma jeszcze przypisanego.
     * Wywoływane automatycznie gdy item trafia do plecaka (UnequipItem, DropService itp.)
     *
     * @param  ItemInstance $item         – musi mieć już ustawione owner_character_id i location
     * @param  int          $capacity     – maksymalna pojemność plecaka/magazynu tej postaci
     * @param  string       $slotColumn   'backpack_slot' lub 'material_slot'
     * @param  string       $location     'inventory' lub 'material_stash'
     */
    public function assignAutoSlot(
        ItemInstance $item,
        int $capacity,
        string $slotColumn = 'backpack_slot',
        string $location = 'inventory'
    ): void {
        if ($item->$slotColumn !== null) {
            return; // Już ma slot
        }

        $usedSlots = ItemInstance::where('owner_character_id', $item->owner_character_id)
            ->where('location', $location)
            ->where('id', '!=', $item->id)
            ->whereNotNull($slotColumn)
            ->pluck($slotColumn)
            ->flip(); // array [slot => index] dla szybkiego lookup

        for ($i = 0; $i < $capacity; $i++) {
            if (!isset($usedSlots[$i])) {
                $item->$slotColumn = $i;
                $item->save();
                return;
            }
        }

        // Brak wolnych slotów – zostaw null (będzie wyświetlany na końcu)
    }

    /**
     * Wyczyść slot_column na wszystkich itemach postaci w danej lokacji.
     * Używane przez „Sortuj plecak" – po wyczyszczeniu ItemSorter auto-posortuje.
     */
    public function clearAllSlots(
        Character $character,
        string $slotColumn = 'backpack_slot',
        string $location = 'inventory'
    ): void {
        ItemInstance::where('owner_character_id', $character->id)
            ->where('location', $location)
            ->update([$slotColumn => null]);
    }

    /**
     * Zwróć tablicę [slot_index => ItemInstance] dla całego plecaka/magazynu.
     * Itemy bez przypisanego slotu (null) są przydzielane do pierwszych wolnych slotów.
     * Używane do renderowania siatki w widoku.
     *
     * @param  \Illuminate\Support\Collection $items
     * @param  int    $capacity    Łączna pojemność siatki
     * @param  string $slotColumn
     * @return array<int, ItemInstance|null>  indeksowana 0..capacity-1
     */
    public function buildSlotMap(
        \Illuminate\Support\Collection $items,
        int $capacity,
        string $slotColumn = 'backpack_slot'
    ): array {
        $slotMap = array_fill(0, $capacity, null);

        // Najpierw umieść itemy z przypisanym slotem
        $unslotted = collect();
        foreach ($items as $item) {
            $slot = $item->$slotColumn;
            if ($slot !== null && $slot < $capacity && $slotMap[$slot] === null) {
                $slotMap[$slot] = $item;
            } else {
                $unslotted->push($item);
            }
        }

        // Itemy bez slotu (lub z konfliktem) – wrzuć do pierwszych wolnych miejsc
        foreach ($unslotted as $item) {
            for ($i = 0; $i < $capacity; $i++) {
                if ($slotMap[$i] === null) {
                    $slotMap[$i] = $item;
                    break;
                }
            }
        }

        return $slotMap;
    }
}
