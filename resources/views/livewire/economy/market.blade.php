<div class="min-h-screen text-amber-100 relative overflow-hidden select-none"
     style="background: radial-gradient(circle at 50% 0%, #1c1917 0%, #0c0a09 60%, #050505 100%); font-family: 'Cinzel', serif;">
    
    {{-- Ambient Ambient Glow and Texture Overlay --}}
    <div class="absolute top-0 inset-x-0 h-64 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-amber-600/15 via-transparent to-transparent pointer-events-none"></div>

    <div class="relative container mx-auto px-4 py-6 sm:py-8 min-h-screen z-10 max-w-7xl">
        
        {{-- Header Section --}}
        <div class="flex flex-col md:flex-row items-center justify-between gap-4 mb-8 border-b-2 border-amber-900/60 pb-6 bg-gradient-to-b from-stone-950/80 to-transparent p-4 rounded-2xl shadow-xl">
            <div class="flex items-center space-x-4">
                <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl bg-gradient-to-b from-amber-700 via-amber-900 to-stone-950 border-2 border-amber-400 flex items-center justify-center text-2xl sm:text-3xl text-amber-300 shadow-[0_0_20px_rgba(245,158,11,0.5)] shrink-0">
                    <i class="fa-solid fa-scale-balanced"></i>
                </div>
                <div>
                    <h1 class="text-2xl sm:text-4xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-yellow-200 via-amber-400 to-amber-600 drop-shadow-md">TARGOWISKO MIEJSKIE</h1>
                    <p class="text-xs sm:text-sm text-amber-300/70 font-sans tracking-wide">Królewski dom aukcyjny – kupuj i wystawiaj ekwipunek z innymi bohaterami</p>
                </div>
            </div>
            
            <div class="flex flex-wrap items-center gap-4">
                {{-- Wallet Display --}}
                <div class="flex items-center gap-3 bg-stone-950/90 px-4 py-2 rounded-xl border-2 border-amber-800/80 shadow-[inset_0_2px_4px_rgba(0,0,0,0.8)]">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-coins text-amber-400 text-base"></i>
                        <div class="text-right">
                            <span class="text-[9px] text-amber-500 font-extrabold uppercase tracking-wider block leading-none">ZŁOTO</span>
                            <span class="font-extrabold text-yellow-300 text-sm sm:text-base drop-shadow">{{ number_format($character->gold) }}</span>
                        </div>
                    </div>
                    <div class="w-px h-7 bg-amber-900/60"></div>
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-gem text-purple-400 text-base"></i>
                        <div class="text-right">
                            <span class="text-[9px] text-purple-400 font-extrabold uppercase tracking-wider block leading-none">KLEJNOTY</span>
                            <span class="font-extrabold text-purple-300 text-sm sm:text-base drop-shadow">{{ number_format(auth()->user()->gems) }}</span>
                        </div>
                    </div>
                </div>

                {{-- Back Button --}}
                <button wire:click="backToHub" @click="$dispatch('location-leave', { text: 'Powrót do Miasta...', icon: 'fa-solid fa-archway', url: '{{ route('city.hub', $character->id) }}' }); $dispatch('play-audio', { type: 'tab' })"
                    class="px-4 py-2.5 min-h-[44px] rounded-xl bg-gradient-to-b from-slate-800 via-slate-900 to-stone-950 text-amber-200 font-extrabold text-xs uppercase tracking-widest border-2 border-slate-700 hover:border-amber-500 hover:text-yellow-100 shadow-[inset_0_1px_0_rgba(255,255,255,0.1),0_4px_10px_rgba(0,0,0,0.8)] transition-all duration-200 flex items-center justify-center gap-2 group cursor-pointer w-full sm:w-auto">
                    <i class="fa-solid fa-archway text-amber-400 group-hover:scale-110 transition-transform"></i>
                    <span>Powrót do Miasta</span>
                </button>
            </div>
        </div>

        {{-- Navigation Tabs --}}
        <div class="flex space-x-3 border-b-2 border-amber-900/60 mb-6">
            <button wire:click="switchTab('buy')" 
                    @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                    class="px-5 sm:px-6 py-3 min-h-[44px] font-extrabold text-xs tracking-widest uppercase rounded-t-xl transition-all duration-200 border-t-2 border-x-2 flex items-center justify-center gap-2.5 cursor-pointer {{ $activeTab === 'buy' ? 'bg-gradient-to-b from-amber-900/90 via-stone-900 to-stone-950 text-yellow-300 border-amber-500 shadow-[0_-5px_15px_rgba(245,158,11,0.2)]' : 'bg-stone-950/60 text-stone-400 border-stone-800 hover:text-amber-200 hover:bg-stone-900' }}">
                <i class="fa-solid fa-cart-shopping {{ $activeTab === 'buy' ? 'text-amber-400' : 'text-stone-500' }}"></i>
                <span>Rynek Ofert</span>
            </button>
            
            <button wire:click="switchTab('my_listings')" 
                    @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                    class="px-5 sm:px-6 py-3 min-h-[44px] font-extrabold text-xs tracking-widest uppercase rounded-t-xl transition-all duration-200 border-t-2 border-x-2 flex items-center justify-center gap-2.5 cursor-pointer {{ $activeTab === 'my_listings' ? 'bg-gradient-to-b from-amber-900/90 via-stone-900 to-stone-950 text-yellow-300 border-amber-500 shadow-[0_-5px_15px_rgba(245,158,11,0.2)]' : 'bg-stone-950/60 text-stone-400 border-stone-800 hover:text-amber-200 hover:bg-stone-900' }}">
                <i class="fa-solid fa-box-open {{ $activeTab === 'my_listings' ? 'text-amber-400' : 'text-stone-500' }}"></i>
                <span>Moje Oferty</span>
            </button>
        </div>

        @if($activeTab === 'buy')
            {{-- Listing Type Sub-Tabs (Przedmioty / Pety) --}}
            <div class="flex gap-2 mb-6">
                <button wire:click="switchListingType('item')"
                        class="px-4 py-2 min-h-[40px] rounded-lg font-extrabold text-[11px] tracking-widest uppercase transition-all duration-200 border-2 flex items-center gap-2 cursor-pointer {{ $listingType === 'item' ? 'bg-amber-900/70 text-yellow-300 border-amber-500' : 'bg-stone-950/60 text-stone-400 border-stone-800 hover:text-amber-200' }}">
                    <i class="fa-solid fa-shield-halved"></i> Przedmioty
                </button>
                <button wire:click="switchListingType('pet')"
                        class="px-4 py-2 min-h-[40px] rounded-lg font-extrabold text-[11px] tracking-widest uppercase transition-all duration-200 border-2 flex items-center gap-2 cursor-pointer {{ $listingType === 'pet' ? 'bg-amber-900/70 text-yellow-300 border-amber-500' : 'bg-stone-950/60 text-stone-400 border-stone-800 hover:text-amber-200' }}">
                    <i class="fa-solid fa-paw"></i> Pety
                </button>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                
                {{-- Filters Sidebar --}}
                <div class="lg:col-span-1 space-y-6">
                    <div class="bg-stone-950/90 border-2 border-amber-800/80 p-5 rounded-2xl shadow-2xl relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-24 h-24 bg-amber-600/10 rounded-full blur-2xl pointer-events-none"></div>

                        <h3 class="text-xs font-extrabold text-amber-400 uppercase tracking-widest mb-4 border-b border-amber-900/60 pb-2.5 flex items-center gap-2">
                            <i class="fa-solid fa-filter text-amber-500"></i>
                            <span>Filtry Aukcyjne</span>
                        </h3>
                        
                        <div class="space-y-4 text-xs">
                            <div>
                                <label class="block font-bold text-amber-200/80 mb-1.5 uppercase tracking-wider text-[10px]">{{ $listingType === 'pet' ? 'Nazwa Chowańca' : 'Nazwa Przedmiotu' }}</label>
                                <div class="relative">
                                    <input type="text" wire:model.live.debounce.300ms="search" class="w-full bg-stone-900 border border-amber-900/80 rounded-lg pl-8 pr-3 py-2 text-xs text-amber-100 placeholder-amber-700/50 focus:border-amber-400 focus:ring-1 focus:ring-amber-400 font-sans shadow-inner" placeholder="Wpisz nazwę...">
                                    <i class="fa-solid fa-magnifying-glass absolute left-2.5 top-1/2 -translate-y-1/2 text-amber-600/60 text-xs"></i>
                                </div>
                            </div>

                            @if($listingType === 'pet')
                                <div>
                                    <label class="block font-bold text-amber-200/80 mb-1.5 uppercase tracking-wider text-[10px]">Tier</label>
                                    <select wire:model.live="petTier" class="w-full bg-stone-900 border border-amber-900/80 rounded-lg px-3 py-2 text-xs text-amber-100 font-sans focus:border-amber-400">
                                        <option value="">Wszystkie Tiery</option>
                                        @foreach($tiers as $t => $meta)
                                            <option value="{{ $t }}">{{ $meta['name'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @else
                                <div>
                                    <label class="block font-bold text-amber-200/80 mb-1.5 uppercase tracking-wider text-[10px]">Rzadkość</label>
                                    <select wire:model.live="rarity" class="w-full bg-stone-900 border border-amber-900/80 rounded-lg px-3 py-2 text-xs text-amber-100 font-sans focus:border-amber-400">
                                        <option value="">Wszystkie Rzadkości</option>
                                        <option value="common">Zwykły (Common)</option>
                                        <option value="uncommon">Niecodzienny (Uncommon)</option>
                                        <option value="rare">Rzadki (Rare)</option>
                                        <option value="epic">Epicki (Epic)</option>
                                        <option value="legendary">Legendarny (Legendary)</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block font-bold text-amber-200/80 mb-1.5 uppercase tracking-wider text-[10px]">Kategoria (Slot)</label>
                                    <select wire:model.live="slot" class="w-full bg-stone-900 border border-amber-900/80 rounded-lg px-3 py-2 text-xs text-amber-100 font-sans focus:border-amber-400">
                                        <option value="">Wszystkie Kategorie</option>
                                        <option value="main_hand">Broń</option>
                                        <option value="head">Głowa</option>
                                        <option value="chest">Zbroja</option>
                                        <option value="boots">Buty</option>
                                        <option value="ring">Pierścień</option>
                                        <option value="neck">Naszyjnik</option>
                                        <option value="material">Materiały</option>
                                        <option value="consumable">Mikstury</option>
                                    </select>
                                </div>
                            @endif

                            <div>
                                <label class="block font-bold text-amber-200/80 mb-1.5 uppercase tracking-wider text-[10px]">Waluta</label>
                                <select wire:model.live="currency" class="w-full bg-stone-900 border border-amber-900/80 rounded-lg px-3 py-2 text-xs text-amber-100 font-sans focus:border-amber-400">
                                    <option value="">Wszystkie Waluty</option>
                                    <option value="gold">Tylko Złoto</option>
                                    <option value="gems">Tylko Klejnoty</option>
                                </select>
                            </div>

                            <div>
                                <label class="block font-bold text-amber-200/80 mb-1.5 uppercase tracking-wider text-[10px]">Maks. Cena</label>
                                <div class="relative">
                                    <input type="text" wire:model.live.debounce.400ms="maxPrice" class="w-full bg-stone-900 border border-amber-900/80 rounded-lg pl-8 pr-3 py-2 text-xs text-amber-100 placeholder-amber-700/50 focus:border-amber-400 focus:ring-1 focus:ring-amber-400 font-sans shadow-inner" placeholder="np. 2kk, 500k">
                                    <i class="fa-solid fa-coins absolute left-2.5 top-1/2 -translate-y-1/2 text-amber-600/60 text-xs"></i>
                                </div>
                                @if($maxPrice !== '' && $maxPrice !== null && \App\Support\PriceParser::parse($maxPrice) > 0)
                                    <span class="text-[10px] text-amber-400/90 font-mono mt-0.5 block">
                                        Do: {{ \App\Support\PriceParser::format(\App\Support\PriceParser::parse($maxPrice)) }}
                                    </span>
                                @endif
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block font-bold text-amber-200/80 mb-1.5 uppercase tracking-wider text-[10px]">Min. Poziom</label>
                                    <div class="relative">
                                        <input type="number" min="0" wire:model.live.debounce.400ms="minLevel" class="w-full bg-stone-900 border border-amber-900/80 rounded-lg pl-8 pr-3 py-2 text-xs text-amber-100 placeholder-amber-700/50 focus:border-amber-400 focus:ring-1 focus:ring-amber-400 font-sans shadow-inner" placeholder="0">
                                        <i class="fa-solid fa-ranking-star absolute left-2.5 top-1/2 -translate-y-1/2 text-amber-600/60 text-xs"></i>
                                    </div>
                                </div>
                                <div>
                                    <label class="block font-bold text-amber-200/80 mb-1.5 uppercase tracking-wider text-[10px]">Maks. Poziom</label>
                                    <div class="relative">
                                        <input type="number" min="0" wire:model.live.debounce.400ms="maxLevel" class="w-full bg-stone-900 border border-amber-900/80 rounded-lg pl-8 pr-3 py-2 text-xs text-amber-100 placeholder-amber-700/50 focus:border-amber-400 focus:ring-1 focus:ring-amber-400 font-sans shadow-inner" placeholder="Bez limitu">
                                        <i class="fa-solid fa-ranking-star absolute left-2.5 top-1/2 -translate-y-1/2 text-amber-600/60 text-xs"></i>
                                    </div>
                                </div>
                            </div>

                            @if($listingType === 'item')
                                <div>
                                    <label class="block font-bold text-amber-200/80 mb-2 uppercase tracking-wider text-[10px]">Statystyki Bonusowe</label>
                                    <div class="grid grid-cols-2 gap-x-2 gap-y-1.5 bg-stone-900/60 border border-amber-900/60 rounded-lg p-2.5">
                                        @foreach($statOptions as $statKey => $statLabel)
                                            <label class="flex items-center gap-1.5 cursor-pointer group">
                                                <input type="checkbox" wire:model.live="stats" value="{{ $statKey }}"
                                                    class="w-3.5 h-3.5 rounded border-amber-700 bg-stone-950 text-amber-500 focus:ring-amber-400 focus:ring-offset-0 cursor-pointer accent-amber-500">
                                                <span class="text-[10px] text-amber-200/80 font-sans group-hover:text-amber-100 truncate">{{ $statLabel }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <div>
                                <label class="block font-bold text-amber-200/80 mb-1.5 uppercase tracking-wider text-[10px]">Sortowanie</label>
                                <div class="flex gap-2">
                                    <select wire:model.live="sortBy" class="w-2/3 bg-stone-900 border border-amber-900/80 rounded-lg px-2 py-2 text-xs text-amber-100 font-sans">
                                        <option value="created_at">Najnowsze</option>
                                        <option value="price">Cena</option>
                                        <option value="level">Poziom</option>
                                        @if($listingType === 'pet')
                                            <option value="tier">Tier</option>
                                        @endif
                                        <option value="expires_at">Wygasające</option>
                                    </select>
                                    <select wire:model.live="sortDir" class="w-1/3 bg-stone-900 border border-amber-900/80 rounded-lg px-2 py-2 text-xs text-amber-100 font-sans">
                                        <option value="desc">↓ Mal.</option>
                                        <option value="asc">↑ Rosn.</option>
                                    </select>
                                </div>
                            </div>

                            <button wire:click="resetFilters" class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-lg bg-stone-900 border border-amber-700/60 text-amber-300 font-extrabold text-[10px] uppercase tracking-wider hover:bg-amber-950 hover:border-amber-400 transition-all cursor-pointer shadow">
                                <i class="fa-solid fa-rotate-left"></i>
                                <span>Wyczyść Filtry</span>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Listings Grid --}}
                <div class="lg:col-span-3">
                    @if(count($listings) > 0)
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                            @foreach($listings as $listing)
                            @if($listingType === 'pet')
                                @php $petTierMeta = $tiers[$listing->pet->tier] ?? ['name' => 'Nieznany']; @endphp
                                <div wire:key="pet-listing-{{ $listing->id }}"
                                     class="bg-gradient-to-b from-stone-900 via-stone-950 to-black border-2 border-amber-500/60 rounded-xl p-4 flex flex-col justify-between shadow-[0_4px_15px_rgba(0,0,0,0.8)] hover:shadow-[0_0_20px_rgba(245,158,11,0.25)] transition-all duration-200 relative group">
                                    <div class="flex items-start space-x-3 mb-3">
                                        <div class="w-12 h-12 rounded-lg border-2 border-amber-500/80 flex items-center justify-center shrink-0 bg-stone-950 text-2xl shadow-inner relative">
                                            <img src="{{ route('assets.items', ['filename' => $listing->pet->icon]) }}" alt="{{ $listing->pet->name }}" class="w-full h-full object-contain p-1">
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <h4 class="font-extrabold text-amber-100 text-xs sm:text-sm truncate leading-snug">{{ $listing->pet->name }}</h4>
                                            <div class="text-[10px] text-amber-400/80 font-bold uppercase tracking-wider mt-0.5 flex items-center gap-2">
                                                <span>Lvl {{ $listing->pet->level }}</span>
                                                <span>•</span>
                                                <span>{{ $petTierMeta['name'] }}</span>
                                                @if($listing->pet->fusion_count > 0)
                                                    <span>•</span>
                                                    <span class="text-sky-400">+{{ $listing->pet->fusion_count }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <div class="text-[11px] text-stone-300 bg-stone-950/80 p-2 rounded-lg border border-stone-800 mb-3 grid grid-cols-2 gap-1 font-sans">
                                        @foreach($listing->pet->stats ?? [] as $stat => $val)
                                            <div class="flex justify-between">
                                                <span class="text-stone-400 uppercase truncate mr-1">{{ $stat }}</span>
                                                <span class="text-emerald-400 font-bold">+{{ $val }}</span>
                                            </div>
                                        @endforeach
                                    </div>

                                    <div class="flex flex-col mt-auto pt-2 border-t border-amber-900/40">
                                        <div class="flex justify-between items-center mb-2 text-[10px] text-stone-400 font-sans">
                                            <span>Sprzedawca: <strong class="text-amber-200">{{ $listing->seller->name }}</strong></span>
                                            <span class="text-amber-500/80 font-bold"><i class="fa-regular fa-clock mr-1"></i>{{ $listing->expires_at->diffForHumans() }}</span>
                                        </div>

                                        <button wire:click="confirmBuy('{{ $listing->id }}')"
                                            wire:loading.attr="disabled" wire:target="confirmBuy('{{ $listing->id }}')"
                                            wire:loading.class="opacity-50 cursor-not-allowed" wire:target="confirmBuy('{{ $listing->id }}')"
                                            class="w-full flex items-center justify-center font-extrabold py-2 px-3 rounded-lg text-xs uppercase tracking-wider transition-all duration-200 shadow-md border cursor-pointer
                                            @if($listing->currency === 'gold' && $character->gold >= $listing->price) bg-gradient-to-b from-amber-700 via-amber-800 to-amber-950 hover:from-amber-600 hover:to-amber-900 text-yellow-200 border-amber-500 shadow-[0_0_10px_rgba(245,158,11,0.3)]
                                            @elseif($listing->currency === 'gems' && auth()->user()->gems >= $listing->price) bg-gradient-to-b from-purple-700 via-purple-800 to-purple-950 hover:from-purple-600 hover:to-purple-900 text-purple-100 border-purple-500 shadow-[0_0_10px_rgba(168,85,247,0.3)]
                                            @else bg-stone-900 text-stone-500 border-stone-800 cursor-not-allowed opacity-60 @endif">

                                            <span wire:loading.remove wire:target="confirmBuy('{{ $listing->id }}')" class="flex items-center gap-1.5">
                                                <span>KUP ZA</span>
                                                <span class="font-extrabold text-sm {{ $listing->currency === 'gold' ? 'text-yellow-300' : 'text-purple-300' }}">
                                                    {{ number_format($listing->price) }}
                                                    @if($listing->currency === 'gold') <i class="fa-solid fa-coins ml-0.5"></i> @else <i class="fa-solid fa-gem ml-0.5"></i> @endif
                                                </span>
                                            </span>
                                            <span wire:loading wire:target="confirmBuy('{{ $listing->id }}')" class="flex items-center gap-2">
                                                <i class="fa-solid fa-circle-notch animate-spin"></i>
                                                <span>Ładowanie...</span>
                                            </span>
                                        </button>
                                    </div>
                                </div>
                            @else
                                <div wire:key="listing-{{ $listing->id }}"
                                     class="bg-gradient-to-b from-stone-900 via-stone-950 to-black border-2 rounded-xl p-4 flex flex-col justify-between shadow-[0_4px_15px_rgba(0,0,0,0.8)] hover:shadow-[0_0_20px_rgba(245,158,11,0.25)] transition-all duration-200 relative group
                                    @if($listing->item->rarity === 'common') border-stone-700
                                    @elseif($listing->item->rarity === 'uncommon') border-emerald-600/80 shadow-[0_0_10px_rgba(16,185,129,0.2)]
                                    @elseif($listing->item->rarity === 'rare') border-sky-500/80 shadow-[0_0_10px_rgba(56,189,248,0.2)]
                                    @elseif($listing->item->rarity === 'epic') border-purple-500/80 shadow-[0_0_12px_rgba(168,85,247,0.3)]
                                    @elseif($listing->item->rarity === 'legendary') border-amber-400 shadow-[0_0_15px_rgba(245,158,11,0.4)]
                                    @endif"
                                      x-data="smartTooltip()" 
                                      @mouseenter="openTooltip()" 
                                      @mouseleave="closeTooltip()" 
                                      @click="toggleTooltip()"
                                      @resize.window.debounce.100ms="updatePosition()"
                                      @tooltip-updated.window="updatePosition()">
                                     
                                    {{-- Item Tooltip (teleportowany do <body>) --}}
                                    <template x-teleport="body">
                                        <div x-show="showInfo" x-transition.opacity x-ref="tooltipContainer" data-tooltip-container
                                             :style="tooltipStyle"
                                             class="absolute z-[100] bottom-full left-1/2 -translate-x-1/2 mb-2 w-auto pointer-events-none">
                                            <x-item-tooltip :item="$listing->item" :equippedItem="$equipped[$listing->item->template->slot ?? ''] ?? null" />
                                        </div>
                                    </template>

                                    <div class="flex items-start space-x-3 mb-3">
                                        {{-- Item Icon Frame --}}
                                        <div class="w-12 h-12 rounded-lg border-2 border-amber-500/80 flex items-center justify-center shrink-0 bg-stone-950 text-2xl shadow-inner relative">
                                            @if($listing->item->template->icon)
                                                <img src="{{ route('assets.items', ['filename' => $listing->item->template->icon]) }}" class="w-full h-full object-contain drop-shadow-md p-0.5" alt="{{ $listing->item->template->name }}">
                                            @else
                                                <span class="text-amber-400 text-lg">
                                                    @if($listing->item->template->slot === 'weapon') <i class="fa-solid fa-khanda"></i>
                                                    @elseif($listing->item->template->slot === 'head') <i class="fa-solid fa-helmet-safety"></i>
                                                    @elseif($listing->item->template->slot === 'chest') <i class="fa-solid fa-shield-halved"></i>
                                                    @elseif($listing->item->template->slot === 'legs') <i class="fa-solid fa-vest"></i>
                                                    @elseif($listing->item->template->slot === 'boots') <i class="fa-solid fa-shoe-prints"></i>
                                                    @else <i class="fa-solid fa-box"></i>
                                                    @endif
                                                </span>
                                            @endif
                                            <x-item-upgrade-overlay :level="$listing->item->upgrade_level ?? 0" :type="$listing->item->template->type ?? ''" />
                                            @if(($listing->item->stack_size ?? 1) > 1)
                                                <span class="absolute bottom-0.5 right-0.5 text-amber-200 font-extrabold text-[9px] bg-black/80 border border-amber-600/60 px-0.5 rounded shadow leading-none z-10">x{{ $listing->item->stack_size }}</span>
                                            @endif
                                        </div>
                                        
                                        <div class="min-w-0 flex-1">
                                            <h4 class="font-extrabold text-amber-100 text-xs sm:text-sm truncate leading-snug">
                                                {{ $listing->item->template->name }}
                                                @if(($listing->item->upgrade_level ?? 0) > 0) <span class="text-amber-400 font-bold ml-0.5">+{{ $listing->item->upgrade_level }}</span> @endif
                                            </h4>
                                            <div class="text-[10px] text-amber-400/80 font-bold uppercase tracking-wider mt-0.5 flex items-center gap-2">
                                                <span>Lvl {{ $listing->item->template->level_requirement }}</span>
                                                <span>•</span>
                                                <span class="
                                                    @if($listing->item->rarity === 'common') text-stone-400
                                                    @elseif($listing->item->rarity === 'uncommon') text-emerald-400
                                                    @elseif($listing->item->rarity === 'rare') text-sky-400
                                                    @elseif($listing->item->rarity === 'epic') text-purple-400
                                                    @elseif($listing->item->rarity === 'legendary') text-amber-400
                                                    @endif">
                                                    {{ ucfirst($listing->item->rarity) }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    {{-- Item stats preview --}}
                                    @php
                                        $previewStats = $listing->item->getTotalStats();
                                        $upStats = $listing->item->getUpgradeBonusStats();
                                        foreach ($upStats as $s => $v) {
                                            $previewStats[$s] = ($previewStats[$s] ?? 0) + $v;
                                        }
                                    @endphp
                                    <div class="text-[11px] text-stone-300 bg-stone-950/80 p-2 rounded-lg border border-stone-800 mb-3 grid grid-cols-2 gap-1 font-sans">
                                        @foreach($previewStats as $stat => $val)
                                            @if($val > 0 && !in_array($stat, ['mint', 'max_mint']))
                                                <div class="flex justify-between">
                                                    <span class="text-stone-400 capitalize truncate mr-1">{{ str_replace('_', ' ', $stat) }}</span>
                                                    <span class="text-emerald-400 font-bold">+{{ $val }}</span>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                    
                                    <div class="flex flex-col mt-auto pt-2 border-t border-amber-900/40">
                                        <div class="flex justify-between items-center mb-2 text-[10px] text-stone-400 font-sans">
                                            <span>Sprzedawca: <strong class="text-amber-200">{{ $listing->seller->name }}</strong></span>
                                            <span class="text-amber-500/80 font-bold"><i class="fa-regular fa-clock mr-1"></i>{{ $listing->expires_at->diffForHumans() }}</span>
                                        </div>
                                        
                                        <button wire:click="confirmBuy('{{ $listing->id }}')" 
                                            wire:loading.attr="disabled" wire:target="confirmBuy('{{ $listing->id }}')"
                                            wire:loading.class="opacity-50 cursor-not-allowed" wire:target="confirmBuy('{{ $listing->id }}')"
                                            class="w-full flex items-center justify-center font-extrabold py-2 px-3 rounded-lg text-xs uppercase tracking-wider transition-all duration-200 shadow-md border cursor-pointer
                                            @if($listing->currency === 'gold' && $character->gold >= $listing->price) bg-gradient-to-b from-amber-700 via-amber-800 to-amber-950 hover:from-amber-600 hover:to-amber-900 text-yellow-200 border-amber-500 shadow-[0_0_10px_rgba(245,158,11,0.3)]
                                            @elseif($listing->currency === 'gems' && auth()->user()->gems >= $listing->price) bg-gradient-to-b from-purple-700 via-purple-800 to-purple-950 hover:from-purple-600 hover:to-purple-900 text-purple-100 border-purple-500 shadow-[0_0_10px_rgba(168,85,247,0.3)]
                                            @else bg-stone-900 text-stone-500 border-stone-800 cursor-not-allowed opacity-60 @endif">
                                            
                                            <span wire:loading.remove wire:target="confirmBuy('{{ $listing->id }}')" class="flex items-center gap-1.5">
                                                <span>KUP ZA</span> 
                                                <span class="font-extrabold text-sm {{ $listing->currency === 'gold' ? 'text-yellow-300' : 'text-purple-300' }}">
                                                    {{ number_format($listing->price) }}
                                                    @if($listing->currency === 'gold') <i class="fa-solid fa-coins ml-0.5"></i> @else <i class="fa-solid fa-gem ml-0.5"></i> @endif
                                                </span>
                                            </span>
                                            <span wire:loading wire:target="confirmBuy('{{ $listing->id }}')" class="flex items-center gap-2">
                                                <i class="fa-solid fa-circle-notch animate-spin"></i>
                                                <span>Ładowanie...</span>
                                            </span>
                                        </button>
                                    </div>
                                </div>
                            @endif
                            @endforeach
                        </div>

                        <div class="mt-4">
                            {{ $listings->links() }}
                        </div>
                    @else
                        <div class="bg-stone-950/80 border-2 border-amber-900/60 rounded-2xl p-12 text-center text-stone-400 shadow-2xl">
                            <div class="mb-4">
                                <i class="fa-solid fa-spider text-amber-700/50 text-5xl"></i>
                            </div>
                            <h3 class="text-lg font-extrabold text-amber-300 mb-1">Brak Wyników Wyszukiwania</h3>
                            <p class="text-xs text-amber-200/60 font-sans">Nie znaleziono ofert spełniających podane kryteria filtracji.</p>
                            <button wire:click="resetFilters" class="mt-5 px-4 py-2 rounded-xl bg-stone-900 border border-amber-700/60 text-amber-300 font-extrabold text-xs uppercase tracking-wider hover:bg-amber-950 hover:border-amber-400 transition-all inline-flex items-center gap-2 cursor-pointer shadow">
                                <i class="fa-solid fa-rotate-left"></i>
                                <span>Wyczyść Filtry</span>
                            </button>
                        </div>
                    @endif
                </div>
            </div>
            
        @else
            {{-- My Listings Tab --}}
            <div class="bg-stone-950/90 border-2 border-amber-800/80 p-6 rounded-2xl shadow-2xl backdrop-blur-sm min-h-[400px]">
                
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 mb-6 border-b border-amber-900/60 pb-4">
                    <div>
                        <h3 class="text-lg font-extrabold text-amber-400 uppercase tracking-wider flex items-center gap-2">
                            <i class="fa-solid fa-box-open text-amber-500"></i>
                            <span>Moje Aktywne Oferty Aukcyjne</span>
                        </h3>
                        <p class="text-xs text-amber-300/60 font-sans mt-0.5">Twoje wystawione przedmioty na targu. Pobierana jest prowizja w wysokości 5% po udanej sprzedaży.</p>
                    </div>
                </div>
                
                @if(count($myItemListings) > 0)
                    <div class="overflow-x-auto custom-scrollbar mb-8">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-stone-900/90 border-b-2 border-amber-900/60 text-amber-400 uppercase text-[10px] tracking-widest font-extrabold">
                                    <th class="p-3">Przedmiot</th>
                                    <th class="p-3 text-center">Status</th>
                                    <th class="p-3 text-right">Cena Ofertowa</th>
                                    <th class="p-3 text-right">Wygasa za</th>
                                    <th class="p-3 text-center">Akcja</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-amber-900/30 text-xs">
                                @foreach($myItemListings as $listing)
                                    <tr class="hover:bg-amber-950/20 transition-colors">
                                        <td class="p-3">
                                            <div class="flex items-center space-x-3">
                                                <div class="w-9 h-9 rounded-lg border border-amber-600/80 bg-stone-900 flex items-center justify-center shrink-0 text-amber-400 text-base shadow-inner relative">
                                                    @if($listing->item->template->icon)
                                                        <img src="{{ route('assets.items', ['filename' => $listing->item->template->icon]) }}" class="w-full h-full object-contain p-0.5" alt="{{ $listing->item->template->name }}">
                                                    @else
                                                        @if($listing->item->template->slot === 'weapon') <i class="fa-solid fa-khanda"></i>
                                                        @elseif($listing->item->template->slot === 'head') <i class="fa-solid fa-helmet-safety"></i>
                                                        @elseif($listing->item->template->slot === 'chest') <i class="fa-solid fa-shield-halved"></i>
                                                        @elseif($listing->item->template->slot === 'legs') <i class="fa-solid fa-vest"></i>
                                                        @elseif($listing->item->template->slot === 'boots') <i class="fa-solid fa-shoe-prints"></i>
                                                        @else <i class="fa-solid fa-box"></i>
                                                        @endif
                                                    @endif
                                                    <x-item-upgrade-overlay :level="$listing->item->upgrade_level ?? 0" :type="$listing->item->template->type ?? ''" />
                                                    @if(($listing->item->stack_size ?? 1) > 1)
                                                        <span class="absolute bottom-0.5 right-0.5 text-amber-200 font-extrabold text-[9px] bg-black/80 border border-amber-600/60 px-0.5 rounded shadow leading-none z-10">x{{ $listing->item->stack_size }}</span>
                                                    @endif
                                                </div>
                                                <div>
                                                    <div class="font-extrabold text-amber-100 
                                                        @if($listing->item->rarity === 'uncommon') text-emerald-300
                                                        @elseif($listing->item->rarity === 'rare') text-sky-300
                                                        @elseif($listing->item->rarity === 'epic') text-purple-300
                                                        @elseif($listing->item->rarity === 'legendary') text-amber-300
                                                        @endif">
                                                        {{ $listing->item->template->name }}
                                                        @if(($listing->item->upgrade_level ?? 0) > 0) <span class="text-amber-400 font-bold ml-0.5">+{{ $listing->item->upgrade_level }}</span> @endif
                                                    </div>
                                                    <div class="text-[10px] text-amber-500/80 font-bold uppercase">Lvl {{ $listing->item->template->level_requirement }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="p-3 text-center">
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider
                                                {{ $listing->status === 'active' ? 'bg-emerald-950 text-emerald-300 border border-emerald-600/80 shadow-[0_0_8px_rgba(16,185,129,0.3)]' : 
                                                  ($listing->status === 'sold' ? 'bg-sky-950 text-sky-300 border border-sky-600/80' : 'bg-stone-900 text-stone-400 border border-stone-700') }}">
                                                @if($listing->status === 'active') <i class="fa-solid fa-circle-check"></i>
                                                @elseif($listing->status === 'sold') <i class="fa-solid fa-handshake"></i>
                                                @endif
                                                {{ $listing->statusLabel() }}
                                            </span>
                                        </td>
                                        <td class="p-3 text-right font-extrabold text-sm">
                                            <span class="{{ $listing->currency === 'gold' ? 'text-yellow-300' : 'text-purple-300' }}">
                                                {{ number_format($listing->price) }}
                                                @if($listing->currency === 'gold') <i class="fa-solid fa-coins ml-0.5 text-xs"></i> @else <i class="fa-solid fa-gem ml-0.5 text-xs"></i> @endif
                                            </span>
                                        </td>
                                        <td class="p-3 text-right text-xs font-sans">
                                            <div class="text-stone-300">{{ $listing->created_at->format('Y-m-d H:i') }}</div>
                                            <div class="text-[10px] text-amber-500/80 font-bold"><i class="fa-regular fa-clock mr-1"></i>{{ $listing->expires_at->diffForHumans() }}</div>
                                        </td>
                                        <td class="p-3 text-center">
                                            @if($listing->status === 'active')
                                                <button wire:click="cancelListing('{{ $listing->id }}')" onclick="confirm('Czy na pewno chcesz anulować tę ofertę? Opłata za wystawienie nie zostanie zwrócona.') || event.stopImmediatePropagation()"
                                                    wire:loading.attr="disabled" wire:target="cancelListing('{{ $listing->id }}')"
                                                    wire:loading.class="opacity-50 cursor-not-allowed" wire:target="cancelListing('{{ $listing->id }}')"
                                                    class="px-3 py-1.5 bg-gradient-to-b from-red-900 to-stone-950 hover:from-red-800 hover:to-stone-900 text-red-200 border border-red-700/80 rounded-lg transition-all text-xs font-extrabold uppercase tracking-wider inline-flex items-center gap-1.5 shadow cursor-pointer">
                                                    <span wire:loading.remove wire:target="cancelListing('{{ $listing->id }}')" class="flex items-center gap-1">
                                                        <i class="fa-solid fa-xmark"></i>
                                                        <span>Anuluj</span>
                                                    </span>
                                                    <span wire:loading wire:target="cancelListing('{{ $listing->id }}')" class="flex items-center gap-1">
                                                        <i class="fa-solid fa-circle-notch animate-spin"></i>
                                                    </span>
                                                </button>
                                            @else
                                                <span class="text-stone-600">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 mb-8">
                        {{ $myItemListings->links() }}
                    </div>
                @endif

                @if(count($myPetListings) > 0)
                    <div class="overflow-x-auto custom-scrollbar">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-stone-900/90 border-b-2 border-amber-900/60 text-amber-400 uppercase text-[10px] tracking-widest font-extrabold">
                                    <th class="p-3">Chowaniec</th>
                                    <th class="p-3 text-center">Status</th>
                                    <th class="p-3 text-right">Cena Ofertowa</th>
                                    <th class="p-3 text-right">Wygasa za</th>
                                    <th class="p-3 text-center">Akcja</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-amber-900/30 text-xs">
                                @foreach($myPetListings as $listing)
                                    <tr class="hover:bg-amber-950/20 transition-colors">
                                        <td class="p-3">
                                            <div class="flex items-center space-x-3">
                                                <div class="w-9 h-9 rounded-lg border border-amber-600/80 bg-stone-900 flex items-center justify-center shrink-0 text-amber-400 text-base shadow-inner relative">
                                                    <img src="{{ route('assets.items', ['filename' => $listing->pet->icon]) }}" alt="{{ $listing->pet->name }}" class="w-full h-full object-contain p-1">
                                                </div>
                                                <div>
                                                    <div class="font-extrabold text-amber-100">{{ $listing->pet->name }}</div>
                                                    <div class="text-[10px] text-amber-500/80 font-bold uppercase">{{ $listing->pet->tierName() }} · Lvl {{ $listing->pet->level }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="p-3 text-center">
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider
                                                {{ $listing->status === 'active' ? 'bg-emerald-950 text-emerald-300 border border-emerald-600/80 shadow-[0_0_8px_rgba(16,185,129,0.3)]' :
                                                  ($listing->status === 'sold' ? 'bg-sky-950 text-sky-300 border border-sky-600/80' : 'bg-stone-900 text-stone-400 border border-stone-700') }}">
                                                @if($listing->status === 'active') <i class="fa-solid fa-circle-check"></i>
                                                @elseif($listing->status === 'sold') <i class="fa-solid fa-handshake"></i>
                                                @endif
                                                {{ $listing->statusLabel() }}
                                            </span>
                                        </td>
                                        <td class="p-3 text-right font-extrabold text-sm">
                                            <span class="{{ $listing->currency === 'gold' ? 'text-yellow-300' : 'text-purple-300' }}">
                                                {{ number_format($listing->price) }}
                                                @if($listing->currency === 'gold') <i class="fa-solid fa-coins ml-0.5 text-xs"></i> @else <i class="fa-solid fa-gem ml-0.5 text-xs"></i> @endif
                                            </span>
                                        </td>
                                        <td class="p-3 text-right text-xs font-sans">
                                            <div class="text-stone-300">{{ $listing->created_at->format('Y-m-d H:i') }}</div>
                                            <div class="text-[10px] text-amber-500/80 font-bold"><i class="fa-regular fa-clock mr-1"></i>{{ $listing->expires_at->diffForHumans() }}</div>
                                        </td>
                                        <td class="p-3 text-center">
                                            @if($listing->status === 'active')
                                                <button wire:click="cancelListing('{{ $listing->id }}')" onclick="confirm('Czy na pewno chcesz anulować tę ofertę? Opłata za wystawienie nie zostanie zwrócona.') || event.stopImmediatePropagation()"
                                                    wire:loading.attr="disabled" wire:target="cancelListing('{{ $listing->id }}')"
                                                    wire:loading.class="opacity-50 cursor-not-allowed" wire:target="cancelListing('{{ $listing->id }}')"
                                                    class="px-3 py-1.5 bg-gradient-to-b from-red-900 to-stone-950 hover:from-red-800 hover:to-stone-900 text-red-200 border border-red-700/80 rounded-lg transition-all text-xs font-extrabold uppercase tracking-wider inline-flex items-center gap-1.5 shadow cursor-pointer">
                                                    <span wire:loading.remove wire:target="cancelListing('{{ $listing->id }}')" class="flex items-center gap-1">
                                                        <i class="fa-solid fa-xmark"></i>
                                                        <span>Anuluj</span>
                                                    </span>
                                                    <span wire:loading wire:target="cancelListing('{{ $listing->id }}')" class="flex items-center gap-1">
                                                        <i class="fa-solid fa-circle-notch animate-spin"></i>
                                                    </span>
                                                </button>
                                            @else
                                                <span class="text-stone-600">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $myPetListings->links() }}
                    </div>
                @endif

                @if(count($myItemListings) === 0 && count($myPetListings) === 0)
                    <div class="text-center py-12 text-stone-400">
                        <div class="mb-4">
                            <i class="fa-solid fa-box-open text-amber-700/50 text-5xl"></i>
                        </div>
                        <h4 class="text-base font-extrabold text-amber-300 mb-1">Brak Wystawionych Ofert</h4>
                        <p class="text-xs text-amber-200/60 font-sans">Nie masz obecnie żadnych przedmiotów ani chowańców na sprzedaż.</p>
                        <p class="text-xs text-stone-500 font-sans mt-2">Aby wystawić przedmiot, otwórz swój ekwipunek i kliknij "Wystaw na targowisko". Aby wystawić chowańca, przejdź do zakładki Pety.</p>
                    </div>
                @endif

            </div>
        @endif
    </div>

    {{-- ===== BUY CONFIRMATION MODAL ===== --}}
    @if($showConfirmBuyModal && $confirmListing)
        @php
            $isPet = $confirmListing->pet_id !== null;
            $buyerBalance = $confirmListing->currency === 'gold' ? $character->gold : (auth()->user()->gems ?? 0);
            $hasEnough = $buyerBalance >= $confirmListing->price;
        @endphp
        <div class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
            {{-- Backdrop --}}
            <div class="fixed inset-0 bg-stone-950/80 backdrop-blur-sm transition-opacity" wire:click="closeConfirmBuyModal"></div>

            {{-- Modal Panel --}}
            <div class="relative bg-stone-950 border-2 border-amber-500/80 rounded-2xl shadow-[0_0_50px_rgba(0,0,0,0.9),0_0_20px_rgba(245,158,11,0.3)] max-w-lg w-full p-6 text-amber-100 z-10 overflow-hidden"
                 style="background: radial-gradient(circle at 50% 0%, #1c1917 0%, #0c0a09 100%);">
                
                {{-- Header --}}
                <div class="flex items-center justify-between border-b-2 border-amber-900/60 pb-4 mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-950/90 border border-amber-500/60 flex items-center justify-center text-amber-400 text-lg shadow-inner">
                            <i class="fa-solid fa-cart-shopping"></i>
                        </div>
                        <div>
                            <h3 class="text-base sm:text-lg font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-yellow-200 via-amber-400 to-amber-500">POTWIERDZENIE ZAKUPU</h3>
                            <p class="text-[11px] text-amber-300/60 font-sans">Czy na pewno chcesz kupić ten {{ $isPet ? 'chowaniec' : 'przedmiot' }}?</p>
                        </div>
                    </div>
                    <button wire:click="closeConfirmBuyModal" class="text-stone-400 hover:text-amber-300 transition-colors p-1 cursor-pointer">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                {{-- Item / Pet Details Card --}}
                <div class="bg-stone-900/90 border border-amber-900/60 rounded-xl p-4 mb-4 shadow-inner">
                    @if($isPet)
                        @php $petTierMeta = $tiers[$confirmListing->pet->tier] ?? ['name' => 'Nieznany']; @endphp
                        <div class="flex items-center space-x-3 mb-3">
                            <div class="w-14 h-14 rounded-xl border-2 border-amber-500/80 flex items-center justify-center shrink-0 bg-stone-950 text-3xl shadow-inner relative">
                                <img src="{{ route('assets.items', ['filename' => $confirmListing->pet->icon]) }}" alt="{{ $confirmListing->pet->name }}" class="w-full h-full object-contain p-1">
                            </div>
                            <div class="min-w-0 flex-1">
                                <h4 class="font-extrabold text-amber-100 text-base truncate leading-snug">{{ $confirmListing->pet->name }}</h4>
                                <div class="text-xs text-amber-400/80 font-bold uppercase tracking-wider mt-0.5 flex items-center gap-2">
                                    <span>Lvl {{ $confirmListing->pet->level }}</span>
                                    <span>•</span>
                                    <span>{{ $petTierMeta['name'] }}</span>
                                    @if($confirmListing->pet->fusion_count > 0)
                                        <span>•</span>
                                        <span class="text-sky-400">+{{ $confirmListing->pet->fusion_count }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        @if(!empty($confirmListing->pet->stats))
                            <div class="text-xs text-stone-300 bg-stone-950/80 p-2.5 rounded-lg border border-stone-800 grid grid-cols-2 gap-1.5 font-sans mb-2">
                                @foreach($confirmListing->pet->stats as $stat => $val)
                                    <div class="flex justify-between">
                                        <span class="text-stone-400 uppercase truncate mr-1">{{ $stat }}</span>
                                        <span class="text-emerald-400 font-bold">+{{ $val }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    @else
                        <div class="flex items-center space-x-3 mb-3">
                            <div class="w-14 h-14 rounded-xl border-2 border-amber-500/80 flex items-center justify-center shrink-0 bg-stone-950 text-3xl shadow-inner relative">
                                @if($confirmListing->item->template->icon)
                                    <img src="{{ route('assets.items', ['filename' => $confirmListing->item->template->icon]) }}" class="w-full h-full object-contain p-1" alt="{{ $confirmListing->item->template->name }}">
                                @else
                                    <span class="text-amber-400 text-xl">
                                        @if($confirmListing->item->template->slot === 'weapon') <i class="fa-solid fa-khanda"></i>
                                        @elseif($confirmListing->item->template->slot === 'head') <i class="fa-solid fa-helmet-safety"></i>
                                        @elseif($confirmListing->item->template->slot === 'chest') <i class="fa-solid fa-shield-halved"></i>
                                        @elseif($confirmListing->item->template->slot === 'legs') <i class="fa-solid fa-vest"></i>
                                        @elseif($confirmListing->item->template->slot === 'boots') <i class="fa-solid fa-shoe-prints"></i>
                                        @else <i class="fa-solid fa-box"></i>
                                        @endif
                                    </span>
                                @endif
                                <x-item-upgrade-overlay :level="$confirmListing->item->upgrade_level ?? 0" :type="$confirmListing->item->template->type ?? ''" />
                                @if(($confirmListing->item->stack_size ?? 1) > 1)
                                    <span class="absolute bottom-0.5 right-0.5 text-amber-200 font-extrabold text-[10px] bg-black/80 border border-amber-600/60 px-1 rounded shadow leading-none z-10">x{{ $confirmListing->item->stack_size }}</span>
                                @endif
                            </div>
                            
                            <div class="min-w-0 flex-1">
                                <h4 class="font-extrabold text-amber-100 text-base truncate leading-snug">
                                    {{ $confirmListing->item->template->name }}
                                    @if(($confirmListing->item->upgrade_level ?? 0) > 0) <span class="text-amber-400 font-bold ml-0.5">+{{ $confirmListing->item->upgrade_level }}</span> @endif
                                </h4>
                                <div class="text-xs font-bold uppercase tracking-wider mt-0.5 flex items-center gap-2">
                                    <span class="text-amber-400/80">Lvl {{ $confirmListing->item->template->level_requirement }}</span>
                                    <span>•</span>
                                    <span class="
                                        @if($confirmListing->item->rarity === 'common') text-stone-400
                                        @elseif($confirmListing->item->rarity === 'uncommon') text-emerald-400
                                        @elseif($confirmListing->item->rarity === 'rare') text-sky-400
                                        @elseif($confirmListing->item->rarity === 'epic') text-purple-400
                                        @elseif($confirmListing->item->rarity === 'legendary') text-amber-400
                                        @endif">
                                        {{ ucfirst($confirmListing->item->rarity) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        @php
                            $modalPreviewStats = $confirmListing->item->getTotalStats();
                            $upStats = $confirmListing->item->getUpgradeBonusStats();
                            foreach ($upStats as $s => $v) {
                                $modalPreviewStats[$s] = ($modalPreviewStats[$s] ?? 0) + $v;
                            }
                        @endphp
                        @if(!empty($modalPreviewStats))
                            <div class="text-xs text-stone-300 bg-stone-950/80 p-2.5 rounded-lg border border-stone-800 grid grid-cols-2 gap-1.5 font-sans mb-2">
                                @foreach($modalPreviewStats as $stat => $val)
                                    @if($val > 0 && !in_array($stat, ['mint', 'max_mint']))
                                        <div class="flex justify-between">
                                            <span class="text-stone-400 capitalize truncate mr-1">{{ str_replace('_', ' ', $stat) }}</span>
                                            <span class="text-emerald-400 font-bold">+{{ $val }}</span>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    @endif

                    <div class="text-xs text-stone-400 font-sans flex items-center justify-between border-t border-stone-800/80 pt-2 mt-2">
                        <span>Sprzedawca: <strong class="text-amber-200 font-serif">{{ $confirmListing->seller->name ?? 'Nieznany' }}</strong></span>
                        <span class="text-amber-500/80 font-bold"><i class="fa-regular fa-clock mr-1"></i>{{ $confirmListing->expires_at->diffForHumans() }}</span>
                    </div>
                </div>

                {{-- Price & Balance Summary Box --}}
                <div class="bg-gradient-to-b from-stone-900 to-stone-950 border border-amber-900/80 rounded-xl p-4 mb-6 shadow-inner space-y-3 font-sans">
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-bold uppercase tracking-wider text-amber-300/80">CENA ZAKUPU:</span>
                        <span class="font-extrabold text-lg sm:text-xl {{ $confirmListing->currency === 'gold' ? 'text-yellow-300' : 'text-purple-300' }}">
                            {{ number_format($confirmListing->price) }}
                            @if($confirmListing->currency === 'gold') <i class="fa-solid fa-coins ml-1 text-amber-400"></i> Złota @else <i class="fa-solid fa-gem ml-1 text-purple-400"></i> Klejnotów @endif
                        </span>
                    </div>

                    <div class="flex justify-between items-center border-t border-amber-900/40 pt-2 text-xs">
                        <span class="text-stone-400">Twój stan posiadania:</span>
                        <span class="font-bold {{ $hasEnough ? 'text-emerald-400' : 'text-red-400' }}">
                            {{ number_format($buyerBalance) }}
                            @if($confirmListing->currency === 'gold') Złota @else Klejnotów @endif
                        </span>
                    </div>

                    @if(!$hasEnough)
                        <div class="p-2 rounded-lg bg-red-950/80 border border-red-800/80 text-red-300 text-xs flex items-center gap-2">
                            <i class="fa-solid fa-triangle-exclamation text-red-400 shrink-0"></i>
                            <span>Nie masz wystarczającej ilości {{ $confirmListing->currency === 'gold' ? 'złota' : 'klejnotów' }}, aby dokonać zakupu.</span>
                        </div>
                    @endif
                </div>

                {{-- Action Buttons --}}
                <div class="flex items-center gap-3">
                    <button wire:click="closeConfirmBuyModal"
                            class="w-1/2 py-2.5 min-h-[44px] rounded-xl bg-stone-900 hover:bg-stone-800 text-stone-300 font-extrabold text-xs uppercase tracking-widest border border-stone-700 transition-all cursor-pointer shadow">
                        Anuluj
                    </button>

                    <button wire:click="buyItem"
                            @if(!$hasEnough) disabled @endif
                            wire:loading.attr="disabled" wire:target="buyItem"
                            wire:loading.class="opacity-50 cursor-not-allowed" wire:target="buyItem"
                            class="w-1/2 py-2.5 min-h-[44px] rounded-xl font-extrabold text-xs uppercase tracking-widest transition-all duration-200 shadow-lg border cursor-pointer flex items-center justify-center gap-2
                            @if($hasEnough)
                                @if($confirmListing->currency === 'gold') bg-gradient-to-b from-amber-600 via-amber-700 to-amber-900 hover:from-amber-500 hover:to-amber-800 text-yellow-100 border-amber-400 shadow-[0_0_15px_rgba(245,158,11,0.4)]
                                @else bg-gradient-to-b from-purple-600 via-purple-700 to-purple-900 hover:from-purple-500 hover:to-purple-800 text-purple-100 border-purple-400 shadow-[0_0_15px_rgba(168,85,247,0.4)]
                                @endif
                            @else bg-stone-900 text-stone-500 border-stone-800 cursor-not-allowed opacity-50 @endif">

                        <span wire:loading.remove wire:target="buyItem" class="flex items-center gap-1.5">
                            <i class="fa-solid fa-check"></i>
                            <span>Potwierdzam zakup</span>
                        </span>
                        <span wire:loading wire:target="buyItem" class="flex items-center gap-2">
                            <i class="fa-solid fa-circle-notch animate-spin"></i>
                            <span>Kupowanie...</span>
                        </span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
