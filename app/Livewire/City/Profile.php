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
    public string $inventoryFilter = 'all';

    // Market Selling
    public ?string $sellingItemUlid = null;
    public int $sellPrice = 100;
    public string $sellCurrency = 'gold';
    public int $sellDuration = 24;

    #[On('tutorial-completed')]
    #[On('skill-equipped')]
    public function refreshProfile()
    {
        $this->character->refresh();
        $this->character->load('equippedSkills.skill');
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
        $inventory = $character->inventoryItems()->get();
        
        $groups = $inventory->groupBy('template_id');
        
        foreach ($groups as $templateId => $items) {
            $template = $items->first()->template;
            // Only stack specific types
            if (in_array($template->type, ['material', 'consumable', 'currency'])) {
                if ($items->count() > 1) {
                    $firstItem = $items->first();
                    $totalStack = $items->sum('stack_size');
                    
                    $firstItem->stack_size = $totalStack;
                    $firstItem->save();
                    
                    // delete other items
                    foreach ($items->skip(1) as $item) {
                        $item->delete();
                    }
                }
            }
        }
        
        $this->dispatch('notify', type: 'success', message: 'Ekwipunek został uporządkowany.');
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

            $this->character->refresh();
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
            $this->character->refresh();
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

    public function saveAttributes(array $addedStats)
    {
        $validAttributes = ['str', 'int', 'vit', 'agi'];
        $totalRequested = 0;
        
        foreach ($validAttributes as $stat) {
            $val = (int)($addedStats[$stat] ?? 0);
            if ($val < 0) return; // invalid
            $totalRequested += $val;
        }

        $points = $this->character->character_points ?? 0;
        
        if ($totalRequested > $points || $totalRequested <= 0) {
            $this->dispatch('stats-saved', points: $points);
            return;
        }

        $attributes = $this->character->getAttribute('attributes') ?? ['str' => 0, 'int' => 0, 'vit' => 0, 'agi' => 0];
        
        foreach ($validAttributes as $stat) {
            $val = (int)($addedStats[$stat] ?? 0);
            $attributes[$stat] = ($attributes[$stat] ?? 0) + $val;
        }

        $this->character->attributes = $attributes;
        $this->character->character_points = $points - $totalRequested;

        $user = auth()->user();
        if ($user && $user->game_stage == 14) {
            $user->game_stage = 15;
            $user->save();
        }

        $this->character->save();

        $this->dispatch('notify', type: 'success', message: "Rozdano punkty atrybutów: {$totalRequested}.");
        $this->dispatch('play-audio', type: 'stat');
        $this->dispatch('stats-saved', points: $this->character->character_points);
    }

    public function openSellModal(string $itemUlid)
    {
        $this->sellingItemUlid = $itemUlid;
        $this->sellPrice = 100;
        $this->sellCurrency = 'gold';
        $this->sellDuration = 24;
    }

    public function closeSellModal()
    {
        $this->sellingItemUlid = null;
    }

    public function sellItem(\App\Application\Economy\Actions\CreateMarketListingAction $action)
    {
        if (!$this->sellingItemUlid) return;
        
        $item = ItemInstance::find($this->sellingItemUlid);
        if (!$item) {
            $this->dispatch('notify', type: 'error', message: 'Przedmiot nie istnieje.');
            return;
        }

        $result = $action->execute($this->character, $item, (int) $this->sellPrice, $this->sellCurrency, (int) $this->sellDuration);
        
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
        
        $baseDmg = 10 + ($str * 2) + ($level * 1);
        $magicDmg = ($int * 2) + ($level * 1);

        $derivedStats = [
            'max_hp' => 100 + ($vit * 10) + ($level * 5) + $eqStats['hp_bonus'],
            'base_damage_min' => $baseDmg + $eqStats['attack_min'],
            'base_damage_max' => $baseDmg + $eqStats['attack_max'],
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

        return view('livewire.city.profile', [
            'equipped' => $equipped,
            'inventory' => $inventory,
            'totalAttributes' => $totalAttributes,
            'derivedStats' => $derivedStats,
            'pets' => $pets,
            'incubator' => $incubator,
            'eggs' => $eggs,
            'baseAvatars' => $baseAvatars,
        ]);
    }
}
