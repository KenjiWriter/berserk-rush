<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Infrastructure\Persistence\Map;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.app')]
class Maps extends Component
{
    public $maps;
    public $name, $level_min, $level_max, $tier;
    public $editingMapId = null;

    protected $rules = [
        'name' => 'required|string|max:255',
        'level_min' => 'required|integer|min:1',
        'level_max' => 'required|integer|gte:level_min',
        'tier' => 'required|integer|min:1',
    ];

    public function mount()
    {
        $this->loadMaps();
    }

    public function loadMaps()
    {
        $this->maps = Map::orderBy('level_min')->get();
    }

    public function save()
    {
        $this->validate();

        if ($this->editingMapId) {
            $map = Map::findOrFail($this->editingMapId);
            $map->update([
                'name' => $this->name,
                'level_min' => $this->level_min,
                'level_max' => $this->level_max,
                'tier' => $this->tier,
            ]);
        } else {
            Map::create([
                'name' => $this->name,
                'level_min' => $this->level_min,
                'level_max' => $this->level_max,
                'tier' => $this->tier,
            ]);
        }

        $this->reset(['name', 'level_min', 'level_max', 'tier', 'editingMapId']);
        $this->loadMaps();
        session()->flash('message', 'Mapa zapisana.');
    }

    public function edit($id)
    {
        $map = Map::findOrFail($id);
        $this->editingMapId = $map->id;
        $this->name = $map->name;
        $this->level_min = $map->level_min;
        $this->level_max = $map->level_max;
        $this->tier = $map->tier;
    }

    public function delete($id)
    {
        Map::findOrFail($id)->delete();
        $this->loadMaps();
    }

    public function render()
    {
        return view('livewire.admin.maps');
    }
}
