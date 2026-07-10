<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Infrastructure\Persistence\ItemRecipe;
use App\Infrastructure\Persistence\ItemTemplate;
use Livewire\Attributes\Layout;
use Illuminate\Support\Str;

#[Layout('components.layouts.app')]
class ItemRecipes extends Component
{
    public $recipes;
    public $templates;
    
    public $editingId = null;
    public $result_item_template_id;
    public $gold_cost = 0;
    public $ingredients = []; // Array of ['template_id' => '...', 'quantity' => 1]

    protected $rules = [
        'result_item_template_id' => 'required|string|exists:item_templates,id',
        'gold_cost' => 'required|integer|min:0',
        'ingredients' => 'required|array|min:1',
        'ingredients.*.template_id' => 'required|string|exists:item_templates,id',
        'ingredients.*.quantity' => 'required|integer|min:1',
    ];

    public function mount()
    {
        $this->templates = ItemTemplate::orderBy('name')->get();
        $this->loadData();
        $this->addIngredient(); // add empty default
    }

    public function loadData()
    {
        $this->recipes = ItemRecipe::with('resultItemTemplate')->get();
    }

    public function addIngredient()
    {
        $this->ingredients[] = ['template_id' => '', 'quantity' => 1];
    }

    public function removeIngredient($index)
    {
        unset($this->ingredients[$index]);
        $this->ingredients = array_values($this->ingredients); // reindex
    }

    public function save()
    {
        $this->validate();

        $data = [
            'result_item_template_id' => $this->result_item_template_id,
            'gold_cost' => $this->gold_cost,
            'ingredients' => $this->ingredients,
        ];

        if ($this->editingId) {
            $recipe = ItemRecipe::findOrFail($this->editingId);
            $recipe->update($data);
            session()->flash('message', 'Przepis zaktualizowany.');
        } else {
            $data['id'] = (string) Str::ulid();
            ItemRecipe::create($data);
            session()->flash('message', 'Przepis utworzony.');
        }

        $this->resetForm();
        $this->loadData();
    }

    public function edit($id)
    {
        $recipe = ItemRecipe::findOrFail($id);
        $this->editingId = $recipe->id;
        $this->result_item_template_id = $recipe->result_item_template_id;
        $this->gold_cost = $recipe->gold_cost;
        $this->ingredients = $recipe->ingredients;
        if (empty($this->ingredients)) {
            $this->addIngredient();
        }
    }

    public function delete($id)
    {
        ItemRecipe::findOrFail($id)->delete();
        $this->loadData();
        session()->flash('message', 'Przepis usunięty.');
    }

    public function resetForm()
    {
        $this->reset(['editingId', 'result_item_template_id', 'gold_cost', 'ingredients']);
        $this->addIngredient();
    }

    public function render()
    {
        return view('livewire.admin.item-recipes');
    }
}
