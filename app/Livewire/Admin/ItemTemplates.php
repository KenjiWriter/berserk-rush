<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Infrastructure\Persistence\ItemTemplate;
use Livewire\Attributes\Layout;
use Illuminate\Support\Str;

#[Layout('layouts.app')]
class ItemTemplates extends Component
{
    public $templates;
    public $template_id, $name, $type, $slot, $level_requirement;
    public $base_stats_json = '{}';
    public $description, $icon;
    public $editingId = null;

    protected $rules = [
        'template_id' => 'required|string|max:255',
        'name' => 'required|string|max:255',
        'type' => 'required|string',
        'slot' => 'nullable|string',
        'level_requirement' => 'required|integer|min:1',
        'base_stats_json' => 'required|json',
    ];

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $this->templates = ItemTemplate::orderBy('level_requirement')->get();
    }

    public function save()
    {
        $this->validate();

        $stats = json_decode($this->base_stats_json, true) ?? [];

        $data = [
            'id' => $this->template_id,
            'name' => $this->name,
            'type' => $this->type,
            'slot' => $this->slot ?: null,
            'level_requirement' => $this->level_requirement,
            'base_stats' => $stats,
            'description' => $this->description,
            'icon' => $this->icon,
        ];

        if ($this->editingId) {
            $item = ItemTemplate::findOrFail($this->editingId);
            // If ID changed, we need to handle it, but for simplicity let's disable ID change or create new
            if ($this->editingId !== $this->template_id) {
                $item->delete();
                ItemTemplate::create($data);
            } else {
                $item->update($data);
            }
        } else {
            ItemTemplate::create($data);
        }

        $this->reset(['template_id', 'name', 'type', 'slot', 'level_requirement', 'base_stats_json', 'description', 'icon', 'editingId']);
        $this->loadData();
        session()->flash('message', 'Szablon przedmiotu zapisany.');
    }

    public function edit($id)
    {
        $template = ItemTemplate::findOrFail($id);
        $this->editingId = $template->id;
        $this->template_id = $template->id;
        $this->name = $template->name;
        $this->type = $template->type;
        $this->slot = $template->slot;
        $this->level_requirement = $template->level_requirement;
        $this->base_stats_json = json_encode($template->base_stats, JSON_PRETTY_PRINT);
        $this->description = $template->description;
        $this->icon = $template->icon;
    }

    public function delete($id)
    {
        ItemTemplate::findOrFail($id)->delete();
        $this->loadData();
    }

    public function generateId()
    {
        $this->template_id = Str::slug($this->name);
    }

    public function render()
    {
        return view('livewire.admin.item-templates');
    }
}
