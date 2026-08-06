<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Application\Skills\UpgradeSkill;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\CharacterCombatSkill;
use App\Infrastructure\Persistence\ItemInstance;
use App\Infrastructure\Persistence\ItemLedger;
use App\Infrastructure\Persistence\ItemTemplate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ResetAllSkillsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'skills:reset-all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Resetuje wszystkie odblokowane umiejętności u wszystkich postaci, przyznaje należne Punkty Umiejętności równe (poziom - 1) * 3, oraz zwraca do ekwipunku Księgi Umiejętności i Kamienie Duchowe zainwestowane w poziomy M/G/P.';

    /**
     * Rebalans 2026-08-06 przesunął progi M1/G1/P (patrz CharacterCombatSkill::getTier())
     * z 18/28/38 na 7/17/27. Ten reset musi zwrócić graczom surowce policzone wg STAREGO
     * układu (18/28/38) z płaskim kosztem 1 księga/1 kamień na poziom - to realnie
     * zostało wydane, zanim ten reset i nowa (skalująca się) ekonomia weszły w życie.
     */
    private const OLD_MASTER_START = 17;
    private const OLD_GRAND_MASTER_START = 27;
    private const OLD_MAX_LEVEL = 38;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Rozpoczynanie resetowania umiejętności dla wszystkich postaci...');

        DB::transaction(function () {
            $skills = CharacterCombatSkill::with(['character', 'skill'])->get();
            $refundedBooks = 0;
            $refundedStones = 0;

            foreach ($skills as $charSkill) {
                if (!$charSkill->character || !$charSkill->skill) {
                    continue;
                }

                $level = $charSkill->level;
                $booksOwed = max(0, min($level, self::OLD_GRAND_MASTER_START) - self::OLD_MASTER_START);
                $stonesOwed = max(0, min($level, self::OLD_MAX_LEVEL) - self::OLD_GRAND_MASTER_START);

                if ($booksOwed > 0) {
                    $reqWeapon = $charSkill->skill->required_weapon_type ?? 'all';
                    $this->refundMaterial(
                        $charSkill->character,
                        UpgradeSkill::getRequiredBookName($reqWeapon),
                        UpgradeSkill::getRequiredBookSubType($reqWeapon),
                        $booksOwed
                    );
                    $refundedBooks += $booksOwed;
                }

                if ($stonesOwed > 0) {
                    $this->refundMaterial($charSkill->character, 'Kamień Duchowy', 'soul_stone', $stonesOwed);
                    $refundedStones += $stonesOwed;
                }
            }

            // Delete all character combat skills
            CharacterCombatSkill::query()->delete();

            // Set skill_points = max(0, (level - 1) * 3) for all characters
            $characters = Character::all();
            $count = 0;

            foreach ($characters as $character) {
                $expectedPoints = max(0, ($character->level - 1) * 3);
                $character->update([
                    'skill_points' => $expectedPoints,
                ]);
                $character->consolidateStackableItems();
                $count++;
            }

            $this->info("Pomyślnie zresetowano umiejętności i przeliczono Punkty Umiejętności dla {$count} postaci.");
            $this->info("Zwrócono łącznie {$refundedBooks}x Księgi Umiejętności i {$refundedStones}x Kamień Duchowy.");
        });

        return Command::SUCCESS;
    }

    private function refundMaterial(Character $character, string $name, string $subType, int $quantity): void
    {
        $template = ItemTemplate::where('sub_type', $subType)->orWhere('name', $name)->first();
        if (!$template) {
            $this->warn("Nie odnaleziono szablonu dla: {$name} - pominięto zwrot dla postaci {$character->id}.");
            return;
        }

        $existingItem = ItemInstance::where('owner_character_id', $character->id)
            ->where('template_id', $template->id)
            ->whereIn('location', ['material_stash', 'inventory'])
            ->first();

        if ($existingItem) {
            $existingItem->stack_size += $quantity;
            $existingItem->save();

            ItemLedger::create([
                'id' => Str::ulid(),
                'character_id' => $character->id,
                'item_instance_id' => $existingItem->id,
                'action' => 'skill_reset_refund',
                'ref_type' => 'skill_reset',
                'ref_id' => null,
                'quantity_change' => $quantity,
                'idempotency_key' => "skill_reset_refund:{$character->id}:{$template->id}:" . now()->timestamp . ':' . Str::random(8),
            ]);

            return;
        }

        $newItem = ItemInstance::create([
            'id' => Str::ulid(),
            'template_id' => $template->id,
            'owner_character_id' => $character->id,
            'location' => 'material_stash',
            'stack_size' => $quantity,
            'rarity' => 'common',
            'roll_stats' => [],
            'upgrade_level' => 0,
        ]);

        ItemLedger::create([
            'id' => Str::ulid(),
            'character_id' => $character->id,
            'item_instance_id' => $newItem->id,
            'action' => 'skill_reset_refund',
            'ref_type' => 'skill_reset',
            'ref_id' => null,
            'quantity_change' => $quantity,
            'idempotency_key' => "skill_reset_refund:{$character->id}:{$template->id}:" . now()->timestamp . ':' . Str::random(8),
        ]);
    }
}
