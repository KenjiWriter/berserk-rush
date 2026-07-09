<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Infrastructure\Persistence\MerchantItem;
use App\Infrastructure\Persistence\ItemTemplate;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.app')]
class MerchantItems extends Component
{
    public $merchantItems;
    public $templates;
    
    public $merchant_id = 'armorsmith';
    public $item_template_id;
    public $required_level = 1;
    public $is_limited = false;
    public $max_quantity = null;
    
    public $editingId = null;

    protected $rules = [
        'merchant_id' => 'required|string',
        'item_template_id' => 'required|string|exists:item_templates,id',
        'required_level' => 'required|integer|min:1',
        'is_limited' => 'boolean',
        'max_quantity' => 'nullable|integer|min:1',
    ];

    public function mount()
    {
        $this->templates = ItemTemplate::orderBy('name')->get();
        $this->loadData();
    }

    public function loadData()
    {
        $this->merchantItems = MerchantItem::with('template')->orderBy('merchant_id')->orderBy('required_level')->get();
    }

    public function save()
    {
        if (!$this->is_limited) {
            $this->max_quantity = null;
        }

        $this->validate();

        $data = [
            'merchant_id' => $this->merchant_id,
            'item_template_id' => $this->item_template_id,
            'required_level' => $this->required_level,
            'is_limited' => $this->is_limited,
            'max_quantity' => $this->max_quantity,
        ];

        if ($this->editingId) {
            MerchantItem::findOrFail($this->editingId)->update($data);
        } else {
            MerchantItem::create($data);
        }

        $this->reset(['item_template_id', 'required_level', 'is_limited', 'max_quantity', 'editingId']);
        $this->loadData();
        session()->flash('message', 'Zapisano asortyment handlarza.');
    }

    public function edit($id)
    {
        $mi = MerchantItem::findOrFail($id);
        $this->editingId = $mi->id;
        $this->merchant_id = $mi->merchant_id;
        $this->item_template_id = $mi->item_template_id;
        $this->required_level = $mi->required_level;
        $this->is_limited = $mi->is_limited;
        $this->max_quantity = $mi->max_quantity;
    }

    public function delete($id)
    {
        MerchantItem::findOrFail($id)->delete();
        $this->loadData();
    }

    public function render()
    {
        return view('livewire.admin.merchant-items');
    }
}
