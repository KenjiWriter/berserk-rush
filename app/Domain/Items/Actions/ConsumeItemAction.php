<?php

namespace App\Domain\Items\Actions;

use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\ItemInstance;
use App\Infrastructure\Persistence\ItemLedger;
use App\Infrastructure\Persistence\ActiveBuff;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class ConsumeItemAction
{
    public function execute(Character $character, string $itemInstanceId): array
    {
        return DB::transaction(function () use ($character, $itemInstanceId) {
            $item = ItemInstance::where('id', $itemInstanceId)
                ->where('owner_character_id', $character->id)
                ->where('location', 'inventory')
                ->lockForUpdate()
                ->first();

            if (!$item) {
                return ['success' => false, 'message' => 'Nie znaleziono przedmiotu w ekwipunku.'];
            }

            if ($item->template->type !== 'consumable') {
                return ['success' => false, 'message' => 'Ten przedmiot nie jest do użycia.'];
            }

            $stats = $item->template->base_stats ?? [];
            if (empty($stats) || !isset($stats['duration_minutes'])) {
                return ['success' => false, 'message' => 'Ten przedmiot nie posiada właściwości do skonsumowania.'];
            }

            $durationMinutes = $stats['duration_minutes'];
            $buffEffects = $stats;
            unset($buffEffects['duration_minutes']);

            if (empty($buffEffects)) {
                return ['success' => false, 'message' => 'Mikstura nie zawiera żadnych efektów.'];
            }

            // Sprawdzenie czy istnieje aktywny buff tego samego typu
            $buffName = $item->template->name;
            
            // Logika nadpisywania:
            // Szukamy buffów o tej samej nazwie u tego samego gracza.
            $existingBuff = ActiveBuff::where('character_id', $character->id)
                ->where('name', $buffName)
                ->where('expires_at', '>', Carbon::now())
                ->first();

            if ($existingBuff) {
                // Jeśli wypijamy miksturę i buff już jest, nadpisujemy czas do pełnego czasu nowej mikstury.
                // Ale zgodnie z prośbą: jeśli istnieje np. buff M i chcemy wypić S, to system nie powinien pozwolić.
                // Trzeba rozpoznać czy nowa mikstura jest "słabsza". 
                // Spróbujmy w uproszczeniu: załóżmy, że sumujemy całkowitą wartość "mocy" mikstury by porównać.
                $existingPower = $this->calculatePower($existingBuff->effects);
                $newPower = $this->calculatePower($buffEffects);

                if ($newPower < $existingPower) {
                    return ['success' => false, 'message' => 'Posiadasz już silniejszy lub równorzędny aktywny efekt tego typu.'];
                }

                $existingBuff->effects = $buffEffects;
                $existingBuff->expires_at = Carbon::now()->addMinutes($durationMinutes);
                $existingBuff->save();
            } else {
                ActiveBuff::create([
                    'character_id' => $character->id,
                    'name' => $buffName,
                    'effects' => $buffEffects,
                    'expires_at' => Carbon::now()->addMinutes($durationMinutes),
                ]);
            }

            // Odejmujemy przedmiot
            if ($item->stack_size > 1) {
                $item->decrement('stack_size');
            } else {
                $item->delete();
            }

            // Log
            ItemLedger::create([
                'id' => (string) Str::ulid(),
                'character_id' => $character->id,
                'item_instance_id' => $itemInstanceId,
                'action' => 'consume',
                'ref_type' => 'manual',
                'quantity_change' => -1,
                'idempotency_key' => 'consume_' . Str::ulid(),
            ]);

            return ['success' => true, 'message' => 'Wypito miksturę. Zastosowano nowe efekty.'];
        });
    }

    private function calculatePower(array $effects): int
    {
        $sum = 0;
        foreach ($effects as $val) {
            $sum += (int) $val;
        }
        return $sum;
    }
}
