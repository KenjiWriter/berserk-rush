<?php

namespace App\Livewire\City;

use App\Application\Items\EquipItem;
use App\Application\Items\EquipmentSetService;
use App\Application\Items\ItemSorter;
use App\Application\Items\UnequipItem;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\CharacterEquipmentSetItem;
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

    // Zaznacz wiele -> Przenieś do magazynu (plecak i materiały)
    public bool $bulkStashMode = false;
    public array $selectedStashItemIds = [];

    // Market Selling
    public ?string $sellingItemUlid = null;
    public $sellPrice = 100;
    public $sellCurrency = 'gold';
    public $sellDuration = 24;
    public $sellQuantity = 1;
    public $sellItemStackSize = 1;

    // Name Change
    public string $newName = '';
    public bool $showNameChangeModal = false;

    // Mirror System State
    public ?int $selectedMirrorMapId = null;
    public int $selectedMirrorDurationHours = 1;
    public ?array $claimedRewardsSummary = null;

    // Pets & Synthesizer State
    public array $selectedSynthesizerPetIds = [];
    public ?int $feedingPetId = null;
    public array $selectedFeedItemIds = [];
    public ?string $errorMessage = null;
    public ?string $successMessage = null;

    #[On('tutorial-completed')]
    #[On('skill-equipped')]
    #[On('inventory-updated')]
    public function refreshProfile()
    {
        $this->character->refresh();
        $this->character->unsetRelations();
        $this->character->load(['equippedSkills.skill', 'inventoryItems.template', 'materialStashItems.template', 'equippedItems.template']);
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
        if ($tab === 'mirror' && $this->character->level < 30) {
            $this->dispatch('notify', type: 'error', message: 'Lustro odblokowuje się na 30 poziomie postaci.');
            return;
        }

        $this->activeTab = $tab;
    }

    public function setInventoryFilter(string $filter)
    {
        $this->inventoryFilter = $filter;
    }

    public function stackItems()
    {
        $this->character->consolidateStackableItems();
        
        $this->dispatch('notify', type: 'success', message: 'Ekwipunek i magazyn materiałów zostały uporządkowane.');
        $this->character->refresh();
        $this->character->load('equippedSkills.skill');
    }

    public function sortInventory()
    {
        $this->dispatch('notify', type: 'success', message: 'Przedmioty zostały posortowane wg kategorii i mocy.');
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

    public const EQUIPMENT_SET_LABELS = [
        CharacterEquipmentSetItem::SET_PVP => 'Arena PvP',
        CharacterEquipmentSetItem::SET_GUILD_WAR => 'Wojna Gildii',
        CharacterEquipmentSetItem::SET_1 => 'Set 1',
        CharacterEquipmentSetItem::SET_2 => 'Set 2',
        CharacterEquipmentSetItem::SET_3 => 'Set 3',
    ];

    public function saveEquipmentSet(string $setType, EquipmentSetService $service)
    {
        $result = $service->saveCurrentAsSet($this->character, $setType);

        if ($result->isOk()) {
            $label = self::EQUIPMENT_SET_LABELS[$setType] ?? $setType;
            $this->dispatch('notify', type: 'success', message: "Zapisano aktualny ekwipunek jako zestaw: {$label}.");
            $this->character->refresh();
        } else {
            $this->dispatch('notify', type: 'error', message: $result->getErrorMessage());
        }
    }

    public function applyEquipmentSet(string $setType, EquipmentSetService $service)
    {
        $result = $service->applySet($this->character, $setType);

        if ($result->isOk()) {
            $label = self::EQUIPMENT_SET_LABELS[$setType] ?? $setType;
            $this->dispatch('notify', type: 'success', message: "Założono zestaw: {$label}.");
            $this->dispatch('play-audio', type: 'equip');
            $this->character->refresh();
            $this->character->load(['equippedItems.template', 'inventoryItems.template']);
        } else {
            $this->dispatch('notify', type: 'error', message: $result->getErrorMessage());
        }
    }

    public function consumeItem(string $itemUlid, int $count = 1)
    {
        $item = ItemInstance::with('template')->find($itemUlid);
        if ($item && $item->template && $item->template->sub_type === 'chest') {
            $this->dispatch('open-case-modal', itemInstanceId: $itemUlid, count: $count, characterId: $this->character->id);
            return;
        }

        $consumeAction = app(\App\Domain\Items\Actions\ConsumeItemAction::class);
        $result = $consumeAction->execute($this->character, $itemUlid);

        if ($result['success']) {
            $this->dispatch('notify', type: 'success', message: $result['message']);
            $this->dispatch('play-audio', type: 'equip');
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

    public function toggleSynthesizerPet(int $petId): void
    {
        $this->errorMessage = null;
        $this->successMessage = null;

        if (in_array($petId, $this->selectedSynthesizerPetIds)) {
            $this->selectedSynthesizerPetIds = array_values(array_diff($this->selectedSynthesizerPetIds, [$petId]));
            return;
        }

        if (count($this->selectedSynthesizerPetIds) >= 3) {
            $this->dispatch('notify', type: 'error', message: 'Możesz wybrać maksymalnie 3 chowańce do transmutacji.');
            return;
        }

        $pet = Pet::where('id', $petId)->where('character_id', $this->character->id)->first();
        if (!$pet || $pet->is_equipped) {
            $this->dispatch('notify', type: 'error', message: 'Nie możesz użyć założonego lub nieistniejącego chowańca.');
            return;
        }

        if (!empty($this->selectedSynthesizerPetIds)) {
            $firstPet = Pet::find($this->selectedSynthesizerPetIds[0]);
            if ($firstPet && $firstPet->rarity !== $pet->rarity) {
                $this->dispatch('notify', type: 'error', message: 'Wszystkie 3 chowańce muszą posiadać ten sam stopień rzadkości (' . ucfirst($firstPet->rarity) . ')!');
                return;
            }
        }

        if ($pet->rarity === 'legendary') {
            $this->dispatch('notify', type: 'error', message: 'Chowańce rangi Legendarnej osiągnęły już najwyższą klasę.');
            return;
        }

        $this->selectedSynthesizerPetIds[] = $petId;
    }

    public function clearSynthesizer(): void
    {
        $this->selectedSynthesizerPetIds = [];
    }

    public function synthesizePets(): void
    {
        $service = app(\App\Application\Pets\PetService::class);
        $result = $service->synthesizePets($this->character, $this->selectedSynthesizerPetIds);

        if ($result->isError()) {
            $this->dispatch('notify', type: 'error', message: $result->getErrorMessage());
            return;
        }

        $payload = $result->getPayload();
        $this->selectedSynthesizerPetIds = [];

        if (!($payload['success'] ?? false)) {
            $this->dispatch('notify', type: 'error', message: $payload['message'] ?? 'Transmutacja nie powiodła się!');
        } else {
            $this->dispatch('notify', type: 'success', message: $payload['message'] ?? 'Transmutacja zakończona sukcesem!');
        }

        $this->character->refresh();
    }

    public function openFeedModal(int $petId): void
    {
        $this->feedingPetId = $petId;
        $this->selectedFeedItemIds = [];
    }

    public function closeFeedModal(): void
    {
        $this->feedingPetId = null;
        $this->selectedFeedItemIds = [];
    }

    public function toggleFeedItem(string $itemId): void
    {
        if (in_array($itemId, $this->selectedFeedItemIds)) {
            $this->selectedFeedItemIds = array_values(array_diff($this->selectedFeedItemIds, [$itemId]));
        } else {
            $this->selectedFeedItemIds[] = $itemId;
        }
    }

    public function selectAllFeedItems(): void
    {
        $inventoryItems = $this->character->inventoryItems->pluck('id')->toArray();

        if (count($this->selectedFeedItemIds) === count($inventoryItems)) {
            $this->selectedFeedItemIds = [];
        } else {
            $this->selectedFeedItemIds = $inventoryItems;
        }
    }

    public function feedPet(): void
    {
        if (!$this->feedingPetId) {
            $this->dispatch('notify', type: 'error', message: 'Wybierz chowańca do nakarmienia.');
            return;
        }

        $service = app(\App\Application\Pets\PetService::class);
        $result = $service->feedPet($this->character, $this->feedingPetId, $this->selectedFeedItemIds);

        if ($result->isError()) {
            $this->dispatch('notify', type: 'error', message: $result->getErrorMessage());
            return;
        }

        $payload = $result->getPayload();
        $this->selectedFeedItemIds = [];

        $msg = "Przekazano pożywienie! Chowaniec otrzymał +{$payload['gainedExp']} EXP.";
        if ($payload['leveledUp'] ?? false) {
            $msg .= " Awans na Poziom {$payload['newLevel']}!";
        }
        $this->dispatch('notify', type: 'success', message: $msg);
        $this->character->refresh();
    }
    // -----------------------------

    // --- NAME CHANGE LOGIC ---
    public function openNameChangeModal(): void
    {
        $this->newName = $this->character->name;
        $this->showNameChangeModal = true;
    }

    public function closeNameChangeModal(): void
    {
        $this->showNameChangeModal = false;
        $this->newName = '';
    }

    public function changeCharacterName(): void
    {
        $this->newName = trim($this->newName);

        if (mb_strlen($this->newName) < 3 || mb_strlen($this->newName) > 16) {
            $this->dispatch('notify', type: 'error', message: 'Nick postaci musi zawierać od 3 do 16 znaków.');
            return;
        }

        if (!preg_match('/^[a-zA-Z0-9_\-\s]+$/u', $this->newName)) {
            $this->dispatch('notify', type: 'error', message: 'Nick zawiera niedozwolone znaki (używaj tylko liter, cyfr, myślników i spacji).');
            return;
        }

        if (strcasecmp($this->newName, $this->character->name) === 0) {
            $this->dispatch('notify', type: 'error', message: 'Nowy nick jest taki sam jak Twój obecny nick.');
            return;
        }

        // Check if name is taken by another character
        $exists = Character::where('name', $this->newName)
            ->where('id', '!=', $this->character->id)
            ->exists();

        if ($exists) {
            $this->dispatch('notify', type: 'error', message: 'Nazwa postaci "' . $this->newName . '" jest już zajęta.');
            return;
        }

        $cost = ($this->character->name_changes_count ?? 0) > 0 ? 200 : 0;

        $user = auth()->user();
        if ($cost > 0 && ($user->gems < $cost)) {
            $this->dispatch('notify', type: 'error', message: 'Nie posiadasz wystarczającej liczby Gemów (wymagane: 200 Gemów).');
            $this->dispatch('not-enough-gems');
            return;
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($user, $cost) {
            if ($cost > 0) {
                $user->decrement('gems', $cost);
            }

            $this->character->name = $this->newName;
            $this->character->name_changes_count = ($this->character->name_changes_count ?? 0) + 1;
            $this->character->save();
        });

        $this->dispatch('notify', type: 'success', message: "Twój nick został pomyślnie zmieniony na {$this->newName}!");
        $this->dispatch('stats-updated', gold: $this->character->gold, gems: auth()->user()->refresh()->gems);
        $this->dispatch('play-audio', type: 'level-up');
        $this->showNameChangeModal = false;
        $this->character->refresh();
    }
    // -------------------------

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

        $price = \App\Support\PriceParser::parse($this->sellPrice);
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
        $this->bulkStashMode = false;
        $this->selectedStashItemIds = [];
    }

    public function toggleBulkStashMode(): void
    {
        $this->bulkStashMode = !$this->bulkStashMode;
        $this->selectedStashItemIds = [];
        $this->dispatch('close-all-tooltips');
    }

    public function toggleSelectStashItem(string $itemUlid): void
    {
        if (in_array($itemUlid, $this->selectedStashItemIds)) {
            $this->selectedStashItemIds = array_values(array_diff($this->selectedStashItemIds, [$itemUlid]));
        } else {
            $this->selectedStashItemIds[] = $itemUlid;
        }
    }

    public function selectAllForStash(): void
    {
        $items = $this->inventoryTab === 'materials'
            ? $this->character->materialStashItems()->get()
            : $this->character->inventoryItems()->get();

        $allIds = $items->pluck('id')->toArray();
        if (count($this->selectedStashItemIds) === count($allIds)) {
            $this->selectedStashItemIds = [];
        } else {
            $this->selectedStashItemIds = $allIds;
        }
    }

    public function clearStashSelection(): void
    {
        $this->selectedStashItemIds = [];
    }

    public function moveSelectedToStash(\App\Application\Storage\PlayerStashService $stashService): void
    {
        if (empty($this->selectedStashItemIds)) {
            $this->dispatch('notify', type: 'error', message: 'Wybierz przedmioty do przeniesienia.');
            return;
        }

        $moved = 0;
        $lastError = null;
        foreach ($this->selectedStashItemIds as $itemUlid) {
            $item = ItemInstance::find($itemUlid);
            if (!$item) continue;

            $result = $stashService->deposit($this->character, $item);
            if ($result->isOk()) {
                $moved++;
            } else {
                $lastError = $result->getErrorMessage();
                break; // magazyn pełny lub inny błąd blokujący - przerwij dalsze przenoszenie
            }
        }

        if ($moved > 0) {
            $this->dispatch('notify', type: 'success', message: "Przeniesiono {$moved} przedmiot(ów) do magazynu gracza!");
        }
        if ($lastError) {
            $this->dispatch('notify', type: 'error', message: $lastError);
        }

        $this->selectedStashItemIds = [];
        $this->character->refresh();
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

        $hasPremium = auth()->user()?->hasPremium() ?? false;
        $savedSetItems = $this->character->equipmentSetItems()->with('itemInstance.template')->get()->groupBy('set_type');

        $equipmentSets = [];
        foreach (CharacterEquipmentSetItem::ALL_SETS as $setType) {
            $itemsForSet = ($savedSetItems->get($setType) ?? collect())->keyBy('slot');
            $slots = [];
            foreach (CharacterEquipmentSetItem::SLOTS as $slot) {
                $slots[$slot] = $itemsForSet->get($slot)?->itemInstance;
            }

            $equipmentSets[$setType] = [
                'label' => self::EQUIPMENT_SET_LABELS[$setType],
                'isWearable' => in_array($setType, CharacterEquipmentSetItem::WEARABLE_SETS, true),
                'locked' => in_array($setType, CharacterEquipmentSetItem::VIP_ONLY_SETS, true) && !$hasPremium,
                'slots' => $slots,
                'configuredCount' => $itemsForSet->count(),
            ];
        }

        $totalAttributes = $this->character->getTotalAttributes();
        
        // Derived stats
        $str = $totalAttributes['str'] ?? 0;
        $int = $totalAttributes['int'] ?? 0;
        $vit = $totalAttributes['vit'] ?? 0;
        $agi = $totalAttributes['agi'] ?? 0;
        $level = $this->character->level;
        
        $eqStats = $this->character->getEquipmentStats();
        
        $activeWeaponType = $this->character->getEquippedWeaponType();

        // Physical damage calculation (uses physical weapon attack_min/max only)
        $physWeaponType = in_array($activeWeaponType, ['wand', 'bell'], true) ? 'sword' : $activeWeaponType;
        $physStatBonus = $this->character->getAttributeAttackBonus($physWeaponType);
        $basePhysDmg = 10 + $physStatBonus + ($level * 1);
        $physAtkMin = (int) ($eqStats['attack_min'] ?? 0);
        $physAtkMax = (int) max($physAtkMin, $eqStats['attack_max'] ?? 0);

        // Magic damage calculation (uses magic weapon magic_attack_min/max only)
        $magicWeaponType = in_array($activeWeaponType, ['wand', 'bell'], true) ? $activeWeaponType : 'wand';
        $magicStatBonus = $this->character->getAttributeAttackBonus($magicWeaponType);
        $baseMagicDmg = 10 + $magicStatBonus + ($level * 1);
        $magicAtkMin = (int) ($eqStats['magic_attack_min'] ?? 0);
        $magicAtkMax = (int) max($magicAtkMin, $eqStats['magic_attack_max'] ?? 0);

        $derivedStats = [
            'max_hp' => $this->character->getMaxHp(),
            'max_mana' => $this->character->getMaxMana(),
            'base_damage_min' => $basePhysDmg + $physAtkMin,
            'base_damage_max' => $basePhysDmg + $physAtkMax,
            'magic_damage_min' => $baseMagicDmg + $magicAtkMin,
            'magic_damage_max' => $baseMagicDmg + $magicAtkMax,
            'crit_chance' => min(50, 5 + ($agi * 0.5) + $eqStats['crit_chance']),
            'dodge_chance' => 2 + ($agi * 0.3) + ($eqStats['dodge_chance'] ?? 0),
            'damage_reduction' => ($vit * 1) + $eqStats['defense'],
        ];

        $inventory = $this->character->inventoryItems;
        if ($this->inventoryFilter !== 'all') {
            $inventory = $inventory->filter(function ($item) {
                return $item->template->type === $this->inventoryFilter;
            });
        }
        $inventory = ItemSorter::sort($inventory);
        // Safety cap to prevent memory exhaustion if a character has an excessive number of items in DB
        $inventory = $inventory->take(64);

        $user = auth()->user();
        $playerStashItems = $user ? $user->playerStashItems()->with('template')->get() : collect();
        if ($this->inventoryFilter !== 'all' && $playerStashItems->isNotEmpty()) {
            $playerStashItems = $playerStashItems->filter(function ($item) {
                return $item->template->type === $this->inventoryFilter;
            });
        }
        $playerStashItems = ItemSorter::sort($playerStashItems);
        $playerStashItems = $playerStashItems->take(64);


        $pets = Pet::where('character_id', $this->character->id)
            ->orderByDesc('is_equipped')
            ->orderByDesc('rarity')
            ->get();

        $incubator = CharacterIncubator::with('eggItemInstance.template')
            ->where('character_id', $this->character->id)
            ->first();

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

        // Batch fetch monster/map drop sources for materials shown in plecak + magazyn materiałów (eliminuje N+1).
        $materialTemplateIds = $inventory->filter(fn ($item) => ($item->template->type ?? null) === 'material')->pluck('template_id')
            ->merge($materialStashItems->pluck('template_id'))
            ->unique()->filter()->values();

        $materialDropSources = [];
        if ($materialTemplateIds->isNotEmpty()) {
            $entries = \App\Infrastructure\Persistence\LootTableEntry::whereIn('ref_ulid', $materialTemplateIds)
                ->whereHas('lootTable.monsters')
                ->with('lootTable.monsters.map')
                ->get();
            foreach ($entries as $entry) {
                if ($entry->lootTable && $entry->lootTable->monsters) {
                    foreach ($entry->lootTable->monsters as $m) {
                        if ($m->name) {
                            $key = $m->name . '_' . ($m->map->name ?? '');
                            $materialDropSources[$entry->ref_ulid][$key] = [
                                'monster' => $m->name,
                                'map' => $m->map->name ?? null,
                            ];
                        }
                    }
                }
            }
            foreach ($materialDropSources as $tid => $list) {
                $materialDropSources[$tid] = array_values($list);
            }
        }

        $activeWeaponType = $this->character->getEquippedWeaponType();
        $activeScalingStats = match ($activeWeaponType) {
            'bow', 'sword', 'dagger' => ['str', 'agi'],
            'bell' => ['str', 'int'],
            'wand' => ['int'],
            default => ['str'],
        };

        // Mirror System Data: Filter maps to accessible ones for player level
        $mirrorMaps = \App\Infrastructure\Persistence\Map::where('level_min', '<=', $this->character->level)
            ->orderBy('level_min')
            ->get();

        if ($mirrorMaps->isEmpty()) {
            $mirrorMaps = \App\Infrastructure\Persistence\Map::orderBy('level_min')->take(1)->get();
        }

        $mirrorService = app(\App\Application\Mirror\MirrorService::class);
        $mapRates = [];
        foreach ($mirrorMaps as $m) {
            $mapRates[$m->id] = $mirrorService->getMapRates($this->character, $m);
        }

        if (!$this->selectedMirrorMapId || !$mirrorMaps->pluck('id')->contains($this->selectedMirrorMapId)) {
            $this->selectedMirrorMapId = $mirrorMaps->last()->id;
        }

        $activeMirrorSession = $this->character->activeMirrorSession;
        $mirrorRewardsPreview = $activeMirrorSession ? $activeMirrorSession->calculateCurrentRewards() : null;

        return view('livewire.city.profile', [
            'equipped' => $equipped,
            'inventory' => $inventory,
            'playerStashItems' => $playerStashItems,
            'materialStashItems' => $materialStashItems,
            'materialDropSources' => $materialDropSources,
            'materialStashCount' => $this->character->getMaterialStashCount(),
            'materialStashCapacity' => $this->character->getMaterialStashCapacity(),
            'stashCapacity' => $user?->getStashCapacity() ?? 2,
            'backpackCount' => $this->character->getBackpackCount(),
            'backpackCapacity' => $this->character->getBackpackCapacity(),
            'totalAttributes' => $totalAttributes,
            'baseAttributes' => $this->character->getBaseAttributes(),
            'bonusAttributes' => $this->character->getBonusAttributes(),
            'derivedStats' => $derivedStats,
            'eqStats' => $eqStats,
            'activeWeaponType' => $activeWeaponType,
            'activeScalingStats' => $activeScalingStats,
            'pets' => $pets,
            'incubator' => $incubator,
            'eggs' => $eggs,
            'inventoryItems' => $inventory,
            'feedingPet' => $this->feedingPetId ? Pet::find($this->feedingPetId) : null,
            'baseAvatars' => $baseAvatars,
            'equipmentSets' => $equipmentSets,
            'mirrorMaps' => $mirrorMaps,
            'mapRates' => $mapRates,
            'activeMirrorSession' => $activeMirrorSession,
            'mirrorRewardsPreview' => $mirrorRewardsPreview,
        ]);
    }

    public function selectMirrorMap(int $mapId): void
    {
        $this->selectedMirrorMapId = $mapId;
    }

    public function selectMirrorDuration(int $hours): void
    {
        $maxHours = ($this->character->user && $this->character->user->hasPremium()) ? 10 : 6;
        $this->selectedMirrorDurationHours = min($maxHours, max(1, $hours));
    }

    public function startMirror(): void
    {
        try {
            if (!$this->selectedMirrorMapId) {
                $this->dispatch('notify', type: 'error', message: 'Wybierz mapę do uruchomienia Lustra.');
                return;
            }

            $map = \App\Infrastructure\Persistence\Map::findOrFail($this->selectedMirrorMapId);
            $mirrorService = app(\App\Application\Mirror\MirrorService::class);
            $session = $mirrorService->startMirror($this->character, $map, $this->selectedMirrorDurationHours);

            $this->dispatch('notify', type: 'success', message: "Lustro zostało pomyślnie aktywowane na mapie {$map->name}!");
            $this->character->refresh();
            $this->activeTab = 'mirror';
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: $e->getMessage());
        }
    }

    public function stopMirror(): void
    {
        try {
            $mirrorService = app(\App\Application\Mirror\MirrorService::class);
            $summary = $mirrorService->stopAndClaim($this->character);
            $this->claimedRewardsSummary = $summary;

            // Clear cached relations on character model so inventory & material stash update immediately
            $this->character->unsetRelation('inventoryItems');
            $this->character->unsetRelation('materialStashItems');
            $this->character->unsetRelation('equipmentItems');
            $this->character->refresh();

            // Notify header & navigation components to update stats and notification badges
            $this->dispatch('character-updated');

            // Trigger level-up modal & sound if level increased
            if (!empty($summary['had_level_up'])) {
                $this->dispatch('open-level-up-modal', level: $summary['new_level']);
                $this->dispatch('play-audio', type: 'level-up');
            }

            $this->dispatch('notify', type: 'success', message: 'Lustro zostało zakończone, a nagrody odebrane!');
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: $e->getMessage());
        }
    }

    public function closeRewardsSummary(): void
    {
        $this->claimedRewardsSummary = null;
    }
}
