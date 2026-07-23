<div class="min-h-screen text-amber-100 relative overflow-hidden bg-black">
    <!-- Static Background -->
    <div class="absolute inset-0 bg-cover bg-center opacity-40 mix-blend-luminosity" style="background-image: url('{{ asset('img/armormaster.png') }}');"></div>
    <div class="absolute inset-0 bg-gradient-to-b from-slate-900/60 via-slate-900/80 to-slate-900/95"></div>

    @php
        $gameStage = auth()->user()->game_stage;
    @endphp

    @if($gameStage == 17)
        <livewire:global.tutorial-overlay :step="18" />
    @elseif($gameStage == 18)
        <livewire:global.tutorial-overlay :step="19" />
    @endif

    <div class="relative w-full px-6 md:px-10 lg:px-12 py-8 min-h-screen flex flex-col">
        {{-- Header --}}
        <div class="flex items-center justify-between mb-8">
            <div class="bg-black/60 border border-amber-700/50 rounded-lg p-4 shadow-2xl backdrop-blur-md">
                <h2 class="text-2xl font-bold text-amber-500 medieval-font flex items-center gap-2 tracking-wider">
                    <span class="text-3xl">🛡️</span> Zbrojmistrz
                </h2>
            </div>

            <div class="flex items-center gap-4">
                <div class="bg-black/80 border border-yellow-600/50 rounded px-4 py-2 font-bold text-yellow-400 backdrop-blur-sm shadow-inner">
                    🪙 {{ number_format($character->gold) }}
                </div>
                <button wire:click="backToHub" @click="$dispatch('location-leave')"
                    class="bg-gradient-to-b from-slate-700 to-slate-800 hover:from-slate-600 hover:to-slate-700 border border-slate-500 text-amber-200 font-bold py-2 px-6 rounded-lg transition-all duration-200 shadow-[0_4px_15px_rgba(0,0,0,0.5)] medieval-font {{ $gameStage == 20 ? 'animate-[pulse_1.5s_ease-in-out_infinite] ring-2 ring-amber-500 shadow-[0_0_20px_rgba(245,158,11,0.6)]' : '' }}">
                    🏰 Powrót
                </button>
            </div>
        </div>

        <div class="bg-black/50 border border-amber-700/30 rounded-xl shadow-[0_0_40px_rgba(0,0,0,0.8)] backdrop-blur-md flex-grow flex flex-col">
            
            {{-- Tabs --}}
            <div class="flex border-b border-amber-900/50 bg-black/40">
                <button wire:click="setTab('shop')" class="flex-1 py-4 font-bold text-lg transition-all {{ $activeTab === 'shop' ? 'bg-amber-900/40 text-amber-400 border-b-2 border-amber-500 shadow-[inset_0_-2px_10px_rgba(245,158,11,0.2)]' : 'text-gray-400 hover:text-amber-200 hover:bg-white/5' }}">
                    Sklep i Ekwipunek
                </button>
                <button wire:click="setTab('forge')" class="flex-1 py-4 font-bold text-lg transition-all flex items-center justify-center gap-2 {{ $activeTab === 'forge' ? 'bg-amber-900/40 text-amber-400 border-b-2 border-amber-500 shadow-[inset_0_-2px_10px_rgba(245,158,11,0.2)]' : 'text-gray-400 hover:text-amber-200 hover:bg-white/5' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    Kuźnia Ulepszeń
                </button>
                <button wire:click="setTab('crafting')" class="flex-1 py-4 font-bold text-lg transition-all flex items-center justify-center gap-2 {{ $activeTab === 'crafting' ? 'bg-amber-900/40 text-amber-400 border-b-2 border-amber-500 shadow-[inset_0_-2px_10px_rgba(245,158,11,0.2)]' : 'text-gray-400 hover:text-amber-200 hover:bg-white/5' }}">
                    🛠️ Rzemiosło
                </button>
            </div>

            {{-- Content --}}
            <div class="p-6 flex-grow flex flex-col h-full">
                @if($activeTab === 'shop')
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 h-full">
                        
                        <!-- Lewa: Ekwipunek gracza -->
                        <div class="bg-gray-900/60 rounded-xl border border-gray-700/50 p-4 flex flex-col">
                            <h3 class="text-xl font-bold text-amber-400 mb-4 border-b border-gray-700/50 pb-2 text-center medieval-font">Twój Ekwipunek</h3>
                            <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3">
                                @forelse($inventoryItems as $item)
                                    <div class="relative" x-data="{ showInfo: false, timeout: null }" 
                                         :class="{ 'z-50': showInfo, 'z-10': !showInfo }"
                                         @mouseenter="clearTimeout(timeout); showInfo = true"
                                         @mouseleave="timeout = setTimeout(() => showInfo = false, 300)"
                                         @click="clearTimeout(timeout); showInfo = !showInfo">
                                        
                                        <div class="aspect-square bg-black/80 border border-gray-600 hover:border-amber-400 rounded-lg flex flex-col items-center justify-center cursor-pointer transition-all {{ count($item->roll_stats['enchants'] ?? []) > 0 ? 'enchanted-border' : '' }}">
                                            @if($item->template->icon)
                                                <div class="w-full h-full p-2 relative flex items-center justify-center">
                                                    <img src="{{ route('assets.items', ['filename' => $item->template->icon]) }}" class="w-full h-full object-contain drop-shadow-md" alt="{{ $item->template->name }}">
                                                    @if($item->upgrade_level > 0)
                                                        <span class="absolute bottom-1 right-1 text-yellow-400 font-bold text-xs bg-black/80 px-1.5 py-0.5 rounded shadow">+{{ $item->upgrade_level }}</span>
                                                    @endif
                                                </div>
                                            @else
                                                <div class="text-xs text-center p-1 truncate w-full">{{ $item->template->name }}</div>
                                            @endif
                                            @if($item->location === 'equipped')
                                                <div class="absolute -top-1 -right-1 bg-blue-600 border border-blue-400 text-white text-[9px] font-bold px-1 py-0.5 rounded shadow">E</div>
                                            @endif
                                        </div>

                                        <!-- Infobox Ekwipunku -->
                                        <div x-show="showInfo" x-transition.opacity 
                                             class="absolute z-[100] bottom-full left-1/2 -translate-x-1/2 mb-2 w-auto pointer-events-auto">
                                            <x-item-tooltip :item="$item" :equippedItem="$equipped[$item->template->slot ?? ''] ?? null">
                                                <x-slot:actions>
                                                    <div class="flex flex-col gap-3 w-full">
                                                        <div class="flex justify-between border-b border-slate-700 pb-2">
                                                            <span class="text-gray-400 text-sm">Wartość:</span>
                                                            <span class="text-yellow-400 font-bold">🪙 {{ $sellPrices[$item->id] }}</span>
                                                        </div>

                                                        <div class="flex gap-2">
                                                            @if($item->location !== 'equipped')
                                                                <button wire:click.stop="sellItem('{{ $item->id }}')" class="flex-1 bg-red-700 hover:bg-red-600 text-white text-xs font-bold py-1.5 rounded transition">Sprzedaj</button>
                                                            @else
                                                                <button disabled class="flex-1 bg-gray-700 text-gray-500 text-xs font-bold py-1.5 rounded cursor-not-allowed">Założone</button>
                                                            @endif

                                                            @if(in_array($item->template->type, ['armor', 'accessory']) && $item->upgrade_level < 9)
                                                                <button wire:click.stop="selectItemForUpgrade('{{ $item->id }}'); showInfo = false;" class="flex-1 bg-amber-600 hover:bg-amber-500 text-white text-xs font-bold py-1.5 rounded transition">Ulepsz</button>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </x-slot:actions>
                                            </x-item-tooltip>
                                        </div>
                                    </div>
                                @empty
                                    <div class="col-span-full text-center text-gray-500 py-12">Pusty ekwipunek</div>
                                @endforelse
                            </div>
                        </div>

                        <!-- Prawa: Asortyment Sklepu -->
                        <div class="bg-gray-900/60 rounded-xl border border-gray-700/50 p-4 flex flex-col">
                            <h3 class="text-xl font-bold text-amber-400 mb-4 border-b border-gray-700/50 pb-2 text-center medieval-font">Asortyment</h3>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                                @forelse($shopItems as $item)
                                    <div class="relative" x-data="{ showInfo: false, timeout: null }" 
                                         :class="{ 'z-50': showInfo, 'z-10': !showInfo }"
                                         @mouseenter="clearTimeout(timeout); showInfo = true"
                                         @mouseleave="timeout = setTimeout(() => showInfo = false, 300)"
                                         @click="clearTimeout(timeout); showInfo = !showInfo">
                                         
                                        <div class="bg-black/80 border border-gray-600 hover:border-amber-400 rounded-lg p-3 flex flex-col items-center text-center cursor-pointer transition-all h-full {{ $gameStage == 19 && $item->template->id === 'miecz-nowicjusza' ? 'animate-pulse ring-2 ring-amber-500' : '' }}">
                                            @if($item->template->icon)
                                                <div class="w-12 h-12 mb-2">
                                                    <img src="{{ route('assets.items', ['filename' => $item->template->icon]) }}" class="w-full h-full object-contain" alt="{{ $item->template->name }}">
                                                </div>
                                            @endif
                                            <h4 class="font-bold text-sm text-blue-300 line-clamp-2 leading-tight">{{ $item->template->name }}</h4>
                                            <div class="mt-auto pt-2 text-yellow-400 text-sm font-bold">🪙 {{ $shopPrices[$item->id] }}</div>
                                        </div>

                                        <!-- Infobox Sklepu -->
                                        <div x-show="showInfo" x-transition.opacity 
                                             class="absolute z-[100] bottom-full left-1/2 -translate-x-1/2 mb-2 w-auto pointer-events-auto">
                                            <x-item-tooltip :item="$item" :equippedItem="$equipped[$item->template->slot ?? ''] ?? null">
                                                <x-slot:actions>
                                                    <button wire:click.stop="buyItem('{{ $item->id }}')" wire:loading.attr="disabled" class="w-full bg-green-700 hover:bg-green-600 text-white font-bold py-2 rounded shadow transition flex items-center justify-center gap-2">
                                                        <span>Kup za 🪙 {{ $shopPrices[$item->id] }}</span>
                                                    </button>
                                                </x-slot:actions>
                                            </x-item-tooltip>
                                        </div>
                                    </div>
                                @empty
                                    <div class="col-span-full text-center text-gray-500 py-12">Brak asortymentu</div>
                                @endforelse
                            </div>
                        </div>

                    </div>
                @elseif($activeTab === 'forge')
                    <div class="h-full flex flex-col gap-6 relative" x-data="{ hammering: false }">
                        @php
                            $upgradeItem = $selectedUpgradeItemId ? $inventoryItems->firstWhere('id', $selectedUpgradeItemId) : null;
                            $cost = $selectedUpgradeItemId ? ($upgradeCosts[$selectedUpgradeItemId] ?? null) : null;
                        @endphp

                        <!-- Top Section: Forge Template (3 columns) -->
                        <div class="w-full flex flex-col md:flex-row items-stretch justify-between gap-8">
                            
                            <!-- Lewo: Obecny Przedmiot -->
                            <div class="flex-1 bg-black/60 border border-slate-600 rounded-xl p-6 flex flex-col items-center text-center shadow-xl backdrop-blur">
                                <h3 class="text-xl font-bold text-gray-400 mb-4 medieval-font">Obecny Stan</h3>
                                <div class="w-24 h-24 bg-slate-800/80 rounded-lg border-2 border-slate-500 p-2 mb-4 flex items-center justify-center">
                                    @if($selectedUpgradeItemId && $upgradeItem)
                                        @if($upgradeItem->template->icon)
                                            <img src="{{ route('assets.items', ['filename' => $upgradeItem->template->icon]) }}" class="w-full h-full object-contain" alt="">
                                        @endif
                                    @else
                                        <span class="text-4xl text-gray-600">?</span>
                                    @endif
                                </div>
                                @if($selectedUpgradeItemId && $upgradeItem)
                                    <h4 class="text-2xl font-bold text-blue-300">{{ $upgradeItem->template->name }} <span class="text-yellow-400">+{{ $upgradeItem->upgrade_level }}</span></h4>
                                @else
                                    <h4 class="text-lg font-bold text-gray-500">Brak przedmiotu</h4>
                                @endif
                            </div>

                            <!-- Środek: Wymagania i Akcja -->
                            <div class="flex-1 flex flex-col items-center justify-center gap-6">
                                <div class="bg-black/80 border-2 border-amber-600/50 rounded-xl p-6 w-full shadow-2xl relative overflow-hidden" :class="{ 'animate-pulse ring-4 ring-amber-500 border-amber-500': hammering }">
                                    
                                    <!-- Efekt iskrzenia -->
                                    <div x-show="hammering" x-transition class="absolute inset-0 pointer-events-none bg-[radial-gradient(ellipse_at_center,_var(--tw-gradient-stops))] from-amber-500/30 to-transparent mix-blend-screen z-0"></div>

                                    <div class="relative z-10">
                                        <h3 class="text-center text-amber-500 font-bold mb-4 text-lg uppercase tracking-wider">Wymagania</h3>
                                        
                                        @if($selectedUpgradeItemId && $upgradeItem)
                                            @if($cost)
                                                <div class="space-y-3 mb-6">
                                                    <div class="flex justify-between items-center bg-gray-900/50 p-2 rounded border border-gray-700/50">
                                                        <span class="text-gray-300">Szansa na sukces:</span>
                                                        <span class="font-bold {{ $cost['chance'] >= 50 ? 'text-green-400' : 'text-orange-400' }}">{{ $cost['chance'] }}%</span>
                                                    </div>
                                                    <div class="flex justify-between items-center bg-gray-900/50 p-2 rounded border border-gray-700/50">
                                                        <span class="text-gray-300">Koszt złota:</span>
                                                        <span class="font-bold text-yellow-400">🪙 {{ number_format($cost['gold']) }}</span>
                                                    </div>
                                                    @foreach($cost['materials'] as $reqMat)
                                                        @php
                                                            $owned = $inventoryMaterials->where('template_id', $reqMat['template_id'])->sum('stack_size');
                                                            $hasEnough = $owned >= $reqMat['quantity'];
                                                        @endphp
                                                        <div class="relative group">
                                                            <div class="flex justify-between items-center bg-gray-900/50 p-2 rounded border border-gray-700/50 cursor-help transition hover:bg-gray-800">
                                                                <div class="flex items-center gap-2">
                                                                    @if(isset($reqMat['icon']) && $reqMat['icon'])
                                                                        <img src="{{ route('assets.items', ['filename' => $reqMat['icon']]) }}" class="w-6 h-6 object-contain" alt="">
                                                                    @endif
                                                                    <span class="text-gray-300">{{ $reqMat['name'] }}:</span>
                                                                </div>
                                                                <span class="font-bold {{ $hasEnough ? 'text-purple-400' : 'text-red-400' }}">{{ $owned }} / {{ $reqMat['quantity'] }}</span>
                                                            </div>
                                                            <!-- Tooltip -->
                                                            <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 w-64 bg-black/95 border border-amber-900/50 rounded-lg p-3 text-sm text-gray-300 hidden group-hover:block z-50 shadow-2xl backdrop-blur-sm">
                                                                <div class="font-bold text-amber-500 mb-1 border-b border-gray-700/50 pb-1 text-center text-xs tracking-wider uppercase">Do zdobycia z</div>
                                                                @if(isset($reqMat['dropped_by']) && count($reqMat['dropped_by']) > 0)
                                                                    <div class="flex flex-wrap justify-center gap-1 mt-2">
                                                                        @foreach(array_unique($reqMat['dropped_by']) as $monsterName)
                                                                            <span class="bg-gray-800 border border-gray-600 text-gray-300 text-[10px] px-1.5 py-0.5 rounded">{{ $monsterName }}</span>
                                                                        @endforeach
                                                                    </div>
                                                                @else
                                                                    <div class="text-[10px] text-gray-500 text-center italic mt-2">Brak w znanych tabelach łupów.</div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>

                                                <button 
                                                    wire:click="upgradeItem('{{ $upgradeItem->id }}')" 
                                                    @click="hammering = true; setTimeout(() => hammering = false, 1000)"
                                                    class="w-full bg-gradient-to-r from-amber-700 to-amber-500 hover:from-amber-600 hover:to-amber-400 text-white text-xl font-bold py-4 rounded-lg shadow-[0_0_15px_rgba(245,158,11,0.5)] transition-all transform hover:scale-105 active:scale-95 medieval-font tracking-wider border border-amber-300">
                                                    🔨 Uderz młotem
                                                </button>
                                            @else
                                                <div class="space-y-3 mb-6 text-center text-red-400 font-bold py-4 border border-red-500/30 bg-red-900/20 rounded-lg">
                                                    Ten przedmiot nie może zostać ulepszony na wyższy poziom (brak określonych zasad w kuźni).
                                                </div>
                                                <button disabled
                                                    class="w-full bg-gray-800 text-gray-500 text-xl font-bold py-4 rounded-lg border border-gray-600 cursor-not-allowed medieval-font tracking-wider">
                                                    🔨 Uderz młotem
                                                </button>
                                            @endif
                                        @else
                                            <div class="space-y-3 mb-6 text-center text-gray-500 py-4">
                                                Wybierz przedmiot z listy poniżej, aby sprawdzić wymagania.
                                            </div>
                                            <button disabled
                                                class="w-full bg-gray-800 text-gray-500 text-xl font-bold py-4 rounded-lg border border-gray-600 cursor-not-allowed medieval-font tracking-wider">
                                                🔨 Uderz młotem
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Prawo: Oczekiwany Rezultat -->
                            <div class="flex-1 bg-black/60 border border-amber-600/50 rounded-xl p-6 flex flex-col items-center text-center shadow-xl backdrop-blur relative overflow-hidden">
                                <div class="absolute -right-10 -top-10 w-32 h-32 bg-amber-500/20 blur-3xl rounded-full"></div>
                                
                                <h3 class="text-xl font-bold text-amber-500 mb-4 medieval-font">Sukces</h3>
                                <div class="w-24 h-24 bg-amber-900/40 rounded-lg border-2 border-amber-500 p-2 mb-4 shadow-[0_0_15px_rgba(245,158,11,0.3)] relative flex items-center justify-center overflow-hidden">
                                    @if($selectedUpgradeItemId && $upgradeItem)
                                        @if($upgradeItem->template->icon)
                                            <img src="{{ route('assets.items', ['filename' => $upgradeItem->template->icon]) }}" class="w-full h-full object-contain relative z-10 drop-shadow-[0_0_8px_rgba(245,158,11,0.8)]" alt="">
                                        @endif
                                        <div class="absolute inset-0 bg-amber-500/20 animate-pulse rounded"></div>
                                    @else
                                        <span class="text-4xl text-amber-700/50">?</span>
                                    @endif
                                </div>
                                @if($selectedUpgradeItemId && $upgradeItem)
                                    @if($cost)
                                        <h4 class="text-2xl font-bold text-amber-400">{{ $upgradeItem->template->name }} <span class="text-white">+{{ $upgradeItem->upgrade_level + 1 }}</span></h4>
                                        <p class="text-xs text-gray-400 mt-4 px-4">W przypadku niepowodzenia: <span class="text-red-400">{{ $cost['on_fail'] === 'nothing' ? 'Nic się nie psuje, tracisz materiały' : ($cost['on_fail'] === 'downgrade' ? 'Spadek poziomu' : 'Zniszczenie przedmiotu!') }}</span></p>
                                    @else
                                        <h4 class="text-lg font-bold text-red-500">Brak przepisu</h4>
                                    @endif
                                @else
                                    <h4 class="text-lg font-bold text-amber-700/50">Nieznany rezultat</h4>
                                @endif
                            </div>
                        </div>

                        <!-- Bottom Section: Upgradable Items Inventory -->
                        <div class="bg-gray-900/60 rounded-xl border border-gray-700/50 p-4 mt-auto">
                            <h3 class="text-lg font-bold text-amber-400 mb-3 border-b border-gray-700/50 pb-2 medieval-font">Wybierz przedmiot do ulepszenia</h3>
                            <div class="grid grid-cols-4 sm:grid-cols-6 md:grid-cols-8 lg:grid-cols-10 gap-3 overflow-y-auto custom-scrollbar pr-2 max-h-[250px]">
                                @forelse($upgradableItems as $item)
                                    @if($item->upgrade_level < 9)
                                        <div wire:click="selectItemForUpgrade('{{ $item->id }}')" 
                                             class="aspect-square bg-black/80 border {{ $selectedUpgradeItemId === $item->id ? 'border-amber-500 shadow-[0_0_10px_rgba(245,158,11,0.5)]' : 'border-gray-600 hover:border-amber-400' }} rounded-lg flex flex-col items-center justify-center cursor-pointer transition-all relative">
                                            @if($item->template->icon)
                                                <div class="w-full h-full p-2 relative flex items-center justify-center">
                                                    <img src="{{ route('assets.items', ['filename' => $item->template->icon]) }}" class="w-full h-full object-contain drop-shadow-md" alt="{{ $item->template->name }}">
                                                    @if($item->upgrade_level > 0)
                                                        <span class="absolute bottom-1 right-1 text-yellow-400 font-bold text-xs bg-black/80 px-1.5 py-0.5 rounded shadow">+{{ $item->upgrade_level }}</span>
                                                    @endif
                                                </div>
                                            @else
                                                <div class="text-[10px] text-center p-1 truncate w-full">{{ $item->template->name }}</div>
                                            @endif
                                            @if($item->location === 'equipped')
                                                <div class="absolute -top-1 -right-1 bg-blue-600 border border-blue-400 text-white text-[9px] font-bold px-1 py-0.5 rounded shadow">E</div>
                                            @endif
                                        </div>
                                    @endif
                                @empty
                                    <div class="col-span-full text-center text-gray-500 py-4">Brak przedmiotów do ulepszenia.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @elseif($activeTab === 'crafting')
                    <div class="flex flex-col gap-4 h-full overflow-y-auto custom-scrollbar pr-2">
                        @forelse($recipes as $recipe)
                            <div x-data="{ showInfo: false, timeout: null }" 
                                 :class="{ 'z-50': showInfo, 'z-10': !showInfo }"
                                 class="bg-black/60 border border-amber-600/30 hover:border-amber-500/80 transition-all rounded-lg p-4 flex flex-col md:flex-row items-center gap-6 shadow-xl backdrop-blur relative">
                                
                                <!-- Left: Result Item -->
                                <div class="flex items-center gap-4 w-full md:w-1/3 border-b md:border-b-0 md:border-r border-gray-700 pb-4 md:pb-0 md:pr-4 relative" 
                                     @mouseenter="clearTimeout(timeout); showInfo = true"
                                     @mouseleave="timeout = setTimeout(() => showInfo = false, 300)">
                                     
                                    <div class="w-16 h-16 bg-gray-900/80 rounded border border-gray-600 flex items-center justify-center p-2 flex-shrink-0 cursor-help">
                                        @if($recipe['result_icon'])
                                            <img src="{{ route('assets.items', ['filename' => $recipe['result_icon']]) }}" class="w-full h-full object-contain drop-shadow-md" alt="{{ $recipe['result_name'] }}">
                                        @else
                                            <span class="text-xs text-gray-500">Brak</span>
                                        @endif
                                    </div>
                                    <div class="flex-grow cursor-help">
                                        <h3 class="font-bold text-lg text-blue-300">{{ $recipe['result_name'] }}</h3>
                                        <p class="text-sm text-gray-400 mt-1">Koszt: <span class="text-yellow-400 font-bold">🪙 {{ number_format($recipe['gold_cost']) }}</span></p>
                                    </div>

                                    <!-- Infobox Docelowego Przedmiotu -->
                                    <div x-show="showInfo" x-transition.opacity 
                                         class="absolute z-[100] top-full left-0 mt-2 w-auto pointer-events-none">
                                        @php
                                            // Tworzymy tymczasowy obiekt do tooltipa
                                            $dummyItem = new \stdClass();
                                            $dummyItem->template = new \stdClass();
                                            $dummyItem->template->name = $recipe['result_name'];
                                            $dummyItem->template->level_requirement = $recipe['result_level'];
                                            $dummyItem->template->type = $recipe['result_type'];
                                            $dummyItem->template->base_stats = $recipe['result_stats'];
                                            $dummyItem->template->slot = \App\Infrastructure\Persistence\ItemTemplate::where('name', $recipe['result_name'])->value('slot');
                                        @endphp
                                        <x-item-tooltip :item="$dummyItem" :equippedItem="$equipped[$dummyItem->template->slot ?? ''] ?? null" />
                                    </div>
                                </div>

                                <!-- Center: Requirements -->
                                <div class="flex-grow w-full flex flex-col">
                                    <h4 class="text-amber-500 font-bold text-xs uppercase tracking-wider mb-2 hidden md:block">Wymagane materiały</h4>
                                    <div class="flex flex-wrap items-center gap-3">
                                        @foreach($recipe['ingredients'] as $ing)
                                            <div class="relative group cursor-help">
                                                <div class="flex items-center gap-2 bg-gray-900/80 p-2 rounded border border-gray-700 transition hover:bg-gray-800">
                                                    @if($ing['icon'])
                                                        <img src="{{ route('assets.items', ['filename' => $ing['icon']]) }}" class="w-8 h-8 object-contain" alt="{{ $ing['name'] }}">
                                                    @else
                                                        <div class="w-8 h-8 bg-gray-800 rounded text-[10px] text-gray-500 flex items-center justify-center">{{ substr($ing['name'],0,3) }}</div>
                                                    @endif
                                                    <div class="flex flex-col">
                                                        <span class="text-xs text-gray-300 font-bold truncate max-w-[100px]">{{ $ing['name'] }}</span>
                                                        <span class="text-xs {{ $ing['ok'] ? 'text-green-400' : 'text-red-400' }} font-bold">
                                                            {{ $ing['owned'] }} / {{ $ing['required'] }}
                                                        </span>
                                                    </div>
                                                </div>

                                                <!-- Tooltip -->
                                                <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 w-48 bg-black/95 border border-amber-900/50 rounded-lg p-3 text-sm text-gray-300 hidden group-hover:block z-50 shadow-2xl backdrop-blur-sm pointer-events-none">
                                                    <div class="font-bold text-amber-500 mb-1 border-b border-gray-700/50 pb-1 text-center text-xs tracking-wider uppercase">Do zdobycia z</div>
                                                    @if(isset($ing['dropped_by']) && count($ing['dropped_by']) > 0)
                                                        <div class="flex flex-wrap justify-center gap-1 mt-2">
                                                            @foreach(array_unique($ing['dropped_by']) as $monsterName)
                                                                <span class="bg-gray-800 border border-gray-600 text-gray-300 text-[10px] px-1.5 py-0.5 rounded">{{ $monsterName }}</span>
                                                            @endforeach
                                                        </div>
                                                    @else
                                                        <div class="text-[10px] text-gray-500 text-center italic mt-2">Brak w znanych tabelach łupów.</div>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <!-- Right: Craft Button -->
                                <div class="w-full md:w-auto flex-shrink-0 flex items-center mt-4 md:mt-0 pt-4 md:pt-0 border-t md:border-t-0 border-gray-700 pl-0 md:pl-4">
                                    @if($recipe['can_craft'])
                                        <button wire:click="craftItem('{{ $recipe['id'] }}')" class="w-full md:w-48 bg-gradient-to-r from-amber-700 to-amber-600 hover:from-amber-600 hover:to-amber-500 text-white font-bold py-3 px-6 rounded shadow transition-all whitespace-nowrap medieval-font">
                                            🛠️ Wytwórz
                                        </button>
                                    @else
                                        <button disabled class="w-full md:w-48 bg-gray-800 text-gray-500 font-bold py-3 px-4 rounded cursor-not-allowed whitespace-nowrap medieval-font border border-gray-700">
                                            ❌ Brak surowców
                                        </button>
                                    @endif
                                </div>

                            </div>
                        @empty
                            <div class="col-span-full text-center text-gray-500 py-12">Brak dostępnych przepisów.</div>
                        @endforelse
                    </div>
                @endif
            </div>

        </div>
    </div>

    {{-- Upgrade Modal (Rezultat) --}}
    @if($showUpgradeModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-md transition-opacity">
            <div class="bg-gray-900 border-4 {{ $upgradeModalType === 'success' ? 'border-green-500 shadow-[0_0_40px_rgba(34,197,94,0.3)]' : 'border-red-600 shadow-[0_0_40px_rgba(220,38,38,0.3)]' }} rounded-xl p-8 max-w-md w-full text-center transform transition-all scale-100">
                <div class="text-7xl mb-6">
                    @if($upgradeModalType === 'success')
                        ✨
                    @else
                        💥
                    @endif
                </div>
                <h3 class="text-3xl font-bold {{ $upgradeModalType === 'success' ? 'text-green-400' : 'text-red-500' }} medieval-font mb-4 tracking-widest uppercase">
                    {{ $upgradeModalTitle }}
                </h3>
                <p class="text-gray-300 text-lg mb-8 leading-relaxed">
                    {{ $upgradeModalMessage }}
                </p>
                <button wire:click="closeUpgradeModal" class="w-full py-4 px-6 rounded-lg font-bold text-white shadow-lg transition-colors {{ $upgradeModalType === 'success' ? 'bg-green-700 hover:bg-green-600' : 'bg-red-700 hover:bg-red-600' }} tracking-wider">
                    KONTUNUUJ
                </button>
            </div>
        </div>
    @endif

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
            z-index: 10;
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
