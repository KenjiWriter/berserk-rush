<?php

namespace App\Livewire\Economy;

use App\Application\Economy\Actions\BuyMarketListingAction;
use App\Application\Economy\Actions\CancelMarketListingAction;
use App\Application\Economy\Queries\GetMarketListingsQuery;
use App\Domain\Pets\PetTier;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\MarketListing;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;

class MarketComponent extends Component
{
    use WithPagination;

    public $characterId;
    public $activeTab = 'buy'; // 'buy' or 'my_listings'
    public $listingType = 'item'; // 'item' or 'pet' - tylko dla activeTab === 'buy'

    // Filters
    public $search = '';
    public $rarity = '';
    public $currency = '';
    public $slot = '';
    public $maxPrice = '';
    public $minLevel = '';
    public $maxLevel = '';
    public $petTier = '';
    public array $stats = [];
    public $sortBy = 'created_at';
    public $sortDir = 'desc';

    public bool $showConfirmBuyModal = false;
    public ?string $selectedListingId = null;

    protected $queryString = [
        'activeTab' => ['except' => 'buy'],
        'listingType' => ['except' => 'item'],
        'search' => ['except' => ''],
        'rarity' => ['except' => ''],
        'currency' => ['except' => ''],
        'slot' => ['except' => ''],
        'maxPrice' => ['except' => ''],
        'minLevel' => ['except' => ''],
        'maxLevel' => ['except' => ''],
        'petTier' => ['except' => ''],
        'stats' => ['except' => []],
        'sortBy' => ['except' => 'created_at'],
        'sortDir' => ['except' => 'desc'],
    ];

    public function mount(Character $character)
    {
        $this->characterId = $character->id;
    }

    public function getCharacterProperty()
    {
        return Character::find($this->characterId);
    }

    public function updating($name, $value)
    {
        if (in_array($name, ['search', 'rarity', 'currency', 'slot', 'maxPrice', 'minLevel', 'maxLevel', 'petTier', 'stats', 'sortBy', 'sortDir', 'activeTab', 'listingType'])) {
            $this->resetPage();
        }
    }

