<div class="min-h-screen bg-gradient-to-b from-blue-900/90 via-indigo-800/90 to-purple-900/90 text-amber-100 relative overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-b from-slate-900/40 via-slate-800/50 to-slate-900/60"></div>

    <div class="relative container mx-auto px-4 py-8 min-h-screen">
        @php
            $gameStage = auth()->user()->game_stage;
        @endphp

        @if($gameStage == 5)
            <livewire:global.tutorial-overlay :step="6" />
        @elseif($gameStage == 7)
            <livewire:global.tutorial-overlay :step="8" :rewardXp="50" />
        @endif

        {{-- Header --}}
        <div class="flex items-center justify-between mb-8">
            <div class="bg-gradient-to-r from-amber-50/95 to-amber-100/95 border-4 border-amber-700 rounded-lg p-4 shadow-2xl backdrop-blur-sm">
                <h2 class="text-xl font-bold text-amber-900 medieval-font">Profil: {{ $character->name }}</h2>
            </div>

            <button wire:click="backToHub" @click="$dispatch('location-leave')"
                class="bg-gradient-to-r from-slate-600 to-slate-700 hover:from-slate-700 hover:to-slate-800 text-amber-200 font-bold py-2 px-4 rounded-lg transition-all duration-300 transform hover:scale-105 shadow-lg medieval-font {{ $gameStage == 8 ? 'animate-[pulse_1.5s_ease-in-out_infinite] ring-4 ring-amber-500 scale-105 shadow-[0_0_15px_rgba(245,158,11,0.6)] relative z-10' : '' }}">
                🏰 Powrót do miasta
            </button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        <!-- Left Side: Character Profile -->
        <div class="bg-gray-900 border-2 border-yellow-600 rounded-lg shadow-xl p-4 flex flex-col h-full">
            <!-- Equipment Slots & Portrait -->
            <div class="flex justify-center items-start gap-4 md:gap-8 mb-8 mt-4">
                <!-- Left Slots -->
                <div class="flex flex-col gap-4">
                    <!-- Pet Slot -->
                    @php $activePet = $pets->firstWhere('is_equipped', true); @endphp
                    <div id="equip-slot-pet" x-data="{ open: false }" @click.outside="open = false" 
                         @if($activePet) wire:loading.class="opacity-50 scale-95 pointer-events-none" wire:target="toggleEquipPet({{ $activePet->id }})" @endif
                         class="w-16 h-16 bg-gray-800 border-2 {{ $activePet ? 'border-amber-500 cursor-pointer hover:border-red-500 enchanted-border' : 'border-gray-600 border-dashed' }} rounded flex items-center justify-center relative"
                         @if($activePet) @click="open = true" @endif>
                        @if($activePet)
                            <div class="text-center text-xs text-white">
                                <span class="text-xl">🐾</span>
                                <span class="block truncate w-14 text-[10px] text-amber-400 mt-1">{{ $activePet->name }}</span>
                            </div>
                            <!-- Modal -->
                            <div x-show="open" style="display: none;" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/80 backdrop-blur-sm p-4 text-left cursor-default">
                                <div class="bg-gray-900 border border-amber-500 p-4 rounded w-full max-w-xs shadow-2xl relative" @click.stop>
                                    <button @click="open = false" class="absolute top-2 right-2 text-gray-400 hover:text-white text-lg font-bold">✕</button>
                                    <div class="flex justify-between items-center mb-2">
                                        <p class="font-bold text-amber-400 text-lg">{{ $activePet->name }}</p>
                                        <span class="text-indigo-300 font-bold">⚡ {{ $activePet->getCombatPower() }}</span>
                                    </div>
                                    <p class="text-gray-300 mb-2">Poziom: {{ $activePet->level }}</p>
                                    @if(count($activePet->stats ?? []) > 0)
                                        <div class="mt-2 text-green-400 border-t border-gray-700 pt-2 space-y-1">
                                            @foreach($activePet->stats ?? [] as $stat => $val)
                                                <div class="flex justify-between text-amber-200">
                                                    <span class="capitalize">{{ $stat }}</span>
                                                    <span class="font-bold">+{{ $val }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                    <button @click="open = false; flyItem('equip-slot-pet', 'inventory-grid', () => $wire.toggleEquipPet({{ $activePet->id }}))" class="mt-4 w-full bg-red-600 hover:bg-red-500 text-white font-bold py-2 rounded">
                                        Odwołaj Peta
                                    </button>
                                </div>
                            </div>
                        @else
                            <div class="text-gray-500 flex flex-col items-center">
                                <span class="text-xl opacity-50 mb-1">🐾</span>
                                <span class="text-[10px]">Pet</span>
                            </div>
                        @endif
                    </div>

                    @foreach(['head', 'chest', 'main_hand'] as $slot)
                        <div id="equip-slot-{{ $slot }}" x-data="{ open: false, hoverTimeout: null }" @click.outside="open = false" 
                             @if(isset($equipped[$slot])) wire:loading.class="opacity-50 scale-95 pointer-events-none" wire:target="unequipItem('{{ $equipped[$slot]->id }}')" @endif
                             class="w-16 h-16 bg-gray-800 border-2 {{ isset($equipped[$slot]) ? 'border-blue-500 cursor-pointer hover:border-red-500' : 'border-gray-600' }} rounded flex items-center justify-center relative {{ isset($equipped[$slot]) && count($equipped[$slot]->roll_stats['enchants'] ?? []) > 0 ? 'enchanted-border' : '' }}"
                             @if(isset($equipped[$slot])) @mouseenter="clearTimeout(hoverTimeout); open = true" @mouseleave="hoverTimeout = setTimeout(() => { open = false }, 250)" @click="clearTimeout(hoverTimeout); open = true" @endif>
                            @if(isset($equipped[$slot]))
                                @if($equipped[$slot]->template->icon)
                                    <div class="text-center text-xs text-white flex flex-col items-center w-full h-full justify-center">
                                        <img src="{{ route('assets.items', ['filename' => $equipped[$slot]->template->icon]) }}" class="w-full h-full object-contain drop-shadow-lg p-1" alt="{{ $equipped[$slot]->template->name }}">
                                        @if($equipped[$slot]->upgrade_level > 0)
                                            <span class="absolute bottom-0 right-0 text-yellow-400 font-bold text-[10px] bg-black/70 px-1 rounded-tl">+{{ $equipped[$slot]->upgrade_level }}</span>
                                        @endif
                                    </div>
                                @else
                                    <div class="text-center text-xs text-white">
                                        <span class="block truncate w-14">{{ $equipped[$slot]->template->name }}</span>
                                        @if($equipped[$slot]->upgrade_level > 0)
                                            <span class="text-yellow-400">+{{ $equipped[$slot]->upgrade_level }}</span>
                                        @endif
                                    </div>
                                @endif
                                <!-- Tooltip / Modal -->
                                <div x-show="open" x-transition.opacity style="display: none;" class="fixed inset-0 sm:absolute sm:inset-auto sm:top-full sm:left-1/2 sm:-translate-x-1/2 sm:mt-2 sm:w-64 z-[200] sm:z-50 flex items-center justify-center sm:block bg-black/80 sm:bg-transparent backdrop-blur-sm sm:backdrop-blur-none p-4 sm:p-0 cursor-default" @click.stop="open = false">
                                    <div class="bg-gray-900 border border-blue-500 p-4 rounded w-full max-w-xs sm:w-auto sm:max-w-none shadow-2xl relative" @click.stop>
                                        <button @click="open = false" class="absolute top-2 right-2 text-gray-400 hover:text-white text-lg font-bold sm:hidden">✕</button>
                                        <div class="flex justify-between items-center mb-2">
                                            <p class="font-bold text-yellow-400 text-lg">{{ $equipped[$slot]->template->name }}</p>
                                            <span class="text-indigo-300 font-bold">⚡ {{ $equipped[$slot]->getCombatPower() }}</span>
                                        </div>
                                        <p class="text-gray-300 mb-2">Slot: {{ $slot }}</p>
                                        @if(isset($equipped[$slot]->roll_stats['mint']))
                                            <p class="text-red-400 font-bold text-xs uppercase mb-2 animate-pulse border-b border-red-500/50 pb-1">
                                                🔥 Nakład: {{ $equipped[$slot]->roll_stats['mint'] }} / {{ $equipped[$slot]->roll_stats['max_mint'] }}
                                            </p>
                                        @endif
                                        
                                        @if(count($equipped[$slot]->template->base_stats ?? []) > 0 || count($equipped[$slot]->roll_stats['enchants'] ?? []) > 0)
                                            <div class="mt-2 text-green-400 border-t border-gray-700 pt-2 space-y-1 mb-4 text-sm">
                                                @foreach($equipped[$slot]->template->base_stats ?? [] as $stat => $val)
                                                    <div class="flex justify-between">
                                                        <span class="capitalize">{{ str_replace('_', ' ', $stat) }}</span>
                                                        <span class="font-bold">+{{ $val }}</span>
                                                    </div>
                                                @endforeach
                                                @foreach($equipped[$slot]->roll_stats['enchants'] ?? [] as $stat => $val)
                                                    <div class="flex justify-between text-purple-400">
                                                        <span class="capitalize">⭐ {{ str_replace('_', ' ', $stat) }}</span>
                                                        <span class="font-bold">+{{ $val }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif

                                        <button @click="open = false; flyItem('equip-slot-{{ $slot }}', 'inventory-grid', () => $wire.unequipItem('{{ $equipped[$slot]->id }}'))" class="w-full bg-red-600 hover:bg-red-500 text-white font-bold py-2 rounded">
                                            Zdejmij przedmiot
                                        </button>
                                    </div>
                                    <!-- Arrow (Desktop only) -->
                                    <div class="hidden sm:block absolute -top-2 left-1/2 -translate-x-1/2 w-4 h-4 bg-gray-900 border-t border-l border-blue-500 transform rotate-45"></div>
                                </div>
                            @else
                                <span class="text-gray-500 text-xs">{{ ucfirst($slot) }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>

                <!-- Portrait & Info -->
                <div class="flex flex-col items-center w-48">
                    <div class="w-full h-64 bg-gray-800 border-4 border-yellow-700 rounded-lg overflow-hidden flex items-center justify-center mb-4 shadow-lg">
                        @if($character->avatar && file_exists(public_path('img/avatars/' . $character->avatar . '.png')))
                            <img src="{{ asset('img/avatars/' . $character->avatar . '.png') }}" alt="Avatar" class="object-cover w-full h-full">
                        @else
                            <div class="text-gray-500 flex flex-col items-center">
                                <svg class="w-16 h-16 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                <span>No Avatar</span>
                            </div>
                        @endif
                    </div>

                    @if($character->activeTitle)
                        <div class="text-[11px] text-purple-400 font-bold uppercase tracking-widest mt-2 -mb-1 drop-shadow-md text-center">
                            {{ $character->activeTitle->prefix }}
                        </div>
                    @endif
                    <h2 class="text-2xl font-bold text-yellow-500 text-center drop-shadow-md">{{ $character->name }}</h2>
                    <p class="text-gray-400 font-semibold text-sm">Poziom {{ $character->level }}</p>
                    <div class="bg-indigo-900/80 border border-indigo-500 text-indigo-200 px-3 py-1 rounded-full text-xs font-bold shadow-lg my-2">
                        ⚡ Moc Bojowa: {{ number_format($character->getTotalCombatPower()) }}
                    </div>
                    
                    @php 
                        $xpRequired = app(\App\Application\Characters\LevelUpService::class)->xpToNext($character->level);
                        $xpPercent = min(100, ($character->xp / max(1, $xpRequired)) * 100);
                    @endphp
                    <div class="w-full bg-gray-900 rounded-full h-5 relative border-2 border-gray-700 shadow-inner overflow-hidden cursor-help" title="Doświadczenie: {{ $character->xp }} / {{ $xpRequired }}">
                        <div class="bg-gradient-to-r from-blue-700 to-blue-400 h-full transition-all duration-300" style="width: {{ $xpPercent }}%"></div>
                        <span class="absolute inset-0 flex items-center justify-center text-[10px] text-white font-bold drop-shadow-[0_1px_1px_rgba(0,0,0,0.8)]">
                            XP: {{ number_format($character->xp) }} / {{ number_format($xpRequired) }}
                        </span>
                    </div>
                </div>

                <!-- Right Slots -->
                <div class="flex flex-col gap-4">
                    @foreach(['neck', 'ring', 'feet'] as $slot)
                        <div id="equip-slot-{{ $slot }}" x-data="{ open: false, hoverTimeout: null }" @click.outside="open = false" 
                             @if(isset($equipped[$slot])) wire:loading.class="opacity-50 scale-95 pointer-events-none" wire:target="unequipItem('{{ $equipped[$slot]->id }}')" @endif
                             class="w-16 h-16 bg-gray-800 border-2 {{ isset($equipped[$slot]) ? 'border-blue-500 cursor-pointer hover:border-red-500' : 'border-gray-600' }} rounded flex items-center justify-center relative {{ isset($equipped[$slot]) && count($equipped[$slot]->roll_stats['enchants'] ?? []) > 0 ? 'enchanted-border' : '' }}"
                             @if(isset($equipped[$slot])) @mouseenter="clearTimeout(hoverTimeout); open = true" @mouseleave="hoverTimeout = setTimeout(() => { open = false }, 250)" @click="clearTimeout(hoverTimeout); open = true" @endif>
                            @if(isset($equipped[$slot]))
                                @if($equipped[$slot]->template->icon)
                                    <div class="text-center text-xs text-white flex flex-col items-center w-full h-full justify-center">
                                        <img src="{{ route('assets.items', ['filename' => $equipped[$slot]->template->icon]) }}" class="w-full h-full object-contain drop-shadow-lg p-1" alt="{{ $equipped[$slot]->template->name }}">
                                        @if($equipped[$slot]->upgrade_level > 0)
                                            <span class="absolute bottom-0 right-0 text-yellow-400 font-bold text-[10px] bg-black/70 px-1 rounded-tl">+{{ $equipped[$slot]->upgrade_level }}</span>
                                        @endif
                                    </div>
                                @else
                                    <div class="text-center text-xs text-white">
                                        <span class="block truncate w-14">{{ $equipped[$slot]->template->name }}</span>
                                        @if($equipped[$slot]->upgrade_level > 0)
                                            <span class="text-yellow-400">+{{ $equipped[$slot]->upgrade_level }}</span>
                                        @endif
                                    </div>
                                @endif
                                <!-- Tooltip / Modal -->
                                <div x-show="open" x-transition.opacity style="display: none;" class="fixed inset-0 sm:absolute sm:inset-auto sm:top-full sm:left-1/2 sm:-translate-x-1/2 sm:mt-2 sm:w-64 z-[200] sm:z-50 flex items-center justify-center sm:block bg-black/80 sm:bg-transparent backdrop-blur-sm sm:backdrop-blur-none p-4 sm:p-0 cursor-default" @click.stop="open = false">
                                    <div class="bg-gray-900 border border-blue-500 p-4 rounded w-full max-w-xs sm:w-auto sm:max-w-none shadow-2xl relative" @click.stop>
                                        <button @click="open = false" class="absolute top-2 right-2 text-gray-400 hover:text-white text-lg font-bold sm:hidden">✕</button>
                                        <div class="flex justify-between items-center mb-2">
                                            <p class="font-bold text-yellow-400 text-lg">{{ $equipped[$slot]->template->name }}</p>
                                            <span class="text-indigo-300 font-bold">⚡ {{ $equipped[$slot]->getCombatPower() }}</span>
                                        </div>
                                        <p class="text-gray-300 mb-2">Slot: {{ $slot }}</p>
                                        @if(isset($equipped[$slot]->roll_stats['mint']))
                                            <p class="text-red-400 font-bold text-xs uppercase mb-2 animate-pulse border-b border-red-500/50 pb-1">
                                                🔥 Nakład: {{ $equipped[$slot]->roll_stats['mint'] }} / {{ $equipped[$slot]->roll_stats['max_mint'] }}
                                            </p>
                                        @endif
                                        
                                        @if(count($equipped[$slot]->template->base_stats ?? []) > 0 || count($equipped[$slot]->roll_stats['enchants'] ?? []) > 0)
                                            <div class="mt-2 text-green-400 border-t border-gray-700 pt-2 space-y-1 mb-4 text-sm">
                                                @foreach($equipped[$slot]->template->base_stats ?? [] as $stat => $val)
                                                    <div class="flex justify-between">
                                                        <span class="capitalize">{{ str_replace('_', ' ', $stat) }}</span>
                                                        <span class="font-bold">+{{ $val }}</span>
                                                    </div>
                                                @endforeach
                                                @foreach($equipped[$slot]->roll_stats['enchants'] ?? [] as $stat => $val)
                                                    <div class="flex justify-between text-purple-400">
                                                        <span class="capitalize">⭐ {{ str_replace('_', ' ', $stat) }}</span>
                                                        <span class="font-bold">+{{ $val }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif

                                        <button @click="open = false; flyItem('equip-slot-{{ $slot }}', 'inventory-grid', () => $wire.unequipItem('{{ $equipped[$slot]->id }}'))" class="w-full bg-red-600 hover:bg-red-500 text-white font-bold py-2 rounded">
                                            Zdejmij przedmiot
                                        </button>
                                    </div>
                                    <!-- Arrow (Desktop only) -->
                                    <div class="hidden sm:block absolute -top-2 left-1/2 -translate-x-1/2 w-4 h-4 bg-gray-900 border-t border-l border-blue-500 transform rotate-45"></div>
                                </div>
                            @else
                                <span class="text-gray-500 text-xs">{{ ucfirst($slot) }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Stats -->
            <div class="bg-gray-800 border border-gray-700 rounded p-4 mt-auto">
                <div class="flex justify-between items-end border-b border-gray-700 pb-2 mb-3">
                    <div class="flex gap-4">
                        <button wire:click="setTab('attributes')" class="font-bold text-md transition-colors {{ $activeTab === 'attributes' ? 'text-yellow-500 border-b-2 border-yellow-500' : 'text-gray-400 hover:text-yellow-400' }}">
                            Atrybuty
                        </button>
                        <button wire:click="setTab('stats')" class="font-bold text-md transition-colors {{ $activeTab === 'stats' ? 'text-yellow-500 border-b-2 border-yellow-500' : 'text-gray-400 hover:text-yellow-400' }}">
                            Statystyki
                        </button>
                        <button wire:click="setTab('pets')" class="font-bold text-md transition-colors {{ $activeTab === 'pets' ? 'text-yellow-500 border-b-2 border-yellow-500' : 'text-gray-400 hover:text-yellow-400' }}">
                            Pety & Inkubator
                        </button>
                        <button wire:click="setTab('collections')" class="font-bold text-md transition-colors {{ $activeTab === 'collections' ? 'text-yellow-500 border-b-2 border-yellow-500' : 'text-gray-400 hover:text-yellow-400' }}">
                            Kolekcje & Tytuły
                        </button>
                    </div>
                    @if($activeTab === 'attributes')
                        <span x-data="{ points: {{ $character->character_points }} }" @stats-saved.window="points = $event.detail.points" x-show="points > 0" class="text-green-400 font-bold text-sm animate-pulse bg-green-900/40 px-2 py-1 rounded border border-green-700">Punkty: <span x-text="points"></span></span>
                    @endif
                </div>
                @if($activeTab === 'attributes')
                    <div x-data="{
                        points: {{ $character->character_points }},
                        added: { str: 0, int: 0, vit: 0, agi: 0 },
                        saveTimeout: null,
                        
                        add(stat, amount) {
                            let actual = Math.min(amount, this.points);
                            if (actual > 0) {
                                this.added[stat] += actual;
                                this.points -= actual;
                                
                                // Animacja po dodaniu punktu
                                let el = document.getElementById('stat-flash-' + stat);
                                if (el) {
                                    el.style.animation = 'none';
                                    el.offsetHeight; // trigger reflow
                                    el.style.animation = 'flashText 0.5s ease-out forwards';
                                }
                                
                                // Auto-zapis po sekundzie bezczynności
                                clearTimeout(this.saveTimeout);
                                this.saveTimeout = setTimeout(() => {
                                    let toSave = { ...this.added };
                                    this.added = { str: 0, int: 0, vit: 0, agi: 0 };
                                    $wire.saveAttributes(toSave);
                                }, 800);
                            }
                        }
                    }" class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 text-sm mt-4 relative" @stats-saved.window="points = $event.detail.points">
                    
                        <style>
                            @keyframes flashText {
                                0% { color: #4ade80; transform: scale(1.4); text-shadow: 0 0 10px #4ade80; }
                                100% { color: white; transform: scale(1); text-shadow: none; }
                            }
                        </style>
                        
                        <!-- Wskaźnik zapisu -->
                        <div wire:loading wire:target="saveAttributes" class="absolute inset-0 bg-gray-900/20 backdrop-blur-[1px] flex items-center justify-center rounded z-10">
                        </div>

                        <!-- STR -->
                        <div class="flex justify-between items-center group">
                            <span class="text-gray-400 cursor-help border-b border-dashed border-gray-600" title="Siła: Zwiększa obrażenia bazowe broni.">Strength (STR):</span>
                            <div class="flex items-center gap-2">
                                <span id="stat-flash-str" class="text-white font-bold text-base w-8 text-right inline-block transition-transform" x-text="{{ $totalAttributes['str'] ?? 0 }} + added.str"></span>
                                <div class="flex gap-1" x-show="points > 0">
                                    <button @click="add('str', 1)" class="w-6 h-6 bg-green-700 hover:bg-green-500 text-white rounded text-xs flex items-center justify-center font-bold transition shadow" title="Dodaj 1">+1</button>
                                    <button x-show="points >= 5" @click="add('str', 5)" class="w-6 h-6 bg-green-800 hover:bg-green-600 text-white rounded text-xs flex items-center justify-center font-bold transition shadow" title="Dodaj 5">+5</button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- INT -->
                        <div class="flex justify-between items-center group">
                            <span class="text-gray-400 cursor-help border-b border-dashed border-gray-600" title="Inteligencja: Wpływa na obronę magiczną.">Intelligence (INT):</span>
                            <div class="flex items-center gap-2">
                                <span id="stat-flash-int" class="text-white font-bold text-base w-8 text-right inline-block transition-transform" x-text="{{ $totalAttributes['int'] ?? 0 }} + added.int"></span>
                                <div class="flex gap-1" x-show="points > 0">
                                    <button @click="add('int', 1)" class="w-6 h-6 bg-green-700 hover:bg-green-500 text-white rounded text-xs flex items-center justify-center font-bold transition shadow" title="Dodaj 1">+1</button>
                                    <button x-show="points >= 5" @click="add('int', 5)" class="w-6 h-6 bg-green-800 hover:bg-green-600 text-white rounded text-xs flex items-center justify-center font-bold transition shadow" title="Dodaj 5">+5</button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- VIT -->
                        <div class="flex justify-between items-center group">
                            <span class="text-gray-400 cursor-help border-b border-dashed border-gray-600" title="Witalność: Zwiększa maksymalną ilość Punktów Życia (HP).">Vitality (VIT):</span>
                            <div class="flex items-center gap-2">
                                <span id="stat-flash-vit" class="text-white font-bold text-base w-8 text-right inline-block transition-transform" x-text="{{ $totalAttributes['vit'] ?? 0 }} + added.vit"></span>
                                <div class="flex gap-1" x-show="points > 0">
                                    <button @click="add('vit', 1)" class="w-6 h-6 bg-green-700 hover:bg-green-500 text-white rounded text-xs flex items-center justify-center font-bold transition shadow" title="Dodaj 1">+1</button>
                                    <button x-show="points >= 5" @click="add('vit', 5)" class="w-6 h-6 bg-green-800 hover:bg-green-600 text-white rounded text-xs flex items-center justify-center font-bold transition shadow" title="Dodaj 5">+5</button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- AGI -->
                        <div class="flex justify-between items-center group">
                            <span class="text-gray-400 cursor-help border-b border-dashed border-gray-600" title="Zręczność: Decyduje o kolejności ataku w walce oraz o szansie na uniki.">Agility (AGI):</span>
                            <div class="flex items-center gap-2">
                                <span id="stat-flash-agi" class="text-white font-bold text-base w-8 text-right inline-block transition-transform" x-text="{{ $totalAttributes['agi'] ?? 0 }} + added.agi"></span>
                                <div class="flex gap-1" x-show="points > 0">
                                    <button @click="add('agi', 1)" class="w-6 h-6 bg-green-700 hover:bg-green-500 text-white rounded text-xs flex items-center justify-center font-bold transition shadow" title="Dodaj 1">+1</button>
                                    <button x-show="points >= 5" @click="add('agi', 5)" class="w-6 h-6 bg-green-800 hover:bg-green-600 text-white rounded text-xs flex items-center justify-center font-bold transition shadow" title="Dodaj 5">+5</button>
                                </div>
                            </div>
                        </div>
                    </div>
                @elseif($activeTab === 'stats')
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4 text-sm mt-4 text-gray-300">
                        <div class="flex justify-between items-center pb-2 border-b border-gray-700/50">
                            <span class="text-gray-400 font-semibold cursor-help border-b border-dashed border-gray-600" title="Maksymalna liczba punktów życia w walce.">Max HP:</span>
                            <span class="text-green-400 font-bold text-base">{{ $derivedStats['max_hp'] }}</span>
                        </div>
                        <div class="flex justify-between items-center pb-2 border-b border-gray-700/50">
                            <span class="text-gray-400 font-semibold cursor-help border-b border-dashed border-gray-600" title="Podstawowe obrażenia ataków fizycznych.">Base Damage:</span>
                            <span class="text-red-400 font-bold text-base">{{ $derivedStats['base_damage_min'] }} - {{ $derivedStats['base_damage_max'] }}</span>
                        </div>
                        <div class="flex justify-between items-center pb-2 border-b border-gray-700/50">
                            <span class="text-gray-400 font-semibold cursor-help border-b border-dashed border-gray-600" title="Podstawowe obrażenia z umiejętności magicznych.">Magic Damage:</span>
                            <span class="text-purple-400 font-bold text-base">{{ $derivedStats['magic_damage_min'] }} - {{ $derivedStats['magic_damage_max'] }}</span>
                        </div>
                        <div class="flex justify-between items-center pb-2 border-b border-gray-700/50">
                            <span class="text-gray-400 font-semibold cursor-help border-b border-dashed border-gray-600" title="Redukuje nadchodzące obrażenia w walce.">Damage Reduction:</span>
                            <span class="text-blue-400 font-bold text-base">{{ $derivedStats['damage_reduction'] }}</span>
                        </div>
                        <div class="flex justify-between items-center pb-2 border-b border-gray-700/50">
                            <span class="text-gray-400 font-semibold cursor-help border-b border-dashed border-gray-600" title="Szansa na zadanie ataku krytycznego (1.5x obrażeń).">Crit Chance:</span>
                            <span class="text-yellow-500 font-bold text-base">{{ $derivedStats['crit_chance'] }}%</span>
                        </div>
                        <div class="flex justify-between items-center pb-2 border-b border-gray-700/50">
                            <span class="text-gray-400 font-semibold cursor-help border-b border-dashed border-gray-600" title="Szansa na całkowite uniknięcie ciosu przeciwnika.">Dodge Chance:</span>
                            <span class="text-emerald-400 font-bold text-base">{{ $derivedStats['dodge_chance'] }}%</span>
                        </div>
                    </div>
                @elseif($activeTab === 'pets')
                    <div class="mt-4">
                        @include('livewire.city.pets')
                    </div>
                @elseif($activeTab === 'collections')
                    <div class="mt-4">
                        @livewire('profile.collections-tab', ['character' => $character])
                    </div>
                @endif
            </div>
        </div>

        <!-- Right Side: Inventory -->
        <div class="bg-gray-900 border-2 border-blue-900 rounded-lg shadow-xl p-4 flex flex-col h-full">
            <div class="flex justify-between items-center mb-4 border-b border-gray-700 pb-2">
                <h2 class="text-2xl font-bold text-blue-400">Backpack</h2>
                <div class="text-yellow-400 font-bold flex gap-4">
                    <span>🪙 {{ $character->gold }}</span>
                    <span>💎 {{ auth()->user()->gems }}</span>
                </div>
            </div>

            <div class="flex overflow-x-auto whitespace-nowrap gap-2 mb-3 pb-2 custom-scrollbar">
                <button wire:click="setInventoryFilter('all')" class="px-3 py-1.5 text-xs rounded transition-colors {{ $inventoryFilter === 'all' ? 'bg-blue-600 text-white font-bold' : 'bg-gray-800 text-gray-400 hover:bg-gray-700' }}">Wszystko</button>
                <button wire:click="setInventoryFilter('weapon')" class="px-3 py-1.5 text-xs rounded transition-colors {{ $inventoryFilter === 'weapon' ? 'bg-blue-600 text-white font-bold' : 'bg-gray-800 text-gray-400 hover:bg-gray-700' }}">Bronie</button>
                <button wire:click="setInventoryFilter('armor')" class="px-3 py-1.5 text-xs rounded transition-colors {{ $inventoryFilter === 'armor' ? 'bg-blue-600 text-white font-bold' : 'bg-gray-800 text-gray-400 hover:bg-gray-700' }}">Pancerz</button>
                <button wire:click="setInventoryFilter('accessory')" class="px-3 py-1.5 text-xs rounded transition-colors {{ $inventoryFilter === 'accessory' ? 'bg-blue-600 text-white font-bold' : 'bg-gray-800 text-gray-400 hover:bg-gray-700' }}">Akcesoria</button>
                <button wire:click="setInventoryFilter('material')" class="px-3 py-1.5 text-xs rounded transition-colors {{ $inventoryFilter === 'material' ? 'bg-blue-600 text-white font-bold' : 'bg-gray-800 text-gray-400 hover:bg-gray-700' }}">Materiały</button>
                <button wire:click="setInventoryFilter('consumable')" class="px-3 py-1.5 text-xs rounded transition-colors {{ $inventoryFilter === 'consumable' ? 'bg-blue-600 text-white font-bold' : 'bg-gray-800 text-gray-400 hover:bg-gray-700' }}">Mikstury</button>
                
                <div class="flex-grow"></div>
                
                <button wire:click="stackItems" class="px-2 py-1 text-xs rounded bg-green-700 hover:bg-green-600 text-white flex items-center gap-1 shadow transition-colors" title="Połącz powtarzające się materiały">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path></svg>
                    Stackuj
                </button>
            </div>

            <!-- Inventory Grid -->
            <div id="inventory-grid" class="grid grid-cols-4 sm:grid-cols-5 gap-2 bg-gray-800 p-2 rounded flex-grow content-start">
                @foreach($inventory as $item)
                    @php
                        $isRustySwordTutorial = $gameStage == 6 && $item->template_id === '01k4jpx94j70x2vv10b835prm4';
                    @endphp
                    <div id="backpack-item-{{ $item->id }}" x-data="{ 
                        open: false, 
                        hoverTimeout: null,
                        posClass: 'sm:bottom-full sm:mb-2',
                        checkPosition() { 
                            this.posClass = this.$el.getBoundingClientRect().top < window.innerHeight / 2 ? 'sm:top-full sm:mt-2' : 'sm:bottom-full sm:mb-2'; 
                        }
                    }" @click.outside="open = false" 
                         wire:loading.class="opacity-50 scale-95 pointer-events-none" wire:target="equipItem('{{ $item->id }}')"
                         class="aspect-square bg-gray-700 border rounded flex items-center justify-center cursor-pointer hover:border-green-400 relative transition-all duration-300 {{ count($item->roll_stats['enchants'] ?? []) > 0 ? 'enchanted-border border-gray-600' : 'border-gray-600' }}"
                         :class="{ 'animate-[pulse_1.5s_ease-in-out_infinite] ring-4 ring-amber-500 scale-105 shadow-[0_0_15px_rgba(245,158,11,0.6)] z-10': {{ $isRustySwordTutorial ? 'true' : 'false' }} && !open }"
                         @mouseenter="clearTimeout(hoverTimeout); checkPosition(); open = true" 
                         @mouseleave="hoverTimeout = setTimeout(() => { open = false }, 250)" 
                         @click="clearTimeout(hoverTimeout); checkPosition(); open = true">
                        
                        @if($item->template->icon)
                            <div class="text-center text-xs text-white flex flex-col items-center w-full h-full justify-center">
                                <img src="{{ route('assets.items', ['filename' => $item->template->icon]) }}" class="w-full h-full object-contain drop-shadow-lg p-1" alt="{{ $item->template->name }}">
                                
                                <div class="absolute bottom-0 right-0 flex flex-col items-end gap-0.5 pointer-events-none">
                                    @if($item->upgrade_level > 0)
                                        <span class="text-yellow-400 font-bold text-[10px] bg-black/70 px-1 rounded-tl">+{{ $item->upgrade_level }}</span>
                                    @endif
                                    @if($item->stack_size > 1)
                                        <span class="text-blue-300 font-bold text-[10px] bg-black/70 px-1 rounded-tl">{{ $item->stack_size }}x</span>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="text-center text-xs text-white">
                                <span class="block truncate w-14">{{ $item->template->name }}</span>
                                @if($item->upgrade_level > 0)
                                    <span class="text-yellow-400">+{{ $item->upgrade_level }}</span>
                                @endif
                                @if($item->stack_size > 1)
                                    <span class="text-blue-300 font-bold block">{{ $item->stack_size }}x</span>
                                @endif
                            </div>
                        @endif

                        <!-- Tooltip / Modal -->
                        <div x-show="open" x-transition.opacity style="display: none;" 
                             :class="posClass"
                             class="fixed inset-0 sm:absolute sm:inset-auto sm:left-1/2 sm:-translate-x-1/2 sm:w-64 z-[200] sm:z-50 flex items-center justify-center sm:block bg-black/80 sm:bg-transparent backdrop-blur-sm sm:backdrop-blur-none p-4 sm:p-0 cursor-default" @click.stop="open = false">
                            <div class="bg-gray-900 border border-blue-500 p-4 rounded w-full max-w-xs sm:w-auto sm:max-w-none shadow-2xl relative" @click.stop>
                                <button @click="open = false" class="absolute top-2 right-2 text-gray-400 hover:text-white text-lg font-bold sm:hidden">✕</button>
                                
                                <div class="flex justify-between items-center mb-2 pr-4">
                                    <p class="font-bold text-blue-300 text-lg">{{ $item->template->name }}</p>
                                    <span class="text-indigo-300 font-bold">⚡ {{ $item->getCombatPower() }}</span>
                                </div>
                                <p class="text-gray-400 mb-1">Slot: {{ $item->template->slot ?? 'Brak' }}</p>
                                <p class="text-gray-400 mb-2">Wymagany Poz: <span class="text-white">{{ $item->template->level_requirement }}</span></p>
                                
                                @if(isset($item->roll_stats['mint']))
                                    <p class="text-red-400 font-bold text-xs uppercase mb-2 animate-pulse border-b border-red-500/50 pb-1">
                                        🔥 Nakład: {{ $item->roll_stats['mint'] }} / {{ $item->roll_stats['max_mint'] }}
                                    </p>
                                @endif
                                
                                @if(count($item->template->base_stats ?? []) > 0 || count($item->roll_stats['enchants'] ?? []) > 0)
                                    <div class="mt-2 text-green-400 border-t border-gray-700 pt-2 space-y-1 mb-4 text-sm">
                                        @foreach($item->template->base_stats ?? [] as $stat => $val)
                                            <div class="flex justify-between">
                                                <span class="capitalize">{{ str_replace('_', ' ', $stat) }}</span>
                                                <span class="font-bold">+{{ $val }}</span>
                                            </div>
                                        @endforeach
                                        @foreach($item->roll_stats['enchants'] ?? [] as $stat => $val)
                                            <div class="flex justify-between text-purple-400">
                                                <span class="capitalize">⭐ {{ str_replace('_', ' ', $stat) }}</span>
                                                <span class="font-bold">+{{ $val }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                                
                                <div class="mt-4 border-t border-gray-700 pt-4 flex flex-col gap-2">
                                    @if($character->level < $item->template->level_requirement)
                                        <p class="text-red-500 font-bold text-center mb-2">Zbyt niski poziom!</p>
                                    @else
                                        @if($item->template->type === 'weapon' || $item->template->type === 'armor' || $item->template->type === 'accessory')
                                            <button @click="open = false; flyItem('backpack-item-{{ $item->id }}', 'equip-slot-{{ $item->template->slot }}', () => $wire.equipItem('{{ $item->id }}'))" class="w-full bg-green-600 hover:bg-green-500 text-white font-bold py-2 rounded transition-colors">
                                                Załóż sprzęt
                                            </button>
                                        @elseif($item->template->type === 'consumable')
                                            <button wire:click="equipItem('{{ $item->id }}')" @click="open = false" class="w-full bg-indigo-600 hover:bg-indigo-500 text-white font-bold py-2 rounded transition-colors">
                                                Użyj przedmiotu
                                            </button>
                                        @endif
                                    @endif
                                    
                                    @if(!($item->bound_to_character ?? false))
                                        <button wire:click.stop="openSellModal('{{ $item->id }}'); open = false;" class="w-full bg-yellow-600 hover:bg-yellow-500 text-white py-2 rounded font-bold shadow transition-colors">
                                            Wystaw na targowisko
                                        </button>
                                    @endif
                                </div>
                            </div>
                            <!-- Arrow (Desktop only) -->
                            <div class="hidden sm:block absolute left-1/2 -translate-x-1/2 w-4 h-4 bg-gray-900 transform rotate-45"
                                 :class="posClass === 'sm:top-full sm:mt-2' ? '-top-2 border-t border-l border-blue-500' : '-bottom-2 border-b border-r border-blue-500'"></div>
                        </div>
                    </div>
                @endforeach

                <!-- Empty Slots Filler -->
                @php $emptySlots = max(0, 25 - count($inventory)) @endphp
                @for($i = 0; $i < $emptySlots; $i++)
                    <div class="empty-slot aspect-square bg-gray-800 border border-gray-700 rounded"></div>
                @endfor
            </div>
        </div>

    </div>

    {{-- Sell Modal --}}
    @if($sellingItemUlid)
        <div class="fixed inset-0 bg-black/80 flex items-center justify-center z-[100] p-4">
            <div class="bg-gray-900 border-2 border-yellow-600 rounded-lg shadow-2xl p-6 max-w-md w-full relative">
                <button wire:click="closeSellModal" class="absolute top-4 right-4 text-gray-400 hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
                
                <h3 class="text-2xl font-bold text-yellow-500 mb-4 medieval-font">Wystaw na Targowisko</h3>
                
                @php
                    $sellItem = \App\Infrastructure\Persistence\ItemInstance::find($sellingItemUlid);
                @endphp
                
                @if($sellItem)
                    <div class="flex items-center space-x-3 mb-6 bg-gray-800 p-3 rounded border border-gray-700">
                        <div class="text-3xl">
                            @if($sellItem->template->slot === 'weapon') ⚔️
                            @elseif($sellItem->template->slot === 'head') 🪖
                            @elseif($sellItem->template->slot === 'chest') 🛡️
                            @elseif($sellItem->template->slot === 'legs') 👖
                            @elseif($sellItem->template->slot === 'boots') 👢
                            @else 📦
                            @endif
                        </div>
                        <div>
                            <div class="font-bold text-amber-200">{{ $sellItem->template->name }}</div>
                            <div class="text-xs text-gray-400">{{ ucfirst($sellItem->rarity) }} | Poz: {{ $sellItem->template->level_requirement }}</div>
                        </div>
                    </div>
                @endif
                
                <div class="space-y-4 mb-6">
                    <div>
                        <label class="block text-sm font-bold text-gray-300 mb-1">Cena</label>
                        <input type="number" wire:model="sellPrice" min="1" class="w-full bg-gray-800 border border-gray-600 rounded p-2 text-white" placeholder="Wpisz cenę...">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-bold text-gray-300 mb-1">Waluta</label>
                        <select wire:model="sellCurrency" class="w-full bg-gray-800 border border-gray-600 rounded p-2 text-white">
                            <option value="gold">Złoto 💰</option>
                            <option value="gems">Klejnoty 💎</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-bold text-gray-300 mb-1">Czas trwania i opłata</label>
                        <select wire:model="sellDuration" class="w-full bg-gray-800 border border-gray-600 rounded p-2 text-white">
                            <option value="24">24 godziny (Koszt: 100 złota)</option>
                            <option value="48">48 godzin (Koszt: 250 złota)</option>
                            <option value="72">72 godziny (Koszt: 500 złota)</option>
                        </select>
                    </div>
                </div>
                
                <div class="flex justify-end gap-3">
                    <button wire:click="closeSellModal" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded font-bold transition">Anuluj</button>
                    <button wire:click="sellItem" class="px-4 py-2 bg-yellow-600 hover:bg-yellow-500 text-white rounded font-bold transition">Wystaw Przedmiot</button>
                </div>
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
    </style>
    <script>
        window.flyItem = async function(sourceId, targetId, wireCall) {
            let sourceEl = document.getElementById(sourceId);
            let targetEl = document.getElementById(targetId);
            
            if (!sourceEl || !targetEl) {
                if (wireCall) wireCall();
                return;
            }
            
            if (targetId === 'inventory-grid') {
                let firstEmptySlot = targetEl.querySelector('.empty-slot');
                if (firstEmptySlot) {
                    targetEl = firstEmptySlot;
                }
            }
            
            let rect1 = sourceEl.getBoundingClientRect();
            let rect2 = targetEl.getBoundingClientRect();
            
            if (targetId === 'inventory-grid' && !targetEl.classList.contains('empty-slot')) {
                // Fallback if inventory is full: keep original item size and target grid center
                rect2 = {
                    top: rect2.top + rect2.height / 2 - rect1.height / 2,
                    left: rect2.left + rect2.width / 2 - rect1.width / 2,
                    width: rect1.width,
                    height: rect1.height
                };
            }
            
            let targetHasItem = sourceId.includes('backpack') && targetEl.querySelector('.text-center.text-xs.text-white') !== null;
            
            let ghost = sourceEl.cloneNode(true);
            let tooltip = ghost.querySelector('[x-show]');
            if(tooltip) tooltip.remove();
            
            let spinner = document.createElement('div');
            spinner.innerHTML = `<svg class="animate-spin h-6 w-6 text-white drop-shadow-md" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>`;
            spinner.className = 'absolute inset-0 bg-black/40 flex items-center justify-center rounded z-20';
            ghost.appendChild(spinner);
            
            ghost.style.position = 'fixed';
            ghost.style.top = rect1.top + 'px';
            ghost.style.left = rect1.left + 'px';
            ghost.style.width = rect1.width + 'px';
            ghost.style.height = rect1.height + 'px';
            ghost.style.zIndex = '9999';
            ghost.style.transition = 'all 0.5s cubic-bezier(0.25, 1, 0.5, 1)';
            ghost.style.margin = '0';
            ghost.style.pointerEvents = 'none';
            document.body.appendChild(ghost);
            
            let oldGhost = null;
            if (targetHasItem) {
                oldGhost = targetEl.cloneNode(true);
                let oldTooltip = oldGhost.querySelector('[x-show]');
                if(oldTooltip) oldTooltip.remove();
                
                oldGhost.style.position = 'fixed';
                oldGhost.style.top = rect2.top + 'px';
                oldGhost.style.left = rect2.left + 'px';
                oldGhost.style.width = rect2.width + 'px';
                oldGhost.style.height = rect2.height + 'px';
                oldGhost.style.zIndex = '9998';
                oldGhost.style.transition = 'all 0.5s cubic-bezier(0.25, 1, 0.5, 1)';
                oldGhost.style.margin = '0';
                oldGhost.style.pointerEvents = 'none';
                document.body.appendChild(oldGhost);
                
                // Hide only inner content for target
                Array.from(targetEl.children).forEach(c => {
                    if(!c.classList.contains('absolute') && c.tagName !== 'SCRIPT') {
                        c.style.opacity = '0';
                    }
                });
            }
            
            // Hide only inner content for source, leaving the slot background visible
            Array.from(sourceEl.children).forEach(c => {
                if(!c.classList.contains('absolute') && c.tagName !== 'SCRIPT') {
                    c.style.opacity = '0';
                }
            });
            
            // 1. New item hovers up
            setTimeout(() => {
                ghost.style.transform = 'scale(1.1) translateY(-20px)';
            }, 10);
            await new Promise(r => setTimeout(r, 300));
            
            // 2. Old item flies to backpack
            if (targetHasItem) {
                oldGhost.style.top = rect1.top + 'px';
                oldGhost.style.left = rect1.left + 'px';
                oldGhost.style.opacity = '0.7';
                await new Promise(r => setTimeout(r, 400));
            }
            
            let livewirePromise = null;
            if (wireCall) {
                livewirePromise = wireCall();
            }
            
            // 3. New item flies to equip slot
            ghost.style.transition = 'all 0.6s cubic-bezier(0.25, 1, 0.5, 1)';
            ghost.style.transform = 'scale(1) translateY(0)';
            ghost.style.top = rect2.top + 'px';
            ghost.style.left = rect2.left + 'px';
            ghost.style.width = rect2.width + 'px';
            ghost.style.height = rect2.height + 'px';
            ghost.style.opacity = '0.7';
            
            await new Promise(r => setTimeout(r, 600));
            
            try {
                if (livewirePromise) await livewirePromise;
                
                let operationFailed = false;
                if (sourceId.includes('backpack')) {
                    operationFailed = document.getElementById(sourceId) !== null;
                } else {
                    let sourceSlot = document.getElementById(sourceId);
                    operationFailed = sourceSlot && sourceSlot.querySelector('.text-center.text-xs.text-white') !== null;
                }
                
                if (operationFailed) {
                    let currentSourceRect = document.getElementById(sourceId).getBoundingClientRect();
                    ghost.style.transition = 'all 0.5s cubic-bezier(0.25, 1, 0.5, 1)';
                    ghost.style.top = currentSourceRect.top + 'px';
                    ghost.style.left = currentSourceRect.left + 'px';
                    ghost.style.width = currentSourceRect.width + 'px';
                    ghost.style.height = currentSourceRect.height + 'px';
                    ghost.style.opacity = '1';
                    
                    if (oldGhost) {
                        let currentTargetRect = document.getElementById(targetId).getBoundingClientRect();
                        oldGhost.style.transition = 'all 0.5s cubic-bezier(0.25, 1, 0.5, 1)';
                        oldGhost.style.top = currentTargetRect.top + 'px';
                        oldGhost.style.left = currentTargetRect.left + 'px';
                        oldGhost.style.width = currentTargetRect.width + 'px';
                        oldGhost.style.height = currentTargetRect.height + 'px';
                        oldGhost.style.opacity = '1';
                    }
                    
                    if (spinner) spinner.remove();
                    await new Promise(r => setTimeout(r, 500));
                }
            } catch (e) {
                console.error(e);
            }
            
            ghost.remove();
            if (oldGhost) oldGhost.remove();
            
            let currentSource = document.getElementById(sourceId);
            if(currentSource) {
                Array.from(currentSource.children).forEach(c => c.style.opacity = '');
            }
            
            let currentTarget = document.getElementById(targetId);
            if(currentTarget) {
                Array.from(currentTarget.children).forEach(c => c.style.opacity = '');
            }
        }
    </script>
</div>
