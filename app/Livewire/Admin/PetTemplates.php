<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Infrastructure\Persistence\PetTemplate;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.app')]
class PetTemplates extends Component
{
    public $templates;
    public $name, $rarity = 'common', $icon;
    public $str = 0, $agi = 0, $int = 0, $vit = 0;
    public $editingId = null;

    protected $rules = [
        'name' => 'required|string|max:255',
        'rarity' => 'required|in:common,uncommon,rare,epic,legendary',
        'icon' => 'nullable|string|max:255',
        'str' => 'required|integer|min:0',
        'agi' => 'required|integer|min:0',
        'int' => 'required|integer|min:0',
        'vit' => 'required|integer|min:0',
    ];

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $this->templates = PetTemplate::orderBy('created_at')->get();
    }

    public function save()
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'rarity' => $this->rarity,
            'base_stats' => [
                'str' => $this->str,
                'agi' => $this->agi,
                'int' => $this->int,
                'vit' => $this->vit,
            ],
            'icon' => $this->icon,
        ];

        if ($this->editingId) {
            PetTemplate::findOrFail($this->editingId)->update($data);
            session()->flash('message', 'Szablon zwierzaka zaktualizowany!');
        } else {
            PetTemplate::create($data);
            session()->flash('message', 'Szablon zwierzaka dodany!');
        }

        $this->reset(['name', 'rarity', 'icon', 'str', 'agi', 'int', 'vit', 'editingId']);
        $this->loadData();
    }

    public function edit($id)
    {
        $template = PetTemplate::findOrFail($id);
        $this->editingId = $template->id;
        $this->name = $template->name;
        $this->rarity = $template->rarity;
        $this->icon = $template->icon;
        
        $stats = $template->base_stats ?? [];
        $this->str = $stats['str'] ?? 0;
        $this->agi = $stats['agi'] ?? 0;
        $this->int = $stats['int'] ?? 0;
        $this->vit = $stats['vit'] ?? 0;
    }

    public function delete($id)
    {
        PetTemplate::findOrFail($id)->delete();
        session()->flash('message', 'Szablon usunięty!');
        $this->loadData();
    }

    public function render()
    {
        return view('livewire.admin.pet-templates');
    }
}
