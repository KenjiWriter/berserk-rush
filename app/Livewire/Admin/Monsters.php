<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Infrastructure\Persistence\Monster;
use App\Infrastructure\Persistence\Map;
use App\Infrastructure\Persistence\LootTable;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.app')]
class Monsters extends Component
{
    public $monsters, $maps, $lootTables;
    public $map_id, $name, $level, $type, $loot_table_id;
    public $hp = 100, $atk = 10, $def = 5, $crit = 0;
    public $editingId = null;

    protected $rules = [
        'map_id' => 'required|exists:maps,id',
        'name' => 'required|string|max:255',
        'type' => 'required|string',
        'level' => 'required|integer|min:1',
        'hp' => 'required|integer|min:1',
        'atk' => 'required|integer|min:0',
        'def' => 'required|integer|min:0',
        'crit' => 'required|integer|min:0|max:100',
        'loot_table_id' => 'nullable|exists:loot_tables,id',
    ];

    public function mount()
    {
        $this->maps = Map::all();
        $this->lootTables = LootTable::all();
        $this->loadData();
    }

    public function loadData()
    {
        $this->monsters = Monster::with(['map', 'lootTable'])->orderBy('level')->get();
    }

    public function save()
    {
        $this->validate();

        $data = [
            'map_id' => $this->map_id,
            'name' => $this->name,
            'level' => $this->level,
            'type' => $this->type ?? 'zwierzę',
            'stats' => ['hp' => $this->hp, 'atk' => $this->atk, 'def' => $this->def, 'crit' => $this->crit],
            'loot_table_id' => $this->loot_table_id ?: null,
        ];

        if ($this->editingId) {
            Monster::findOrFail($this->editingId)->update($data);
        } else {
            Monster::create($data);
        }

        $this->reset(['name', 'level', 'hp', 'atk', 'def', 'crit', 'editingId']);
        $this->loadData();
        session()->flash('message', 'Potwór zapisany.');
    }

    public function edit($id)
    {
        $monster = Monster::findOrFail($id);
        $this->editingId = $monster->id;
        $this->map_id = $monster->map_id;
        $this->name = $monster->name;
        $this->type = $monster->type ?? 'zwierzę';
        $this->level = $monster->level;
        $this->hp = $monster->stats['hp'] ?? 100;
        $this->atk = $monster->stats['atk'] ?? 10;
        $this->def = $monster->stats['def'] ?? 5;
        $this->crit = $monster->stats['crit'] ?? 0;
        $this->loot_table_id = $monster->loot_table_id;
    }

    public function delete($id)
    {
        Monster::findOrFail($id)->delete();
        $this->loadData();
    }

    public function render()
    {
        return view('livewire.admin.monsters');
    }
}
