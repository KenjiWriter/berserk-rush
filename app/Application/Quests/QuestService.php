<?php

namespace App\Application\Quests;

use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\Quest;
use App\Infrastructure\Persistence\CharacterQuest;
use App\Infrastructure\Persistence\ItemInstance;
use App\Infrastructure\Persistence\ItemLedger;
use App\Domain\Quests\Enums\QuestType;
use App\Domain\Quests\Enums\QuestStatus;
use App\Application\Shared\Result;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class QuestService
{
    public function getAvailableQuests(Character $character)
    {
        // Get active/completed quest IDs to exclude them from available list
        $takenQuestIds = $character->characterQuests()
            ->whereIn('status', [QuestStatus::ACTIVE->value, QuestStatus::COMPLETED->value, QuestStatus::REWARDED->value])
            ->pluck('quest_id')
            ->toArray();

        return Quest::where('is_active', true)
            ->where('required_level', '<=', $character->level)
            ->where(function($query) use ($character) {
                $query->whereNull('max_level')
                      ->orWhere('max_level', '>=', $character->level);
            })
            ->whereNotIn('id', $takenQuestIds)
            ->get();
    }

    public function acceptQuest(Character $character, Quest $quest): Result
    {
        if (!$quest->is_active) {
            return Result::error('QUEST_INACTIVE', 'Ten quest jest nieaktywny.');
        }

        if ($character->level < $quest->required_level) {
            return Result::error('LEVEL_TOO_LOW', 'Twój poziom jest za niski na tego questa.');
        }

        if ($quest->max_level !== null && $character->level > $quest->max_level) {
            return Result::error('LEVEL_TOO_HIGH', 'Twój poziom jest za wysoki na tego questa.');
        }

        $existing = CharacterQuest::where('character_id', $character->id)
            ->where('quest_id', $quest->id)
            ->first();

        if ($existing && in_array($existing->status->value, [QuestStatus::ACTIVE->value, QuestStatus::COMPLETED->value, QuestStatus::REWARDED->value])) {
            return Result::error('ALREADY_TAKEN', 'Ten quest został już przez Ciebie podjęty.');
        }

        CharacterQuest::updateOrCreate(
            ['character_id' => $character->id, 'quest_id' => $quest->id],
            ['status' => QuestStatus::ACTIVE, 'progress' => 0]
        );

        return Result::ok(null, 'Pomyślnie przyjęto misję: ' . $quest->name);
    }

    public function progressQuest(Character $character, string $type, array $targets = [], int $amount = 1): void
    {
        $activeQuests = $character->characterQuests()
            ->with('quest')
            ->where('status', QuestStatus::ACTIVE->value)
            ->get()
            ->filter(function ($cq) use ($type) {
                return $cq->quest->type->value === $type;
            });

        foreach ($activeQuests as $cq) {
            $quest = $cq->quest;

            // Sprawdzanie celu (target_id)
            if ($quest->target_id !== null && !in_array($quest->target_id, $targets)) {
                continue;
            }

            // Aktualizacja progresu (dla 'gathering' progres może być liczony dynamicznie, ale tu robimy sztywny licznik dla hunting i action)
            if (in_array($quest->type, [QuestType::HUNTING, QuestType::ACTION])) {
                $cq->progress += $amount;
                
                if ($cq->progress >= $quest->target_amount) {
                    $cq->progress = $quest->target_amount;
                    $cq->status = QuestStatus::COMPLETED;
                }
                
                $cq->save();
            }
        }
    }

    public function claimReward(Character $character, CharacterQuest $cq): Result
    {
        if ($cq->character_id !== $character->id) {
            return Result::error('UNAUTHORIZED', 'To nie Twój quest.');
        }

        if ($cq->status === QuestStatus::REWARDED) {
            return Result::error('ALREADY_REWARDED', 'Nagroda została już odebrana.');
        }

        $quest = $cq->quest;

        // Dla gathering sprawdzamy zawartość ekwipunku
        if ($quest->type === QuestType::GATHERING) {
            $hasItems = $this->hasRequiredGatheringItems($character, $quest);
            if (!$hasItems) {
                return Result::error('MISSING_ITEMS', 'Nie posiadasz wymaganych przedmiotów, aby zakończyć misję.');
            }
        } else {
            if ($cq->status !== QuestStatus::COMPLETED) {
                return Result::error('NOT_COMPLETED', 'Misja nie została jeszcze ukończona.');
            }
        }

        return DB::transaction(function () use ($character, $cq, $quest) {
            // Ponowne sprawdzenie blokad
            $cq = CharacterQuest::lockForUpdate()->find($cq->id);
            if ($cq->status === QuestStatus::REWARDED) {
                return Result::error('ALREADY_REWARDED', 'Nagroda została już odebrana.');
            }

            // Zabierz przedmioty dla 'gathering'
            if ($quest->type === QuestType::GATHERING) {
                $this->takeGatheringItems($character, $quest);
            }

            // Dodaj nagrody
            if ($quest->reward_gold > 0) {
                $character->gold += $quest->reward_gold;
            }

            if ($quest->reward_exp > 0) {
                // To nie awansuje automatycznie, ale dla uproszczenia (system w berserk-rush korzysta ze swojego mechanizmu po walce zazwyczaj, tu dodajemy xp do postaci)
                // Używamy CharacterDungeonRun lub podobnych do dodawania xp, lub manualnie dodajemy:
                $character->xp += $quest->reward_exp;
                // Ewentualny event awansu należy sprawdzić ręcznie w Livewire lub wyrzucić event jeśli zaszła potrzeba
            }

            // Zapisujemy zmiany
            $character->save();

            // Dodaj przedmioty (reward_items)
            if ($quest->reward_items && is_array($quest->reward_items)) {
                foreach ($quest->reward_items as $rewardItem) {
                    $templateId = $rewardItem['template_id'] ?? null;
                    $amount = $rewardItem['amount'] ?? 1;
                    if ($templateId) {
                        $this->giveItem($character, $templateId, $amount, $quest->id);
                    }
                }
            }

            $cq->status = QuestStatus::REWARDED;
            $cq->save();

            return Result::ok(null, 'Odebrano nagrodę za questa: ' . $quest->name);
        });
    }

    public function cancelExpiredQuests(Character $character): void
    {
        $activeQuests = $character->characterQuests()
            ->with('quest')
            ->where('status', QuestStatus::ACTIVE->value)
            ->get();

        foreach ($activeQuests as $cq) {
            if ($cq->quest->max_level !== null && $character->level > $cq->quest->max_level) {
                $cq->status = QuestStatus::CANCELLED;
                $cq->save();
            }
        }
    }

    private function hasRequiredGatheringItems(Character $character, Quest $quest): bool
    {
        $templateId = $quest->target_id;
        $requiredAmount = $quest->target_amount;

        $totalAmount = ItemInstance::where('owner_character_id', $character->id)
            ->where('template_id', $templateId)
            ->where('location', 'inventory')
            ->sum('stack_size');

        return $totalAmount >= $requiredAmount;
    }

    private function takeGatheringItems(Character $character, Quest $quest): void
    {
        $templateId = $quest->target_id;
        $requiredAmount = $quest->target_amount;
        $remainingToTake = $requiredAmount;

        $items = ItemInstance::where('owner_character_id', $character->id)
            ->where('template_id', $templateId)
            ->where('location', 'inventory')
            ->orderBy('stack_size', 'asc') // zużywamy mniejsze stacki najpierw
            ->get();

        foreach ($items as $item) {
            if ($remainingToTake <= 0) break;

            if ($item->stack_size <= $remainingToTake) {
                $remainingToTake -= $item->stack_size;
                
                ItemLedger::create([
                    'id' => Str::ulid(),
                    'character_id' => $character->id,
                    'item_instance_id' => $item->id,
                    'action' => 'quest_consume',
                    'ref_type' => 'quest',
                    'ref_id' => $quest->id,
                    'quantity_change' => -$item->stack_size,
                    'idempotency_key' => "quest_consume:{$quest->id}:item:{$item->id}:" . time()
                ]);

                $item->delete();
            } else {
                $item->stack_size -= $remainingToTake;
                $item->save();

                ItemLedger::create([
                    'id' => Str::ulid(),
                    'character_id' => $character->id,
                    'item_instance_id' => $item->id,
                    'action' => 'quest_consume',
                    'ref_type' => 'quest',
                    'ref_id' => $quest->id,
                    'quantity_change' => -$remainingToTake,
                    'idempotency_key' => "quest_consume:{$quest->id}:item:{$item->id}:" . time()
                ]);

                $remainingToTake = 0;
            }
        }
    }

    private function giveItem(Character $character, string $templateId, int $amount, $questId): void
    {
        $template = \App\Infrastructure\Persistence\ItemTemplate::find($templateId);
        if (!$template) return;

        if (in_array($template->type, ['material', 'consumable', 'currency'])) {
            $existingItem = ItemInstance::where('owner_character_id', $character->id)
                ->where('template_id', $templateId)
                ->where('location', 'inventory')
                ->first();

            if ($existingItem) {
                $existingItem->stack_size += $amount;
                $existingItem->save();

                ItemLedger::create([
                    'id' => Str::ulid(),
                    'character_id' => $character->id,
                    'item_instance_id' => $existingItem->id,
                    'action' => 'quest_reward',
                    'ref_type' => 'quest',
                    'ref_id' => $questId,
                    'quantity_change' => $amount,
                    'idempotency_key' => "quest_reward:{$questId}:item:{$existingItem->id}:" . time()
                ]);
                return;
            }
        }

        for ($i = 0; $i < $amount; $i++) {
            $itemInstance = ItemInstance::create([
                'id' => Str::ulid(),
                'template_id' => $templateId,
                'owner_character_id' => $character->id,
                'location' => 'inventory',
                'stack_size' => 1,
                'rarity' => 'common',
                'roll_stats' => [],
                'upgrade_level' => 0
            ]);

            ItemLedger::create([
                'id' => Str::ulid(),
                'character_id' => $character->id,
                'item_instance_id' => $itemInstance->id,
                'action' => 'quest_reward',
                'ref_type' => 'quest',
                'ref_id' => $questId,
                'quantity_change' => 1,
                'idempotency_key' => "quest_reward:{$questId}:item:{$itemInstance->id}:" . time()
            ]);
        }
    }
}
