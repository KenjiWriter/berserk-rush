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

    public function unlockSkill(string $skillId, UnlockSkill $unlockAction)
    {
        $skill = CombatSkill::findOrFail($skillId);
        $result = $unlockAction->execute($this->character, $skill);

        if ($result->isOk()) {
            $this->dispatch('notify', type: 'success', message: 'Odblokowano nową umiejętność!');
            $this->character->refresh();
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
            $this->character->refresh();
        } else {
            $this->dispatch('notify', type: 'error', message: $result->getErrorMessage());
        }
    }

    public function render()
    {
        $allSkills = CombatSkill::orderBy('required_level', 'asc')->get();
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
