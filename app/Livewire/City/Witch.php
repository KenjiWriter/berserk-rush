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
use App\Application\Wizard\EnchantItem;
use App\Application\Wizard\RerollEnchantments;
use App\Domain\Wizard\EnchantmentStrategy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Livewire\Attributes\On;

class Witch extends Component
{
    public Character $character;
    public $message = '';
    public $messageType = 'info';
    public $activeTab = 'shop'; // shop, crafting, enchant

    // Zakładka Zaczarowania (przeniesiona od Czarodzieja)
    public ?string $activeItemId = null;
    public ?string $actionMessage = null;
    public ?string $actionType = null; // 'success' or 'error'

    #[On('tutorial-completed')]
    public function refreshOnTutorial()
    {
        // Re-render component on tutorial step update
    }

    public function mount(Character $character): void
    {
        Gate::authorize('view', $character);
        $this->character = $character;
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->message = '';
        $this->dispatch('play-audio', type: 'tab');
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
            $this->character->refresh();
            $this->dispatch('stats-updated', gold: $this->character->gold);
        } else {
            $this->showMessage($result['message'], 'error');
        }
        $this->character->refresh();
    }

    public function craftPotion($recipeId)
    {
        $recipe = ItemRecipe::find($recipeId);
        if (!$recipe) return;

        $action = app(CraftingService::class);
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

    // --- Zaczarowanie przedmiotów (przeniesione od Czarodzieja) ---

    public function selectItemToEnchant(string $itemInstanceId)
    {
        $this->activeItemId = $itemInstanceId;
        $this->clearEnchantMessages();
    }

    public function deselectItem()
    {
        $this->activeItemId = null;
        $this->clearEnchantMessages();
    }

    public function clearEnchantMessages()
    {
        $this->actionMessage = null;
        $this->actionType = null;
    }

    public function toggleEnchantLock(string $bonusType)
    {
        if (!$this->activeItemId) return;

        $item = ItemInstance::find($this->activeItemId);
        if (!$item || $item->owner_character_id !== $this->character->id) {
            return;
        }

        $item->toggleEnchantLock($bonusType);
        $item->save();
    }

    public function enchant(string $currencyType, EnchantItem $enchantItemAction)
    {
        $this->clearEnchantMessages();

        if (!$this->activeItemId) return;

        $item = ItemInstance::find($this->activeItemId);
        if (!$item) return;

        try {
            $result = $enchantItemAction->execute($item, $this->character, $currencyType);

            if ($result->isError()) {
                $this->actionType = 'error';
                $this->actionMessage = $result->getErrorMessage();
                $this->dispatch('play-audio', type: 'enchant-fail');
            } else {
                $payload = $result->getPayload();
                if ($payload['success'] ?? false) {
                    $this->actionType = 'success';
                    $this->actionMessage = $payload['message'] ?? 'Przedmiot został pomyślnie zaklęty. Dodano nowy bonus!';
                    $this->dispatch('play-audio', type: 'enchant-success');

                    // Tutorial step update
                    if (auth()->user()->game_stage == 32) {
                        auth()->user()->update(['game_stage' => 33]);
                    }
                } else {
                    $this->actionType = 'error';
                    $this->actionMessage = $payload['message'] ?? 'Zaklinanie nie powiodło się.';
                    $this->dispatch('play-audio', type: 'enchant-fail');
                }
                $this->character->refresh();
                $this->dispatch('stats-updated', gold: $this->character->gold, gems: $this->character->gems);
            }
        } catch (\Exception $e) {
            $this->actionType = 'error';
            $this->actionMessage = $e->getMessage();
            $this->dispatch('play-audio', type: 'enchant-fail');
        }
    }

    public function reroll(string $currencyType, RerollEnchantments $rerollAction)
    {
        $this->clearEnchantMessages();

        if (!$this->activeItemId) return;

        $item = ItemInstance::find($this->activeItemId);
        if (!$item) return;

        try {
            $result = $rerollAction->execute($item, $this->character, $currencyType);

            if ($result->isError()) {
                $this->actionType = 'error';
                $this->actionMessage = $result->getErrorMessage();
                $this->dispatch('play-audio', type: 'enchant-fail');
            } else {
                $payload = $result->getPayload();
                if ($payload['success'] ?? false) {
                    $this->actionType = 'success';
                    $this->actionMessage = $payload['message'] ?? 'Bonusy przedmiotu zostały wylosowane na nowo!';
                    $this->dispatch('play-audio', type: 'enchant-success');
                } else {
                    $this->actionType = 'error';
                    $this->actionMessage = $payload['message'] ?? 'Operacja nie powiodła się.';
                    $this->dispatch('play-audio', type: 'enchant-fail');
                }
                $this->character->refresh();
                $this->dispatch('stats-updated', gold: $this->character->gold, gems: $this->character->gems);
            }
        } catch (\Exception $e) {
            $this->actionType = 'error';
            $this->actionMessage = $e->getMessage();
            $this->dispatch('play-audio', type: 'enchant-fail');
        }
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
            ->whereHas('template')
            ->with('template')
            ->get()
            ->filter(function($mi) {
                return $mi->template && (!$mi->is_limited || $mi->sold_quantity < $mi->max_quantity);
            });

        $shopPrices = [];
        foreach($shopItems as $mi) {
            if (!$mi->template) continue;
            $shopPrices[$mi->id] = $shopService->getBuyPrice($mi->template);
        }


        // Crafting Recipes
        $recipes = ItemRecipe::with('resultItemTemplate')->whereHas('resultItemTemplate', function($q) {
            $q->where('type', 'consumable');
        })->get();

        $inventory = $this->character->materialStashItems()->get()->merge($this->character->inventoryItems()->get());

        $preparedRecipes = [];
        foreach ($recipes as $recipe) {
            $preparedIngredients = [];
            $canCraft = $this->character->gold >= $recipe->gold_cost;

            foreach ($recipe->ingredients as $ing) {
                $mat = ItemTemplate::find($ing['template_id']);
                $owned = $inventory->where('template_id', $ing['template_id'])->sum('stack_size');
                $req = $ing['quantity'];

                if ($owned < $req) $canCraft = false;

                $dropMonsters = [];
                if ($mat) {
                    $dropMonsters = \App\Infrastructure\Persistence\Monster::whereHas('lootTable.entries', function($q) use ($mat) {
                        $q->where('ref_ulid', $mat->id);
                    })->pluck('name')->toArray();
                }

                $preparedIngredients[] = [
                    'name' => $mat ? $mat->name : 'Nieznany',
                    'icon' => $mat ? $mat->icon : null,
                    'owned' => $owned,
                    'required' => $req,
                    'ok' => $owned >= $req,
                    'dropped_by' => $dropMonsters,
                ];
            }

            $preparedRecipes[] = [
                'id' => $recipe->id,
                'result_name' => $recipe->resultItemTemplate->name ?? 'Nieznany',
                'result_icon' => $recipe->resultItemTemplate->icon ?? null,
                'result_description' => $recipe->resultItemTemplate->description ?? null,
                'gold_cost' => $recipe->gold_cost,
                'ingredients' => $preparedIngredients,
                'can_craft' => $canCraft,
            ];
        }

        // Zaczarowywalne przedmioty (broń, zbroja, biżuteria)
        $enchantableItems = $this->character->inventoryItems()->whereHas('template', function($q) {
            $q->whereIn('type', ['weapon', 'armor', 'accessory']);
        })->get()->merge(
            $this->character->equippedItems()->whereHas('template', function($q) {
                $q->whereIn('type', ['weapon', 'armor', 'accessory']);
            })->get()
        );

        $equipped = [];
        foreach ($this->character->equippedItems()->with('template')->get() as $eq) {
            $equipped[$eq->template->slot] = $eq;
        }

        $activeItem = $this->activeItemId ? $enchantableItems->firstWhere('id', $this->activeItemId) : null;

        // Lista możliwych zaklęć (klucz => [min, max]) dla typu wybranego przedmiotu -
        // wyświetlana na dole karty "Stół do Zaklinania" (patrz witch.blade.php).
        $possibleBonuses = $activeItem
            ? app(EnchantmentStrategy::class)->getPossibleBonuses($activeItem)
            : [];

        return view('livewire.city.witch', [
            'canBuySpecial' => $canBuySpecial,
            'specialCooldown' => $specialCooldown,
            'shopItems' => $shopItems,
            'shopPrices' => $shopPrices,
            'recipes' => $preparedRecipes,
            'enchantableItems' => $enchantableItems,
            'activeItem' => $activeItem,
            'possibleBonuses' => $possibleBonuses,
            'equipped' => $equipped,
        ]);
    }
}
