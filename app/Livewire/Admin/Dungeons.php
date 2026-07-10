<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Infrastructure\Persistence\Dungeon;
use App\Infrastructure\Persistence\DungeonStage;
use App\Infrastructure\Persistence\Monster;
use App\Infrastructure\Persistence\ItemTemplate;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.app')]
class Dungeons extends Component
{
    public $dungeons, $monsters, $itemTemplates;
    public $name, $min_level = 1, $entry_item_template_id;
    public $editingId = null;
    public $stages = [];

    protected $rules = [
        'name' => 'required|string|max:255',
        'min_level' => 'required|integer|min:1',
        'entry_item_template_id' => 'nullable|exists:item_templates,id',
        'stages' => 'array',
        'stages.*.monster_id' => 'required|exists:monsters,id',
    ];

    public function mount()
    {
        $this->monsters = Monster::orderBy('level')->get();
        $this->itemTemplates = ItemTemplate::orderBy('name')->get();
        $this->loadData();
    }

    public function loadData()
    {
        $this->dungeons = Dungeon::with(['stages.monster', 'entryItemTemplate'])->orderBy('min_level')->get();
    }

    public function save()
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'min_level' => $this->min_level,
            'entry_item_template_id' => $this->entry_item_template_id ?: null,
        ];

        if ($this->editingId) {
            $dungeon = Dungeon::findOrFail($this->editingId);
            $dungeon->update($data);
            $dungeon->stages()->delete();
        } else {
            $dungeon = Dungeon::create($data);
        }

        foreach ($this->stages as $index => $stage) {
            DungeonStage::create([
                'dungeon_id' => $dungeon->id,
                'monster_id' => $stage['monster_id'],
                'stage_order' => $index + 1,
            ]);
        }

        $this->reset(['name', 'min_level', 'entry_item_template_id', 'editingId', 'stages']);
        $this->min_level = 1;
        $this->loadData();
        session()->flash('message', 'Dungeon zapisany.');
    }

    public function edit($id)
    {
        $dungeon = Dungeon::with('stages')->findOrFail($id);
        $this->editingId = $dungeon->id;
        $this->name = $dungeon->name;
        $this->min_level = $dungeon->min_level;
        $this->entry_item_template_id = $dungeon->entry_item_template_id;
        $this->stages = $dungeon->stages->map(function ($stage) {
            return ['monster_id' => $stage->monster_id];
        })->toArray();
    }

    public function delete($id)
    {
        $dungeon = Dungeon::findOrFail($id);
        $dungeon->stages()->delete();
        $dungeon->delete();
        $this->loadData();
    }

    public function addStage()
    {
        $this->stages[] = ['monster_id' => ''];
    }

    public function removeStage($index)
    {
        unset($this->stages[$index]);
        $this->stages = array_values($this->stages);
    }

    public function render()
    {
        return view('livewire.admin.dungeons');
    }
}
