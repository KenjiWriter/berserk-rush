<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Infrastructure\Persistence\LootTable;
use App\Infrastructure\Persistence\LootTableEntry;
use App\Infrastructure\Persistence\ItemTemplate;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.app')]
class LootTables extends Component
{
    public $lootTables;
    public $name, $description;
    public $editingId = null;

    // Entry management properties
    public $selectedTableId = null;
    public $editingEntryId = null;
    public $entryRewardType = 'item';
    public $entryRefUlid = null;
    public $entryMinQty = 1;
    public $entryMaxQty = 1;
    public $entryWeight = 10;
    public $itemSearch = '';

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
            session()->flash('message', 'Tabela łupów zaktualizowana.');
        } else {
            LootTable::create([
                'name' => $this->name,
                'description' => $this->description,
            ]);
            session()->flash('message', 'Tabela łupów utworzona.');
        }

        $this->reset(['name', 'description', 'editingId']);
        $this->loadData();
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
        if ($this->selectedTableId == $id) {
            $this->selectedTableId = null;
        }
        $this->loadData();
        session()->flash('message', 'Tabela łupów usunięta.');
    }

    public function selectTable($id)
    {
        $this->selectedTableId = $id;
        $this->resetEntryForm();
    }

    public function closeEntries()
    {
        $this->selectedTableId = null;
        $this->resetEntryForm();
    }

    public function updatedEntryRewardType()
    {
        $this->entryRefUlid = null;
        $this->itemSearch = '';
    }

    public function saveEntry()
    {
        if (!$this->selectedTableId) {
            return;
        }

        $this->validate([
            'entryRewardType' => 'required|string|in:gold,exp,gems,item,material',
            'entryMinQty' => 'required|integer|min:1',
            'entryMaxQty' => 'required|integer|min:1|gte:entryMinQty',
            'entryWeight' => 'required|integer|min:1',
            'entryRefUlid' => in_array($this->entryRewardType, ['item', 'material']) ? 'required|string|exists:item_templates,id' : 'nullable',
        ], [
            'entryRefUlid.required' => 'Wybierz przedmiot/materiał z listy.',
            'entryMaxQty.gte' => 'Maksymalna ilość musi być większa lub równa minimalnej.',
        ]);

        $refUlid = in_array($this->entryRewardType, ['item', 'material']) ? $this->entryRefUlid : null;

        if ($this->editingEntryId) {
            LootTableEntry::findOrFail($this->editingEntryId)->update([
                'reward_type' => $this->entryRewardType,
                'ref_ulid' => $refUlid,
                'min_qty' => $this->entryMinQty,
                'max_qty' => $this->entryMaxQty,
                'weight' => $this->entryWeight,
            ]);
            session()->flash('message', 'Wpis dropu zaktualizowany.');
        } else {
            LootTableEntry::create([
                'loot_table_id' => $this->selectedTableId,
                'reward_type' => $this->entryRewardType,
                'ref_ulid' => $refUlid,
                'min_qty' => $this->entryMinQty,
                'max_qty' => $this->entryMaxQty,
                'weight' => $this->entryWeight,
            ]);
            session()->flash('message', 'Wpis dropu dodany.');
        }

        $this->resetEntryForm();
        $this->loadData();
    }

    public function editEntry($entryId)
    {
        $entry = LootTableEntry::findOrFail($entryId);
        $this->editingEntryId = $entry->id;
        $this->entryRewardType = $entry->reward_type;
        $this->entryRefUlid = $entry->ref_ulid;
        $this->entryMinQty = $entry->min_qty;
        $this->entryMaxQty = $entry->max_qty;
        $this->entryWeight = $entry->weight;
        $this->itemSearch = '';
    }

    public function deleteEntry($entryId)
    {
        LootTableEntry::findOrFail($entryId)->delete();
        $this->loadData();
        if ($this->editingEntryId == $entryId) {
            $this->resetEntryForm();
        }
        session()->flash('message', 'Wpis dropu usunięty.');
    }

    public function resetEntryForm()
    {
        $this->editingEntryId = null;
        $this->entryRewardType = 'item';
        $this->entryRefUlid = null;
        $this->entryMinQty = 1;
        $this->entryMaxQty = 1;
        $this->entryWeight = 10;
        $this->itemSearch = '';
    }

    public function render()
    {
        $selectedTable = null;
        $entries = collect();
        $totalWeight = 0;

        if ($this->selectedTableId) {
            $selectedTable = LootTable::find($this->selectedTableId);
            if ($selectedTable) {
                $entries = LootTableEntry::where('loot_table_id', $this->selectedTableId)
                    ->with('itemTemplate')
                    ->get();
                $totalWeight = $entries->sum('weight');
            }
        }

        $itemTemplatesQuery = ItemTemplate::query();

        $search = trim($this->itemSearch);
        if (!empty($search)) {
            $itemTemplatesQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('id', 'like', "%{$search}%")
                  ->orWhere('type', 'like', "%{$search}%")
                  ->orWhere('sub_type', 'like', "%{$search}%");
            });
        } else {
            if ($this->entryRewardType === 'material') {
                $itemTemplatesQuery->where('type', 'material');
            } elseif ($this->entryRewardType === 'item') {
                $itemTemplatesQuery->where('type', '!=', 'material');
            }
        }

        if ($this->entryRefUlid) {
            $selectedUlid = $this->entryRefUlid;
            $itemTemplatesQuery->orWhere('id', $selectedUlid);
        }

        $itemTemplates = $itemTemplatesQuery->orderBy('name')->get();
        $selectedItemTemplate = $this->entryRefUlid ? ItemTemplate::find($this->entryRefUlid) : null;

        return view('livewire.admin.loot-tables', [
            'selectedTable' => $selectedTable,
            'entries' => $entries,
            'totalWeight' => $totalWeight,
            'itemTemplates' => $itemTemplates,
            'selectedItemTemplate' => $selectedItemTemplate,
        ]);
    }
}
