<?php

namespace App\Livewire\Admin;

use App\Infrastructure\Persistence\Title;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Str;

#[Layout('components.layouts.app')]
class Titles extends Component
{
    public $titles;
    
    // Form fields
    public $titleId;
    public $name;
    public $prefix;
    public $description;
    public $stats_bonus = []; // json array

    // UI state
    public $isEditing = false;
    public $showModal = false;

    // Stat keys for the UI form
    public $availableStats = ['str', 'int', 'vit', 'agi', 'hp', 'defense', 'attack_min', 'attack_max', 'bonus_vs_demon', 'bonus_vs_undead', 'bonus_vs_animal', 'bonus_vs_orc'];

    protected $rules = [
        'name' => 'required|string|max:255',
        'prefix' => 'nullable|string|max:255',
        'description' => 'nullable|string',
        'stats_bonus' => 'nullable|array'
    ];

    public function mount()
    {
        $this->loadTitles();
    }

    public function loadTitles()
    {
        $this->titles = Title::all();
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
        $title = Title::findOrFail($id);
        
        $this->titleId = $title->id;
        $this->name = $title->name;
        $this->prefix = $title->prefix;
        $this->description = $title->description;
        $this->stats_bonus = $title->stats_bonus ?? [];
        
        $this->isEditing = true;
        $this->showModal = true;
    }

    public function addStat()
    {
        $this->stats_bonus[] = ['key' => 'str', 'value' => 0];
    }

    public function removeStat($index)
    {
        unset($this->stats_bonus[$index]);
        $this->stats_bonus = array_values($this->stats_bonus);
    }

    public function save()
    {
        $this->validate();

        // Convert stats_bonus array format back to key-value pairs
        $formattedStats = [];
        foreach ($this->stats_bonus as $stat) {
            if (isset($stat['key']) && isset($stat['value'])) {
                $formattedStats[$stat['key']] = (int) $stat['value'];
            } else if (is_string(key($this->stats_bonus))) {
                // Already key-value
                $formattedStats = $this->stats_bonus;
                break;
            }
        }

        if ($this->isEditing) {
            $title = Title::findOrFail($this->titleId);
            $title->update([
                'name' => $this->name,
                'prefix' => $this->prefix,
                'description' => $this->description,
                'stats_bonus' => $formattedStats,
            ]);
            session()->flash('message', 'Tytuł zaktualizowany.');
        } else {
            Title::create([
                'id' => Str::ulid(),
                'name' => $this->name,
                'prefix' => $this->prefix,
                'description' => $this->description,
                'stats_bonus' => $formattedStats,
            ]);
            session()->flash('message', 'Tytuł utworzony.');
        }

        $this->showModal = false;
        $this->loadTitles();
    }

    public function delete($id)
    {
        Title::findOrFail($id)->delete();
        session()->flash('message', 'Tytuł usunięty.');
        $this->loadTitles();
    }

    private function resetForm()
    {
        $this->titleId = null;
        $this->name = '';
        $this->prefix = '';
        $this->description = '';
        $this->stats_bonus = [];
    }

    public function render()
    {
        return view('livewire.admin.titles');
    }
}
