<?php

namespace App\Application\Achievements;

use App\Infrastructure\Persistence\Achievement;
use App\Infrastructure\Persistence\CharacterAchievement;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\CharacterTitle;
use App\Infrastructure\Persistence\ItemInstance;
use App\Infrastructure\Persistence\ItemLedger;
use App\Application\Shared\Result;
use Illuminate\Support\Str;

class AchievementService
{
    /**
     * Progresuje wszystkie osiągnięcia danego typu dla postaci.
     */
    public function progress(Character $character, string $type, int $amount = 1, array $context = []): void
    {
        // Wybieramy osiągnięcia, które nie mają rodzica ALBO których rodzic został ukończony przez tego gracza
        $achievements = Achievement::where('type', $type)
            ->where(function ($query) use ($character) {
                $query->whereNull('parent_achievement_id')
                    ->orWhereHas('parentAchievement.characterAchievements', function ($subQuery) use ($character) {
                        $subQuery->where('character_id', $character->id)
                                 ->whereNotNull('completed_at');
                    });
            })->get()
            ->filter(function ($achievement) use ($context) {
                if (empty($achievement->conditions)) return true;
                
                foreach ($achievement->conditions as $key => $value) {
                    // Pomijamy puste warunki
                    if (empty($value)) continue;
                    
                    // Jeśli kontekst nie zawiera danego klucza lub wartość się nie zgadza
                    if (!isset($context[$key]) || (string)$context[$key] !== (string)$value) {
                        return false;
                    }
                }
                
                return true;
            });

        foreach ($achievements as $achievement) {
            $charAchievement = CharacterAchievement::firstOrCreate(
                ['character_id' => $character->id, 'achievement_id' => $achievement->id],
                ['progress' => 0, 'rewarded' => false]
            );

            if ($charAchievement->completed_at === null) {
                $charAchievement->progress += $amount;
                
                if ($charAchievement->progress >= $achievement->target_value) {
                    $charAchievement->progress = $achievement->target_value;
                    $charAchievement->completed_at = now();
                    
                    // Inicjalizacja kolejnego stopnia (dziecka) od razu po ukończeniu rodzica
                    $childAchievements = Achievement::where('parent_achievement_id', $achievement->id)->get();
                    foreach ($childAchievements as $childAchievement) {
                        CharacterAchievement::firstOrCreate(
                            ['character_id' => $character->id, 'achievement_id' => $childAchievement->id],
                            [
                                'progress' => min($charAchievement->progress, $childAchievement->target_value),
                                'rewarded' => false
                            ]
                        );
                    }
                }
                
                $charAchievement->save();
            }
        }
    }

    /**
     * Odbiór nagrody za osiągnięcie.
     */
    public function claimReward(Character $character, CharacterAchievement $charAchievement): Result
    {
        if ($charAchievement->character_id !== $character->id) {
            return Result::error('UNAUTHORIZED', 'To nie jest twoje osiągnięcie.');
        }

        if ($charAchievement->completed_at === null) {
            return Result::error('NOT_COMPLETED', 'To osiągnięcie nie zostało jeszcze ukończone.');
        }

        if ($charAchievement->rewarded) {
            return Result::error('ALREADY_REWARDED', 'Nagroda została już odebrana.');
        }

        $achievement = $charAchievement->achievement;

        // Dodawanie punktów osiągnięć
        if ($achievement->reward_points > 0) {
            $character->achievement_points += $achievement->reward_points;
        }

        if ($achievement->reward_gold > 0) {
            $character->gold += $achievement->reward_gold;
        }

        if ($achievement->reward_exp > 0) {
            $character->xp += $achievement->reward_exp;
            // Aplikowanie logiki awansu w tle (wymaga LevelUpService, które trzeba zrzucić gdzieś lub uruchomić)
            app(\App\Application\Characters\LevelUpService::class)->checkAndApply($character);
        }

        // Dodawanie tytułu
        if ($achievement->reward_title_id) {
            CharacterTitle::firstOrCreate([
                'character_id' => $character->id,
                'title_id' => $achievement->reward_title_id
            ], [
                'unlocked_at' => now()
            ]);
        }

        // Dodawanie przedmiotu
        if ($achievement->reward_item_template_id) {
            $itemInstance = ItemInstance::create([
                'id' => Str::ulid(),
                'template_id' => $achievement->reward_item_template_id,
                'owner_character_id' => $character->id,
                'location' => 'inventory',
                'stack_size' => 1,
                'rarity' => 'epic', // Przykładowo
                'roll_stats' => [],
                'upgrade_level' => 0
            ]);

            ItemLedger::create([
                'id' => Str::ulid(),
                'character_id' => $character->id,
                'item_instance_id' => $itemInstance->id,
                'action' => 'achievement_reward',
                'ref_type' => 'achievement',
                'ref_id' => $achievement->id,
                'quantity_change' => 1,
                'idempotency_key' => "achievement_reward:{$achievement->id}:item:{$itemInstance->id}:" . time()
            ]);
        }

        $character->save();
        $charAchievement->rewarded = true;
        $charAchievement->save();

        // Czyścimy cache dla statystyk, w razie gdyby achievement dawał pasywne bonusy
        $character->clearStatsCache();

        return Result::ok(null, 'Odebrano nagrodę za osiągnięcie!');
    }
}
