<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Infrastructure\Persistence\Quest;
use App\Domain\Quests\Enums\QuestType;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.app')]
class Quests extends Component
{
    public $quests;
    
    // Form fields
    public $name, $description, $type, $target_id, $target_amount, $required_level;
    public $max_level, $reward_gold, $reward_exp, $is_active = true;
    public $reward_items = [];
    public $hunting_type = 'monster'; // 'monster' or 'map'
    
    public $editingId = null;

    protected $rules = [
        'name' => 'required|string|max:255',
        'description' => 'required|string',
        'type' => 'required|string',
        'target_id' => 'nullable|string',
        'target_amount' => 'required|integer|min:1',
        'required_level' => 'required|integer|min:1',
        'max_level' => 'nullable|integer|min:1',
        'reward_gold' => 'required|integer|min:0',
        'reward_exp' => 'required|integer|min:0',
        'is_active' => 'boolean',
    ];

    public function mount()
    {
        $this->type = QuestType::HUNTING->value;
        $this->loadQuests();
    }

    public function loadQuests()
    {
        $this->quests = Quest::all();
    }

    public function edit($id)
    {
        $quest = Quest::findOrFail($id);
        $this->editingId = $quest->id;
        $this->name = $quest->name;
        $this->description = $quest->description;
        $this->type = $quest->type->value;
        $this->target_id = $quest->target_id;
        $this->target_amount = $quest->target_amount;
        $this->required_level = $quest->required_level;
        $this->max_level = $quest->max_level;
        $this->reward_gold = $quest->reward_gold;
        $this->reward_exp = $quest->reward_exp;
        $this->is_active = $quest->is_active;
        $this->reward_items = $quest->reward_items ?? [];
        
        if ($this->type === 'hunting') {
            // Check if target is a map
            if (\App\Infrastructure\Persistence\Map::find($this->target_id)) {
                $this->hunting_type = 'map';
            } else {
                $this->hunting_type = 'monster';
            }
        }
    }

    public function resetForm()
    {
        $this->editingId = null;
        $this->name = '';
        $this->description = '';
        $this->type = QuestType::HUNTING->value;
        $this->target_id = '';
        $this->target_amount = 1;
        $this->required_level = 1;
        $this->max_level = null;
        $this->reward_gold = 0;
        $this->reward_exp = 0;
        $this->is_active = true;
        $this->hunting_type = 'monster';
        $this->reward_items = [];
    }

    public function save()
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'target_id' => $this->target_id,
            'target_amount' => $this->target_amount,
            'required_level' => $this->required_level,
            'max_level' => $this->max_level,
            'reward_gold' => $this->reward_gold,
            'reward_exp' => $this->reward_exp,
            'is_active' => $this->is_active,
            'reward_items' => $this->reward_items,
        ];

        if ($this->editingId) {
            Quest::find($this->editingId)->update($data);
        } else {
            Quest::create($data);
        }

        $this->resetForm();
        $this->loadQuests();
        session()->flash('message', 'Quest zapisany pomyślnie.');
    }

    public function delete($id)
    {
        Quest::find($id)->delete();
        $this->loadQuests();
        session()->flash('message', 'Quest usunięty.');
    }

    public function render()
    {
        return view('livewire.admin.quests', [
            'types' => QuestType::cases(),
            'monsters' => \App\Infrastructure\Persistence\Monster::orderBy('level')->get(),
            'maps' => \App\Infrastructure\Persistence\Map::orderBy('level_min')->get(),
            'items' => \App\Infrastructure\Persistence\ItemTemplate::orderBy('level_requirement')->get(),
        ]);
    }
}
