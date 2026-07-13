<?php

namespace App\Livewire\Admin;

use App\Infrastructure\Persistence\Achievement;
use App\Infrastructure\Persistence\ItemTemplate;
use App\Infrastructure\Persistence\Title;
use App\Infrastructure\Persistence\Map;
use App\Infrastructure\Persistence\Monster;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Str;

#[Layout('components.layouts.app')]
class Achievements extends Component
{
    public $achievements;
    public $itemTemplates;
    public $titles;
    public $maps;
    public $monsters;
    
    // Form fields
    public $achievementId;
    public $name;
    public $description;
    public $type;
    public $target_value;
    public $reward_points = 0;
    public $reward_item_template_id;
    public $reward_title_id;
    public $parent_achievement_id;
    public $reward_gold = 0;
    public $reward_exp = 0;

    // UI state
    public $isEditing = false;
    public $showModal = false;

    // Dynamic fields
    public $stats_bonus = [];
    public $conditions = [
        'map_id' => '',
        'monster_id' => '',
        'monster_type' => '',
        'monster_rank' => ''
    ];

    public $achievementTypes = [
        'monsters_killed' => 'Zabij potwory (Ogólnie)',
        'items_discovered' => 'Odkryj przedmioty',
    ];

    protected $rules = [
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'type' => 'required|string|max:255',
        'target_value' => 'required|integer|min:1',
        'reward_points' => 'required|integer|min:0',
        'reward_item_template_id' => 'nullable|string',
        'reward_title_id' => 'nullable|string',
        'parent_achievement_id' => 'nullable|string',
        'reward_gold' => 'required|integer|min:0',
        'reward_exp' => 'required|integer|min:0',
        'stats_bonus' => 'nullable|array',
        'stats_bonus.*.key' => 'required|string',
        'stats_bonus.*.value' => 'required|numeric',
        'conditions.map_id' => 'nullable|string',
        'conditions.monster_id' => 'nullable|string',
        'conditions.monster_type' => 'nullable|string',
        'conditions.monster_rank' => 'nullable|string',
    ];

    public function mount()
    {
        $this->itemTemplates = ItemTemplate::all();
        $this->titles = Title::all();
        $this->maps = Map::all();
        $this->monsters = Monster::all();
        $this->loadAchievements();
        $this->resetForm();
    }

    public function loadAchievements()
    {
        $this->achievements = Achievement::with(['title', 'itemTemplate', 'parentAchievement'])->get();
    }

    public function addStat()
    {
        $this->stats_bonus[] = ['key' => '', 'value' => 0];
    }

    public function removeStat($index)
    {
        unset($this->stats_bonus[$index]);
        $this->stats_bonus = array_values($this->stats_bonus);
    }

    public function create()
    {
        $this->resetForm();
        $this->isEditing = false;
        $this->showModal = true;
    }

    public function edit($id)
    {
        $this->resetForm();
        $achievement = Achievement::findOrFail($id);
        
        $this->achievementId = $achievement->id;
        $this->name = $achievement->name;
        $this->description = $achievement->description;
        $this->type = $achievement->type;
        $this->target_value = $achievement->target_value;
        $this->reward_points = $achievement->reward_points;
        $this->reward_item_template_id = $achievement->reward_item_template_id;
        $this->reward_title_id = $achievement->reward_title_id;
        $this->parent_achievement_id = $achievement->parent_achievement_id;
        $this->reward_gold = $achievement->reward_gold;
        $this->reward_exp = $achievement->reward_exp;
        
        $this->stats_bonus = [];
        if ($achievement->stats_bonus) {
            foreach ($achievement->stats_bonus as $k => $v) {
                $this->stats_bonus[] = ['key' => $k, 'value' => $v];
            }
        }

        if ($achievement->conditions) {
            $this->conditions = array_merge([
                'map_id' => '',
                'monster_id' => '',
                'monster_type' => '',
                'monster_rank' => ''
            ], $achievement->conditions);
        }
        
        $this->isEditing = true;
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        $formattedStats = [];
        foreach ($this->stats_bonus as $stat) {
            if (!empty($stat['key'])) {
                $formattedStats[$stat['key']] = (int) $stat['value'];
            }
        }

        $formattedConditions = [];
        foreach ($this->conditions as $key => $value) {
            if (!empty($value)) {
                $formattedConditions[$key] = $value;
            }
        }

        $data = [
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'target_value' => $this->target_value,
            'reward_points' => $this->reward_points,
            'reward_item_template_id' => $this->reward_item_template_id ?: null,
            'reward_title_id' => $this->reward_title_id ?: null,
            'parent_achievement_id' => $this->parent_achievement_id ?: null,
            'stats_bonus' => empty($formattedStats) ? null : $formattedStats,
            'conditions' => empty($formattedConditions) ? null : $formattedConditions,
            'reward_gold' => $this->reward_gold,
            'reward_exp' => $this->reward_exp,
        ];

        if ($this->isEditing) {
            $achievement = Achievement::findOrFail($this->achievementId);
            $achievement->update($data);
            session()->flash('message', 'Osiągnięcie zaktualizowane.');
        } else {
            $data['id'] = Str::ulid();
            Achievement::create($data);
            session()->flash('message', 'Osiągnięcie utworzone.');
        }

        $this->showModal = false;
        $this->loadAchievements();
        $this->resetForm();
    }

    public function delete($id)
    {
        Achievement::findOrFail($id)->delete();
        session()->flash('message', 'Osiągnięcie usunięte.');
        $this->loadAchievements();
    }

    private function resetForm()
    {
        $this->achievementId = null;
        $this->name = '';
        $this->description = '';
        $this->type = 'monsters_killed';
        $this->target_value = 1;
        $this->reward_points = 0;
        $this->reward_item_template_id = null;
        $this->reward_title_id = null;
        $this->parent_achievement_id = null;
        $this->reward_gold = 0;
        $this->reward_exp = 0;
        $this->stats_bonus = [];
        $this->conditions = [
            'map_id' => '',
            'monster_id' => '',
            'monster_type' => '',
            'monster_rank' => ''
        ];
    }

    public function render()
    {
        return view('livewire.admin.achievements');
    }
}
