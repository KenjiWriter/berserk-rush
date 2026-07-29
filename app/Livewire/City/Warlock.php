<?php

namespace App\Livewire\City;

use Livewire\Component;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\CombatSkill;
use App\Infrastructure\Persistence\CharacterCombatSkill;
use App\Application\Skills\UnlockSkill;
use App\Application\Skills\UpgradeSkill;

class Warlock extends Component
{
    public Character $character;

    public string $weaponFilter = 'all';
    public string $typeFilter = 'all';
    public string $categoryFilter = 'all';

    public function mount(Character $character)
    {
        $this->character = $character;

        if (auth()->id() !== $character->user_id) {
            abort(403);
        }
    }

    public function backToHub()
    {
        $this->redirect(route('city.hub', $this->character), navigate: true);
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
        $result = $upgradeAction->execute($this->character, $charSkill);

        if ($result->isOk()) {
            $this->dispatch('notify', type: 'success', message: 'Umiejętność została rozwinięta!');
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

        return view('livewire.city.warlock', [
            'allSkills' => $allSkills,
            'mySkills' => $mySkills,
        ])->layout('components.layouts.app');
    }
}
