<div class="min-h-screen text-amber-100 relative overflow-hidden bg-black">
    <!-- Static Background -->
    <div class="absolute inset-0 bg-cover bg-center opacity-40 mix-blend-luminosity" style="background-image: url('{{ asset('img/swordmaster.png') }}');"></div>
    <div class="absolute inset-0 bg-gradient-to-b from-slate-900/60 via-slate-900/80 to-slate-900/95"></div>

    @php
        $gameStage = auth()->user()->game_stage;
        $slotFilters = [
            'all' => ['label' => 'Wszystko', 'icon' => 'fa-list'],
            'weapon' => ['label' => 'Broń', 'icon' => 'fa-khanda'],
            'chest' => ['label' => 'Zbroje', 'icon' => 'fa-shield-halved'],
            'head' => ['label' => 'Hełmy', 'icon' => 'fa-crown'],
            'feet' => ['label' => 'Buty', 'icon' => 'fa-shoe-prints'],
            'neck' => ['label' => 'Naszyjniki', 'icon' => 'fa-gem'],
            'ring' => ['label' => 'Pierścienie', 'icon' => 'fa-ring'],
        ];
    @endphp

    @if($gameStage == 17)
        <livewire:global.tutorial-overlay :step="18" />
    @elseif($gameStage == 18)
        <livewire:global.tutorial-overlay :step="19" />
    @endif

    <div class="relative w-full px-6 md:px-10 lg:px-12 py-8 min-h-screen flex flex-col">
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-8 gap-4">
            <div class="bg-black/60 border border-amber-700/50 rounded-lg p-3 sm:p-4 shadow-2xl backdrop-blur-md">
                <h2 class="text-xl sm:text-2xl font-bold text-amber-500 medieval-font flex items-center gap-2 tracking-wider">
                    <i class="fa-solid fa-shop text-amber-500 text-xl sm:text-2xl mr-1"></i> Handlarz
                </h2>
            </div>

            <div class="flex items-center gap-3 w-full sm:w-auto justify-between sm:justify-end">
                <div class="bg-black/80 border border-yellow-600/50 rounded px-4 py-2 min-h-[44px] flex items-center font-bold text-yellow-400 backdrop-blur-sm shadow-inner">
                    <i class="fa-solid fa-coins text-yellow-400 mr-1.5"></i> {{ number_format($character->gold) }}
                </div>
                <button wire:click="backToHub" @click="$dispatch('location-leave', { text: 'Podróż do Miasta...', icon: 'fa-solid fa-archway', url: '{{ route('city.hub', $character->id) }}' })"
                    class="min-h-[44px] bg-gradient-to-b from-slate-700 to-slate-800 hover:from-slate-600 hover:to-slate-700 border border-slate-500 text-amber-200 font-bold py-2.5 px-6 rounded-lg transition-all duration-200 shadow-[0_4px_15px_rgba(0,0,0,0.5)] medieval-font flex items-center justify-center {{ $gameStage == 20 ? 'animate-[pulse_1.5s_ease-in-out_infinite] ring-2 ring-amber-500 shadow-[0_0_20px_rgba(245,158,11,0.6)]' : '' }}">
                    <i class="fa-solid fa-archway mr-2 text-amber-400"></i> Powrót
                </button>
            </div>
        </div>

        <div class="bg-black/50 border border-amber-700/30 rounded-xl shadow-[0_0_40px_rgba(0,0,0,0.8)] backdrop-blur-md flex-grow flex flex-col">

            {{-- Content --}}
            <div class="p-6 flex-grow flex flex-col h-full">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 h-full">

                        <!-- Lewa: Asortyment Sklepu -->
                        <div class="bg-gray-900/60 rounded-xl border border-gray-700/50 p-4 flex flex-col">
                            <h3 class="text-xl font-bold text-amber-400 mb-3 border-b border-gray-700/50 pb-2 text-center medieval-font">Asortyment</h3>

                            <div class="flex flex-col gap-2 mb-4" wire:loading.class="opacity-60 pointer-events-none" wire:target="setTierFilter, setSlotFilter">
                                @if($availableTiers->count() > 1)
                                    <div class="flex flex-wrap items-center gap-1.5 justify-center">
                                        <button wire:click="setTierFilter('all')" wire:loading.attr="disabled" wire:target="setTierFilter" class="px-2.5 py-1 rounded text-[11px] font-bold flex items-center gap-1 transition border {{ $tierFilter === 'all' ? 'bg-amber-600 border-amber-400 text-white' : 'bg-slate-900/70 border-slate-700 text-gray-400 hover:text-amber-200 hover:border-amber-600' }}">
                                            <i class="fa-solid fa-layer-group"></i> Wszystkie Tiery
                                        </button>
                                        @foreach($availableTiers as $tierLevel)
                                            <button wire:click="setTierFilter({{ $tierLevel }})" wire:loading.attr="disabled" wire:target="setTierFilter" class="px-2.5 py-1 rounded text-[11px] font-bold flex items-center gap-1 transition border {{ (string) $tierFilter === (string) $tierLevel ? 'bg-amber-600 border-amber-400 text-white' : 'bg-slate-900/70 border-slate-700 text-gray-400 hover:text-amber-200 hover:border-amber-600' }}">
                                                Tier {{ $loop->iteration }} <span class="opacity-70">(poz. {{ $tierLevel }}+)</span>
                                            </button>
                                        @endforeach
                                    </div>
                                @endif

                                <div class="flex flex-wrap items-center gap-1.5 justify-center">
                                    @foreach($slotFilters as $key => $meta)
                                        <button wire:click="setSlotFilter('{{ $key }}')" wire:loading.attr="disabled" wire:target="setSlotFilter" class="px-2.5 py-1 rounded text-[11px] font-bold flex items-center gap-1 transition border {{ $slotFilter === $key ? 'bg-amber-600 border-amber-400 text-white' : 'bg-slate-900/70 border-slate-700 text-gray-400 hover:text-amber-200 hover:border-amber-600' }}">
                                            <i class="fa-solid {{ $meta['icon'] }}"></i> {{ $meta['label'] }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            <div class="flex flex-col gap-5">
                                @forelse($shopItems->sortBy('required_level')->groupBy('required_level') as $tierLevel => $tierItems)
                                    <div wire:key="tier-group-{{ $tierLevel }}">
                                        <div class="flex items-center gap-2 mb-2.5">
                                            <div class="h-px bg-gradient-to-r from-amber-600/50 to-transparent flex-1"></div>
                                            <span class="text-[11px] font-extrabold uppercase tracking-widest text-amber-400/90 whitespace-nowrap">
                                                Tier {{ $availableTiers->search($tierLevel) + 1 }} <span class="text-amber-200/50 font-semibold normal-case">(poz. {{ $tierLevel }}+)</span>
                                            </span>
                                            <div class="h-px bg-gradient-to-l from-amber-600/50 to-transparent flex-1"></div>
                                        </div>

                                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                                            @foreach($tierItems as $item)
                                                <div wire:key="shop-{{ $item->id }}" class="relative smart-tooltip-trigger cursor-pointer" x-data="smartTooltip()"
                                                     :class="{ 'z-50': showInfo, 'z-10': !showInfo }"
                                                     @click="toggleTooltip()"
                                                     @resize.window.debounce.100ms="updatePosition()"
                                                     @tooltip-updated.window="updatePosition()">

                                                    <div class="bg-black/80 border {{ \App\Helpers\ItemHelper::getRarityBorderClass($item->rarity ?? 'common') }} rounded-lg p-3 flex flex-col items-center text-center cursor-pointer transition-all h-full {{ $gameStage == 19 && $item->template->type === 'weapon' ? 'animate-pulse ring-2 ring-amber-500 shadow-[0_0_20px_rgba(245,158,11,0.6)]' : '' }}">
                                                        @if($item->template->icon)
                                                            <div class="w-12 h-12 mb-2">
                                                                <img src="{{ route('assets.items', ['filename' => $item->template->icon]) }}" class="w-full h-full object-contain" alt="{{ $item->template->name }}">
                                                            </div>
                                                        @endif
                                                        <h4 class="font-bold text-sm text-blue-300 line-clamp-2 leading-tight">{{ $item->template->name }}</h4>
                                                        <div class="mt-auto pt-2 text-yellow-400 text-sm font-bold"><i class="fa-solid fa-coins text-yellow-400 mr-1"></i> {{ $shopPrices[$item->id] ?? 0 }}</div>
                                                    </div>

                                                    <!-- Infobox Sklepu (teleportowany do <body>) -->
                                                    <template x-teleport="body">
                                                        <div x-show="showInfo" x-transition.opacity
                                                             class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-xs pointer-events-auto"
                                                             @click.self="showInfo = false"
                                                             @keydown.escape.window="showInfo = false">
                                                            <div x-ref="tooltipContainer" data-tooltip-container
                                                                 :style="tooltipStyle"
                                                                 class="pointer-events-auto"
                                                                 @click.stop>
                                                                <x-item-tooltip :item="$item" :equippedItem="$equipped[$item->template->slot ?? ''] ?? null">
                                                                    <x-slot:actions>
                                                                        <button wire:click.stop="buyItem('{{ $item->id }}')" @click="showInfo = false" wire:loading.attr="disabled" class="w-full bg-green-700 hover:bg-green-600 text-white font-bold py-2 rounded shadow transition flex items-center justify-center gap-2 cursor-pointer">
                                                                            <span>Kup za <i class="fa-solid fa-coins text-yellow-400 mx-1"></i> {{ $shopPrices[$item->id] ?? 0 }}</span>
                                                                        </button>
                                                                    </x-slot:actions>
                                                                </x-item-tooltip>
                                                            </div>
                                                        </div>
                                                    </template>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-center text-gray-500 py-12">Brak asortymentu w tej kategorii</div>
                                @endforelse
                            </div>
                        </div>

                        <!-- Prawa: Ekwipunek gracza -->
                        <div class="bg-gray-900/60 rounded-xl border border-gray-700/50 p-4 flex flex-col">
                            <div class="flex flex-col gap-2 border-b border-gray-700/50 pb-2 mb-3">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-xl font-bold text-amber-400 medieval-font">
                                        {{ $playerItemFilter === 'materials' ? 'Magazyn Materiałów (' . $character->getMaterialStashCount() . '/100)' : 'Twój Plecak (' . $character->getBackpackCount() . '/' . $character->getBackpackCapacity() . ')' }}
                                    </h3>
                                    <button wire:click="toggleBulkSellMode" class="px-2.5 py-1 rounded text-xs font-bold transition flex items-center gap-1.5 {{ $bulkSellMode ? 'bg-amber-600 hover:bg-amber-500 text-white shadow' : 'bg-slate-800 hover:bg-slate-700 text-amber-300 border border-amber-500/40' }}">
                                        <i class="fa-solid {{ $bulkSellMode ? 'fa-square-check' : 'fa-list-check' }}"></i>
                                        <span>{{ $bulkSellMode ? 'Wyłącz masową sprzedaż' : 'Masowa sprzedaż' }}</span>
                                    </button>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button wire:click="setPlayerItemFilter('items')" class="px-3 py-1 text-xs font-bold rounded transition flex items-center gap-1.5 {{ $playerItemFilter === 'items' ? 'bg-amber-500 text-black shadow-md font-extrabold' : 'bg-slate-800 text-amber-300 hover:bg-slate-700 border border-amber-500/30' }}">
                                        <i class="fa-solid fa-toolbox"></i> Przedmioty
                                    </button>
                                    <button wire:click="setPlayerItemFilter('materials')" class="px-3 py-1 text-xs font-bold rounded transition flex items-center gap-1.5 {{ $playerItemFilter === 'materials' ? 'bg-amber-500 text-black shadow-md font-extrabold' : 'bg-slate-800 text-amber-300 hover:bg-slate-700 border border-amber-500/30' }}">
                                        <i class="fa-solid fa-cubes"></i> Materiały
                                    </button>
                                </div>

                                @if($playerItemFilter === 'items')
                                    <div class="flex flex-wrap items-center gap-1.5" wire:loading.class="opacity-60 pointer-events-none" wire:target="setSlotFilter">
                                        @foreach($slotFilters as $key => $meta)
                                            <button wire:click="setSlotFilter('{{ $key }}')" wire:loading.attr="disabled" wire:target="setSlotFilter" class="px-2 py-1 rounded text-[11px] font-bold flex items-center gap-1 transition border {{ $slotFilter === $key ? 'bg-amber-600 border-amber-400 text-white' : 'bg-slate-900/70 border-slate-700 text-gray-400 hover:text-amber-200 hover:border-amber-600' }}">
                                                <i class="fa-solid {{ $meta['icon'] }}"></i> {{ $meta['label'] }}
                                            </button>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            @if($bulkSellMode)
                                <div class="mb-3 bg-slate-950/80 p-2.5 rounded-lg border border-amber-500/30 flex flex-col gap-2 animate-[fade-in_0.2s_ease-out]">
                                    <div class="flex items-center justify-between flex-wrap gap-1.5">
                                        <span class="text-[11px] font-semibold text-gray-400">Szybkie zaznaczanie:</span>
                                        <div class="flex items-center gap-1 flex-wrap">
                                            <button wire:click="selectByRarity('common')" class="px-2 py-0.5 bg-slate-800 hover:bg-slate-700 text-gray-300 text-[11px] font-semibold rounded border border-gray-600 transition" title="Zaznacz/odznacz zwykłe przedmioty">
                                                + Zwykłe
                                            </button>
                                            <button wire:click="selectByRarity('uncommon')" class="px-2 py-0.5 bg-emerald-950/80 hover:bg-emerald-900 text-emerald-300 text-[11px] font-semibold rounded border border-emerald-700 transition" title="Zaznacz/odznacz niepospolite przedmioty">
                                                + Niepospolite
                                            </button>
                                            <button wire:click="selectAllInventory" class="px-2 py-0.5 bg-amber-950/80 hover:bg-amber-900 text-amber-300 text-[11px] font-semibold rounded border border-amber-700 transition">
                                                Wszystkie
                                            </button>
                                            @if(count($selectedItemIds) > 0)
                                                <button wire:click="clearSelection" class="px-2 py-0.5 bg-red-950/80 hover:bg-red-900 text-red-300 text-[11px] font-semibold rounded border border-red-700 transition">
                                                    Wyczyść
                                                </button>
                                            @endif
                                        </div>
                                    </div>

                                    @if(count($selectedItemIds) > 0)
                                        @php
                                            $bulkTotalValue = 0;
                                            foreach($inventoryItems as $invItem) {
                                                if(in_array($invItem->id, $selectedItemIds)) {
                                                    $bulkTotalValue += ($sellPrices[$invItem->id] ?? 0) * max(1, $invItem->stack_size ?? 1);
                                                }
                                            }
                                        @endphp
                                        <div class="p-2 bg-amber-950/60 border border-amber-500/50 rounded flex items-center justify-between gap-2">
                                            <div class="text-xs text-amber-200">
                                                Wybrano: <span class="font-bold text-white">{{ count($selectedItemIds) }}</span> szt. | Razem: <span class="font-extrabold text-yellow-400"><i class="fa-solid fa-coins text-yellow-400 mr-0.5"></i>{{ number_format($bulkTotalValue, 0, ',', ' ') }}</span>
                                            </div>
                                            <button wire:click="sellSelectedItems" class="px-3 py-1 bg-gradient-to-r from-red-700 to-amber-600 hover:from-red-600 hover:to-amber-500 text-white font-extrabold text-xs rounded shadow transition flex items-center gap-1">
                                                <i class="fa-solid fa-coins"></i>
                                                Sprzedaj
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            @endif

                            <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3">
                                @forelse($inventoryItems as $item)
                                    <div wire:key="inv-{{ $item->id }}" class="relative smart-tooltip-trigger cursor-pointer" x-data="smartTooltip()"
                                         :class="{ 'z-50': showInfo, 'z-10': !showInfo }"
                                         @click="toggleTooltip()"
                                         @resize.window.debounce.100ms="updatePosition()"
                                         @tooltip-updated.window="updatePosition()">

                                        <div class="aspect-square bg-black/80 border {{ \App\Helpers\ItemHelper::getRarityBorderClass($item->rarity ?? 'common') }} rounded-lg flex flex-col items-center justify-center cursor-pointer transition-all relative {{ \App\Helpers\ItemHelper::isEnchanted($item) ? 'enchanted-effect' : '' }}">
                                            @if(\App\Helpers\ItemHelper::isEnchanted($item))
                                                <div class="absolute top-1 left-1 z-10 text-[9px] text-fuchsia-300 drop-shadow-[0_0_4px_rgba(217,70,239,0.9)] enchanted-sparkle-icon pointer-events-none" title="Przedmiot zaczarowany"><i class="fa-solid fa-wand-sparkles"></i></div>
                                            @endif
                                            @if($item->template->icon)
                                                <div class="w-full h-full p-2 relative flex items-center justify-center">
                                                    <img src="{{ route('assets.items', ['filename' => $item->template->icon]) }}" class="w-full h-full object-contain drop-shadow-md" alt="{{ $item->template->name }}">
                                                    <x-item-upgrade-overlay :level="$item->upgrade_level ?? 0" :type="$item->template->type ?? ''" />
                                                    @if(($item->stack_size ?? 1) > 1)
                                                        <span class="absolute bottom-1 right-1 text-white font-bold text-xs bg-black/80 border border-slate-600 px-1.5 py-0.5 rounded shadow">x{{ $item->stack_size }}</span>
                                                    @endif
                                                </div>
                                            @else
                                                <div class="text-xs text-center p-1 truncate w-full">{{ $item->template->name }}</div>
                                            @endif
                                            @if($item->location === 'equipped')
                                                <div class="absolute -top-1 -right-1 bg-blue-600 border border-blue-400 text-white text-[9px] font-bold px-1 py-0.5 rounded shadow">E</div>
                                            @endif

                                            @if($bulkSellMode && $item->location !== 'equipped')
                                                <div wire:click.stop="toggleSelectItem('{{ $item->id }}')"
                                                     class="absolute inset-0 z-30 rounded-lg cursor-pointer flex items-start justify-end p-1 transition-all {{ in_array($item->id, $selectedItemIds) ? 'bg-amber-500/30 border-2 border-amber-400 shadow-[0_0_10px_rgba(245,158,11,0.5)]' : 'bg-black/40 hover:bg-amber-500/10 border-2 border-transparent' }}">
                                                    <div class="w-5 h-5 rounded flex items-center justify-center text-xs font-bold {{ in_array($item->id, $selectedItemIds) ? 'bg-amber-500 text-slate-950' : 'bg-slate-900/90 text-gray-400 border border-gray-600' }}">
                                                        @if(in_array($item->id, $selectedItemIds))
                                                            <i class="fa-solid fa-check"></i>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endif
                                        </div>

                                        <!-- Infobox Ekwipunku (teleportowany do <body>) -->
                                        <template x-teleport="body">
                                            <div x-show="showInfo" x-transition.opacity
                                                 class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-xs pointer-events-auto"
                                                 @click.self="showInfo = false"
                                                 @keydown.escape.window="showInfo = false">
                                                <div x-ref="tooltipContainer" data-tooltip-container
                                                     :style="tooltipStyle"
                                                     class="pointer-events-auto"
                                                     @click.stop>
                                                    <x-item-tooltip :item="$item" :equippedItem="$equipped[$item->template->slot ?? ''] ?? null">
                                                        <x-slot:actions>
                                                            <div class="flex flex-col gap-3 w-full">
                                                                <div class="flex justify-between border-b border-slate-700 pb-2">
                                                                    <span class="text-gray-400 text-sm">Wartość (szt.):</span>
                                                                    <span class="text-yellow-400 font-bold"><i class="fa-solid fa-coins text-yellow-400 mr-1"></i> {{ $sellPrices[$item->id] ?? 0 }}</span>
                                                                </div>

                                                                <div class="flex flex-col gap-2">
                                                                    @if($item->location !== 'equipped')
                                                                        @if(($item->stack_size ?? 1) > 1)
                                                                            <div class="flex flex-col gap-1.5 w-full">
                                                                                <span class="text-xs text-gray-400 font-semibold">Sprzedaj ilość (posiadasz: <span class="text-amber-400 font-bold">{{ $item->stack_size }}</span>):</span>
                                                                                <div class="grid grid-cols-5 gap-1">
                                                                                    <button wire:click.stop="sellItem('{{ $item->id }}', 1)" @click="showInfo = false"
                                                                                        class="bg-red-800 hover:bg-red-700 text-white text-[11px] font-bold py-1.5 px-1 rounded transition text-center cursor-pointer"
                                                                                        title="Sprzedaj 1 szt. za {{ $sellPrices[$item->id] ?? 0 }} złota">
                                                                                        x1
                                                                                    </button>
                                                                                    <button wire:click.stop="sellItem('{{ $item->id }}', 5)" @click="showInfo = false"
                                                                                        class="bg-red-800 hover:bg-red-700 text-white text-[11px] font-bold py-1.5 px-1 rounded transition text-center cursor-pointer {{ $item->stack_size < 5 ? 'opacity-50' : '' }}"
                                                                                        title="Sprzedaj 5 szt. za {{ ($sellPrices[$item->id] ?? 0) * min(5, $item->stack_size) }} złota">
                                                                                        x5
                                                                                    </button>
                                                                                    <button wire:click.stop="sellItem('{{ $item->id }}', 10)" @click="showInfo = false"
                                                                                        class="bg-red-800 hover:bg-red-700 text-white text-[11px] font-bold py-1.5 px-1 rounded transition text-center cursor-pointer {{ $item->stack_size < 10 ? 'opacity-50' : '' }}"
                                                                                        title="Sprzedaj 10 szt. za {{ ($sellPrices[$item->id] ?? 0) * min(10, $item->stack_size) }} złota">
                                                                                        x10
                                                                                    </button>
                                                                                    <button wire:click.stop="sellItem('{{ $item->id }}', 50)" @click="showInfo = false"
                                                                                        class="bg-red-800 hover:bg-red-700 text-white text-[11px] font-bold py-1.5 px-1 rounded transition text-center cursor-pointer {{ $item->stack_size < 50 ? 'opacity-50' : '' }}"
                                                                                        title="Sprzedaj 50 szt. za {{ ($sellPrices[$item->id] ?? 0) * min(50, $item->stack_size) }} złota">
                                                                                        x50
                                                                                    </button>
                                                                                    <button wire:click.stop="sellItem('{{ $item->id }}', 'all')" @click="showInfo = false"
                                                                                        class="bg-red-900 hover:bg-red-700 text-amber-300 text-[10px] font-extrabold py-1.5 px-1 rounded transition text-center border border-red-600/50 cursor-pointer"
                                                                                        title="Sprzedaj wszystkie {{ $item->stack_size }} szt. za {{ ($sellPrices[$item->id] ?? 0) * $item->stack_size }} złota">
                                                                                        Wszystko
                                                                                    </button>
                                                                                </div>
                                                                            </div>
                                                                        @else
                                                                            <button wire:click.stop="sellItem('{{ $item->id }}', 1)" @click="showInfo = false" class="w-full bg-red-700 hover:bg-red-600 text-white text-xs font-bold py-1.5 rounded transition cursor-pointer">Sprzedaj</button>
                                                                        @endif
                                                                    @else
                                                                        <button disabled class="w-full bg-gray-700 text-gray-500 text-xs font-bold py-1.5 rounded cursor-not-allowed">Założone</button>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </x-slot:actions>
                                                    </x-item-tooltip>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                @empty
                                    <div class="col-span-full text-center text-gray-500 py-12">Pusty ekwipunek</div>
                                @endforelse
                            </div>
                        </div>

                    </div>
            </div>

        </div>
    </div>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&display=swap');
        .medieval-font { font-family: 'Cinzel', serif; }

        @keyframes borderGlow {
            0% { box-shadow: 0 0 5px #a855f7, inset 0 0 5px #a855f7; border-color: #a855f7; }
            50% { box-shadow: 0 0 15px #d946ef, inset 0 0 10px #d946ef; border-color: #d946ef; }
            100% { box-shadow: 0 0 5px #a855f7, inset 0 0 5px #a855f7; border-color: #a855f7; }
        }
        .enchanted-border {
            animation: borderGlow 2s infinite alternate;
            border-width: 2px;
        }


        .custom-scrollbar::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: rgba(0,0,0,0.5);
            border-radius: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(245,158,11,0.5);
            border-radius: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(245,158,11,0.8);
        }
    </style>
</div>
