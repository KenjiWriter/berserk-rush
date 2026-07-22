<?php

namespace App\Livewire\Profile;

use Livewire\Component;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\CharacterCombatSkill;

class SkillsTab extends Component
{
    public Character $character;

    public function mount(Character $character)
    {
        $this->character = $character;
    }

    public function equipSkill(string $characterSkillId)
    {
        $characterSkill = CharacterCombatSkill::where('character_id', $this->character->id)
            ->where('id', $characterSkillId)
            ->first();

        if (!$characterSkill) {
            return;
        }

        // Limit do 3 wyposazonych skilli
        $equippedCount = CharacterCombatSkill::where('character_id', $this->character->id)
            ->where('is_equipped', true)
            ->count();

        if (!$characterSkill->is_equipped && $equippedCount >= 3) {
            $this->dispatch('notify', message: 'Możesz wyposażyć maksymalnie 3 umiejętności.', type: 'error');
            return;
        }

        $characterSkill->is_equipped = !$characterSkill->is_equipped;
        $characterSkill->save();
        
        $this->dispatch('notify', message: 'Zaktualizowano wyposażone umiejętności.', type: 'success');
        $this->dispatch('skill-equipped');
    }

    public function render()
    {
        $skills = CharacterCombatSkill::where('character_id', $this->character->id)
            ->with('skill')
            ->get();

        return view('livewire.profile.skills-tab', [
            'skills' => $skills
        ]);
    }
}
