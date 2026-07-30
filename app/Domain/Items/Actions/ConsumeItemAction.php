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
            $effect = $stats['effect'] ?? null;

            if ($effect) {
                $message = '';
                if ($effect === 'reset_skills') {
                    $spentPoints = \App\Infrastructure\Persistence\CharacterCombatSkill::with('skill')
                        ->where('character_id', $character->id)
                        ->get()
                        ->sum(function ($cs) {
                            return ($cs->skill->unlock_cost ?? 1) + max(0, $cs->level - 1);
                        });

                    \App\Infrastructure\Persistence\CharacterCombatSkill::where('character_id', $character->id)->delete();
                    if ($spentPoints > 0) {
                        $character->skill_points += $spentPoints;
                    }
                    $character->save();
                    $message = "Pomyślnie zresetowano umiejętności bojowe! Odzyskano {$spentPoints} pkt. umiejętności.";
                } elseif ($effect === 'reset_attributes') {
                    $character->attributes = ['str' => 0, 'int' => 0, 'vit' => 0, 'agi' => 0];
                    $character->character_points = 10 + max(0, ($character->level - 1) * 3);
                    $character->clearStatsCache();
                    $character->save();
                    $message = "Pomyślnie zresetowano atrybuty postaci! Odzyskano punkty postaci do ponownego rozdania.";
                } elseif ($effect === 'reset_full') {
                    $spentPoints = \App\Infrastructure\Persistence\CharacterCombatSkill::with('skill')
                        ->where('character_id', $character->id)
                        ->get()
                        ->sum(function ($cs) {
                            return ($cs->skill->unlock_cost ?? 1) + max(0, $cs->level - 1);
                        });

                    \App\Infrastructure\Persistence\CharacterCombatSkill::where('character_id', $character->id)->delete();
                    if ($spentPoints > 0) {
                        $character->skill_points += $spentPoints;
                    }

                    $character->attributes = ['str' => 0, 'int' => 0, 'vit' => 0, 'agi' => 0];
                    $character->character_points = 10 + max(0, ($character->level - 1) * 3);
                    $character->clearStatsCache();
                    $character->save();
                    $message = "Pomyślnie zresetowano atrybuty oraz umiejętności bojowe postaci!";
                } elseif ($effect === 'arena_attempt') {
                    $character->checkAndResetDailyPvpFights();
                    if (($character->daily_pvp_fights_used ?? 0) <= 0) {
                        return ['success' => false, 'message' => 'Posiadasz już pełny limit prób na Arenie Walk (5/5).'];
                    }
                    $character->decrement('daily_pvp_fights_used');
                    $remaining = $character->getRemainingDailyPvpFights();
                    $message = "Przywrócono 1 próbę na Arenie Walk! Pozostało: {$remaining}/5 prób dzisiaj.";
                } else {
                    return ['success' => false, 'message' => 'Nieznany efekt zwoju.'];
                }

                // Log consume
                ItemLedger::create([
                    'id' => (string) Str::ulid(),
                    'character_id' => $character->id,
                    'item_instance_id' => $itemInstanceId,
                    'action' => 'consume',
                    'ref_type' => 'manual',
                    'quantity_change' => -1,
                    'idempotency_key' => 'consume_' . Str::ulid(),
                ]);

                // Update stack or delete
                if ($item->stack_size > 1) {
                    $item->decrement('stack_size');
                } else {
                    $item->delete();
                }

                return ['success' => true, 'message' => $message];
            }

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

            // Odejmujemy przedmiot
            if ($item->stack_size > 1) {
                $item->decrement('stack_size');
            } else {
                $item->delete();
            }

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