    public function resetFilters()
    {
        $this->reset(['search', 'rarity', 'currency', 'slot', 'maxPrice', 'minLevel', 'maxLevel', 'petTier', 'stats']);
        $this->resetPage();
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function switchListingType($type)
    {
        $this->listingType = $type;
        $this->resetPage();
    }

    public function confirmBuy(string $listingId)
    {
        $listing = MarketListing::find($listingId);

        if (!$listing || $listing->status !== 'active') {
            $this->dispatch('notify', message: 'Oferta nie jest już aktywna.', type: 'error');
            return;
        }

        $this->selectedListingId = $listingId;
        $this->showConfirmBuyModal = true;
    }

    public function closeConfirmBuyModal()
    {
        $this->showConfirmBuyModal = false;
        $this->selectedListingId = null;
    }

    public function buyItem(?string $listingId = null, ?BuyMarketListingAction $action = null)
    {
        $targetId = $listingId ?: $this->selectedListingId;

        if (!$targetId) {
            $this->dispatch('notify', message: 'Nie wybrano oferty do zakupu.', type: 'error');
            return;
        }

        $character = $this->character;
        $listing = MarketListing::find($targetId);

        if (!$listing) {
            $this->closeConfirmBuyModal();
            $this->dispatch('notify', message: 'Oferta nie została znaleziona.', type: 'error');
            return;
        }

        $isPetListing = $listing->pet_id !== null;

        $action = $action ?: app(BuyMarketListingAction::class);
        $result = $action->execute($character, $listing);

        $this->closeConfirmBuyModal();

        if ($result->isError()) {
            $this->dispatch('notify', message: $result->getErrorMessage(), type: 'error');
            return;
        }

        $this->dispatch('notify', message: $isPetListing ? 'Chowaniec został pomyślnie kupiony!' : 'Przedmiot został pomyślnie kupiony!', type: 'success');
        $this->dispatch('play-audio', type: 'buy');
        $character->refresh();
        $this->dispatch('stats-updated', gold: $character->gold, gems: auth()->user()->gems ?? 0);
        $this->dispatch('character-updated');
    }

    public function cancelListing(string $listingId, CancelMarketListingAction $action)
    {
        $character = $this->character;
        $listing = MarketListing::find($listingId);

        if (!$listing) {
            $this->dispatch('notify', message: 'Oferta nie została znaleziona.', type: 'error');
            return;
        }

        $isPetListing = $listing->pet_id !== null;

        $result = $action->execute($character, $listing);

        if ($result->isError()) {
            $this->dispatch('notify', message: $result->getErrorMessage(), type: 'error');
            return;
        }

        $this->dispatch('notify', message: $isPetListing ? 'Oferta została anulowana.' : 'Oferta została anulowana. Przedmiot wrócił do ekwipunku.', type: 'success');
        $character->refresh();
        $this->dispatch('stats-updated', gold: $character->gold, gems: auth()->user()->gems ?? 0);
    }
    
    public function backToCity()
    {
        return redirect()->route('city.hub', $this->character);
    }

    public function statOptions(): array
    {
        return [
            'str_bonus' => 'STR',
            'agi_bonus' => 'AGI',
            'int_bonus' => 'INT',
            'vit_bonus' => 'VIT',
            'attack_min' => 'Obrażenia Fizyczne',
            'magic_attack_min' => 'Obrażenia Magiczne',
            'defense' => 'Obrona',
            'crit_chance' => 'Szansa Kryt.',
            'hp_bonus' => 'Bonus HP',
            'mana_bonus' => 'Bonus Many',
        ];
    }

    public function render(GetMarketListingsQuery $query)
    {
        $character = $this->character;
        $listings = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 12);
        $myItemListings = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 12);
        $myPetListings = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 12);

        if ($this->activeTab === 'buy' && $this->listingType === 'pet') {
            $filters = [
                'type' => 'pet',
                'search' => $this->search,
                'currency' => $this->currency,
                'tier' => $this->petTier,
            ];

            if ($this->maxPrice !== '' && $this->maxPrice !== null) {
                $filters['max_price'] = \App\Support\PriceParser::parse($this->maxPrice);
            }

            if ($this->minLevel !== '' && $this->minLevel !== null) {
                $filters['min_level'] = $this->minLevel;
            }

            if ($this->maxLevel !== '' && $this->maxLevel !== null) {
                $filters['max_level'] = $this->maxLevel;
            }

            $listings = $query->execute($filters, $this->sortBy, $this->sortDir, 12);
        } elseif ($this->activeTab === 'buy') {
            $filters = [
                'search' => $this->search,
                'rarity' => $this->rarity,
                'currency' => $this->currency,
                'slot' => $this->slot,
            ];

            if ($this->maxPrice !== '' && $this->maxPrice !== null) {
                $filters['max_price'] = \App\Support\PriceParser::parse($this->maxPrice);
            }

            if ($this->minLevel !== '' && $this->minLevel !== null) {
                $filters['min_level'] = $this->minLevel;
            }

            if ($this->maxLevel !== '' && $this->maxLevel !== null) {
                $filters['max_level'] = $this->maxLevel;
            }

            if (!empty($this->stats)) {
                $filters['stats'] = $this->stats;
            }

            $listings = $query->execute($filters, $this->sortBy, $this->sortDir, 12);
        } else {
            $myItemListings = MarketListing::with('item.template')
                ->where('seller_character_id', $character->id)
                ->has('item')
                ->orderBy('created_at', 'desc')
                ->paginate(12, ['*'], 'itemsPage');

            $myPetListings = MarketListing::with('pet')
                ->where('seller_character_id', $character->id)
                ->has('pet')
                ->orderBy('created_at', 'desc')
                ->paginate(12, ['*'], 'petsPage');
        }

        $equipped = [];
        foreach($character->equippedItems()->with('template')->get() as $eq) {
            $equipped[$eq->template->slot] = $eq;
        }

        $confirmListing = null;
        if ($this->showConfirmBuyModal && $this->selectedListingId) {
            $confirmListing = MarketListing::with(['item.template', 'pet', 'seller'])->find($this->selectedListingId);
            if (!$confirmListing || $confirmListing->status !== 'active') {
                $this->showConfirmBuyModal = false;
                $this->selectedListingId = null;
                $confirmListing = null;
            }
        }

        return view('livewire.economy.market', [
            'character' => $character,
            'listings' => $listings,
            'myItemListings' => $myItemListings,
            'myPetListings' => $myPetListings,
            'equipped' => $equipped,
            'statOptions' => $this->statOptions(),
            'tiers' => PetTier::all(),
            'confirmListing' => $confirmListing,
        ]);
    }
}
