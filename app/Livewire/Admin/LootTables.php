<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Infrastructure\Persistence\LootTable;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.app')]
class LootTables extends Component
{
    public $lootTables;
    public $name, $description;
    public $editingId = null;

    protected $rules = [
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
    ];

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $this->lootTables = LootTable::withCount('entries')->get();
    }

    public function save()
    {
        $this->validate();

        if ($this->editingId) {
            LootTable::findOrFail($this->editingId)->update([
                'name' => $this->name,
                'description' => $this->description,
            ]);
        } else {
            LootTable::create([
                'name' => $this->name,
                'description' => $this->description,
            ]);
        }

        $this->reset(['name', 'description', 'editingId']);
        $this->loadData();
        session()->flash('message', 'Tabela łupów zapisana.');
    }

    public function edit($id)
    {
        $lt = LootTable::findOrFail($id);
        $this->editingId = $lt->id;
        $this->name = $lt->name;
        $this->description = $lt->description;
    }

    public function delete($id)
    {
        LootTable::findOrFail($id)->delete();
        $this->loadData();
    }

    public function render()
    {
        return view('livewire.admin.loot-tables');
    }
}
