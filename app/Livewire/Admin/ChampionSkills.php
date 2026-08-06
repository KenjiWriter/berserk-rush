<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Infrastructure\Persistence\ChampionSkill;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.app')]
class ChampionSkills extends Component
{
    public $skills;

    public $key, $name, $description;
    public $stat_type = 'phys_dmg_pct';
    public $bonus_per_point = 1;
    public $max_points = 10;
    public $icon;
    public $sort_order = 0;
    public $editingId = null;

    public const STAT_TYPES = [
        'phys_dmg_pct' => 'Obrażenia fizyczne (%)',
        'magic_dmg_pct' => 'Obrażenia magiczne (%)',
        'max_hp_pct' => 'Maksymalne HP (%)',
        'mana_regen_pct' => 'Regeneracja many (%)',
        'accuracy_pct' => 'Celność (%)',
        'dodge_pct' => 'Unik (%)',
        'dmg_reduction_pct' => 'Redukcja obrażeń (%)',
        'gold_pct' => 'Złoto z potworów (%)',
        'loot_pct' => 'Szansa na lepszy łup (%)',
        'exp_pct' => 'Doświadczenie (%)',
    ];

    protected $rules = [
        'key' => 'required|string|max:64|alpha_dash',
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'stat_type' => 'required|string',
        'bonus_per_point' => 'required|numeric|min:0',
        'max_points' => 'required|integer|min:1',
        'icon' => 'nullable|string|max:255',
        'sort_order' => 'required|integer|min:0',
    ];

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $this->skills = ChampionSkill::orderBy('sort_order')->get();
    }

    public function save()
    {
        $this->validate([
            'key' => 'required|string|max:64|alpha_dash|unique:champion_skills,key,' . ($this->editingId ?? 'NULL'),
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'stat_type' => 'required|string',
            'bonus_per_point' => 'required|numeric|min:0',
            'max_points' => 'required|integer|min:1',
            'icon' => 'nullable|string|max:255',
            'sort_order' => 'required|integer|min:0',
        ]);

        $data = [
            'key' => $this->key,
            'name' => $this->name,
            'description' => $this->description,
            'stat_type' => $this->stat_type,
            'bonus_per_point' => $this->bonus_per_point,
            'max_points' => $this->max_points,
            'icon' => $this->icon,
            'sort_order' => $this->sort_order,
        ];

        if ($this->editingId) {
            ChampionSkill::findOrFail($this->editingId)->update($data);
        } else {
            ChampionSkill::create($data);
        }

        $this->reset(['key', 'name', 'description', 'stat_type', 'bonus_per_point', 'max_points', 'icon', 'sort_order', 'editingId']);
        $this->stat_type = 'phys_dmg_pct';
        $this->bonus_per_point = 1;
        $this->max_points = 10;
        $this->sort_order = 0;

        $this->loadData();
        session()->flash('message', 'Umiejętność Mistrzostwa zapisana.');
    }

    public function edit($id)
    {
        $skill = ChampionSkill::findOrFail($id);
        $this->editingId = $skill->id;
        $this->key = $skill->key;
        $this->name = $skill->name;
        $this->description = $skill->description;
        $this->stat_type = $skill->stat_type;
        $this->bonus_per_point = $skill->bonus_per_point;
        $this->max_points = $skill->max_points;
        $this->icon = $skill->icon;
        $this->sort_order = $skill->sort_order;
    }

    public function cancelEdit()
    {
        $this->reset(['key', 'name', 'description', 'stat_type', 'bonus_per_point', 'max_points', 'icon', 'sort_order', 'editingId']);
        $this->stat_type = 'phys_dmg_pct';
        $this->bonus_per_point = 1;
        $this->max_points = 10;
        $this->sort_order = 0;
    }

    public function delete($id)
    {
        ChampionSkill::findOrFail($id)->delete();
        $this->loadData();
    }

    public function render()
    {
        return view('livewire.admin.champion-skills');
    }
}
