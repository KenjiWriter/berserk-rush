<?php

namespace App\Livewire\City;

use Livewire\Component;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\CombatSkill;
use App\Infrastructure\Persistence\CharacterCombatSkill;
use App\Infrastructure\Persistence\ChampionSkill;
use App\Application\Skills\UnlockSkill;
use App\Application\Skills\UpgradeSkill;
use App\Application\Mastery\ChampionService;
use App\Application\Characters\LevelUpService;

class Warlock extends Component
{
    public Character $character;

    public string $activeTab = 'skills'; // 'skills', 'mastery'

    public string $weaponFilter = 'all';
    public string $typeFilter = 'all';
    public string $categoryFilter = 'all';

    // --- INFO / OPIS MECHANIK ---
    public bool $showInfoModal = false;

    // Gdy zaznaczone, następna próba ulepszenia skilla w etapie Mistrza (M1-M10)
    // zużyje 1x Zwój Egzorcyzmu i zagwarantuje sukces - patrz UpgradeSkill::execute().
    public bool $useExorcismScroll = false;

    public function mount(Character $character)
    {
        $this->character = $character;

        if (auth()->id() !== $character->user_id) {
            abort(403);
        }
    }

    public function setTab(string $tab)
    {
        $this->activeTab = $tab;
    }

    public function backToHub()
    {
        $this->redirect(route('city.hub', $this->character), navigate: true);
    }

    public function toggleInfoModal(): void
    {
        $this->showInfoModal = !$this->showInfoModal;
    }

    public function toggleExorcismScroll(): void
    {
        $this->useExorcismScroll = !$this->useExorcismScroll;
    }

    public function filterByWeapon(string $weaponType): void
    {
        $this->weaponFilter = $weaponType;
    }

    public function filterByType(string $skillType): void
    {
        $this->typeFilter = $skillType;
    }

    public function filterByCategory(string $category): void
    {
        $this->categoryFilter = $category;
    }

    public function unlockSkill(string $skillId, UnlockSkill $unlockAction)
    {
        $skill = CombatSkill::findOrFail($skillId);
        $result = $unlockAction->execute($this->character, $skill);

        if ($result->isOk()) {
            $this->dispatch('notify', type: 'success', message: 'Odblokowano nową umiejętność!');
            $this->dispatch('play-audio', type: 'upgrade-success');
            $this->character->refresh();
            $this->dispatch('stats-updated');
        } else {
            $this->dispatch('notify', type: 'error', message: $result->getErrorMessage());
        }
    }

    public function upgradeSkill(string $charSkillId, UpgradeSkill $upgradeAction)
    {
        $charSkill = CharacterCombatSkill::findOrFail($charSkillId);
        $result = $upgradeAction->execute($this->character, $charSkill, $this->useExorcismScroll);

        if ($result->isOk()) {
            $data = $result->getPayload();
            $message = is_array($data) && !empty($data['message']) ? $data['message'] : 'Umiejętność została rozwinięta!';
            $this->dispatch('notify', type: 'success', message: $message);
            $this->dispatch('play-audio', type: 'upgrade-success');
            $this->useExorcismScroll = false;
            $this->character->refresh();
            $this->dispatch('stats-updated');
        } else {
            $this->dispatch('notify', type: 'error', message: $result->getErrorMessage());

            // Próba nieudana (UPGRADE_ROLL_FAILED) nadal zużywa surowce (PKT/książki/
            // kamienie/złoto) - trzeba odświeżyć stan, inaczej UI pokazuje stare wartości.
            if ($result->getContext()['consumed'] ?? false) {
                $this->character->refresh();
                $this->dispatch('stats-updated');
            }
        }
    }

    public function donateMaterial(string $itemTemplateId, int $quantity, ChampionService $championService)
    {
        $result = $championService->donateMaterial($this->character, $itemTemplateId, $quantity);

        if ($result->isOk()) {
            $this->dispatch('notify', type: 'success', message: 'Przekazano ulepszacze Czarnoksiężnikowi.');
            $this->dispatch('play-audio', type: 'upgrade-success');
            $this->character->refresh();
        } else {
            $this->dispatch('notify', type: 'error', message: $result->getErrorMessage());
        }
    }

