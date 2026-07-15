<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Infrastructure\Persistence\UpgradeRule;
use App\Infrastructure\Persistence\ItemTemplate;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.app')]
class UpgradeRules extends Component
{
    public $rulesList;
    public $templates;
    
    public $editingId = null;
    public $applies_to = 'slot';
    public $applies_value = 'weapon';
    public $from_level = 0;
    public $to_level = 1;
    public $success_chance = 0.95;
    public $on_fail = 'nothing';
    
    public $gold_cost = 100;
    public $materials = []; // Array of ['template_id' => '...', 'quantity' => 1]

    protected $rules = [
        'applies_to' => 'required|string',
        'applies_value' => 'nullable|string',
        'from_level' => 'required|integer|min:0',
        'to_level' => 'required|integer|min:1',
        'success_chance' => 'required|numeric|min:0|max:1',
        'on_fail' => 'required|string',
        'gold_cost' => 'required|integer|min:0',
        'materials' => 'array',
        'materials.*.template_id' => 'required|string|exists:item_templates,id',
        'materials.*.quantity' => 'required|integer|min:1',
    ];

    public function mount()
    {
        $this->templates = ItemTemplate::orderBy('name')->get();
        $this->loadData();
    }

    public function loadData()
    {
        $this->rulesList = UpgradeRule::orderBy('applies_value')->orderBy('from_level')->get();
    }

    public function addMaterial()
    {
        $this->materials[] = ['template_id' => '', 'quantity' => 1];
    }

    public function removeMaterial($index)
    {
        unset($this->materials[$index]);
        $this->materials = array_values($this->materials);
    }

    public function save()
    {
        $this->validate();

        $cost = [
            'gold' => $this->gold_cost,
            'materials' => $this->materials,
        ];

        $data = [
            'applies_to' => $this->applies_to,
            'applies_value' => $this->applies_value,
            'from_level' => $this->from_level,
            'to_level' => $this->to_level,
            'success_chance' => $this->success_chance,
            'on_fail' => $this->on_fail,
            'cost' => $cost,
        ];

        if ($this->editingId) {
            $rule = UpgradeRule::findOrFail($this->editingId);
            $rule->update($data);
            session()->flash('message', 'Zasada zaktualizowana.');
        } else {
            UpgradeRule::create($data);
            session()->flash('message', 'Zasada utworzona.');
        }

        $this->resetForm();
        $this->loadData();
    }

    public function edit($id)
    {
        $rule = UpgradeRule::findOrFail($id);
        $this->editingId = $rule->id;
        $this->applies_to = $rule->applies_to;
        $this->applies_value = $rule->applies_value;
        $this->from_level = $rule->from_level;
        $this->to_level = $rule->to_level;
        $this->success_chance = $rule->success_chance;
        $this->on_fail = $rule->on_fail;
        
        $this->gold_cost = $rule->cost['gold'] ?? 0;
        $this->materials = $rule->cost['materials'] ?? [];
    }

    public function delete($id)
    {
        UpgradeRule::findOrFail($id)->delete();
        $this->loadData();
        session()->flash('message', 'Zasada usunięta.');
    }

    public function resetForm()
    {
        $this->reset(['editingId', 'applies_to', 'applies_value', 'from_level', 'to_level', 'success_chance', 'on_fail', 'gold_cost', 'materials']);
    }

    public function render()
    {
        return view('livewire.admin.upgrade-rules');
    }
}
