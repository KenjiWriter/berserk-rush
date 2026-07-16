<?php

namespace App\Livewire\City;

use Livewire\Component;
use App\Infrastructure\Persistence\Character;
use Illuminate\Support\Facades\Gate;
use App\Infrastructure\Persistence\ItemTemplate;
use App\Infrastructure\Persistence\ItemInstance;
use App\Infrastructure\Persistence\ItemLedger;
use App\Infrastructure\Persistence\CharacterCooldown;
use App\Infrastructure\Persistence\ItemRecipe;
use App\Infrastructure\Persistence\MerchantItem;
use App\Application\Items\CraftingService;
use App\Application\Items\ShopService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class Witch extends Component
{
    public Character $character;
    public $message = '';
    public $messageType = 'info';
    public $activeTab = 'shop'; // shop, crafting

    public function mount(Character $character): void
    {
        Gate::authorize('view', $character);
        $this->character = $character;
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->message = '';
    }

    public function backToHub(): void
    {
        $this->redirect(route('city.hub', $this->character), navigate: true);
    }

    public function buySpecialExpPotion()
    {
        $character = $this->character;

        // Sprawdź cooldown
        $cooldown = CharacterCooldown::where('character_id', $character->id)
            ->where('cooldown_key', 'witch_exp_potion_daily')
            ->first();

        if ($cooldown && $cooldown->expires_at > Carbon::now()) {
            $this->showMessage('Już kupiłeś tę miksturę dzisiaj. Wróć jutro!', 'error');
            return;
        }

        $price = 1500;
        $template = ItemTemplate::where('id', 'potion-exp-special')->first();
        
        if (!$template) {
            $this->showMessage('Mikstura nie istnieje w bazie.', 'error');
            return;
        }

        if ($character->gold < $price) {
            $this->showMessage('Nie masz wystarczająco złota.', 'error');
            return;
        }

        DB::transaction(function () use ($character, $template, $price, $cooldown) {
            $character->decrement('gold', $price);

            $item = ItemInstance::create([
                'id' => (string) Str::ulid(),
                'template_id' => $template->id,
                'owner_character_id' => $character->id,
                'location' => 'inventory',
                'rarity' => 'rare',
                'stack_size' => 1,
            ]);

            ItemLedger::create([
                'id' => (string) Str::ulid(),
                'character_id' => $character->id,
                'item_instance_id' => $item->id,
                'action' => 'buy_npc',
                'ref_type' => 'witch',
                'quantity_change' => 1,
                'idempotency_key' => 'witch_buy_' . Str::ulid(),
            ]);

            if ($cooldown) {
                $cooldown->update(['expires_at' => Carbon::now()->addDay()]);
            } else {
                CharacterCooldown::create([
                    'character_id' => $character->id,
                    'cooldown_key' => 'witch_exp_potion_daily',
                    'expires_at' => Carbon::now()->addDay(),
                ]);
            }
        });

        $this->showMessage('Kupiłeś: ' . $template->name, 'success');
        $this->dispatch('play-audio', type: 'buy');
        $this->character->refresh();
    }

    public function buyItem(int $merchantItemId, ShopService $shop)
    {
        $merchantItem = MerchantItem::with('template')->findOrFail($merchantItemId);
        $result = $shop->buyItem($this->character, $merchantItem);
        if ($result['success']) {
            $this->showMessage($result['message'], 'success');
            $this->dispatch('play-audio', type: 'buy');
        } else {
            $this->showMessage($result['message'], 'error');
        }
        $this->character->refresh();
    }

    public function craftPotion($recipeId)
    {
        $recipe = ItemRecipe::find($recipeId);
        if (!$recipe) return;

        $action = new CraftingService();
        $result = $action->craftItem($this->character, $recipe);

        if ($result['success']) {
            $this->showMessage($result['message'], 'success');
            $this->dispatch('play-audio', type: 'upgrade-success');
        } else {
            $this->showMessage($result['message'], 'error');
        }
        $this->character->refresh();
    }

    private function showMessage($text, $type)
    {
        $this->message = $text;
        $this->messageType = $type;
    }

    public function render(ShopService $shopService)
    {
        // Special Potion Cooldown Logic
        $canBuySpecial = true;
        $specialCooldown = null;
        $cd = CharacterCooldown::where('character_id', $this->character->id)
            ->where('cooldown_key', 'witch_exp_potion_daily')
            ->first();
        
        if ($cd && $cd->expires_at > Carbon::now()) {
            $canBuySpecial = false;
            $specialCooldown = $cd->expires_at;
        }

        // Regular Potions from MerchantItems
        $shopItems = MerchantItem::where('merchant_id', 'witch')
            ->where('required_level', '<=', $this->character->level)
            ->with('template')
            ->get()
            ->filter(function($mi) {
                return !$mi->is_limited || $mi->sold_quantity < $mi->max_quantity;
            });
            
        $shopPrices = [];
        foreach($shopItems as $mi) {
            $shopPrices[$mi->id] = $shopService->getBuyPrice($mi->template);
        }

        // Crafting Recipes
        $recipes = ItemRecipe::with('resultItemTemplate')->whereHas('resultItemTemplate', function($q) {
            $q->where('type', 'consumable');
        })->get();

        $inventory = $this->character->inventoryItems()->get();
        
        $preparedRecipes = [];
        foreach ($recipes as $recipe) {
            $preparedIngredients = [];
            $canCraft = $this->character->gold >= $recipe->gold_cost;

            foreach ($recipe->ingredients as $ing) {
                $mat = ItemTemplate::find($ing['template_id']);
                $owned = $inventory->where('template_id', $ing['template_id'])->sum('stack_size');
                $req = $ing['quantity'];
                
                if ($owned < $req) $canCraft = false;

                $preparedIngredients[] = [
                    'name' => $mat ? $mat->name : 'Nieznany',
                    'owned' => $owned,
                    'required' => $req,
                    'ok' => $owned >= $req,
                ];
            }

            $preparedRecipes[] = [
                'id' => $recipe->id,
                'result_name' => $recipe->resultItemTemplate->name ?? 'Nieznany',
                'gold_cost' => $recipe->gold_cost,
                'ingredients' => $preparedIngredients,
                'can_craft' => $canCraft,
            ];
        }

        return view('livewire.city.witch', [
            'canBuySpecial' => $canBuySpecial,
            'specialCooldown' => $specialCooldown,
            'shopItems' => $shopItems,
            'shopPrices' => $shopPrices,
            'recipes' => $preparedRecipes,
        ]);
    }
}

