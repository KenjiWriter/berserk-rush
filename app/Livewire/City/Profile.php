<?php

namespace App\Livewire\City;

use App\Application\Items\EquipItem;
use App\Application\Items\UnequipItem;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\ItemInstance;
use App\Infrastructure\Persistence\Pet;
use App\Infrastructure\Persistence\CharacterIncubator;
use App\Application\Pets\IncubatorService;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\Attributes\On;

class Profile extends Component
{
    public Character $character;
    public string $activeTab = 'attributes';
    public string $inventoryTab = 'backpack'; // 'backpack', 'stash', or 'materials'
    public string $inventoryFilter = 'all';

    // Market Selling
    public ?string $sellingItemUlid = null;
    public $sellPrice = 100;
    public $sellCurrency = 'gold';
    public $sellDuration = 24;
    public $sellQuantity = 1;
    public $sellItemStackSize = 1;

    #[On('tutorial-completed')]
    #[On('skill-equipped')]
    public function refreshProfile()
    {
        $this->character->refresh();
        $this->character->load('equippedSkills.skill');
    }

    // Wysyłane np. przez panel admina na czacie, gdy ktoś przyzna punkty postaci z zewnątrz.
    #[On('stats-saved')]
    public function refreshAttributes(): void
    {
        $this->character->refresh();
    }

    public function mount(Character $character)
    {
        $this->character = $character;
        $this->character->syncMissingPoints();
        $this->character->load('equippedSkills.skill');
        
        // Ensure character belongs to user
        if (auth()->id() !== $character->user_id) {
            abort(403);
        }
    }

    public function setTab(string $tab)
    {
        $this->activeTab = $tab;
    }

    public function setInventoryFilter(string $filter)
    {
        $this->inventoryFilter = $filter;
    }

    public function stackItems()
    {
        $character = $this->character;
        $allItems = $character->inventoryItems()->get()->merge($character->materialStashItems()->get());
        
        $groups = $allItems->groupBy(function($item) {
            return $item->template_id . '_' . $item->location;
        });
        
        foreach ($groups as $groupKey => $items) {
            $template = $items->first()->template;
            if ($template && in_array($template->type, ['material', 'consumable', 'currency'])) {
                if ($items->count() > 1) {
                    $firstItem = $items->first();
                    $totalStack = $items->sum('stack_size');
                    
                    $firstItem->stack_size = $totalStack;
                    $firstItem->save();
                    
                    foreach ($items->skip(1) as $item) {
                        $item->delete();
                    }
                }
            }
        }
        
        $this->dispatch('notify', type: 'success', message: 'Ekwipunek i magazyn materiałów zostały uporządkowane.');
        $this->character->refresh();
        $this->character->load('equippedSkills.skill');
    }

    public function unequipSkill(string $characterSkillId)
    {
        $characterSkill = \App\Infrastructure\Persistence\CharacterCombatSkill::where('character_id', $this->character->id)
            ->where('id', $characterSkillId)
            ->first();

        if ($characterSkill && $characterSkill->is_equipped) {
            $characterSkill->is_equipped = false;
            $characterSkill->save();
            $this->dispatch('notify', type: 'success', message: 'Umiejętność zdjęta.');
            $this->dispatch('skill-equipped');
            $this->character->refresh();
            $this->character->load('equippedSkills.skill');
        }
    }

    public function equipItem(string $itemUlid, EquipItem $equipAction)
    {
        $item = ItemInstance::with('template')->find($itemUlid);
        
        if (!$item) {
            $this->dispatch('notify', type: 'error', message: 'Przedmiot nie istnieje.');
            return;
        }

        $result = $equipAction->handle($this->character, $item);

        if ($result->isOk()) {
            $this->dispatch('notify', type: 'success', message: 'Przedmiot założony pomyślnie.');
            $this->dispatch('play-audio', type: 'equip');
            
            $user = auth()->user();
            if ($user && $user->game_stage == 6 && $item->template->type === 'weapon') {
                $user->game_stage = 7;
                $user->save();
            }

            $this->character->clearStatsCache();
            $this->character->refresh();
            $this->character->load(['equippedItems.template', 'inventoryItems.template']);
        } else {
            $this->dispatch('notify', type: 'error', message: $result->getErrorMessage());
        }
    }

    public function backToHub(): void
    {
        $this->redirect(route('city.hub', $this->character), navigate: true);
    }

