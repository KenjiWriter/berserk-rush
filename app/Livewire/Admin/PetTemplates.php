<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Domain\Pets\PetArchetype;
use App\Domain\Pets\PetTier;
use App\Infrastructure\Persistence\PetTemplate;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.app')]
class PetTemplates extends Component
{
    public $templates;
    public $name, $tier = 1, $icon, $archetype = null;
    public $str = 0, $agi = 0, $int = 0, $vit = 0;
    public $editingId = null;

    protected $rules = [
        'name' => 'required|string|max:255',
        'tier' => 'required|integer|between:1,6',
        'icon' => 'nullable|string|max:255',
        'archetype' => 'nullable|in:attacker,defense,support',
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
        $this->templates = PetTemplate::orderBy('tier')->orderBy('name')->get();
    }

    public function tierOptions(): array
    {
        $options = [];
        foreach (PetTier::all() as $tier => $meta) {
            $options[$tier] = $meta['name'];
        }
        return $options;
    }

    public function archetypeOptions(): array
    {
        $options = [];
        foreach (PetArchetype::all() as $archetype) {
            $options[$archetype] = PetArchetype::label($archetype);
        }
        return $options;
    }

    public function save()
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'tier' => (int) $this->tier,
            'archetype' => $this->archetype ?: null,
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
            session()->flash('message', 'Gatunek chowańca zaktualizowany!');
        } else {
            PetTemplate::create($data);
            session()->flash('message', 'Gatunek chowańca dodany!');
        }

        $this->reset(['name', 'tier', 'icon', 'archetype', 'str', 'agi', 'int', 'vit', 'editingId']);
        $this->tier = 1;
        $this->loadData();
    }

    public function edit($id)
    {
        $template = PetTemplate::findOrFail($id);
        $this->editingId = $template->id;
        $this->name = $template->name;
        $this->tier = $template->tier ?? 1;
        $this->icon = $template->icon;
        $this->archetype = $template->archetype;

        $stats = $template->base_stats ?? [];
        $this->str = $stats['str'] ?? 0;
        $this->agi = $stats['agi'] ?? 0;
        $this->int = $stats['int'] ?? 0;
        $this->vit = $stats['vit'] ?? 0;
    }

    public function delete($id)
    {
        PetTemplate::findOrFail($id)->delete();
        session()->flash('message', 'Gatunek usunięty!');
        $this->loadData();
    }

    public function render()
    {
        return view('livewire.admin.pet-templates', [
            'tierOptions' => $this->tierOptions(),
            'archetypeOptions' => $this->archetypeOptions(),
        ]);
    }
}
