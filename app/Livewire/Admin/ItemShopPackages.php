<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\ItemShopPackage;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.app')]
class ItemShopPackages extends Component
{
    public $packages;
    public $name, $gem_amount, $price, $currency = 'PLN', $is_active = true;
    public $editingPackageId = null;

    protected $rules = [
        'name' => 'required|string|max:255',
        'gem_amount' => 'required|integer|min:1',
        'price' => 'required|numeric|min:0.01',
        'currency' => 'required|string|max:3',
        'is_active' => 'boolean',
    ];

    public function mount()
    {
        $this->loadPackages();
    }

    public function loadPackages()
    {
        $this->packages = ItemShopPackage::orderBy('price_in_cents')->get();
    }

    public function save()
    {
        $this->validate();

        $priceInCents = (int) round($this->price * 100);

        if ($this->editingPackageId) {
            $package = ItemShopPackage::findOrFail($this->editingPackageId);
            $package->update([
                'name' => $this->name,
                'gem_amount' => $this->gem_amount,
                'price_in_cents' => $priceInCents,
                'currency' => $this->currency,
                'is_active' => $this->is_active,
            ]);
        } else {
            ItemShopPackage::create([
                'name' => $this->name,
                'gem_amount' => $this->gem_amount,
                'price_in_cents' => $priceInCents,
                'currency' => $this->currency,
                'is_active' => $this->is_active,
            ]);
        }

        $this->reset(['name', 'gem_amount', 'price', 'currency', 'is_active', 'editingPackageId']);
        $this->loadPackages();
        session()->flash('message', 'Pakiet zapisany.');
    }

    public function edit($id)
    {
        $package = ItemShopPackage::findOrFail($id);
        $this->editingPackageId = $package->id;
        $this->name = $package->name;
        $this->gem_amount = $package->gem_amount;
        $this->price = number_format($package->price_in_cents / 100, 2, '.', '');
        $this->currency = $package->currency;
        $this->is_active = $package->is_active;
    }

    public function delete($id)
    {
        ItemShopPackage::findOrFail($id)->delete();
        $this->loadPackages();
    }

    public function render()
    {
        return view('livewire.admin.item-shop-packages');
    }
}