    public function unequipItem(string $itemUlid, UnequipItem $unequipAction)
    {
        $item = ItemInstance::find($itemUlid);
        
        if (!$item) {
            $this->dispatch('notify', type: 'error', message: 'Przedmiot nie istnieje.');
            return;
        }

        $result = $unequipAction->handle($this->character, $item);

        if ($result->isOk()) {
            $this->dispatch('notify', type: 'success', message: 'Przedmiot zdjęty pomyślnie.');
            $this->dispatch('play-audio', type: 'unequip');
            $this->character->clearStatsCache();
            $this->character->refresh();
            $this->character->load(['equippedItems.template', 'inventoryItems.template']);
        } else {
            $this->dispatch('notify', type: 'error', message: $result->getErrorMessage());
        }
    }

    public function consumeItem(string $itemUlid, \App\Domain\Items\Actions\ConsumeItemAction $consumeAction)
    {
        $result = $consumeAction->execute($this->character, $itemUlid);

        if ($result['success']) {
            $this->dispatch('notify', type: 'success', message: $result['message']);
            $this->dispatch('play-audio', type: 'equip'); // Or some consume sound
            $this->dispatch('buff-applied');
            $this->character->refresh();
        } else {
            $this->dispatch('notify', type: 'error', message: $result['message']);
        }
    }

    // --- PETS & INCUBATOR LOGIC ---
    public function placeEgg(string $eggItemInstanceId): void
    {
        $service = app(IncubatorService::class);
        $result = $service->placeEgg($this->character, $eggItemInstanceId);

        if ($result->isError()) {
            $this->dispatch('notify', type: 'error', message: $result->getErrorMessage());
            return;
        }

        $this->dispatch('notify', type: 'success', message: 'Jajko zostało umieszczone w inkubatorze!');
    }

    public function hatchEgg(): void
    {
        $service = app(IncubatorService::class);
        $result = $service->hatchEgg($this->character);

        if ($result->isError()) {
            $this->dispatch('notify', type: 'error', message: $result->getErrorMessage());
            return;
        }

        $pet = $result->getPayload();
        $this->dispatch('notify', type: 'success', message: "Wykluł się nowy pet: {$pet->name} ({$pet->rarity})!");
        $this->character->refresh();
    }

    public function toggleEquipPet(int $petId): void
    {
        $service = app(IncubatorService::class);
        $result = $service->toggleEquipPet($this->character, $petId);

        if ($result->isError()) {
            $this->dispatch('notify', type: 'error', message: $result->getErrorMessage());
            return;
        }

        $payload = $result->getPayload();
        $action = $payload['action'] ?? '';
        $pet = $payload['pet'] ?? null;

        if ($action === 'equipped') {
            $this->dispatch('notify', type: 'success', message: "Pet {$pet->name} został założony!");
        } else {
            $this->dispatch('notify', type: 'success', message: "Pet {$pet->name} został zdjęty.");
        }

        $this->character->clearStatsCache();
        $this->character->refresh();
    }
    // -----------------------------

    /**
     * Dodaje punkty do jednego atrybutu w pojedynczym, natychmiastowym żądaniu -
     * bez buforowania po stronie klienta. Każde kliknięcie to osobny round-trip,
     * a przyciski są blokowane (wire:loading) na czas trwania żądania, więc nie
     * ma lokalnej kopii stanu, która mogłaby rozjechać się z bazą danych.
     */
    public function addAttributePoints(string $stat, int $amount, \App\Application\Characters\AllocateAttributesAction $action)
    {
        if (!in_array($stat, ['str', 'int', 'vit', 'agi'], true)) {
            return;
        }

        $result = $action->execute($this->character, [$stat => $amount]);

        if ($result->isOk()) {
            /** @var Character $updatedCharacter */
            $updatedCharacter = $result->getPayload();
            $this->character = $updatedCharacter;

            $user = auth()->user();
            if ($user && $user->game_stage == 14) {
                $user->game_stage = 15;
                $user->save();
            }

            $this->dispatch('play-audio', type: 'stat');
        } else {
            $this->character->refresh();
            $this->dispatch('notify', type: 'error', message: $result->getErrorMessage());
        }
    }

    public function openSellModal(string $itemUlid)
    {
        $this->sellingItemUlid = $itemUlid;
        $this->sellPrice = 100;
        $this->sellCurrency = 'gold';
        $this->sellDuration = 24;
        $this->sellQuantity = 1;

        $item = ItemInstance::find($itemUlid);
        $this->sellItemStackSize = $item ? (int) ($item->stack_size ?? 1) : 1;
    }