    public function levelUpChampion(ChampionService $championService)
    {
        $result = $championService->attemptLevelUp($this->character);

        if ($result->isOk()) {
            $newLevel = $result->getPayload()['champion_level'];
            $this->dispatch('notify', type: 'success', message: "Awans! Osiągnięto poziom Mistrzostwa 99({$newLevel})!");
            $this->dispatch('play-audio', type: 'levelup');
            $this->dispatch('open-champion-levelup-modal', level: $newLevel);
            $this->character->refresh();
            $this->dispatch('stats-updated');
        } else {
            $this->dispatch('notify', type: 'error', message: $result->getErrorMessage());
        }
    }

    public function investChampionPoint(string $championSkillId, ChampionService $championService)
    {
        $result = $championService->investPoint($this->character, $championSkillId);

        if ($result->isOk()) {
            $this->dispatch('notify', type: 'success', message: 'Punkt Mistrzostwa zainwestowany.');
            $this->dispatch('play-audio', type: 'upgrade-success');
            $this->character->refresh();
            $this->dispatch('stats-updated');
        } else {
            $this->dispatch('notify', type: 'error', message: $result->getErrorMessage());
        }
    }

    public function resetChampionSkills(ChampionService $championService)
    {
        $result = $championService->resetSkills($this->character);

        if ($result->isOk()) {
            $this->dispatch('notify', type: 'success', message: 'Zresetowano drzewko Mistrzostwa.');
            $this->dispatch('play-audio', type: 'upgrade-success');
            $this->character->refresh();
            $this->dispatch('stats-updated');
        } else {
            $this->dispatch('notify', type: 'error', message: $result->getErrorMessage());
        }
    }

    public function render()
    {
        $query = CombatSkill::orderBy('required_level', 'asc');

        if ($this->weaponFilter !== 'all') {
            $query->where('required_weapon_type', $this->weaponFilter);
        }

        if ($this->typeFilter !== 'all') {
            $query->where('type', $this->typeFilter);
        }

        if ($this->categoryFilter !== 'all') {
            match ($this->categoryFilter) {
                'poison' => $query->whereIn('effect_type', ['poison', 'dot_poison']),
                'fire' => $query->whereIn('effect_type', ['fire', 'dot_fire']),
                'aoe' => $query->where('is_aoe', true),
                'heal' => $query->where('effect_type', 'heal'),
                'defense' => $query->where('effect_type', 'buff_defense'),
                'dmg' => $query->whereNotIn('effect_type', ['poison', 'dot_poison', 'fire', 'dot_fire', 'heal', 'buff_defense']),
                default => null,
            };
        }

        $allSkills = $query->get();
        $mySkills = CharacterCombatSkill::with('skill')
            ->where('character_id', $this->character->id)
            ->get()
            ->keyBy('combat_skill_id');

        $skillBookSubTypes = [
            'skill_book_sword',
            'skill_book_axe',
            'skill_book_bow',
            'skill_book_wand',
            'skill_book_bell',
            'skill_book_dagger',
            'skill_book_all',
        ];

        $bookTplMap = \App\Infrastructure\Persistence\ItemTemplate::whereIn('sub_type', $skillBookSubTypes)
            ->pluck('id', 'sub_type')
            ->toArray();

        $ownedSkillBooksBySubType = [];
        $skillBooksCount = 0;

        foreach ($skillBookSubTypes as $subType) {
            $tplId = $bookTplMap[$subType] ?? null;
            $count = $tplId ? \App\Infrastructure\Persistence\ItemInstance::where('owner_character_id', $this->character->id)
                ->where('template_id', $tplId)
                ->whereIn('location', ['inventory', 'material_stash'])
                ->sum('stack_size') : 0;
            $ownedSkillBooksBySubType[$subType] = (int) $count;
            $skillBooksCount += (int) $count;
        }

        $stoneTplId = \App\Infrastructure\Persistence\ItemTemplate::where('name', 'Kamień Duchowy')
            ->orWhere('sub_type', 'soul_stone')
            ->value('id');

        $soulStonesCount = $stoneTplId ? \App\Infrastructure\Persistence\ItemInstance::where('owner_character_id', $this->character->id)
            ->where('template_id', $stoneTplId)
            ->whereIn('location', ['inventory', 'material_stash'])
            ->sum('stack_size') : 0;

        $exorcismTplId = \App\Infrastructure\Persistence\ItemTemplate::where('sub_type', 'exorcism_scroll')->value('id');

        $exorcismScrollsCount = $exorcismTplId ? \App\Infrastructure\Persistence\ItemInstance::where('owner_character_id', $this->character->id)
            ->where('template_id', $exorcismTplId)
            ->whereIn('location', ['inventory', 'material_stash'])
            ->sum('stack_size') : 0;

        // Katalog 10 umiejętności Mistrzostwa - niezależny od odblokowania (level 99),
        // żeby panel "Opis mechanik" mógł je opisać graczom poniżej tego poziomu.
        $championSkillsCatalog = ChampionSkill::orderBy('sort_order')->get();

        return view('livewire.city.warlock', array_merge([
            'allSkills' => $allSkills,
            'mySkills' => $mySkills,
            'skillBooksCount' => $skillBooksCount,
            'ownedSkillBooksBySubType' => $ownedSkillBooksBySubType,
            'soulStonesCount' => $soulStonesCount,
            'exorcismScrollsCount' => $exorcismScrollsCount,
            'championSkillsCatalog' => $championSkillsCatalog,
        ], $this->getChampionViewData()))->layout('components.layouts.app');
    }

