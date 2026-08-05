<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Infrastructure\Persistence\ItemInstance;
use App\Infrastructure\Persistence\Character;

class ResyncItemStatsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'items:resync {--dry-run : Pokazuje podgląd bez zapisywania zmian}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Przelicza i aktualizuje rolled_stats istniejących instancji przedmiotów do nowych szablonów (zachowując jakość % rzadkości).';

    /**
     * Map rzadkości przedmiotu na docelowy percentile jakości (0.0 .. 1.0).
     */
    private function getQualityPercentile(?string $rarity): float
    {
        return match ($rarity) {
            'legendary' => 0.96,
            'epic'      => 0.875,
            'rare'      => 0.70,
            'uncommon'  => 0.475,
            default     => 0.20,
        };
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');

        if ($isDryRun) {
            $this->info('Tryb DRY-RUN (brak zmian w bazie danych)...');
        }

        $instances = ItemInstance::with('template')->get();
        $updatedCount = 0;
        $affectedCharacterIds = [];

        foreach ($instances as $instance) {
            $template = $instance->template;
            if (!$template) {
                continue;
            }

            $baseStats = $template->base_stats ?? [];
            if (empty($baseStats)) {
                continue;
            }

            $percentile = $this->getQualityPercentile($instance->rarity);
            $newRolledStats = [];

            foreach ($baseStats as $stat => $range) {
                if (is_array($range) && count($range) >= 2) {
                    $min = (int) $range[0];
                    $max = (int) $range[1];

                    $rolledVal = ($max > $min)
                        ? (int) round($min + $percentile * ($max - $min))
                        : $min;

                    $newRolledStats[$stat] = $rolledVal;
                }
            }

            if (!empty($newRolledStats)) {
                $oldRolledStats = $instance->rolled_stats ?? [];
                if ($oldRolledStats !== $newRolledStats) {
                    if (!$isDryRun) {
                        $instance->rolled_stats = $newRolledStats;
                        $instance->save();
                    }
                    $updatedCount++;
                    if ($instance->owner_character_id) {
                        $affectedCharacterIds[$instance->owner_character_id] = true;
                    }
                }
            }
        }

        if (!$isDryRun && !empty($affectedCharacterIds)) {
            $characters = Character::whereIn('id', array_keys($affectedCharacterIds))->get();
            foreach ($characters as $char) {
                $char->clearStatsCache();
            }
        }

        $charCount = count($affectedCharacterIds);
        if ($isDryRun) {
            $this->info("DRY-RUN zakończony. Znaleziono {$updatedCount} przedmiotów do aktualizacji u {$charCount} postaci.");
        } else {
            $this->info("Pomyślnie przeliczono statystyki {$updatedCount} przedmiotów u {$charCount} postaci.");
        }

        return Command::SUCCESS;
    }
}