    public function closeSellModal()
    {
        $this->sellingItemUlid = null;
    }

    public function sellItem(\App\Application\Economy\Actions\CreateMarketListingAction $action)
    {
        if (!$this->sellingItemUlid) return;

        $price = (int) $this->sellPrice;
        if ($price < 1) {
            $this->dispatch('notify', type: 'error', message: 'Cena musi być większa od zera.');
            return;
        }
        if ($price > 999_999_999) {
            $this->dispatch('notify', type: 'error', message: 'Maksymalna cena to 999 999 999.');
            return;
        }

        $item = ItemInstance::find($this->sellingItemUlid);
        if (!$item) {
            $this->dispatch('notify', type: 'error', message: 'Przedmiot nie istnieje.');
            return;
        }

        $quantity = max(1, min((int) $this->sellQuantity, (int) ($item->stack_size ?? 1)));

        $result = $action->execute($this->character, $item, $price, (string) $this->sellCurrency, (int) $this->sellDuration, $quantity);

        if ($result->isOk()) {
            $this->dispatch('notify', type: 'success', message: 'Przedmiot wystawiony na market!');
            $this->closeSellModal();
            $this->character->refresh();
        } else {
            $this->dispatch('notify', type: 'error', message: $result->getErrorMessage());
        }
    }

    public function changeAvatar(string $avatar, bool $isPremium = false)
    {
        $user = auth()->user();

        if ($isPremium) {
            if (!in_array($avatar, $user->unlocked_avatars ?? [])) {
                $this->dispatch('notify', type: 'error', message: 'Nie posiadasz tego avatara!');
                return;
            }
            $this->character->avatar = 'premium/' . $avatar;
        } else {
            $avatarPath = public_path('img/avatars/' . $avatar . '.png');
            if (!\Illuminate\Support\Facades\File::exists($avatarPath) || $avatar === 'plate') {
                $this->dispatch('notify', type: 'error', message: 'Niedozwolony avatar.');
                return;
            }
            $this->character->avatar = $avatar;
        }

        $this->character->save();
        $this->dispatch('notify', type: 'success', message: 'Avatar zmieniony pomyślnie!');
    }

    public function setInventoryTab(string $tab): void
    {
        $this->inventoryTab = $tab;
    }

    public function moveToStash(string $itemUlid, \App\Application\Storage\PlayerStashService $stashService): void
    {
        $item = ItemInstance::find($itemUlid);
        if (!$item) {
            $this->dispatch('notify', type: 'error', message: 'Przedmiot nie istnieje.');
            return;
        }

        $result = $stashService->deposit($this->character, $item);
        if ($result->isOk()) {
            $this->dispatch('notify', type: 'success', message: 'Przedmiot przeniesiony do magazynu gracza!');
            $this->character->refresh();
        } else {
            $this->dispatch('notify', type: 'error', message: $result->getErrorMessage());
        }
    }

    public function withdrawFromStash(string $itemUlid, \App\Application\Storage\PlayerStashService $stashService): void
    {
        $item = ItemInstance::find($itemUlid);
        if (!$item) {
            $this->dispatch('notify', type: 'error', message: 'Przedmiot nie istnieje.');
            return;
        }

        $result = $stashService->withdraw($this->character, $item);
        if ($result->isOk()) {
            $this->dispatch('notify', type: 'success', message: 'Przedmiot przeniesiony do plecaka!');
            $this->character->refresh();
        } else {
            $this->dispatch('notify', type: 'error', message: $result->getErrorMessage());
        }
    }

    public function expandStash(\App\Application\Storage\PlayerStashService $stashService): void
    {
        $user = auth()->user();
        if (!$user) return;

        $result = $stashService->expandStash($user);
        if ($result->isOk()) {
            $this->dispatch('notify', type: 'success', message: "Magazyn powiększony! Nowa pojemność: {$user->stash_slots} sloty.");
            $this->dispatch('play-audio', type: 'stat');
        } else {
            $this->dispatch('notify', type: 'error', message: $result->getErrorMessage());
        }
    }