    private function getChampionViewData(): array
    {
        $isChampionUnlocked = $this->character->level >= LevelUpService::MAX_LEVEL;

        if (!$isChampionUnlocked) {
            return [
                'championUnlocked' => false,
                'championXpTarget' => 0,
                'championMaterials' => [],
                'championSkillsList' => [],
                'myChampionSkills' => collect(),
                'championResetAvailable' => false,
                'championResetDaysLeft' => 0,
                'championResetGoldCost' => ChampionService::RESET_GOLD_COST,
            ];
        }

        $championService = app(ChampionService::class);

        // Losuje pierwszy zestaw ulepszaczy leniwie, przy pierwszym wejściu na
        // zakładkę po osiągnięciu 99 poziomu (patrz docs/modules/mastery.md).
        if ($this->character->champion_level < ChampionService::LEVEL_CAP && empty($this->character->champion_material_progress)) {
            $championService->rollMaterialRequirements($this->character);
        }

        $materialTemplateIds = collect($this->character->champion_material_progress ?? [])->pluck('template_id');
        $ownedByTemplate = $materialTemplateIds->isEmpty() ? collect() : \App\Infrastructure\Persistence\ItemInstance::where('owner_character_id', $this->character->id)
            ->whereIn('template_id', $materialTemplateIds)
            ->whereIn('location', ['inventory', 'material_stash'])
            ->selectRaw('template_id, SUM(stack_size) as total')
            ->groupBy('template_id')
            ->pluck('total', 'template_id');

        $championMaterials = collect($this->character->champion_material_progress ?? [])->map(function ($row) use ($ownedByTemplate) {
            $row['owned'] = (int) ($ownedByTemplate[$row['template_id']] ?? 0);
            return $row;
        })->all();

        $championSkillsList = ChampionSkill::orderBy('sort_order')->get();
        $myChampionSkills = $this->character->championSkills()->get()->keyBy('champion_skill_id');

        $resetAvailable = true;
        $resetDaysLeft = 0;
        if ($this->character->last_champion_reset_at && $this->character->last_champion_reset_at->diffInMonths(now()) < 1) {
            $resetAvailable = false;
            $availableAt = $this->character->last_champion_reset_at->copy()->addMonth();
            $resetDaysLeft = max(1, (int) ceil(now()->diffInHours($availableAt) / 24));
        }

        $reqRunicShards = $championService->requiredRunicShards($this->character->champion_level ?? 0);
        $runicTpl = \App\Infrastructure\Persistence\ItemTemplate::where('name', 'Runiczny Odłamek')->first();
        $ownedRunicShards = $runicTpl ? (int) \App\Infrastructure\Persistence\ItemInstance::where('owner_character_id', $this->character->id)
            ->where('template_id', $runicTpl->id)
            ->whereIn('location', ['inventory', 'material_stash'])
            ->sum('stack_size') : 0;

        return [
            'championUnlocked' => true,
            'championXpTarget' => $championService->xpTarget(),
            'championMaterials' => $championMaterials,
            'reqRunicShards' => $reqRunicShards,
            'ownedRunicShards' => $ownedRunicShards,
            'championSkillsList' => $championSkillsList,
            'myChampionSkills' => $myChampionSkills,
            'championResetAvailable' => $resetAvailable,
            'championResetDaysLeft' => $resetDaysLeft,
            'championResetGoldCost' => ChampionService::RESET_GOLD_COST,
        ];
    }
}
