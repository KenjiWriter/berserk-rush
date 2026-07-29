<?php

namespace App\Livewire\Economy;

use App\Application\Economy\Actions\BuyMarketListingAction;
use App\Application\Economy\Actions\CancelMarketListingAction;
use App\Application\Economy\Queries\GetMarketListingsQuery;
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
    
    // Filters
    public $search = '';
    public $rarity = '';
    public $currency = '';
    public $slot = '';
    public $maxPrice = '';
    public $minLevel = '';
    public $maxLevel = '';
    public array $stats = [];
    public $sortBy = 'created_at';
    public $sortDir = 'desc';

    protected $queryString = [
        'activeTab' => ['except' => 'buy'],
        'search' => ['except' => ''],
        'rarity' => ['except' => ''],
        'currency' => ['except' => ''],
        'slot' => ['except' => ''],
        'maxPrice' => ['except' => ''],
        'minLevel' => ['except' => ''],
        'maxLevel' => ['except' => ''],
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
        if (in_array($name, ['search', 'rarity', 'currency', 'slot', 'maxPrice', 'minLevel', 'maxLevel', 'stats', 'sortBy', 'sortDir', 'activeTab'])) {
            $this->resetPage();
        }
    }

    public function resetFilters()
    {
        $this->reset(['search', 'rarity', 'currency', 'slot', 'maxPrice', 'minLevel', 'maxLevel', 'stats']);
        $this->resetPage();
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function buyItem(string $listingId, BuyMarketListingAction $action)
    {
        $character = $this->character;
        $listing = MarketListing::find($listingId);

        if (!$listing) {
            $this->dispatch('notify', message: 'Oferta nie została znaleziona.', type: 'error');
            return;
        }

        $result = $action->execute($character, $listing);

        if ($result->isError()) {
            $this->dispatch('notify', message: $result->getErrorMessage(), type: 'error');
            return;
        }

        $this->dispatch('notify', message: 'Przedmiot został pomyślnie kupiony!', type: 'success');
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

        $result = $action->execute($character, $listing);

        if ($result->isError()) {
            $this->dispatch('notify', message: $result->getErrorMessage(), type: 'error');
            return;
        }

        $this->dispatch('notify', message: 'Oferta została anulowana. Przedmiot wrócił do ekwipunku.', type: 'success');
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
        $myListings = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 12);

        if ($this->activeTab === 'buy') {
            $filters = [
                'search' => $this->search,
                'rarity' => $this->rarity,
                'currency' => $this->currency,
                'slot' => $this->slot,
            ];

            if ($this->maxPrice !== '' && $this->maxPrice !== null) {
                $filters['max_price'] = $this->maxPrice;
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
            $myListings = MarketListing::with('item.template')
                ->where('seller_character_id', $character->id)
                ->has('item')
                ->orderBy('created_at', 'desc')
                ->paginate(12);
        }

        $equipped = [];
        foreach($character->equippedItems()->with('template')->get() as $eq) {
            $equipped[$eq->template->slot] = $eq;
        }

        return view('livewire.economy.market', [
            'character' => $character,
            'listings' => $listings,
            'myListings' => $myListings,
            'equipped' => $equipped,
            'statOptions' => $this->statOptions(),
        ]);
    }
}
