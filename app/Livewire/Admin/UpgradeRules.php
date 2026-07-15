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
    public $upgradeableItems;
    
    public $editingId = null;
    public $template_id = '';
    public $to_level = 1;
    public $success_chance = 0.95;
    public $on_fail = 'nothing';
    
    public $gold_cost = 100;
    public $materials = []; // Array of ['template_id' => '...', 'quantity' => 1]

    protected $rules = [
        'template_id' => 'required|string|exists:item_templates,id',
        'to_level' => 'required|integer|min:1|max:9',
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
        $this->upgradeableItems = $this->templates->whereIn('type', ['weapon', 'armor', 'accessory']);
        $this->loadData();
    }

    public function loadData()
    {
        $this->rulesList = UpgradeRule::where('applies_to', 'template')->orderBy('applies_value')->orderBy('from_level')->get();
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
            'applies_to' => 'template',
            'applies_value' => $this->template_id,
            'from_level' => $this->to_level - 1,
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
            // Check if rule already exists for this level and template
            $existing = UpgradeRule::where('applies_to', 'template')
                ->where('applies_value', $this->template_id)
                ->where('to_level', $this->to_level)
                ->first();
            
            if ($existing) {
                $existing->update($data);
                session()->flash('message', 'Istniejąca zasada została nadpisana.');
            } else {
                UpgradeRule::create($data);
                session()->flash('message', 'Zasada utworzona.');
            }
        }

        $this->resetForm();
        $this->loadData();
    }

    public function edit($id)
    {
        $rule = UpgradeRule::findOrFail($id);
        $this->editingId = $rule->id;
        $this->template_id = $rule->applies_value;
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
        $this->reset(['editingId', 'template_id', 'to_level', 'success_chance', 'on_fail', 'gold_cost', 'materials']);
    }

    public function render()
    {
        return view('livewire.admin.upgrade-rules');
    }
}
