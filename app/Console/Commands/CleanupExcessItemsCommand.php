<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\ItemInstance;
use App\Infrastructure\Persistence\ItemTemplate;
use Illuminate\Support\Facades\DB;

class CleanupExcessItemsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'items:cleanup 
                            {--dry-run : Pokazuje raport bez modyfikacji bazy danych}
                            {--user= : Filtruj według ID/emaila użytkownika}
                            {--character= : Filtruj według ID/nazwy postaci}
                            {--force-max= : Wymuś maksymalną dopuszczalną liczbę przedmiotów (np. 32 lub 64)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Weryfikuje i usuwa nadmiarowe przedmioty z plecaków postaci oraz magazynów graczy, złącza stosy (stacki) i czyści osierocone rekordy.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $filterUser = $this->option('user');
        $filterCharacter = $this->option('character');
        $forceMax = $this->option('force-max') ? (int) $this->option('force-max') : null;

        if ($dryRun) {
            $this->warn('--- TRYB DRY-RUN: Żadne zmiany nie zostaną zapisane w bazie danych ---');
        }

        $this->info('1. Weryfikacja osieroconych przedmiotów (z nieistniejącym szablonem lub właścicielem)...');
        $this->cleanupOrphanedItems($dryRun);

        $this->newLine();
        $this->info('2. Weryfikacja plecaków postaci (location = inventory)...');
        $this->cleanupCharacterInventories($dryRun, $filterCharacter, $filterUser, $forceMax);

        $this->newLine();
        $this->info('3. Weryfikacja magazynów graczy (location = player_stash)...');
        $this->cleanupPlayerStashes($dryRun, $filterUser, $forceMax);

        $this->newLine();
        $this->info('--- ZAKOŃCZONO AUDYT PRZEDMIOTÓW ---');

        return Command::SUCCESS;
    }

    /**
     * Czyszczenie uszkodzonych/osieroconych przedmiotów.
     */
    protected function cleanupOrphanedItems(bool $dryRun): void
    {
        $validTemplateIds = ItemTemplate::pluck('id')->toArray();
        
        $orphanedQuery = ItemInstance::whereNotIn('template_id', $validTemplateIds);
        $countOrphaned = $orphanedQuery->count();

        if ($countOrphaned > 0) {
            $this->error("Znaleziono {$countOrphaned} przedmiotów z nieistniejącym `template_id`!");
            if (!$dryRun) {
                $orphanedQuery->delete();
                $this->info("Usunięto {$countOrphaned} uszkodzonych przedmiotów.");
            }
        } else {
            $this->line(" - Brak uszkodzonych przedmiotów bez szablonu.");
        }
    }

    /**
     * Czyszczenie i złączanie przedmiotów w plecakach postaci.
     */
    protected function cleanupCharacterInventories(bool $dryRun, ?string $filterCharacter, ?string $filterUser, ?int $forceMax): void
    {
        $query = Character::query();

        if ($filterCharacter) {
            $query->where(function($q) use ($filterCharacter) {
                $q->where('id', $filterCharacter)->orWhere('name', $filterCharacter);
            });
        }

        if ($filterUser) {
            $user = User::where('id', $filterUser)->orWhere('email', $filterUser)->first();
            if ($user) {
                $query->where('user_id', $user->id);
            }
        }

        $characters = $query->get();

        $rows = [];
        $totalStackedDeleted = 0;
        $totalOverflowDeleted = 0;

        foreach ($characters as $character) {
            $capacity = $forceMax ?? $character->getBackpackCapacity();
            $items = ItemInstance::where('owner_character_id', $character->id)
                ->where('location', 'inventory')
                ->with('template')
                ->get();

            $initialCount = $items->count();

            if ($initialCount === 0) {
                continue;
            }

            // 1. Złączanie stosów (stackowanie)
            $stackedDeletedCount = 0;
            $groups = $items->groupBy('template_id');

            foreach ($groups as $templateId => $groupItems) {
                $template = $groupItems->first()->template;
                if ($template && in_array($template->type, ['material', 'consumable', 'currency', 'egg']) && $groupItems->count() > 1) {
                    $mainItem = $groupItems->first();
                    $totalStack = $groupItems->sum('stack_size');

                    if (!$dryRun) {
                        $mainItem->stack_size = $totalStack;
                        $mainItem->save();

                        foreach ($groupItems->skip(1) as $excessItem) {
                            $excessItem->delete();
                            $stackedDeletedCount++;
                        }
                    } else {
                        $stackedDeletedCount += ($groupItems->count() - 1);
                    }
                }
            }

            // Pobierz aktualną listę po stackowaniu
            $remainingItems = ItemInstance::where('owner_character_id', $character->id)
                ->where('location', 'inventory')
                ->orderByDesc('upgrade_level')
                ->orderByDesc('created_at')
                ->get();

            $afterStackCount = $dryRun ? max(0, $initialCount - $stackedDeletedCount) : $remainingItems->count();
            $overflowDeletedCount = 0;

            // 2. Przycinanie nadmiaru ponad pojemność plecaka
            if ($afterStackCount > $capacity) {
                $excessCount = $afterStackCount - $capacity;

                if (!$dryRun) {
                    // Zachowaj pierwsze $capacity przedmiotów (posortowane wg wyższego upgrade/daty), resztę usuń
                    $itemsToDelete = $remainingItems->skip($capacity);
                    foreach ($itemsToDelete as $toDelete) {
                        $toDelete->delete();
                        $overflowDeletedCount++;
                    }
                } else {
                    $overflowDeletedCount = $excessCount;
                }
            }

            if ($initialCount > $capacity || $stackedDeletedCount > 0) {
                $rows[] = [
                    'Postać' => "{$character->name} ({$character->id})",
                    'Pojemność' => $capacity,
                    'Przedmiotów przed' => $initialCount,
                    'Złączonych (Stack)' => $stackedDeletedCount,
                    'Usunięty nadmiar' => $overflowDeletedCount,
                    'Pozycji po' => min($capacity, max(0, $initialCount - $stackedDeletedCount - $overflowDeletedCount)),
                ];
            }

            $totalStackedDeleted += $stackedDeletedCount;
            $totalOverflowDeleted += $overflowDeletedCount;
        }

        if (count($rows) > 0) {
            $this->table(
                ['Postać', 'Pojemność', 'Przedmiotów przed', 'Złączonych (Stack)', 'Usunięty nadmiar', 'Pozycji po'],
                $rows
            );
            $this->info("Łącznie złączono/usunięto {$totalStackedDeleted} duplicate-stacków i usunięto {$totalOverflowDeleted} nadmiarowych przedmiotów ponad limit plecaków.");
        } else {
            $this->line(" - Wszystkie sprawdzane plecaki postaci mieszczą się w limitach pojemności.");
        }
    }

    /**
     * Czyszczenie i złączanie przedmiotów w magazynach graczy.
     */
    protected function cleanupPlayerStashes(bool $dryRun, ?string $filterUser, ?int $forceMax): void
    {
        $query = User::query();

        if ($filterUser) {
            $query->where(function($q) use ($filterUser) {
                $q->where('id', $filterUser)->orWhere('email', $filterUser);
            });
        }

        $users = $query->get();

        $rows = [];
        $totalStackedDeleted = 0;
        $totalOverflowDeleted = 0;

        foreach ($users as $user) {
            $capacity = $forceMax ?? $user->getStashCapacity();
            $items = ItemInstance::where('user_id', $user->id)
                ->where('location', 'player_stash')
                ->with('template')
                ->get();

            $initialCount = $items->count();

            if ($initialCount === 0) {
                continue;
            }

            // 1. Stackowanie
            $stackedDeletedCount = 0;
            $groups = $items->groupBy('template_id');

            foreach ($groups as $templateId => $groupItems) {
                $template = $groupItems->first()->template;
                if ($template && in_array($template->type, ['material', 'consumable', 'currency', 'egg']) && $groupItems->count() > 1) {
                    $mainItem = $groupItems->first();
                    $totalStack = $groupItems->sum('stack_size');

                    if (!$dryRun) {
                        $mainItem->stack_size = $totalStack;
                        $mainItem->save();

                        foreach ($groupItems->skip(1) as $excessItem) {
                            $excessItem->delete();
                            $stackedDeletedCount++;
                        }
                    } else {
                        $stackedDeletedCount += ($groupItems->count() - 1);
                    }
                }
            }

            // Pobierz aktualną listę po stackowaniu
            $remainingItems = ItemInstance::where('user_id', $user->id)
                ->where('location', 'player_stash')
                ->orderByDesc('upgrade_level')
                ->orderByDesc('created_at')
                ->get();

            $afterStackCount = $dryRun ? max(0, $initialCount - $stackedDeletedCount) : $remainingItems->count();
            $overflowDeletedCount = 0;

            // 2. Przycinanie nadmiaru
            if ($afterStackCount > $capacity) {
                $excessCount = $afterStackCount - $capacity;

                if (!$dryRun) {
                    $itemsToDelete = $remainingItems->skip($capacity);
                    foreach ($itemsToDelete as $toDelete) {
                        $toDelete->delete();
                        $overflowDeletedCount++;
                    }
                } else {
                    $overflowDeletedCount = $excessCount;
                }
            }

            if ($initialCount > $capacity || $stackedDeletedCount > 0) {
                $rows[] = [
                    'Użytkownik' => "{$user->name} ({$user->email})",
                    'Pojemność Magazynu' => $capacity,
                    'Przedmiotów przed' => $initialCount,
                    'Złączonych (Stack)' => $stackedDeletedCount,
                    'Usunięty nadmiar' => $overflowDeletedCount,
                    'Pozycji po' => min($capacity, max(0, $initialCount - $stackedDeletedCount - $overflowDeletedCount)),
                ];
            }

            $totalStackedDeleted += $stackedDeletedCount;
            $totalOverflowDeleted += $overflowDeletedCount;
        }

        if (count($rows) > 0) {
            $this->table(
                ['Użytkownik', 'Pojemność Magazynu', 'Przedmiotów przed', 'Złączonych (Stack)', 'Usunięty nadmiar', 'Pozycji po'],
                $rows
            );
            $this->info("Łącznie złączono/usunięto {$totalStackedDeleted} duplicate-stacków w magazynach oraz usunięto {$totalOverflowDeleted} nadmiarowych przedmiotów.");
        } else {
            $this->line(" - Wszystkie magazyny użytkowników mieszczą się w limitach pojemności.");
        }
    }
}