    public function render()
    {
        $this->character->loadMissing(['equippedItems.template', 'inventoryItems.template', 'equippedSkills.skill']);

        $equipped = [];
        foreach ($this->character->equippedItems as $item) {
            $slot = $item->template->slot;
            if ($slot) {
                $equipped[$slot] = $item;
            }
        }
        
        $totalAttributes = $this->character->getTotalAttributes();
        
        // Derived stats
        $str = $totalAttributes['str'] ?? 0;
        $int = $totalAttributes['int'] ?? 0;
        $vit = $totalAttributes['vit'] ?? 0;
        $agi = $totalAttributes['agi'] ?? 0;
        $level = $this->character->level;
        
        $eqStats = $this->character->getEquipmentStats();
        
        $statBonus = $this->character->getAttributeAttackBonus();
        $baseDmg = 10 + $statBonus + ($level * 1);
        $magicDmg = ($int * 2) + ($level * 1);

        $weaponAtkMin = ($eqStats['attack_min'] ?? 0) + ($eqStats['magic_attack_min'] ?? 0);
        $weaponAtkMax = ($eqStats['attack_max'] ?? 0) + ($eqStats['magic_attack_max'] ?? 0);

        $derivedStats = [
            'max_hp' => 100 + ($vit * 10) + ($level * 5) + $eqStats['hp_bonus'],
            'base_damage_min' => $baseDmg + $weaponAtkMin,
            'base_damage_max' => $baseDmg + $weaponAtkMax,
            'magic_damage_min' => $magicDmg + $eqStats['magic_attack_min'],
            'magic_damage_max' => $magicDmg + $eqStats['magic_attack_max'],
            'crit_chance' => min(50, 5 + ($agi * 0.5) + $eqStats['crit_chance']),
            'dodge_chance' => min(50, 2 + ($agi * 0.3)),
            'damage_reduction' => ($vit * 1) + $eqStats['defense'],
        ];

        $inventory = $this->character->inventoryItems;
        if ($this->inventoryFilter !== 'all') {
            $inventory = $inventory->filter(function ($item) {
                return $item->template->type === $this->inventoryFilter;
            });
        }
        // Safety cap to prevent memory exhaustion if a character has an excessive number of items in DB
        $inventory = $inventory->take(64);

        $user = auth()->user();
        $playerStashItems = $user ? $user->playerStashItems()->with('template')->get() : collect();
        if ($this->inventoryFilter !== 'all' && $playerStashItems->isNotEmpty()) {
            $playerStashItems = $playerStashItems->filter(function ($item) {
                return $item->template->type === $this->inventoryFilter;
            });
        }
        $playerStashItems = $playerStashItems->take(64);


        $pets = Pet::where('character_id', $this->character->id)
            ->orderByDesc('is_equipped')
            ->orderByDesc('rarity')
            ->get();

        $incubator = CharacterIncubator::where('character_id', $this->character->id)->first();

        $eggs = $this->character->inventoryItems->filter(function($item) {
            return $item->template->type === 'egg';
        });

        $baseAvatars = [];
        $avatarPath = public_path('img/avatars');
        if (\Illuminate\Support\Facades\File::exists($avatarPath)) {
            $files = \Illuminate\Support\Facades\File::files($avatarPath);
            foreach ($files as $file) {
                if ($file->getFilename() === 'plate.png') continue;
                if (in_array($file->getExtension(), ['png', 'jpg', 'jpeg', 'webp'])) {
                    $baseAvatars[] = $file->getFilenameWithoutExtension();
                }
            }
        }

        $materialStashItems = $this->character->materialStashItems->take(100);

        $activeWeaponType = $this->character->getEquippedWeaponType();
        $activeScalingStats = match ($activeWeaponType) {
            'bow', 'sword', 'dagger' => ['str', 'agi'],
            'bell' => ['str', 'int'],
            'wand' => ['int'],
            default => ['str'],
        };

        return view('livewire.city.profile', [
            'equipped' => $equipped,
            'inventory' => $inventory,
            'playerStashItems' => $playerStashItems,
            'materialStashItems' => $materialStashItems,
            'materialStashCount' => $this->character->getMaterialStashCount(),
            'materialStashCapacity' => $this->character->getMaterialStashCapacity(),
            'stashCapacity' => $user?->getStashCapacity() ?? 2,
            'backpackCount' => $this->character->getBackpackCount(),
            'backpackCapacity' => $this->character->getBackpackCapacity(),
            'totalAttributes' => $totalAttributes,
            'baseAttributes' => $this->character->getBaseAttributes(),
            'bonusAttributes' => $this->character->getBonusAttributes(),
            'derivedStats' => $derivedStats,
            'activeWeaponType' => $activeWeaponType,
            'activeScalingStats' => $activeScalingStats,
            'pets' => $pets,
            'incubator' => $incubator,
            'eggs' => $eggs,
            'baseAvatars' => $baseAvatars,
        ]);
    }
}
