<div id="adventure-map-component" class="min-h-screen relative overflow-hidden" 
     x-data="{ 
         travelingTo: null,
         isPaused: false,
         speed: {{ $playbackSpeed }},
         autoChain: @entangle('autoChain')
     }">

    {{-- Dynamic background per map --}}
    <div class="absolute inset-0 bg-cover bg-center bg-no-repeat" style="background-image: url('{{ $background }}');">
    </div>

    {{-- Dark overlay for depth --}}
    <div class="absolute inset-0 bg-black/60"></div>

    {{-- Dynamic Attack FX Layer --}}
    <div id="combat-fx-overlay" class="fixed inset-0 pointer-events-none z-[150] overflow-hidden"></div>

    {{-- Transition Overlay --}}
    <div x-show="$data.travelingTo" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         class="fixed inset-0 z-[100] flex items-center justify-center bg-black/80 backdrop-blur-sm"
         style="display: none;">
         
         <div class="relative w-full max-w-lg mx-auto p-8 text-center">
            <img src="{{ asset('img/avatars/plate.png') }}" class="absolute inset-0 w-full h-full object-cover rounded-2xl shadow-2xl border-4 border-amber-700">
            <div class="absolute inset-0 bg-amber-900/60 rounded-2xl"></div>
            
            <div class="relative z-10 flex flex-col items-center">
                <div class="text-6xl mb-4 animate-bounce" x-text="$data.travelingTo === 'Miasto' ? '🏰' : '🗺️'"></div>
                <h2 class="text-3xl font-bold text-amber-100 medieval-font mb-4 drop-shadow-lg">
                    Przenoszenie do...
                </h2>
                <h3 class="text-2xl text-amber-300 font-bold drop-shadow-md mb-6" x-text="$data.travelingTo"></h3>
                
                <div class="w-12 h-12 border-4 border-amber-500 border-t-transparent rounded-full animate-spin"></div>
            </div>
         </div>
    </div>

    {{-- Event Lokacji: modal wyboru trybu (Normalny/Hardcore) --}}
    @if ($pendingEventPreview)
        <div wire:key="location-event-choice-modal" class="fixed inset-0 z-[10000] flex items-center justify-center bg-black/80 backdrop-blur-sm"
             x-data="{ visible: false }" x-init="setTimeout(() => visible = true, 50)"
             x-show="visible"
             x-transition:enter="transition ease-out duration-500 transform"
             x-transition:enter-start="opacity-0 scale-50"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-300 transform"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-90">
            <div class="bg-gradient-to-b from-stone-900 to-black border-4 border-purple-600 rounded-2xl p-8 max-w-md w-full text-center shadow-[0_0_50px_rgba(168,85,247,0.4)] relative mx-4">
                <div class="absolute -top-12 left-1/2 transform -translate-x-1/2">
                    <div class="w-24 h-24 bg-gradient-to-br from-purple-400 to-purple-700 rounded-full border-4 border-stone-900 flex items-center justify-center shadow-lg shadow-purple-500/50">
                        <span class="text-4xl">⚔️</span>
                    </div>
                </div>

                <div class="mt-10 mb-6">
                    <h2 class="text-purple-400 font-bold uppercase tracking-widest text-sm mb-1" style="font-family: 'Cinzel', serif;">Wylosowano event!</h2>
                    <h3 class="text-3xl font-bold text-white drop-shadow-md" style="font-family: 'Cinzel', serif;">{{ $pendingEventPreview['name'] }}</h3>
                </div>

                <p class="text-stone-300 mb-4 px-2 text-sm sm:text-base">
                    Łańcuch <strong class="text-amber-300">{{ $pendingEventPreview['monster_count_min'] }}-{{ $pendingEventPreview['monster_count_max'] }}</strong> potworów
                    (ostatni to boss), nagrody x<strong class="text-amber-300">{{ $pendingEventPreview['reward_multiplier'] }}</strong>.
                </p>

                <div class="flex flex-col gap-3">
                    <button wire:click="chooseEventMode(false)"
                            class="rounded-xl px-5 py-3 bg-gradient-to-r from-emerald-700 via-emerald-600 to-green-600 border border-emerald-400/60 text-white font-extrabold medieval-font shadow-lg hover:scale-[1.02] active:scale-95 transition-all">
                        Wejdź (Normalny)
                    </button>
                    <button wire:click="chooseEventMode(true)"
                            class="rounded-xl px-5 py-3 bg-gradient-to-r from-red-800 via-red-700 to-rose-700 border border-red-400/60 text-white font-extrabold medieval-font shadow-lg hover:scale-[1.02] active:scale-95 transition-all">
                        Wejdź (Hardcore, +50% nagród)
                        <span class="block text-[11px] font-normal text-red-200 mt-0.5 normal-case">Brak regeneracji HP między potworami - tylko skille i mikstury</span>
                    </button>
                    <button wire:click="declineEvent"
                            class="rounded-xl px-5 py-2 bg-slate-900/80 border border-slate-700 text-stone-300 font-semibold hover:bg-slate-800 transition-all text-sm">
                        Pomiń
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Event Lokacji: ekran podsumowania runu --}}
    @if ($eventRunResult)
        <div wire:key="location-event-summary-overlay" class="fixed inset-0 z-[10000] flex items-center justify-center bg-black/85 backdrop-blur-sm"
             x-data="{ visible: false }" x-init="setTimeout(() => visible = true, 50)"
             x-show="visible"
             x-transition:enter="transition ease-out duration-500 transform"
             x-transition:enter-start="opacity-0 scale-50"
             x-transition:enter-end="opacity-100 scale-100">
            @if ($eventRunResult === 'event_complete')
                <div class="bg-gradient-to-b from-stone-900 to-black border-4 border-amber-600 rounded-2xl p-8 max-w-md w-full text-center shadow-[0_0_50px_rgba(245,158,11,0.4)] relative mx-4">
                    <div class="absolute -top-12 left-1/2 transform -translate-x-1/2">
                        <div class="w-24 h-24 bg-gradient-to-br from-yellow-400 to-amber-600 rounded-full border-4 border-stone-900 flex items-center justify-center shadow-lg shadow-amber-500/50">
                            <span class="text-4xl">🏆</span>
                        </div>
                    </div>
                    <div class="mt-10 mb-4">
                        <h2 class="text-amber-500 font-bold uppercase tracking-widest text-sm mb-1" style="font-family: 'Cinzel', serif;">Event ukończony!</h2>
                        <h3 class="text-2xl font-bold text-white drop-shadow-md" style="font-family: 'Cinzel', serif;">{{ $eventName }}</h3>
                    </div>

                    <div class="bg-slate-900/90 border border-amber-500/30 rounded-xl p-4 text-left mb-6 space-y-1.5 text-sm">
                        <div class="text-indigo-200">+{{ \App\Helpers\FormatHelper::short($eventRunFinalLoot['xp'] ?? 0) }} XP</div>
                        <div class="text-amber-200">+{{ \App\Helpers\FormatHelper::short($eventRunFinalLoot['gold'] ?? 0) }} Złota</div>
                        @foreach ($eventRunFinalLoot['items'] ?? [] as $item)
                            @if ($item['type'] !== 'gems')
                                <div class="text-purple-300 font-semibold">{{ $item['name'] }} ({{ $item['quantity'] }}x)</div>
                            @else
                                <div class="text-blue-300">+{{ $item['quantity'] }} Klejnotów</div>
                            @endif
                        @endforeach
                    </div>

                    <button wire:click="dismissEventRun"
                        class="rounded-xl px-6 py-3 bg-gradient-to-r from-amber-700 to-amber-600 border border-amber-400/60 text-white font-bold medieval-font shadow-lg hover:scale-105 active:scale-95 transition-all w-full">
                        Kontynuuj eksplorację
                    </button>
                </div>
            @else
                <div class="bg-gradient-to-b from-stone-900 to-black border-4 border-red-700 rounded-2xl p-8 max-w-md w-full text-center shadow-[0_0_50px_rgba(220,38,38,0.4)] relative mx-4">
                    <div class="absolute -top-12 left-1/2 transform -translate-x-1/2">
                        <div class="w-24 h-24 bg-gradient-to-br from-red-700 to-red-900 rounded-full border-4 border-stone-900 flex items-center justify-center shadow-lg shadow-red-500/50">
                            <span class="text-4xl">💀</span>
                        </div>
                    </div>
                    <div class="mt-10 mb-4">
                        <h2 class="text-red-400 font-bold uppercase tracking-widest text-sm mb-1" style="font-family: 'Cinzel', serif;">Porażka!</h2>
                        <h3 class="text-xl font-bold text-white drop-shadow-md" style="font-family: 'Cinzel', serif;">{{ $eventName }}</h3>
                        <p class="text-stone-400 text-sm mt-3">Cała zdobycz z tego eventu przepadła.</p>
                    </div>

                    <button wire:click="dismissEventRun"
                        class="rounded-xl px-6 py-3 bg-gradient-to-r from-red-800 to-red-700 border border-red-400/60 text-white font-bold medieval-font shadow-lg hover:scale-105 active:scale-95 transition-all w-full">
                        Powrót do eksploracji
                    </button>
                </div>
            @endif
        </div>
    @endif

    {{-- Baner: aktywny event lokacji na innej mapie --}}
    @if ($otherMapEventMapName && !$inLocationEvent)
        <div wire:key="location-event-other-map-banner" class="absolute top-4 left-1/2 transform -translate-x-1/2 z-50 bg-purple-950/90 border border-purple-500/50 text-purple-200 px-4 py-2 rounded-xl text-sm font-semibold shadow-lg">
            Masz aktywny event na mapie "{{ $otherMapEventMapName }}" - wróć tam, aby go dokończyć.
        </div>
    @endif

    {{-- Warning message --}}
    @if (session('warning'))
        <div class="absolute top-4 left-1/2 transform -translate-x-1/2 z-50">
            <div class="bg-amber-100 border-2 border-amber-600 rounded-lg px-4 py-2 shadow-lg ring-1 ring-amber-800/50">
                <p class="text-amber-800 font-semibold text-sm">{{ session('warning') }}</p>
            </div>
        </div>
    @endif

    {{-- Battle error message / Inactive Tab banner --}}
    @if ($isInactiveTab || $errors->has('battle'))
        <div class="absolute top-16 left-1/2 transform -translate-x-1/2 z-50 max-w-md w-full px-4">
            <div class="bg-slate-900/95 border-2 border-amber-500/80 rounded-xl p-4 shadow-2xl backdrop-blur-md text-center">
                <div class="flex items-center justify-center space-x-2 text-amber-400 font-bold mb-1 medieval-font">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span>Uwaga</span>
                </div>
                <p class="text-amber-100 text-sm mb-3">
                    {{ $errors->first('battle') ?? 'Przygoda została aktywowana w innej karcie przeglądarki.' }}
                </p>
                @if ($isInactiveTab)
                    <button wire:click="claimActiveTab" class="bg-amber-600 hover:bg-amber-500 text-amber-950 font-bold px-4 py-1.5 rounded-lg text-xs medieval-font transition-all shadow-md">
                        <i class="fa-solid fa-play mr-1"></i> Przejmij przygodę w tej karcie
                    </button>
                @endif
            </div>
        </div>
    @endif

    <div class="relative z-10 container mx-auto px-4 py-2 lg:py-3 min-h-screen">
        @php
            $gameStage = auth()->user()->game_stage;
        @endphp

        @if($gameStage == 11)
            <livewire:global.tutorial-overlay :step="12" />
        @elseif($gameStage == 12 && $battleCompleted)
            <livewire:global.tutorial-overlay :step="13" :rewardItemTemplateId="'01k4jpx94j70x2vv10b835hlm1'" />
        @endif

        {{-- Header with navigation --}}
        <div class="flex items-center justify-between mb-2 lg:mb-3">
            <h1 class="text-2xl sm:text-3xl font-bold text-amber-100 medieval-font drop-shadow-2xl">
                {{ $map->name }}
            </h1>

            <div class="flex items-center space-x-3">
                {{-- Character level and points info --}}
                <div class="text-amber-100 text-sm medieval-font">
                    <div>{{ $character->name }} (Poziom {{ $character->level }})</div>
                    @if ($character->character_points > 0)
                        <div class="text-green-300">{{ $character->character_points }} punktów do rozdania</div>
                    @endif
                </div>

                <button @click="travelingTo = 'Wybór Mapy'; setTimeout(() => $wire.backToAdventure(), 500)"
                    class="relative rounded-lg px-4 py-2 shadow-lg overflow-hidden group">
                    <img src="{{ asset('img/avatars/plate.png') }}" alt=""
                        class="absolute inset-0 w-full h-full object-cover rounded-lg">
                    <div class="absolute inset-0 bg-amber-900/20 rounded-lg"></div>
                    <span
                        class="relative text-amber-100 font-bold medieval-font drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)]">
                        Mapy
                    </span>
                </button>
                <button @click="travelingTo = 'Miasto'; $dispatch('play-audio', { type: 'tab' }); setTimeout(() => $wire.backToHub(), 500)" 
                    class="relative rounded-lg px-4 py-2 shadow-lg overflow-hidden group">
                    <img src="{{ asset('img/avatars/plate.png') }}" alt=""
                        class="absolute inset-0 w-full h-full object-cover rounded-lg">
                    <div class="absolute inset-0 bg-amber-900/20 rounded-lg"></div>
                    <span
                        class="relative text-amber-100 font-bold medieval-font drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)]">
                        Miasto
                    </span>
                </button>
            </div>
        </div>

        {{-- Over-Level Banner & Targeting Strategy Selector --}}
        @if ($map->isOverLevel($character))
            <div class="relative z-[150] mb-3 p-3 bg-purple-950/90 border-2 border-purple-500/80 rounded-xl shadow-xl backdrop-blur-md flex flex-col md:flex-row items-center justify-between gap-3 text-amber-100">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-users-rays text-amber-400 text-xl"></i>
                    <div>
                        <div class="font-bold text-sm text-purple-200 medieval-font">Walka z Wieloma Przeciwnikami (3-4 Potwory)</div>
                        <div class="text-xs text-purple-300/90">Przekroczono sugerowany max poziom ({{ $map->level_max }}). Obrażenia potworów zredukowane (-10%/ekstra potwora). Nagrody zredukowane o 66%.</div>
                    </div>
                </div>

                {{-- Custom Targeting Strategy Dropdown with FontAwesome Icons --}}
                <div class="relative flex items-center gap-2 text-xs font-bold font-sans z-[100]"
                     x-data="{ 
                         open: false, 
                         strategy: @entangle('targetStrategy'),
                         labels: {
                             'random': { label: 'Losowy przeciwnik', icon: 'fa-solid fa-dice text-purple-400' },
                             'highest_hp': { label: 'Najwięcej HP', icon: 'fa-solid fa-heart text-red-500' },
                             'lowest_hp': { label: 'Najmniej HP', icon: 'fa-solid fa-droplet text-rose-400' },
                             'highest_att': { label: 'Największy Atak', icon: 'fa-solid fa-swords text-amber-400' },
                             'highest_def': { label: 'Największa Obrona', icon: 'fa-solid fa-shield-halved text-blue-400' }
                         }
                     }">
                    <span class="whitespace-nowrap text-amber-300 flex items-center gap-1.5 medieval-font">
                        <i class="fa-solid fa-crosshairs text-amber-400"></i> Taktyka ataku:
                    </span>

                    {{-- Dropdown Trigger Button --}}
                    <button @click="open = !open" @click.outside="open = false" 
                        class="bg-slate-900 border border-purple-400/80 hover:border-purple-300 text-amber-100 font-bold px-3 py-1.5 rounded-xl text-xs flex items-center gap-2 shadow-md backdrop-blur-md transition-all active:scale-95">
                        <i :class="labels[strategy]?.icon || 'fa-solid fa-dice text-purple-400'"></i>
                        <span x-text="labels[strategy]?.label || 'Losowy przeciwnik'"></span>
                        <i class="fa-solid fa-chevron-down text-[10px] text-purple-300 ml-1 transition-transform" :class="{ 'rotate-180': open }"></i>
                    </button>

                    {{-- Dropdown Menu Items --}}
                    <div x-show="open" 
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute right-0 top-full mt-2 w-52 bg-slate-950/95 border-2 border-purple-500/80 rounded-xl shadow-2xl backdrop-blur-xl p-1.5 z-[200] space-y-1"
                         style="display: none;">
                        
                        <button @click="$wire.setTargetStrategy('random'); open = false" 
                            :class="strategy === 'random' ? 'bg-purple-900/90 text-amber-200 border-purple-400/60 font-black' : 'text-slate-200 hover:bg-slate-900 hover:text-amber-100'"
                            class="w-full text-left px-2.5 py-2 rounded-lg text-xs font-bold flex items-center gap-2.5 transition-all border border-transparent">
                            <i class="fa-solid fa-dice text-purple-400 w-4 text-center"></i>
                            <span>Losowy przeciwnik</span>
                        </button>

                        <button @click="$wire.setTargetStrategy('highest_hp'); open = false" 
                            :class="strategy === 'highest_hp' ? 'bg-purple-900/90 text-amber-200 border-purple-400/60 font-black' : 'text-slate-200 hover:bg-slate-900 hover:text-amber-100'"
                            class="w-full text-left px-2.5 py-2 rounded-lg text-xs font-bold flex items-center gap-2.5 transition-all border border-transparent">
                            <i class="fa-solid fa-heart text-red-500 w-4 text-center"></i>
                            <span>Najwięcej HP</span>
                        </button>

                        <button @click="$wire.setTargetStrategy('lowest_hp'); open = false" 
                            :class="strategy === 'lowest_hp' ? 'bg-purple-900/90 text-amber-200 border-purple-400/60 font-black' : 'text-slate-200 hover:bg-slate-900 hover:text-amber-100'"
                            class="w-full text-left px-2.5 py-2 rounded-lg text-xs font-bold flex items-center gap-2.5 transition-all border border-transparent">
                            <i class="fa-solid fa-droplet text-rose-400 w-4 text-center"></i>
                            <span>Najmniej HP</span>
                        </button>

                        <button @click="$wire.setTargetStrategy('highest_att'); open = false" 
                            :class="strategy === 'highest_att' ? 'bg-purple-900/90 text-amber-200 border-purple-400/60 font-black' : 'text-slate-200 hover:bg-slate-900 hover:text-amber-100'"
                            class="w-full text-left px-2.5 py-2 rounded-lg text-xs font-bold flex items-center gap-2.5 transition-all border border-transparent">
                            <i class="fa-solid fa-swords text-amber-400 w-4 text-center"></i>
                            <span>Największy Atak</span>
                        </button>

                        <button @click="$wire.setTargetStrategy('highest_def'); open = false" 
                            :class="strategy === 'highest_def' ? 'bg-purple-900/90 text-amber-200 border-purple-400/60 font-black' : 'text-slate-200 hover:bg-slate-900 hover:text-amber-100'"
                            class="w-full text-left px-2.5 py-2 rounded-lg text-xs font-bold flex items-center gap-2.5 transition-all border border-transparent">
                            <i class="fa-solid fa-shield-halved text-blue-400 w-4 text-center"></i>
                            <span>Największa Obrona</span>
                        </button>
                    </div>
                </div>
            </div>
        @endif

        {{-- Classic RPG Battle Layout --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4 lg:gap-5 xl:gap-6 max-w-[1600px] w-full mx-auto pb-24 lg:pb-6">

            {{-- Left Side - Player Panel --}}
            <div class="col-span-1 md:col-span-1 lg:col-span-1 order-2 lg:order-1" id="player-panel-container">
                <div id="player-panel"
                    class="relative rounded-2xl shadow-2xl overflow-hidden bg-slate-950/80 backdrop-blur-xl border border-amber-500/30 transition-all duration-300 {{ $this->isPlayerTurn() ? 'ring-2 ring-amber-400/80 shadow-[0_0_35px_rgba(245,158,11,0.4)] scale-[1.01]' : '' }}">
                    
                    {{-- Glossy Inner Ambient Glow --}}
                    <div class="absolute inset-0 bg-gradient-to-b from-amber-500/10 via-transparent to-black/70 pointer-events-none"></div>

                    <div class="relative p-3 sm:p-3.5 lg:p-3.5 xl:p-5 space-y-2.5 lg:space-y-3 xl:space-y-4">
                        {{-- Player Header & Avatar --}}
                        <div class="text-center">
                            <div class="relative w-16 h-16 sm:w-20 sm:h-20 lg:w-20 lg:h-20 xl:w-24 xl:h-24 2xl:w-28 2xl:h-28 mx-auto">
                                <div class="w-full h-full rounded-2xl overflow-hidden ring-4 ring-amber-500/80 shadow-[0_0_25px_rgba(245,158,11,0.35)] bg-slate-900">
                                    @if (!empty($player) && !empty($player['avatar']))
                                        <img src="{{ $player['avatar'] }}" alt="{{ $player['name'] }}"
                                            class="w-full h-full object-cover">
                                    @else
                                        <img src="{{ $character->getEffectiveAvatarUrl() }}" alt="{{ $character->name }}"
                                            class="w-full h-full object-cover">
                                    @endif
                                </div>
                                <span class="absolute -bottom-2 left-1/2 -translate-x-1/2 bg-gradient-to-r from-amber-600 to-amber-500 text-amber-950 text-[10px] sm:text-xs font-black px-2.5 py-0.5 rounded-full border border-amber-300 shadow-lg medieval-font">
                                    Lvl {{ $character->level }}
                                </span>
                            </div>

                            {{-- Player Name --}}
                            <h3 class="mt-2 text-sm sm:text-base lg:text-lg xl:text-xl font-extrabold text-amber-200 medieval-font drop-shadow-[0_2px_6px_rgba(0,0,0,0.9)] tracking-wide">
                                {{ !empty($player) ? $player['name'] : $character->name }}
                            </h3>
                            <p class="text-[11px] sm:text-xs text-amber-400/80 tracking-wider">
                                {{ $character->class ?? 'Bohater' }}
                            </p>
                        </div>

                        {{-- Player HP Bar (Always loaded!) --}}
                        <div class="space-y-1">
                            {{-- Faza 3: pasek aktywnych efektów statusowych gracza (DoT/CC nałożone przez potwory) --}}
                            <x-combat-status-bar :effects="$this->getPlayerStatusEffects()" />
                            <div class="flex justify-between text-xs font-bold text-amber-200 medieval-font drop-shadow">
                                <span>Życie</span>
                                <span class="font-mono text-emerald-300 text-xs sm:text-sm" title="{{ number_format($this->getCurrentPlayerHp()) }}/{{ number_format($this->player['maxHp'] ?? $character->getMaxHp()) }}">{{ \App\Helpers\FormatHelper::short($this->getCurrentPlayerHp()) }}/{{ \App\Helpers\FormatHelper::short($this->player['maxHp'] ?? $character->getMaxHp()) }}</span>
                            </div>
                            <x-combat-resource-bar id="player-hp-bar" :percent="$this->getPlayerHpPercent()"
                                gradient-class="from-emerald-600 via-emerald-500 to-green-400"
                                glow-shadow="shadow-[0_0_12px_rgba(16,185,129,0.6)]"
                                ring-class="ring-amber-500/40" height="h-3.5 sm:h-4"
                                droplet-color="rgba(220,38,38,0.92)" />
                        </div>

                        {{-- Player Mana Bar --}}
                        <div class="space-y-1 mt-1.5">
                            <div class="flex justify-between text-xs font-bold text-cyan-200 medieval-font drop-shadow">
                                <span>Mana</span>
                                <span class="font-mono text-cyan-300 text-xs sm:text-sm" title="{{ number_format($this->getCurrentPlayerMana()) }}/{{ number_format($character->getMaxMana()) }}">{{ \App\Helpers\FormatHelper::short($this->getCurrentPlayerMana()) }}/{{ \App\Helpers\FormatHelper::short($character->getMaxMana()) }}</span>
                            </div>
                            <x-combat-resource-bar id="player-mana-bar" :percent="$this->getPlayerManaPercent()"
                                gradient-class="from-blue-600 via-cyan-500 to-teal-400"
                                glow-shadow="shadow-[0_0_12px_rgba(6,182,212,0.6)]"
                                ring-class="ring-cyan-500/40" height="h-3 sm:h-3.5"
                                droplet-color="rgba(139,92,246,0.92)" />
                        </div>

                        {{-- Active Buffs --}}
                        @php $currentState = method_exists($this, 'getCurrentState') ? $this->getCurrentState() : null; @endphp
                        @if($currentState && !empty($currentState['buffs']))
                            <div class="flex flex-wrap gap-2 justify-center">
                                @foreach($currentState['buffs'] as $key => $buff)
                                    <div class="group relative bg-slate-900/90 border border-blue-400/70 hover:border-blue-300 rounded-xl px-2.5 py-1 text-xs text-blue-200 flex items-center gap-1.5 shadow-lg cursor-pointer transition-all hover:scale-105">
                                        @if(!empty($buff['icon']))
                                            <img src="{{ route('assets.skills.icons', ['filename' => $buff['icon']]) }}" class="w-4 h-4 object-contain" alt="{{ $buff['name'] ?? 'Wzmocnienie' }}">
                                        @else
                                            <span class="text-xs">⚔️</span>
                                        @endif
                                        <span class="font-bold font-mono text-blue-300 text-xs">{{ $buff['duration'] }}T</span>

                                        {{-- Interactive Hover Infobox --}}
                                        <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-52 p-3 bg-slate-950/95 border border-blue-400/80 rounded-xl shadow-[0_0_20px_rgba(59,130,246,0.35)] backdrop-blur-md opacity-0 group-hover:opacity-100 pointer-events-none transition-all duration-200 z-50 text-left">
                                            <div class="flex items-center gap-2 mb-1.5 border-b border-blue-500/30 pb-1.5">
                                                @if(!empty($buff['icon']))
                                                    <img src="{{ route('assets.skills.icons', ['filename' => $buff['icon']]) }}" class="w-5 h-5 object-contain" alt="">
                                                @else
                                                    <span class="text-sm">⚔️</span>
                                                @endif
                                                <span class="text-xs font-bold text-blue-200 medieval-font tracking-wide">{{ $buff['name'] ?? 'Wzmocnienie' }}</span>
                                                <span class="ml-auto text-[10px] font-bold px-2 py-0.5 rounded-full bg-blue-900/90 text-blue-200 border border-blue-400/50 font-mono">{{ $buff['duration'] }}T</span>
                                            </div>
                                            <div class="text-[11px] text-slate-300 leading-snug space-y-1">
                                                <div class="text-blue-300/90 font-semibold">Status: Wzmocnienie</div>
                                                <p class="text-slate-200">{{ $buff['description'] ?? 'Aktywne wzmocnienie postaci.' }}</p>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        {{-- Equipped Skills HUD --}}
                        @if($this->equippedSkills && count($this->equippedSkills) > 0)
                            <div class="bg-black/60 rounded-2xl p-1.5 lg:p-2 border border-amber-500/20 shadow-inner">
                                <div class="flex gap-2 justify-center">
                                    @foreach($this->equippedSkills as $cs)
                                        @php
                                            $skill = $cs->skill;
                                            $lvl = $cs->level ?? 1;
                                            $cd = $currentState['cooldowns'][$cs->id] ?? 0;
                                            $eqWeapon = $character->getEquippedWeaponType();
                                            $reqWep = $skill->required_weapon_type;
                                            $isWepMatched = ($reqWep === 'all' || $reqWep === $eqWeapon);
                                            $manaCost = $cs->getManaCost();
                                            $effVal = $skill->base_value + ($skill->scaling_value * ($lvl - 1));

                                            $wepLabel = 'Wszystkie';
                                            $wepIcon = 'fa-solid fa-swords';
                                            if ($reqWep === 'sword') { $wepLabel = 'Miecz'; $wepIcon = 'fa-solid fa-khanda'; }
                                            elseif ($reqWep === 'axe') { $wepLabel = 'Topór'; $wepIcon = 'fa-solid fa-axe'; }
                                            elseif ($reqWep === 'wand') { $wepLabel = 'Różdżka'; $wepIcon = 'fa-solid fa-wand-magic-sparkles'; }
                                            elseif ($reqWep === 'bell') { $wepLabel = 'Dzwon'; $wepIcon = 'fa-solid fa-bell'; }
                                            elseif ($reqWep === 'bow') { $wepLabel = 'Łuk'; $wepIcon = 'fa-solid fa-bow-arrow'; }
                                            elseif ($reqWep === 'dagger') { $wepLabel = 'Sztylet'; $wepIcon = 'fa-solid fa-scissors'; }

                                            $effText = '';
                                            if (in_array($skill->effect_type, ['direct_dmg', 'direct', 'aoe_dmg'])) {
                                                $effText = round($effVal * 100) . '% Obrażeń Broni';
                                            } elseif (in_array($skill->effect_type, ['buff_phys_dmg', 'buff_damage'])) {
                                                $effText = '+' . round($effVal * 100) . '% Fizycznych Obrażeń';
                                            } elseif (in_array($skill->effect_type, ['fire', 'dot_fire'])) {
                                                $effText = number_format($effVal * 100, 1) . '% Max HP / Turę';
                                            } elseif (in_array($skill->effect_type, ['poison', 'dot_poison'])) {
                                                $effText = number_format($effVal * 100, 1) . '% Akt. HP / Turę';
                                            } elseif ($skill->effect_type === 'heal') {
                                                $effText = '+' . round($effVal * 100) . '% Max HP';
                                            } elseif (in_array($skill->effect_type, ['freeze', 'stun'])) {
                                                $effText = round($effVal * 100) . '% Obrażeń, ' . $skill->base_duration . ' Tur CC';
                                            } elseif ($skill->effect_type === 'buff_defense') {
                                                $effText = '-' . round($effVal * 100) . '% Obrażeń Przychodzących';
                                            } elseif ($skill->effect_type === 'passive_aura_dmg') {
                                                $effText = '+' . round($effVal * 100) . '% Obrażeń Fizycznych (Pasywna Aura)';
                                            } elseif ($skill->effect_type === 'passive_extra_attack') {
                                                $effText = round(min(0.75, $effVal) * 100) . '% Szansy na Dodatkowy Atak';
                                            } else {
                                                $effText = round($effVal * 100) . '% Mocy';
                                            }
                                        @endphp

                                        <div class="group relative inline-block cursor-pointer z-20">
                                            {{-- Skill Icon Badge --}}
                                            <div class="relative w-9 h-9 sm:w-10 sm:h-10 lg:w-11 lg:h-11 rounded-xl border {{ $cd > 0 ? 'border-slate-700 bg-slate-900' : ($skill->type === 'passive' ? 'border-purple-500/80 bg-purple-950/80 shadow-[0_0_12px_rgba(168,85,247,0.4)]' : 'border-amber-500/80 bg-amber-950/80 shadow-[0_0_12px_rgba(245,158,11,0.4)]') }} flex items-center justify-center overflow-hidden transition-transform group-hover:scale-105">
                                                @if($cd > 0)
                                                    <div class="absolute inset-0 bg-black/80 flex items-center justify-center z-10">
                                                        <span class="text-white font-extrabold text-xs drop-shadow font-mono">{{ $cd }}</span>
                                                    </div>
                                                @endif
                                                @if($skill->icon)
                                                    <img src="{{ route('assets.skills.icons', ['filename' => $skill->icon]) }}" class="w-full h-full object-contain p-1 {{ $cd > 0 ? 'opacity-30' : 'opacity-100' }}" alt="{{ $skill->name }}">
                                                @else
                                                    <span class="text-[10px] font-bold text-amber-200 {{ $cd > 0 ? 'opacity-40' : 'opacity-100' }}">{{ mb_substr($skill->name, 0, 3) }}</span>
                                                @endif
                                            </div>

                                            {{-- Rich Hover Popover / Modal --}}
                                            <div class="opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 pointer-events-none group-hover:pointer-events-auto z-[100] absolute bottom-full mb-2.5 left-1/2 -translate-x-1/2 w-64 sm:w-72 bg-slate-950/95 border-2 border-amber-500/80 rounded-xl p-3 shadow-[0_10px_30px_rgba(0,0,0,0.9)] backdrop-blur-md text-left cursor-default">
                                                <div class="flex items-center gap-2.5 mb-2 pb-2 border-b border-amber-500/30">
                                                    <div class="w-9 h-9 rounded-lg border border-amber-500/60 bg-black/80 p-0.5 shrink-0">
                                                        @if($skill->icon)
                                                            <img src="{{ route('assets.skills.icons', ['filename' => $skill->icon]) }}" class="w-full h-full object-contain" alt="">
                                                        @else
                                                            <i class="fa-solid fa-khanda text-amber-400"></i>
                                                        @endif
                                                    </div>
                                                    <div class="min-w-0 flex-1">
                                                        <h5 class="text-xs sm:text-sm font-extrabold text-amber-200 medieval-font truncate">{{ $skill->name }}</h5>
                                                        <div class="flex items-center gap-1.5 mt-0.5">
                                                            <span class="px-1.5 py-0.2 rounded text-[9px] font-black uppercase {{ $skill->type === 'passive' ? 'bg-purple-950 text-purple-300 border border-purple-600/60' : 'bg-blue-950 text-blue-300 border border-blue-600/60' }}">
                                                                {{ $skill->type === 'passive' ? 'Pasywna' : 'Aktywna' }}
                                                            </span>
                                                            <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-amber-950 text-amber-300 border border-amber-600/60 font-mono">
                                                                Lv. {{ $lvl }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <p class="text-[11px] text-slate-300 mb-2 leading-relaxed font-sans">
                                                    {{ $skill->description }}
                                                </p>

                                                <div class="space-y-1.5 text-[10px] font-semibold">
                                                    <div class="bg-slate-900/90 border border-emerald-800/60 rounded px-2 py-1 text-emerald-300 flex items-center justify-between">
                                                        <span><i class="fa-solid fa-bolt text-emerald-400 mr-1"></i>Efekt:</span>
                                                        <span class="font-bold text-amber-200">{{ $effText }}</span>
                                                    </div>

                                                    <div class="flex items-center gap-1.5 flex-wrap">
                                                        @if($manaCost > 0)
                                                            <span class="bg-cyan-950/90 border border-cyan-700/60 text-cyan-300 px-2 py-0.5 rounded font-mono">
                                                                <i class="fa-solid fa-droplet mr-1"></i>{{ $manaCost }} MP {{ $skill->type === 'passive' ? '/ turę' : '' }}
                                                            </span>
                                                        @else
                                                            <span class="bg-slate-900 border border-slate-700 text-slate-400 px-2 py-0.5 rounded font-mono">
                                                                0 MP
                                                            </span>
                                                        @endif

                                                        @if($skill->type === 'active')
                                                            <span class="bg-sky-950/90 border border-sky-700/60 text-sky-300 px-2 py-0.5 rounded font-mono">
                                                                <i class="fa-regular fa-clock mr-1"></i>CD: {{ $skill->base_cooldown }} Tur
                                                            </span>
                                                        @endif

                                                        @if($skill->base_duration > 1)
                                                            <span class="bg-purple-950/90 border border-purple-700/60 text-purple-300 px-2 py-0.5 rounded font-mono">
                                                                <i class="fa-solid fa-hourglass-half mr-1"></i>Czas: {{ $skill->base_duration }} Tur
                                                            </span>
                                                        @endif

                                                        <span class="px-2 py-0.5 rounded font-mono border {{ $isWepMatched ? 'bg-amber-950/80 border-amber-600/60 text-amber-300' : 'bg-red-950/80 border-red-600/60 text-red-400' }}">
                                                            <i class="{{ $wepIcon }} mr-1"></i>{{ $wepLabel }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- XP Progress Bar --}}
                        <div class="space-y-1">
                            <div class="flex justify-between text-xs font-semibold text-indigo-200">
                                <span>Doświadczenie</span>
                                <span class="font-mono text-indigo-300" title="{{ number_format($character->xp) }}/{{ number_format($this->getXpToNextLevel()) }}">{{ \App\Helpers\FormatHelper::short($character->xp) }}/{{ \App\Helpers\FormatHelper::short($this->getXpToNextLevel()) }}</span>
                            </div>
                            <div class="h-2 sm:h-2.5 w-full rounded-full bg-indigo-950/70 ring-1 ring-indigo-700/40 p-0.5 relative overflow-hidden">
                                <div class="h-full relative rounded-full overflow-hidden shadow-[0_0_10px_rgba(99,102,241,0.5)] transition-[width] duration-700 ease-out"
                                    style="width: {{ max(2, $this->getXpPercentage()) }}%">
                                    <div class="absolute inset-0 xp-liquid-fill"></div>
                                    <div class="absolute inset-0 opacity-35 xp-wave-1"></div>
                                    <div class="absolute inset-0 opacity-30 xp-wave-2"></div>
                                    <div class="absolute inset-0 xp-liquid-sparkles opacity-60"></div>
                                </div>
                            </div>
                        </div>

                        {{-- Player Attributes Grid (Loaded immediately!) --}}
                        <div>
                            <h4 class="text-[11px] sm:text-xs font-bold text-amber-200/90 mb-1.5 medieval-font tracking-wide">
                                Atrybuty Bojowe
                            </h4>
                            @php
                                $pStats = $this->player['stats'] ?? $character->getTotalAttributes();
                            @endphp
                            <div class="grid grid-cols-2 gap-1.5 lg:gap-2">
                                <div class="bg-slate-900/90 border border-red-800/40 rounded-xl p-1.5 lg:p-2 text-center shadow-md">
                                    <div class="text-[10px] sm:text-[11px] font-semibold text-red-300 tracking-wider">STR (Siła)</div>
                                    <div class="text-xs sm:text-sm lg:text-base font-black text-amber-100 font-mono">{{ \App\Helpers\FormatHelper::short($pStats['str'] ?? 0) }}</div>
                                </div>
                                <div class="bg-slate-900/90 border border-blue-800/40 rounded-xl p-1.5 lg:p-2 text-center shadow-md">
                                    <div class="text-[10px] sm:text-[11px] font-semibold text-blue-300 tracking-wider">INT (Wiedza)</div>
                                    <div class="text-xs sm:text-sm lg:text-base font-black text-amber-100 font-mono">{{ \App\Helpers\FormatHelper::short($pStats['int'] ?? 0) }}</div>
                                </div>
                                <div class="bg-slate-900/90 border border-emerald-800/40 rounded-xl p-1.5 lg:p-2 text-center shadow-md">
                                    <div class="text-[10px] sm:text-[11px] font-semibold text-emerald-300 tracking-wider">VIT (Witalność)</div>
                                    <div class="text-xs sm:text-sm lg:text-base font-black text-amber-100 font-mono">{{ \App\Helpers\FormatHelper::short($pStats['vit'] ?? 0) }}</div>
                                </div>
                                <div class="bg-slate-900/90 border border-amber-800/40 rounded-xl p-1.5 lg:p-2 text-center shadow-md">
                                    <div class="text-[10px] sm:text-[11px] font-semibold text-amber-300 tracking-wider">AGI (Zręczność)</div>
                                    <div class="text-xs sm:text-sm lg:text-base font-black text-amber-100 font-mono">{{ \App\Helpers\FormatHelper::short($pStats['agi'] ?? 0) }}</div>
                                </div>
                            </div>
                        </div>

                        {{-- Player Effective Combat Stats --}}
                        @php
                            $playerCombatStats = $this->getPlayerCombatStats();
                        @endphp
                        <div class="mt-2.5 pt-2 border-t border-amber-900/40">
                            <h4 class="text-[11px] sm:text-xs font-bold text-amber-200/90 mb-1.5 medieval-font tracking-wide flex items-center justify-between">
                                <span>Statystyki podczas Walki</span>
                                <span class="text-[9px] text-amber-400/80 font-normal">Bieżące wartości</span>
                            </h4>
                            <div class="grid grid-cols-2 gap-1.5 lg:gap-2">
                                <div class="bg-amber-950/60 border border-yellow-600/40 rounded-xl p-1.5 text-center shadow-md">
                                    <div class="text-[9px] sm:text-[10px] font-semibold text-yellow-400 tracking-wider flex items-center justify-center gap-1">
                                        <i class="fa-solid fa-bolt text-yellow-400"></i> Szansa na Kryt
                                    </div>
                                    <div class="text-xs sm:text-sm font-black text-yellow-300 font-mono">{{ $playerCombatStats['crit_chance'] }}%</div>
                                </div>
                                <div class="bg-amber-950/60 border border-emerald-600/40 rounded-xl p-1.5 text-center shadow-md">
                                    <div class="text-[9px] sm:text-[10px] font-semibold text-emerald-400 tracking-wider flex items-center justify-center gap-1">
                                        <i class="fa-solid fa-shield-halved text-emerald-400"></i> Szansa na Unik
                                    </div>
                                    <div class="text-xs sm:text-sm font-black text-emerald-300 font-mono">{{ $playerCombatStats['dodge_chance'] }}%</div>
                                </div>
                                <div class="bg-amber-950/60 border border-red-600/40 rounded-xl p-1.5 text-center shadow-md">
                                    <div class="text-[9px] sm:text-[10px] font-semibold text-red-400 tracking-wider flex items-center justify-center gap-1">
                                        <i class="fa-solid fa-crosshairs text-red-400"></i> Atak (DMG)
                                    </div>
                                    <div class="text-xs sm:text-sm font-black text-red-200 font-mono">{{ \App\Helpers\FormatHelper::short($playerCombatStats['atk_min']) }} - {{ \App\Helpers\FormatHelper::short($playerCombatStats['atk_max']) }}</div>
                                </div>
                                <div class="bg-amber-950/60 border border-purple-600/40 rounded-xl p-1.5 text-center shadow-md">
                                    <div class="text-[9px] sm:text-[10px] font-semibold text-purple-400 tracking-wider flex items-center justify-center gap-1">
                                        <i class="fa-solid fa-wand-magic-sparkles text-purple-400"></i> Atak Mag. (DMG)
                                    </div>
                                    <div class="text-xs sm:text-sm font-black text-purple-200 font-mono">{{ \App\Helpers\FormatHelper::short($playerCombatStats['magic_atk_min']) }} - {{ \App\Helpers\FormatHelper::short($playerCombatStats['magic_atk_max']) }}</div>
                                </div>
                                <div class="col-span-2 bg-amber-950/60 border border-blue-600/40 rounded-xl p-1.5 text-center shadow-md">
                                    <div class="text-[9px] sm:text-[10px] font-semibold text-blue-400 tracking-wider flex items-center justify-center gap-1">
                                        <i class="fa-solid fa-shield text-blue-400"></i> Obrona (DEF)
                                    </div>
                                    <div class="text-xs sm:text-sm font-black text-blue-200 font-mono">{{ \App\Helpers\FormatHelper::short($playerCombatStats['defense']) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Center - Glossy Battle Log --}}
            <div class="col-span-1 md:col-span-2 lg:col-span-1 order-1 lg:order-2 mb-2 lg:mb-0">
                <section class="relative rounded-2xl shadow-2xl overflow-hidden bg-slate-950/80 backdrop-blur-xl border border-amber-500/30 h-[340px] sm:h-[370px] md:h-[400px] lg:h-[420px] xl:h-[480px] 2xl:h-[540px] flex flex-col">
                    
                    {{-- Header --}}
                    <header class="relative p-2 text-center bg-amber-950/40 border-b border-amber-500/20 backdrop-blur-md">
                        <h3 class="font-serif text-base sm:text-lg lg:text-xl text-amber-200 tracking-wider medieval-font drop-shadow">
                            Kronika Bitwy
                        </h3>
                        @if ($inLocationEvent)
                            <div wire:key="location-event-header-progress">
                                <div class="mt-1.5 flex items-center justify-center gap-2 flex-wrap">
                                    <span class="text-xs sm:text-sm font-bold text-purple-300 medieval-font">
                                        {{ $eventName }} - Potwór {{ $eventRunProgressCurrent }}/{{ $eventRunProgressTotal }}
                                    </span>
                                    @if ($eventIsHardcore)
                                        <span class="text-[10px] font-bold text-red-300 bg-red-950/70 border border-red-500/40 rounded-full px-2 py-0.5">HARDCORE</span>
                                    @endif
                                </div>
                                <div class="mt-1.5 h-1.5 w-full max-w-xs mx-auto bg-slate-800 rounded-full overflow-hidden">
                                    <div class="h-full bg-purple-500 transition-all" style="width: {{ $eventRunProgressTotal > 0 ? min(100, ($eventRunProgressCurrent / $eventRunProgressTotal) * 100) : 0 }}%"></div>
                                </div>
                            </div>
                        @endif
                    </header>

                    {{-- Battle Log Scroll Area --}}
                    <div id="combat-log-container" class="relative flex-1 overflow-y-auto p-3 lg:p-4 custom-scrollbar">
                        {{-- Loading Overlay during startBattle --}}
                        <div wire:loading.flex wire:target="startBattle" class="absolute inset-0 z-20 flex-col items-center justify-center bg-slate-950/90 backdrop-blur-md text-center">
                            <div class="relative w-20 h-20 mb-3">
                                <div class="absolute inset-0 rounded-full border-4 border-amber-500/30 border-t-amber-400 animate-[spin_1s_linear_infinite]"></div>
                            </div>
                            <h3 class="font-serif text-xl sm:text-2xl text-amber-200 tracking-wider medieval-font drop-shadow animate-pulse">
                                Szukanie przeciwnika...
                            </h3>
                        </div>

                        @if($isCalculating)
                            <div class="h-full flex flex-col items-center justify-center text-center" wire:poll.500ms="checkCombatStatus">
                                <div class="relative w-24 h-24 sm:w-28 sm:h-28 mb-4">
                                    <div class="absolute inset-0 rounded-full border-4 border-amber-500/30 border-t-amber-400 animate-spin"></div>
                                    <div class="absolute inset-2 rounded-full border-4 border-amber-700/30 border-b-amber-600 animate-[spin_1.5s_linear_infinite_reverse]"></div>
                                </div>
                                <h3 class="font-serif text-2xl sm:text-3xl text-amber-200 tracking-wider medieval-font drop-shadow animate-pulse">
                                    Obliczanie walki...
                                </h3>
                                <p class="text-amber-300/80 italic mt-2 font-semibold text-sm">Krzyżowanie mieczy...</p>
                            </div>
                        @elseif($isEventCalculating)
                            <div wire:key="location-event-calculating" class="h-full flex flex-col items-center justify-center text-center" wire:poll.500ms="checkEventCombatStatus">
                                <div class="relative w-24 h-24 sm:w-28 sm:h-28 mb-4">
                                    <div class="absolute inset-0 rounded-full border-4 border-purple-500/30 border-t-purple-400 animate-spin"></div>
                                    <div class="absolute inset-2 rounded-full border-4 border-purple-700/30 border-b-purple-600 animate-[spin_1.5s_linear_infinite_reverse]"></div>
                                </div>
                                <h3 class="font-serif text-2xl sm:text-3xl text-purple-200 tracking-wider medieval-font drop-shadow animate-pulse">
                                    Obliczanie walki eventu...
                                </h3>
                                <p class="text-purple-300/80 italic mt-2 font-semibold text-sm">{{ $eventRunProgressCurrent }}/{{ $eventRunProgressTotal }} - Krzyżowanie mieczy...</p>
                            </div>
                        @else
                            <ul class="space-y-2 text-amber-100">
                                @if (empty($visibleTurns))
                                    @if ($isPlaying)
                                        <li class="text-center py-10 animate-pulse">
                                            <div class="text-amber-300/80 font-serif italic text-lg sm:text-xl">
                                                Rozpoczynanie bitwy...
                                            </div>
                                        </li>
                                    @elseif (!$battleCompleted)
                                        <li class="text-center py-10">
                                            <div class="text-amber-300/80 font-serif italic text-lg sm:text-xl">
                                                Naciśnij "Rozpocznij Walkę" aby rozpocząć przygodę...
                                            </div>
                                        </li>
                                    @endif
                            @else
                                @foreach ($visibleTurns as $index => $turn)
                                    <li wire:key="turn-{{ $currentEncounterId }}-{{ $index }}" class="leading-relaxed bg-slate-900/70 border border-amber-500/20 rounded-xl px-3 py-2 lg:px-3.5 lg:py-2.5 shadow-sm backdrop-blur-sm text-xs sm:text-sm lg:text-sm xl:text-base">
                                        <x-combat-log-entry :turn="$turn" :index="$index" :player-name="$player['name']" :enemy-name="$enemy['name']" />
                                    </li>
                                @endforeach

                                {{-- Battle Result & Rewards --}}
                                @if ($battleCompleted)
                                    <li
                                        wire:key="battle-result-{{ $currentEncounterId }}"
                                        class="text-center mt-4 p-4 rounded-2xl {{ $result == 'win' ? 'bg-emerald-950/80 border border-emerald-500/40 text-emerald-200 shadow-[0_0_25px_rgba(16,185,129,0.2)]' : ($result == 'finished' ? 'bg-purple-950/80 border border-purple-500/40 text-purple-200' : 'bg-red-950/80 border border-red-500/40 text-red-200') }} backdrop-blur-md">
                                        <div class="text-xl sm:text-2xl font-bold medieval-font tracking-wide">
                                            {{ $result == 'win' ? 'TRIUMF!' : ($result == 'finished' ? 'WALKA ZAKOŃCZONA' : 'KLĘSKA!') }}
                                        </div>
                                        @if ($result == 'win' || $result == 'finished')
                                            @if($result == 'finished')
                                                <div class="text-base sm:text-lg mt-1.5 font-bold text-purple-200">
                                                    Łączny dmg: <span class="text-red-400 font-mono drop-shadow-md">{{ number_format($damageDealt) }}</span>
                                                </div>
                                            @endif

                                            {{-- Loot Display --}}
                                            @if (!empty($drops))
                                                <div class="mt-3 p-3 bg-slate-900/90 border border-amber-500/30 rounded-xl text-left">
                                                    <h4 class="font-bold text-amber-300 mb-2 medieval-font text-center text-sm sm:text-base">Zdobycz Bitewna:</h4>
                                                    <div class="space-y-1.5 text-xs sm:text-sm lg:text-base">
                                                        <div class="flex items-center space-x-2">
                                                            <span class="text-indigo-200">
                                                                +{{ \App\Helpers\FormatHelper::short(!empty($xpData) ? $xpData['base'] : $xpGained) }} XP (Doświadczenie)
                                                                @if (!empty($xpData) && isset($xpData['multiplier']) && $xpData['multiplier'] > 1.0)
                                                                    <span class="font-bold text-xs text-emerald-400">(+{{ \App\Helpers\FormatHelper::short($xpData['bonus']) }} z bonusu {{ round(($xpData['multiplier'] - 1) * 100) }}%)</span>
                                                                @endif
                                                            </span>
                                                        </div>

                                                        <div class="flex items-center space-x-2">
                                                            <span class="text-amber-200">
                                                                +{{ \App\Helpers\FormatHelper::short(!empty($goldData) ? $goldData['base'] : $goldGained) }} Złota
                                                                @if (!empty($goldData) && isset($goldData['multiplier']) && $goldData['multiplier'] > 1.0)
                                                                    <span class="font-bold text-xs text-yellow-400">(+{{ \App\Helpers\FormatHelper::short($goldData['bonus']) }} z bonusu {{ round(($goldData['multiplier'] - 1) * 100) }}%)</span>
                                                                @endif
                                                            </span>
                                                        </div>

                                                        @if (isset($drops['gems']) && $drops['gems'] > 0)
                                                            <div class="flex items-center space-x-2">
                                                                <span class="text-blue-300">+{{ $drops['gems'] }} Klejnotów</span>
                                                            </div>
                                                        @endif

                                                        @foreach ($drops['items'] ?? [] as $item)
                                                            <div class="flex items-center space-x-2">
                                                                <span class="text-purple-300 font-semibold">{{ $item['name'] }} ({{ $item['quantity'] }}x)</span>
                                                            </div>
                                                        @endforeach

                                                        @foreach ($drops['materials'] ?? [] as $material)
                                                            <div class="flex items-center space-x-2">
                                                                <span class="text-emerald-300">{{ $material['name'] }} ({{ $material['quantity'] }}x)</span>
                                                            </div>
                                                        @endforeach

                                                        @if ($character->isBackpackFull())
                                                            <div class="text-[10px] sm:text-xs text-amber-400 font-semibold mt-2 flex items-center gap-1.5 bg-amber-950/60 p-1.5 rounded border border-amber-500/40">
                                                                <i class="fa-solid fa-triangle-exclamation text-amber-400"></i>
                                                                <span>Plecak jest pełny ({{ $character->getBackpackCount() }}/{{ $character->getBackpackCapacity() }}) - nowe przedmioty nie są zbierane!</span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endif
                                        @else
                                            <div class="text-xs sm:text-sm mt-1 font-semibold text-red-300">
                                                Zostałeś pokonany w walce...
                                            </div>
                                        @endif
                                    </li>
                                @endif
                            @endif
                        </ul>
                        @endif
                    </div>

                    {{-- Battle Controls (100% Instant Client-Side Reactive Controls) --}}
                    <footer wire:key="battle-controls-footer" class="relative p-3 lg:p-3.5 bg-amber-950/40 border-t border-amber-500/20 backdrop-blur-md">

                        @if ($inLocationEvent)
                            <div wire:key="location-event-footer-controls" class="flex flex-col gap-2.5 items-center">
                                @if ($eventRunResult)
                                    <button wire:click="dismissEventRun"
                                        class="rounded-xl px-6 py-2.5 sm:px-7 sm:py-3 bg-gradient-to-r from-amber-700 to-amber-600 border border-amber-400/60 text-white font-bold medieval-font shadow-lg hover:scale-105 active:scale-95 transition-all text-sm sm:text-base">
                                        Kontynuuj eksplorację
                                    </button>
                                @elseif (!$isEventCalculating && !$isPlaying)
                                    <button wire:click="{{ $eventAutoAdvancePaused ? 'resumeEventAutoAdvance' : 'pauseEventAutoAdvance' }}"
                                        class="rounded-xl px-5 py-2 sm:px-6 sm:py-2.5 bg-amber-900/70 border border-amber-500/50 text-amber-100 font-bold hover:bg-amber-800/80 active:scale-95 transition-all medieval-font shadow-md text-sm sm:text-base">
                                        {{ $eventAutoAdvancePaused ? 'Wznów łańcuch' : 'Pauza' }}
                                    </button>
                                @elseif (!$battleCompleted)
                                    <button @click="isPaused = !isPaused; window.toggleCombatPlayback(isPaused)"
                                        class="rounded-xl px-5 py-2 sm:px-6 sm:py-2.5 bg-amber-900/70 border border-amber-500/50 text-amber-100 font-bold hover:bg-amber-800/80 active:scale-95 transition-all medieval-font shadow-md text-sm sm:text-base">
                                        <span x-text="isPaused ? 'Wznów' : 'Pauza'"></span>
                                    </button>
                                @endif

                                @if (!empty($visibleTurns))
                                    @php $canSpeed5Event = $this->canUseSpeed5(); @endphp
                                    <div class="flex gap-1.5 sm:gap-2">
                                        <button @click="speed = 1; window.setCombatSpeed(1)"
                                            :class="speed === 1 ? 'bg-amber-600/90 border-amber-300 text-white shadow-[0_0_12px_rgba(245,158,11,0.5)] scale-105' : 'bg-slate-900/80 border-slate-700 text-amber-200/70 hover:bg-slate-800'"
                                            class="rounded-xl px-3 py-1.5 sm:px-4 sm:py-2 text-xs sm:text-sm font-bold medieval-font border transition-all">x1</button>
                                        <button @click="speed = 2; window.setCombatSpeed(2)"
                                            :class="speed === 2 ? 'bg-amber-600/90 border-amber-300 text-white shadow-[0_0_12px_rgba(245,158,11,0.5)] scale-105' : 'bg-slate-900/80 border-slate-700 text-amber-200/70 hover:bg-slate-800'"
                                            class="rounded-xl px-3 py-1.5 sm:px-4 sm:py-2 text-xs sm:text-sm font-bold medieval-font border transition-all">x2</button>
                                        @if ($canSpeed5Event)
                                            <button @click="speed = 5; window.setCombatSpeed(5)"
                                                :class="speed === 5 ? 'bg-purple-600/90 border-purple-300 text-white shadow-[0_0_12px_rgba(168,85,247,0.6)] scale-105' : 'bg-slate-900/80 border-slate-700 text-purple-200/70 hover:bg-slate-800'"
                                                class="rounded-xl px-3 py-1.5 sm:px-4 sm:py-2 text-xs sm:text-sm font-bold medieval-font border transition-all">x5</button>
                                        @endif
                                    </div>
                                @endif

                                @if ($eventIsHardcore && !$eventRunResult)
                                    @php
                                        $eventPotions = \App\Infrastructure\Persistence\ItemInstance::where('owner_character_id', $character->id)
                                            ->where('location', 'inventory')
                                            ->whereHas('template', function ($q) {
                                                $q->where('type', 'consumable')
                                                  ->where(function ($sub) {
                                                      $sub->whereNotNull('base_stats->heal_amount')
                                                          ->orWhereNotNull('base_stats->heal_pct');
                                                  });
                                            })
                                            ->with('template')
                                            ->get();
                                    @endphp
                                    @if ($eventPotions->isNotEmpty())
                                        <div class="flex flex-wrap gap-1.5 justify-center">
                                            @foreach ($eventPotions as $potion)
                                                <button wire:click="useEventPotion('{{ $potion->id }}')"
                                                    wire:loading.attr="disabled" wire:target="useEventPotion"
                                                    class="rounded-xl px-3 py-1.5 bg-emerald-950/80 border border-emerald-500/40 text-emerald-200 font-semibold hover:bg-emerald-900/80 transition-all text-xs">
                                                    {{ $potion->template->name }} ({{ $potion->stack_size }}x)
                                                </button>
                                            @endforeach
                                        </div>
                                    @endif
                                @endif
                            </div>
                        @else
                        <div wire:key="normal-footer-controls" class="flex flex-col gap-2.5">
                            {{-- Main Controls --}}
                            <div class="flex items-center justify-center gap-2.5">
                                @if ($isCalculating)
                                    <button wire:click="cancelBattle"
                                        class="rounded-xl px-5 py-2.5 bg-red-950/80 border border-red-500/40 text-red-200 font-bold hover:bg-red-900/80 transition-all medieval-font shadow-lg text-xs sm:text-sm"
                                        wire:loading.attr="disabled"
                                        wire:target="cancelBattle">
                                        <span wire:loading.remove wire:target="cancelBattle">Przerwij walkę</span>
                                        <span wire:loading wire:target="cancelBattle">Przerywanie...</span>
                                    </button>
                                @elseif (empty($visibleTurns) && !$isPlaying && !$battleCompleted)
                                    <button wire:click="startBattle"
                                        class="rounded-xl px-6 py-2.5 sm:px-7 sm:py-3 bg-gradient-to-r from-emerald-700 via-emerald-600 to-green-600 border border-emerald-400/60 text-white font-extrabold text-sm sm:text-base medieval-font shadow-[0_0_20px_rgba(16,185,129,0.4)] hover:scale-105 active:scale-95 transition-all">
                                        Rozpocznij Walkę
                                    </button>
                                @elseif (!$battleCompleted)
                                    <button @click="isPaused = !isPaused; window.toggleCombatPlayback(isPaused)"
                                        class="rounded-xl px-5 py-2 sm:px-6 sm:py-2.5 bg-amber-900/70 border border-amber-500/50 text-amber-100 font-bold hover:bg-amber-800/80 active:scale-95 transition-all medieval-font shadow-md text-sm sm:text-base">
                                        <span x-text="isPaused ? 'Wznów' : 'Pauza'"></span>
                                    </button>
                                @endif

                                {{-- Speed Controls --}}
                                @if (!empty($visibleTurns))
                                    @php
                                        $canSpeed5 = $this->canUseSpeed5();
                                    @endphp
                                    <div class="flex gap-1.5 sm:gap-2">
                                        <button @click="speed = 1; window.setCombatSpeed(1)"
                                            :class="speed === 1 ? 'bg-amber-600/90 border-amber-300 text-white shadow-[0_0_12px_rgba(245,158,11,0.5)] scale-105' : 'bg-slate-900/80 border-slate-700 text-amber-200/70 hover:bg-slate-800'"
                                            class="rounded-xl px-3 py-1.5 sm:px-4 sm:py-2 text-xs sm:text-sm font-bold medieval-font border transition-all">
                                            x1
                                        </button>
                                        <button @click="speed = 2; window.setCombatSpeed(2)"
                                            :class="speed === 2 ? 'bg-amber-600/90 border-amber-300 text-white shadow-[0_0_12px_rgba(245,158,11,0.5)] scale-105' : 'bg-slate-900/80 border-slate-700 text-amber-200/70 hover:bg-slate-800'"
                                            class="rounded-xl px-3 py-1.5 sm:px-4 sm:py-2 text-xs sm:text-sm font-bold medieval-font border transition-all">
                                            x2
                                        </button>
                                        @if ($canSpeed5)
                                            <button @click="speed = 5; window.setCombatSpeed(5)"
                                                :class="speed === 5 ? 'bg-purple-600/90 border-purple-300 text-white shadow-[0_0_12px_rgba(168,85,247,0.6)] scale-105' : 'bg-slate-900/80 border-slate-700 text-purple-200/70 hover:bg-slate-800'"
                                                class="rounded-xl px-3 py-1.5 sm:px-4 sm:py-2 text-xs sm:text-sm font-bold medieval-font border transition-all">
                                                x5
                                            </button>
                                        @else
                                            <button disabled
                                                title="Wymagany 30 poziom postaci lub aktywne konto VIP"
                                                class="rounded-xl px-3 py-1.5 sm:px-4 sm:py-2 text-xs sm:text-sm font-bold medieval-font border border-slate-800 bg-slate-950/60 text-slate-500 cursor-not-allowed opacity-60 flex items-center gap-1">
                                                <i class="fa-solid fa-lock text-[10px]"></i> x5
                                            </button>
                                        @endif
                                    </div>
                                @endif

                                {{-- Reset Battle --}}
                                @if ($battleCompleted && !$isWorldBoss)
                                    <button wire:click="resetEncounter"
                                        class="rounded-xl px-5 py-2.5 sm:px-6 sm:py-3 bg-gradient-to-r from-amber-700 to-amber-600 border border-amber-400/60 text-white font-bold medieval-font shadow-lg hover:scale-105 active:scale-95 transition-all text-sm sm:text-base">
                                        Kolejna Walka
                                    </button>
                                @endif
                            </div>

                            {{-- Single Consolidated Auto Chain Button --}}
                            @if (!empty($visibleTurns))
                                <div class="flex items-center justify-center">
                                    <button @click="autoChain = !autoChain; window.toggleCombatAuto(autoChain)"
                                        :class="autoChain 
                                            ? 'bg-emerald-950/90 border-emerald-500/80 text-emerald-200 shadow-[0_0_15px_rgba(16,185,129,0.45)] ring-1 ring-emerald-400/50 scale-105' 
                                            : 'bg-red-950/90 border-red-500/70 text-red-200 shadow-[0_0_15px_rgba(239,68,68,0.35)] ring-1 ring-red-500/40'"
                                        class="rounded-xl px-4 py-1.5 sm:px-5 sm:py-2 text-xs sm:text-sm font-bold medieval-font border transition-all active:scale-95 hover:brightness-110 {{ $gameStage <= 12 ? 'opacity-50 cursor-not-allowed' : '' }}"
                                        {{ $gameStage <= 12 ? 'disabled' : '' }}>
                                        <span x-text="'Auto: ' + (autoChain ? 'ON' : 'OFF')"></span>
                                    </button>
                                </div>
                            @endif

                            {{-- Udział w eventach lokacji: opt-out --}}
                            <div class="flex items-center justify-center">
                                <button wire:click="toggleEventsEnabled" wire:loading.attr="disabled" wire:target="toggleEventsEnabled"
                                    class="rounded-xl px-4 py-1.5 sm:px-5 sm:py-2 text-xs sm:text-sm font-bold medieval-font border transition-all active:scale-95 hover:brightness-110 {{ $eventsEnabled ? 'bg-purple-950/90 border-purple-500/80 text-purple-200 shadow-[0_0_15px_rgba(168,85,247,0.45)] ring-1 ring-purple-400/50' : 'bg-slate-900/80 border-slate-700 text-slate-400' }}">
                                    Eventy Lokacji: {{ $eventsEnabled ? 'ON' : 'OFF' }}
                                </button>
                            </div>
                        </div>
                        @endif
                    </footer>
                </section>
            </div>

            {{-- Right Side - Enemy Panel --}}
            <div class="col-span-1 md:col-span-1 lg:col-span-1 order-3 lg:order-3" id="enemy-panel-container">
                <div id="enemy-panel"
                    class="relative rounded-2xl shadow-2xl overflow-hidden bg-slate-950/80 backdrop-blur-xl border border-red-500/30 transition-all duration-300 {{ $this->isEnemyTurn() ? 'ring-2 ring-red-500/80 shadow-[0_0_35px_rgba(239,68,68,0.4)] scale-[1.01]' : '' }}">
                    
                    {{-- Glossy Inner Ambient Glow --}}
                    <div class="absolute inset-0 bg-gradient-to-b from-red-500/10 via-transparent to-black/70 pointer-events-none"></div>

                    <div class="relative p-3.5 sm:p-4 lg:p-4 xl:p-6 space-y-3 lg:space-y-3.5 xl:space-y-5">
                        @php
                            $monstersState = $isOverLevelCombat ? $this->getCurrentMonstersState() : [];
                            $displayMonster = ($isOverLevelCombat && !empty($monstersState)) ? $this->getActiveMonster() : $enemy;
                            $activeMonsterIdx = $isOverLevelCombat ? $this->getActiveMonsterIndex() : 0;
                        @endphp

                        @if(!empty($displayMonster))
                            {{-- Group Monster Stack Header (if in over-level multi-monster fight) --}}
                            @if ($isOverLevelCombat && !empty($monstersState))
                                <div class="mb-3 bg-slate-900/90 rounded-2xl p-2.5 border border-purple-500/40 shadow-inner">
                                    <div class="flex items-center justify-between mb-2 px-1">
                                        <span class="text-xs font-black text-purple-300 medieval-font flex items-center gap-1.5">
                                            <i class="fa-solid fa-layer-group text-amber-400"></i> Stos Przeciwników ({{ count($monstersState) }})
                                        </span>
                                        <span class="text-[10px] bg-purple-900/80 text-amber-200 px-2 py-0.5 rounded-full font-bold font-mono border border-purple-400/40">
                                            Aktywny: #{{ $activeMonsterIdx + 1 }} {{ $displayMonster['name'] }}
                                        </span>
                                    </div>

                                    {{-- Stack Chips --}}
                                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-1.5">
                                        @foreach($monstersState as $mIdx => $m)
                                            @php
                                                $mHpPct = max(0, min(100, (($m['hp'] ?? 0) / max(1, $m['maxHp'] ?? 1)) * 100));
                                                $isDead = (($m['hp'] ?? 0) <= 0);
                                                $isActive = ($mIdx === $activeMonsterIdx && !$isDead);
                                            @endphp
                                            <div class="relative bg-slate-950/80 rounded-xl p-1.5 border transition-all duration-300 {{ $isActive ? 'border-amber-400 ring-2 ring-amber-400/80 bg-amber-950/50 scale-[1.03] shadow-[0_0_15px_rgba(245,158,11,0.5)]' : ($isDead ? 'border-slate-800 opacity-40 grayscale' : 'border-red-900/40 hover:border-red-500/60') }}">
                                                <div class="flex items-center gap-1.5">
                                                    <div class="relative w-8 h-8 rounded-lg overflow-hidden border border-red-500/50 bg-slate-900 flex-shrink-0">
                                                        @if(!empty($m['avatar']))
                                                            <img src="{{ route('assets.monsters.avatars', ['filename' => $m['avatar']]) }}" class="w-full h-full object-cover">
                                                        @else
                                                            <img src="{{ asset('img/monsters/placeholder.png') }}" class="w-full h-full object-cover">
                                                        @endif
                                                        @if($isDead)
                                                            <div class="absolute inset-0 bg-black/80 flex items-center justify-center text-red-500 font-bold text-[10px]">❌</div>
                                                        @endif
                                                    </div>
                                                    <div class="flex-1 min-w-0">
                                                        <div class="text-[10px] font-bold text-red-200 truncate leading-tight">
                                                            {{ $m['name'] }}
                                                            @if(!empty($m['tier_label']))
                                                                <span class="ml-0.5 text-[8px] font-black text-amber-300 bg-amber-900/60 border border-amber-500/50 rounded px-1 py-0">{{ $m['tier_label'] }}</span>
                                                            @endif
                                                        </div>
                                                        <div class="text-[9px] text-amber-300/80 font-mono">Lvl {{ $m['level'] }}</div>
                                                        <x-combat-resource-bar :id="'monster-hp-bar-'.$mIdx" :percent="$mHpPct" :liquid="false"
                                                            gradient-class="from-red-600 to-rose-400" glow-shadow=""
                                                            ring-class="ring-red-700/30" height="h-1.5 mt-0.5"
                                                            droplet-color="rgba(220,38,38,0.92)" />
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            {{-- Featured Active Monster Main Card --}}
                            <div class="text-center">
                                <div class="relative w-20 h-20 sm:w-24 sm:h-24 lg:w-24 lg:h-24 xl:w-28 xl:h-28 2xl:w-32 2xl:h-32 mx-auto">
                                    <div class="w-full h-full rounded-2xl overflow-hidden ring-4 ring-red-600/80 shadow-[0_0_25px_rgba(239,68,68,0.35)] bg-slate-900">
                                        @if(!empty($displayMonster['avatar']))
                                            <img src="{{ route('assets.monsters.avatars', ['filename' => $displayMonster['avatar']]) }}"
                                                alt="{{ $displayMonster['name'] }}"
                                                class="w-full h-full object-cover">
                                        @else
                                            <img src="{{ asset('img/monsters/placeholder.png') }}"
                                                alt="{{ $displayMonster['name'] }}"
                                                class="w-full h-full object-cover">
                                        @endif
                                    </div>
                                    <span class="absolute -bottom-2.5 left-1/2 -translate-x-1/2 bg-gradient-to-r from-red-700 to-rose-600 text-red-100 text-xs sm:text-sm font-black px-3 py-0.5 rounded-full border border-red-400 shadow-lg medieval-font">
                                        Lvl {{ $displayMonster['level'] }}
                                    </span>
                                </div>

                                {{-- Enemy Name --}}
                                <h3 class="mt-2.5 lg:mt-3 text-base sm:text-lg lg:text-xl xl:text-2xl font-extrabold text-red-200 medieval-font drop-shadow-[0_2px_6px_rgba(0,0,0,0.9)] tracking-wide flex items-center justify-center gap-2 flex-wrap">
                                    {{ $displayMonster['name'] }}
                                    @php
                                        $activeTierLabel = $displayMonster['tier_label'] ?? $tierLabel;
                                    @endphp
                                    @if(!empty($activeTierLabel))
                                        <span class="inline-flex items-center text-xs font-black px-2 py-0.5 rounded-lg bg-gradient-to-r from-amber-600 to-yellow-500 text-slate-900 border border-amber-400 shadow-[0_0_10px_rgba(245,158,11,0.6)] drop-shadow-none" title="Tier {{ $activeTierLabel }} — statystyki potwora są wzmocnione">
                                            <i class="fa-solid fa-star-of-david text-[9px] mr-1"></i>{{ $activeTierLabel }}
                                        </span>
                                    @endif
                                </h3>
                                <p class="text-xs lg:text-sm text-red-400/80 tracking-wider">
                                    @php
                                        $enemyRankValue = $displayMonster['rank'] ?? null;
                                        $enemyRankLabel = match ($enemyRankValue) {
                                            'boss' => 'Boss Mapy',
                                            'worldboss' => 'World Boss',
                                            'elite' => 'Elita',
                                            default => 'Przeciwnik',
                                        };
                                    @endphp
                                    {{ $enemyRankLabel }}
                                </p>
                            </div>

                            {{-- Enemy HP Bar --}}
                            @php
                                $displayHp = ($isOverLevelCombat && !empty($monstersState))
                                    ? ($displayMonster['hp'] ?? 0)
                                    : $this->getCurrentEnemyHp();
                                $displayMaxHp = $displayMonster['maxHp'] ?? 1;
                                $displayHpPercent = max(0, min(100, ($displayHp / max(1, $displayMaxHp)) * 100));
                            @endphp
                            <div class="space-y-1.5">
                                {{-- Faza 3: pasek aktywnych efektów statusowych przeciwnika (DoT/CC nałożone przez gracza) --}}
                                @if (!($isOverLevelCombat && !empty($monstersState)))
                                    <x-combat-status-bar :effects="$this->getEnemyStatusEffects()" />
                                @endif
                                <div class="flex justify-between text-xs lg:text-sm font-bold text-red-200 medieval-font drop-shadow">
                                    <span>Życie Przeciwnika</span>
                                    <span class="font-mono text-red-300 text-sm lg:text-base" title="{{ number_format(max(0, $displayHp)) }}/{{ number_format($displayMaxHp) }}">{{ \App\Helpers\FormatHelper::short(max(0, $displayHp)) }}/{{ \App\Helpers\FormatHelper::short($displayMaxHp) }}</span>
                                </div>
                                <x-combat-resource-bar id="enemy-hp-bar" :percent="$displayHpPercent"
                                    gradient-class="from-red-700 via-red-500 to-rose-400"
                                    glow-shadow="shadow-[0_0_12px_rgba(239,68,68,0.6)]"
                                    ring-class="ring-red-700/50" height="h-4 sm:h-5"
                                    droplet-color="rgba(220,38,38,0.92)" />
                            </div>

                            {{-- Active DoTs --}}
                            @if(isset($currentState) && $currentState && !empty($currentState['dots']))
                                <div class="flex flex-wrap gap-2 justify-center">
                                    @foreach($currentState['dots'] as $dot)
                                        <div class="group relative bg-slate-900/90 border border-purple-400/70 hover:border-purple-300 rounded-xl px-2.5 py-1 text-xs text-purple-200 flex items-center gap-1.5 shadow-lg cursor-pointer transition-all hover:scale-105">
                                            @if(!empty($dot['icon']))
                                                <img src="{{ route('assets.skills.icons', ['filename' => $dot['icon']]) }}" class="w-4 h-4 object-contain" alt="{{ $dot['name'] ?? 'Status' }}">
                                            @elseif(($dot['type'] ?? '') === 'poison')
                                                <span class="text-xs">🐍</span>
                                            @elseif(($dot['type'] ?? '') === 'fire')
                                                <span class="text-xs">🔥</span>
                                            @else
                                                <span class="text-xs">✨</span>
                                            @endif
                                            <span class="font-bold font-mono text-purple-300 text-xs">{{ $dot['duration'] }}T</span>

                                            {{-- Interactive Hover Infobox --}}
                                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-52 p-3 bg-slate-950/95 border border-purple-400/80 rounded-xl shadow-[0_0_20px_rgba(168,85,247,0.35)] backdrop-blur-md opacity-0 group-hover:opacity-100 pointer-events-none transition-all duration-200 z-50 text-left">
                                                <div class="flex items-center gap-2 mb-1.5 border-b border-purple-500/30 pb-1.5">
                                                    @if(!empty($dot['icon']))
                                                        <img src="{{ route('assets.skills.icons', ['filename' => $dot['icon']]) }}" class="w-5 h-5 object-contain" alt="">
                                                    @elseif(($dot['type'] ?? '') === 'poison')
                                                        <span class="text-sm">🐍</span>
                                                    @elseif(($dot['type'] ?? '') === 'fire')
                                                        <span class="text-sm">🔥</span>
                                                    @else
                                                        <span class="text-sm">✨</span>
                                                    @endif
                                                    <span class="text-xs font-bold text-purple-200 medieval-font tracking-wide">{{ $dot['name'] ?? (($dot['type'] ?? '') === 'poison' ? 'Otrucie' : 'Ogień') }}</span>
                                                    <span class="ml-auto text-[10px] font-bold px-2 py-0.5 rounded-full bg-purple-900/90 text-purple-200 border border-purple-400/50 font-mono">{{ $dot['duration'] }}T</span>
                                                </div>
                                                <div class="text-[11px] text-slate-300 leading-snug space-y-1">
                                                    <div class="text-purple-300/90 font-semibold">Status: Osłabienie / DoT</div>
                                                    <p class="text-slate-200">{{ $dot['description'] ?? ((($dot['type'] ?? '') === 'poison') ? 'Zadaje obrażenia od otrucia co turę.' : 'Zadaje obrażenia od ognia co turę.') }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            {{-- Enemy Stats Grid --}}
                            <div>
                                <h4 class="text-xs lg:text-sm font-bold text-red-200/90 mb-2 medieval-font tracking-wide">
                                    Statystyki Bestii
                                </h4>
                                <div class="grid grid-cols-2 gap-2 lg:gap-2.5">
                                    <div class="bg-slate-900/90 border border-red-800/40 rounded-2xl p-2 lg:p-2.5 xl:p-3 text-center shadow-md">
                                        <div class="text-[11px] sm:text-xs font-semibold text-red-300 tracking-wider">ATK (Atak)</div>
                                        <div class="text-sm sm:text-base lg:text-lg xl:text-xl font-black text-red-100 font-mono">{{ \App\Helpers\FormatHelper::short($displayMonster['stats']['atk'] ?? 0) }}</div>
                                    </div>
                                    <div class="bg-slate-900/90 border border-slate-700/50 rounded-2xl p-2 lg:p-2.5 xl:p-3 text-center shadow-md">
                                        <div class="text-[11px] sm:text-xs font-semibold text-slate-300 tracking-wider">DEF (Obrona)</div>
                                        <div class="text-sm sm:text-base lg:text-lg xl:text-xl font-black text-slate-100 font-mono">{{ \App\Helpers\FormatHelper::short($displayMonster['stats']['def'] ?? 0) }}</div>
                                    </div>
                                    <div class="bg-slate-900/90 border border-amber-800/40 rounded-2xl p-2 lg:p-2.5 xl:p-3 text-center shadow-md">
                                        <div class="text-[11px] sm:text-xs font-semibold text-amber-300 tracking-wider">AGI (Zręczność)</div>
                                        <div class="text-sm sm:text-base lg:text-lg xl:text-xl font-black text-amber-100 font-mono">{{ \App\Helpers\FormatHelper::short($displayMonster['stats']['agi'] ?? 0) }}</div>
                                    </div>
                                </div>

                                {{-- Enemy Combat Stats --}}
                                @php
                                    $enemyCombatStats = $this->getEnemyCombatStats();
                                @endphp
                                <div class="mt-2.5 pt-2 border-t border-red-900/40">
                                    <div class="grid grid-cols-2 gap-1.5 lg:gap-2">
                                        <div class="bg-red-950/60 border border-yellow-600/40 rounded-xl p-1.5 text-center shadow-md">
                                            <div class="text-[9px] sm:text-[10px] font-semibold text-yellow-400 tracking-wider flex items-center justify-center gap-1">
                                                <i class="fa-solid fa-bolt text-yellow-400"></i> Krytyk Bestii
                                            </div>
                                            <div class="text-xs sm:text-sm font-black text-yellow-300 font-mono">{{ $enemyCombatStats['crit_chance'] ?? 0 }}%</div>
                                        </div>
                                        <div class="bg-red-950/60 border border-emerald-600/40 rounded-xl p-1.5 text-center shadow-md">
                                            <div class="text-[9px] sm:text-[10px] font-semibold text-emerald-400 tracking-wider flex items-center justify-center gap-1">
                                                <i class="fa-solid fa-shield-halved text-emerald-400"></i> Unik Bestii
                                            </div>
                                            <div class="text-xs sm:text-sm font-black text-emerald-300 font-mono">{{ $enemyCombatStats['dodge_chance'] ?? 0 }}%</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            {{-- Pre-combat Mysterious Encounter Altar --}}
                            <div class="text-center py-4 lg:py-5 space-y-2.5 lg:space-y-3">
                                <div class="space-y-1">
                                    <h3 class="text-sm sm:text-base lg:text-lg font-bold text-amber-200 medieval-font tracking-wide">
                                        WYZWANIE MAPY
                                    </h3>
                                    <p class="text-xs text-purple-200/85 max-w-[240px] mx-auto leading-relaxed">
                                        Eksploruj obszar <span class="text-amber-300 font-bold">{{ $map->name }}</span> i zmierz się z panoszącymi się tutaj potworami!
                                    </p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700;900&display=swap');

        .medieval-font {
            font-family: 'Cinzel', serif;
        }

        /* Target Hit Bounce & Impact Pop */
        @keyframes targetHitBounce {
            0% {
                transform: scale(1) translate(0, 0);
                filter: brightness(1);
            }
            15% {
                transform: scale(1.18) translate(-8px, -12px) rotate(-5deg);
                filter: brightness(2) drop-shadow(0 0 25px rgba(239,68,68,0.95)) sepia(1) hue-rotate(-50deg);
            }
            35% {
                transform: scale(1.12) translate(10px, 8px) rotate(4deg);
                filter: brightness(1.6) drop-shadow(0 0 20px rgba(239,68,68,0.8));
            }
            60% {
                transform: scale(1.04) translate(-4px, -2px) rotate(-2deg);
                filter: brightness(1.2);
            }
            100% {
                transform: scale(1) translate(0, 0);
                filter: brightness(1);
            }
        }

        /* Attacker Lunge Steps */
        @keyframes attackerLungeRight {
            0% { transform: translateX(0) scale(1); }
            35% { transform: translateX(45px) scale(1.06) rotate(2deg); }
            100% { transform: translateX(0) scale(1); }
        }

        @keyframes attackerLungeLeft {
            0% { transform: translateX(0) scale(1); }
            35% { transform: translateX(-45px) scale(1.06) rotate(-2deg); }
            100% { transform: translateX(0) scale(1); }
        }

        /* Floating Damage Text Popup */
        @keyframes floatDamageUp {
            0% {
                opacity: 0;
                transform: translate(-50%, 0) scale(0.4) rotate(-5deg);
            }
            20% {
                opacity: 1;
                transform: translate(-50%, -30px) scale(1.3) rotate(0deg);
            }
            70% {
                opacity: 1;
                transform: translate(-50%, -65px) scale(1.05);
            }
            100% {
                opacity: 0;
                transform: translate(-50%, -95px) scale(0.85);
            }
        }

        .anim-hit-bounce {
            animation: targetHitBounce 450ms cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards !important;
            z-index: 60 !important;
        }

        .anim-lunge-player {
            animation: attackerLungeRight 350ms ease-out forwards;
            z-index: 50;
        }

        .anim-lunge-enemy {
            animation: attackerLungeLeft 350ms ease-out forwards;
            z-index: 50;
        }

        .fct-damage-number {
            position: absolute;
            pointer-events: none;
            z-index: 200;
            animation: floatDamageUp 900ms cubic-bezier(0.165, 0.84, 0.44, 1) forwards;
            font-family: 'Cinzel', serif;
            text-shadow: 0 4px 12px rgba(0,0,0,0.95), 0 0 15px rgba(0,0,0,0.9);
            white-space: nowrap;
        }

        /* Keyframes dla Magicznej Cieczy w Pasku XP */
        @keyframes xpLiquidFlow {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        @keyframes xpWaveMove1 {
            0% { transform: translateX(0) scaleY(1); }
            50% { transform: translateX(-18px) scaleY(1.15); }
            100% { transform: translateX(0) scaleY(1); }
        }
        @keyframes xpWaveMove2 {
            0% { transform: translateX(0) scaleY(1.1); }
            50% { transform: translateX(18px) scaleY(0.9); }
            100% { transform: translateX(0) scaleY(1.1); }
        }
        @keyframes xpSparkleFloat {
            0% { background-position: 0 0; }
            100% { background-position: 120px -40px; }
        }

        .xp-liquid-fill {
            background: linear-gradient(90deg, #4338ca, #6366f1, #7c3aed, #a855f7, #6366f1, #4338ca);
            background-size: 250% 100%;
            animation: xpLiquidFlow 6s ease-in-out infinite;
        }
        .xp-wave-1 {
            background: radial-gradient(ellipse at 50% -20%, rgba(255, 255, 255, 0.45) 0%, transparent 65%);
            animation: xpWaveMove1 3.5s ease-in-out infinite;
        }
        .xp-wave-2 {
            background: radial-gradient(ellipse at 30% 120%, rgba(168, 85, 247, 0.6) 0%, transparent 70%);
            animation: xpWaveMove2 4.5s ease-in-out infinite;
        }
        .xp-liquid-sparkles {
            background-image: radial-gradient(rgba(255, 255, 255, 0.75) 1.2px, transparent 0);
            background-size: 18px 18px;
            animation: xpSparkleFloat 7s linear infinite;
        }
    </style>
    {{-- HP/Mana bar chip-damage + liquid + shake/splash CSS & JS now live in the shared
         <x-combat-resource-bar> component (@once-injected on its first use above). --}}

    <script>
        document.addEventListener('livewire:navigated', () => {
            initMapStubComponent();
        });

        document.addEventListener('livewire:init', () => {
            initMapStubComponent();
        });

        let mapStubUserScrolledUp = false;

        function scrollCombatLogToBottom(force = false) {
            const container = document.getElementById('combat-log-container');
            if (!container) return;

            if (force) {
                mapStubUserScrolledUp = false;
            }

            const distanceFromBottom = container.scrollHeight - container.scrollTop - container.clientHeight;
            if (force || !mapStubUserScrolledUp || distanceFromBottom < 150) {
                container.scrollTop = container.scrollHeight;
            }
        }

        // Rebinds the log auto-scroll on every mount/navigation. Livewire can swap out
        // #combat-log-container for a fresh DOM node, which would leave a previously
        // attached MutationObserver watching an already-detached element.
        function bindCombatLogAutoScroll() {
            const logContainer = document.getElementById('combat-log-container');
            if (!logContainer) return;

            if (window._combatLogObserver) {
                window._combatLogObserver.disconnect();
            }
            window._combatLogObserver = new MutationObserver(() => {
                scrollCombatLogToBottom();
            });
            window._combatLogObserver.observe(logContainer, { childList: true, subtree: true });

            if (!logContainer.dataset.autoscrollBound) {
                logContainer.dataset.autoscrollBound = '1';
                logContainer.addEventListener('scroll', () => {
                    const distanceFromBottom = logContainer.scrollHeight - logContainer.scrollTop - logContainer.clientHeight;
                    mapStubUserScrolledUp = distanceFromBottom > 150;
                }, { passive: true });
            }

            scrollCombatLogToBottom(true);
        }

        function initMapStubComponent() {
            bindCombatLogAutoScroll();

            if (window._mapStubListenersBound) return;
            window._mapStubListenersBound = true;

            // Licznik zabójstw i czas farmienia w tej sesji (widoczne po najechaniu na
            // zakładkę przeglądarki - dokument.title jest tym, co pokazuje natywny tooltip
            // karty, gdy tytuł jest przycięty). Liczy zwycięskie starcia (result === 'win'/
            // 'finished'), więc przy walkach grupowych/over-level (kilka potworów na jedno
            // starcie) jest to przybliżenie liczby starć, nie ścisła liczba zabitych sztuk.
            let farmSessionStartedAt = Date.now();
            let farmSessionKills = 0;
            const originalDocumentTitle = document.title;
            let farmTitleInterval = null;

            function formatFarmDuration(ms) {
                const totalSeconds = Math.floor(ms / 1000);
                const h = Math.floor(totalSeconds / 3600);
                const m = Math.floor((totalSeconds % 3600) / 60);
                const s = totalSeconds % 60;
                const pad = (n) => String(n).padStart(2, '0');
                return h > 0 ? `${h}:${pad(m)}:${pad(s)}` : `${pad(m)}:${pad(s)}`;
            }

            function updateFarmSessionTitle() {
                const elapsed = formatFarmDuration(Date.now() - farmSessionStartedAt);
                document.title = `⚔️ ${farmSessionKills} • ${elapsed} - ${originalDocumentTitle}`;
            }

            function startFarmTitleInterval() {
                if (farmTitleInterval) clearInterval(farmTitleInterval);
                updateFarmSessionTitle();
                farmTitleInterval = setInterval(updateFarmSessionTitle, 1000);
            }

            function stopFarmTitleInterval() {
                if (farmTitleInterval) {
                    clearInterval(farmTitleInterval);
                    farmTitleInterval = null;
                }
                // Przywróć oryginalny tytuł strony po opuszczeniu mapy
                document.title = originalDocumentTitle;
            }

            startFarmTitleInterval();

            // Czyść tytuł przy opuszczeniu strony (zamknięcie karty / przeładowanie)
            window.addEventListener('beforeunload', stopFarmTitleInterval);

            // Czyść tytuł przy nawigacji Livewire (przejście do innej sekcji bez reload)
            document.addEventListener('livewire:navigate', stopFarmTitleInterval);

            Livewire.on('play-audio', (event) => {
                const payload = (event && event[0]) ? event[0] : event;
                if (payload && payload.type === 'victory') {
                    farmSessionKills++;
                    updateFarmSessionTitle();
                }
            });

            // Create inline Web Worker Blob for un-throttled background timers
            let bgWorker = null;
            try {
                const workerBlob = new Blob([`
                    let timers = {};
                    onmessage = function(e) {
                        const { id, action, delay } = e.data;
                        if (action === 'start') {
                            if (timers[id]) clearTimeout(timers[id]);
                            timers[id] = setTimeout(() => {
                                postMessage({ id, type: 'tick' });
                                delete timers[id];
                            }, delay);
                        } else if (action === 'cancel') {
                            if (timers[id]) clearTimeout(timers[id]);
                            delete timers[id];
                        }
                    };
                `], { type: 'application/javascript' });
                bgWorker = new Worker(URL.createObjectURL(workerBlob));
            } catch (e) {
                console.warn('Web Worker not supported, falling back to setTimeout', e);
            }

            let bgTimerCallbacks = {};

            if (bgWorker) {
                bgWorker.onmessage = function(e) {
                    const { id } = e.data;
                    if (bgTimerCallbacks[id]) {
                        const cb = bgTimerCallbacks[id];
                        delete bgTimerCallbacks[id];
                        cb();
                    }
                };
            }

            function setUnthrottledTimeout(callback, delayMs, timerIdName) {
                if (bgWorker) {
                    if (bgTimerCallbacks[timerIdName]) {
                        bgWorker.postMessage({ id: timerIdName, action: 'cancel' });
                    }
                    bgTimerCallbacks[timerIdName] = callback;
                    bgWorker.postMessage({ id: timerIdName, action: 'start', delay: delayMs });
                    return timerIdName;
                } else {
                    return setTimeout(callback, delayMs);
                }
            }

            function clearUnthrottledTimeout(timerIdName, fallbackTimerRef) {
                if (bgWorker && typeof timerIdName === 'string') {
                    if (bgTimerCallbacks[timerIdName]) {
                        delete bgTimerCallbacks[timerIdName];
                    }
                    bgWorker.postMessage({ id: timerIdName, action: 'cancel' });
                } else if (fallbackTimerRef) {
                    clearTimeout(fallbackTimerRef);
                }
            }

            let turnTimer = null;
            let autoChainTimeout = null;
            let autoChainWatchdog = null;
            let watchdogTimer = null;
            let isExecutingTurn = false;
            let isPaused = false;
            let currentSpeed = {{ $playbackSpeed }};

            function cleanUp() {
                clearUnthrottledTimeout('turnTimer', turnTimer);
                clearUnthrottledTimeout('autoChainTimeout', autoChainTimeout);
                clearUnthrottledTimeout('turnAnimTimer', null); // timer animacji tury (turn-played)
                if (watchdogTimer) clearTimeout(watchdogTimer);
                if (autoChainWatchdog) { clearTimeout(autoChainWatchdog); autoChainWatchdog = null; }
                turnTimer = null;
                autoChainTimeout = null;
                watchdogTimer = null;
                isExecutingTurn = false;
            }

            window.addEventListener('beforeunload', cleanUp);

            document.addEventListener('visibilitychange', () => {
                if (!document.hidden && !isPaused) {
                    // Po powrocie do karty resetuj blokadę isExecutingTurn - throttlowane
                    // timery z turn-played mogły nie odpalić gdy karta była ukryta,
                    // co powodowało deadlock (isExecutingTurn=true na zawsze).
                    isExecutingTurn = false;
                    scheduleNextTurn(50);
                }
            });

            function getComponent() {
                const el = document.getElementById('adventure-map-component');
                return el ? Livewire.find(el.getAttribute('wire:id')) : null;
            }

            function triggerNextTurn() {
                if (isPaused) return;

                if (isExecutingTurn) return;

                const component = getComponent();
                if (component) {
                    isExecutingTurn = true;
                    if (watchdogTimer) clearTimeout(watchdogTimer);
                    watchdogTimer = setTimeout(() => {
                        isExecutingTurn = false;
                    }, 2500);
                    component.call('nextTurn');
                }
            }

            function scheduleNextTurn(delayMs) {
                if (isPaused) return;
                clearUnthrottledTimeout('turnTimer', turnTimer);

                turnTimer = setUnthrottledTimeout(() => {
                    triggerNextTurn();
                }, delayMs, 'turnTimer');
            }

            window.setCombatSpeed = function(s) {
                currentSpeed = s;
                const component = getComponent();
                if (component) component.call('setPlaybackSpeed', s);
            };

            window.toggleCombatPlayback = function(pausedState) {
                if (typeof pausedState === 'boolean') {
                    isPaused = pausedState;
                } else {
                    isPaused = !isPaused;
                }

                if (isPaused) {
                    cleanUp();
                } else {
                    scheduleNextTurn(50);
                }

                const component = getComponent();
                if (component) component.call('togglePlayback');
            };

            window.toggleCombatAuto = function(active) {
                const component = getComponent();
                if (component) component.call('toggleAutoChain', active);
            };

            Livewire.on('start-playback', (event) => {
                cleanUp();
                isPaused = false;
                mapStubUserScrolledUp = false;
                let evtSpeed = (event && event[0] && event[0].speed) ? event[0].speed : (event && event.speed ? event.speed : null);
                if (evtSpeed) {
                    currentSpeed = evtSpeed;
                }

                setTimeout(() => scrollCombatLogToBottom(true), 10);
                setTimeout(() => scrollCombatLogToBottom(true), 50);
                setTimeout(() => scrollCombatLogToBottom(true), 150);

                const startDelay = currentSpeed === 5 ? 30 : (currentSpeed === 2 ? 100 : 200);
                scheduleNextTurn(startDelay);
            });

            Livewire.on('stop-playback', () => {
                cleanUp();
            });

            Livewire.on('update-playback-speed', (event) => {
                currentSpeed = (event && event[0] && event[0].speed) ? event[0].speed : (event && event.speed ? event.speed : 1);
            });

            function spawnSelfParticles(targetPanel, particleType) {
                const rect = targetPanel.getBoundingClientRect();
                const fxOverlay = document.getElementById('combat-fx-overlay');
                if (!fxOverlay) return;

                // Glowing Aura Ring around avatar
                const aura = document.createElement('div');
                aura.className = 'fixed pointer-events-none z-[190] rounded-full border-2 transition-all duration-700 animate-ping';
                const size = Math.min(rect.width, rect.height) * 0.75;
                aura.style.width = `${size}px`;
                aura.style.height = `${size}px`;
                aura.style.left = `${rect.left + rect.width / 2 - size / 2}px`;
                aura.style.top = `${rect.top + rect.height / 3 - size / 2}px`;
                aura.style.borderColor = particleType === 'buff' ? 'rgba(52, 211, 153, 0.9)' : 'rgba(245, 158, 11, 0.9)';
                aura.style.boxShadow = particleType === 'buff' ? '0 0 35px rgba(52, 211, 153, 0.85)' : '0 0 35px rgba(245, 158, 11, 0.85)';
                fxOverlay.appendChild(aura);

                setTimeout(() => { if (aura.parentNode) aura.parentNode.removeChild(aura); }, 650);

                // Rising Sparkle Particles over Caster Avatar
                for (let i = 0; i < 12; i++) {
                    const p = document.createElement('div');
                    p.className = 'fixed pointer-events-none z-[195] rounded-full';
                    const pSize = Math.floor(Math.random() * 8) + 6;
                    p.style.width = `${pSize}px`;
                    p.style.height = `${pSize}px`;
                    const startX = rect.left + rect.width / 2 + (Math.random() * 80 - 40);
                    const startY = rect.top + rect.height / 2 + (Math.random() * 40 - 20);
                    p.style.left = `${startX}px`;
                    p.style.top = `${startY}px`;
                    p.style.backgroundColor = particleType === 'buff' ? '#34d399' : '#fbbf24';
                    p.style.boxShadow = `0 0 12px ${particleType === 'buff' ? '#10b981' : '#f59e0b'}`;
                    fxOverlay.appendChild(p);

                    p.animate([
                        { transform: 'translateY(0) scale(1)', opacity: 1 },
                        { transform: `translateY(-${Math.floor(Math.random() * 70 + 40)}px) translateX(${Math.random() * 40 - 20}px) scale(0)`, opacity: 0 }
                    ], {
                        duration: 750 + Math.random() * 300,
                        easing: 'ease-out',
                        fill: 'forwards'
                    });

                    setTimeout(() => { if (p.parentNode) p.parentNode.removeChild(p); }, 1000);
                }
            }

            function spawnImpactParticles(targetPanel, pType) {
                const rect = targetPanel.getBoundingClientRect();
                const fxOverlay = document.getElementById('combat-fx-overlay');
                if (!fxOverlay) return;

                const centerX = rect.left + rect.width / 2;
                const centerY = rect.top + rect.height / 3;

                const particleCount = pType === 'crit' ? 16 : 10;
                let color = '#f87171';
                let shadowColor = 'rgba(239, 68, 68, 0.8)';

                if (pType === 'poison') {
                    color = '#34d399';
                    shadowColor = 'rgba(52, 211, 153, 0.9)';
                } else if (pType === 'fire') {
                    color = '#f97316';
                    shadowColor = 'rgba(249, 115, 22, 0.9)';
                } else if (pType === 'skill' || pType === 'crit') {
                    color = '#fbbf24';
                    shadowColor = 'rgba(245, 158, 11, 0.9)';
                }

                for (let i = 0; i < particleCount; i++) {
                    const p = document.createElement('div');
                    p.className = 'fixed pointer-events-none z-[195] rounded-full';
                    const size = Math.floor(Math.random() * 10) + 6;
                    p.style.width = `${size}px`;
                    p.style.height = `${size}px`;
                    p.style.left = `${centerX}px`;
                    p.style.top = `${centerY}px`;
                    p.style.backgroundColor = color;
                    p.style.boxShadow = `0 0 12px ${shadowColor}`;
                    fxOverlay.appendChild(p);

                    const angle = (i / particleCount) * Math.PI * 2 + (Math.random() * 0.4 - 0.2);
                    const distance = Math.floor(Math.random() * 75) + 30;
                    const targetX = Math.cos(angle) * distance;
                    const targetY = Math.sin(angle) * distance;

                    p.animate([
                        { transform: 'translate(-50%, -50%) scale(1.2)', opacity: 1 },
                        { transform: `translate(calc(${targetX}px - 50%), calc(${targetY}px - 50%)) scale(0)`, opacity: 0 }
                    ], {
                        duration: 450 + Math.random() * 250,
                        easing: 'cubic-bezier(0.1, 0.8, 0.3, 1)',
                        fill: 'forwards'
                    });

                    setTimeout(() => { if (p.parentNode) p.parentNode.removeChild(p); }, 750);
                }
            }

            function formatShortNum(num) {
                if (num === null || num === undefined) return '0';
                const abs = Math.abs(num);
                const sign = num < 0 ? '-' : '';
                if (abs >= 1000000000) {
                    let val = (abs / 1000000000).toFixed(1);
                    return sign + (val.endsWith('.0') ? val.slice(0, -2) : val) + 'B';
                }
                if (abs >= 1000000) {
                    let val = (abs / 1000000).toFixed(1);
                    return sign + (val.endsWith('.0') ? val.slice(0, -2) : val) + 'M';
                }
                if (abs >= 1000) {
                    let val = (abs / 1000).toFixed(1);
                    return sign + (val.endsWith('.0') ? val.slice(0, -2) : val) + 'k';
                }
                return sign + Math.floor(abs);
            }

            Livewire.on('turn-played', (event) => {
                isExecutingTurn = true;

                const data = (event && event[0]) ? event[0] : event;
                const actor = data.actor;
                const type = data.type;
                const effectType = data.effectType || null;
                const value = data.value || 0;
                const isCrit = data.crit || false;
                const skillName = data.skillName || null;
                const audioType = data.audioType || (type === 'miss' ? 'dodge' : (isCrit ? 'crit' : 'hit'));
                
                const playerPanel = document.getElementById('player-panel-container');
                const enemyPanel = document.getElementById('enemy-panel-container');
                const fxOverlay = document.getElementById('combat-fx-overlay');
                
                if (!playerPanel || !enemyPanel || !fxOverlay) {
                    isExecutingTurn = false;
                    return;
                }

                const attackerPanel = actor === 'player' ? playerPanel : enemyPanel;
                const defenderPanel = actor === 'player' ? enemyPanel : playerPanel;

                // Remove existing animation classes to re-trigger
                playerPanel.classList.remove('anim-lunge-player', 'anim-lunge-enemy', 'anim-hit-bounce');
                enemyPanel.classList.remove('anim-lunge-player', 'anim-lunge-enemy', 'anim-hit-bounce');
                
                // Force reflow
                void playerPanel.offsetWidth;
                void enemyPanel.offsetWidth;

                const isHeal = type === 'skill_heal' || effectType === 'heal';
                const isBuff = isHeal || effectType === 'buff_phys_dmg' || (type === 'skill' && value === 0);
                const isPoison = effectType === 'poison' || (skillName && skillName.toLowerCase().includes('truj'));
                const isFire = effectType === 'fire' || (skillName && skillName.toLowerCase().includes('ogien'));

                // 1. Buff / Self Enhancement / Heal FX: Particles rise over Caster, Caster Glows!
                if (isBuff) {
                    spawnSelfParticles(attackerPanel, 'buff');
                    Livewire.dispatch('play-audio', { type: 'tab' });

                    const attackerRect = attackerPanel.getBoundingClientRect();
                    const fct = document.createElement('div');
                    fct.className = 'fct-damage-number';
                    fct.style.left = `${attackerRect.left + attackerRect.width / 2}px`;
                    fct.style.top = `${attackerRect.top + attackerRect.height / 3 - 20}px`;
                    if (isHeal) {
                        fct.innerHTML = `<span class="text-emerald-300 font-extrabold text-2xl sm:text-3xl drop-shadow-[0_0_20px_rgba(52,211,153,1)]">+${formatShortNum(value)} HP</span>`;
                    } else {
                        fct.innerHTML = `<span class="text-emerald-300 font-extrabold text-2xl sm:text-3xl drop-shadow-[0_0_20px_rgba(52,211,153,1)]">WZMOCNIENIE! ${skillName ? skillName : ''}</span>`;
                    }
                    fxOverlay.appendChild(fct);

                    setTimeout(() => { if (fct.parentNode) fct.parentNode.removeChild(fct); }, 850);

                    setUnthrottledTimeout(() => {
                        isExecutingTurn = false;
                        if (watchdogTimer) clearTimeout(watchdogTimer);
                        if (!isPaused) {
                            const basePause = currentSpeed === 5 ? 60 : (currentSpeed === 2 ? 200 : 550);
                            scheduleNextTurn(basePause);
                        }
                    }, currentSpeed === 5 ? 120 : 500, 'turnAnimTimer');
                    return;
                }

                // 2. Attacker Lunge Motion
                if (actor === 'player') {
                    playerPanel.classList.add('anim-lunge-player');
                } else {
                    enemyPanel.classList.add('anim-lunge-enemy');
                }

                // 3. Trajectory Calculation for SVG Attack Wave
                const attackerRect = attackerPanel.getBoundingClientRect();
                const defenderRect = defenderPanel.getBoundingClientRect();

                const startX = attackerRect.left + attackerRect.width / 2;
                const startY = attackerRect.top + attackerRect.height / 3;

                const endX = defenderRect.left + defenderRect.width / 2;
                const endY = defenderRect.top + defenderRect.height / 3;

                // 4. Spawn SVG Attack Wave Energy Blade depending on skill type
                const waveEl = document.createElement('div');
                waveEl.className = 'fixed pointer-events-none z-[180] transition-all';
                waveEl.style.left = `${startX}px`;
                waveEl.style.top = `${startY}px`;

                let svgIcon = '';
                if (type === 'miss') {
                    svgIcon = `<svg class="w-14 h-14 text-slate-400 opacity-60" viewBox="0 0 100 100"><path d="M 20 20 L 80 80 M 80 20 L 20 80" stroke="currentColor" stroke-width="12" stroke-linecap="round"/></svg>`;
                } else if (isPoison) {
                    svgIcon = `<svg class="w-20 h-20 text-emerald-400 drop-shadow-[0_0_25px_rgba(52,211,153,1)]" viewBox="0 0 100 100"><path d="M 10 50 Q 50 10 90 50 Q 50 90 10 50 Z" fill="url(#poisonGrad)"/><defs><linearGradient id="poisonGrad" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="#a7f3d0"/><stop offset="100%" stop-color="#047857"/></linearGradient></defs></svg>`;
                } else if (isFire) {
                    svgIcon = `<svg class="w-20 h-20 text-orange-400 drop-shadow-[0_0_25px_rgba(249,115,22,1)]" viewBox="0 0 100 100"><path d="M 20 10 Q 90 50 20 90 Q 50 50 20 10 Z" fill="url(#fireGrad)"/><defs><linearGradient id="fireGrad" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="#fef08a"/><stop offset="100%" stop-color="#c2410c"/></linearGradient></defs></svg>`;
                } else if (type === 'skill') {
                    svgIcon = `<svg class="w-24 h-24 text-amber-300 drop-shadow-[0_0_30px_rgba(245,158,11,1)]" viewBox="0 0 100 100"><circle cx="50" cy="50" r="38" stroke="currentColor" stroke-width="6" fill="none" opacity="0.6"/><path d="M 10 50 Q 50 10 90 50 Q 50 90 10 50 Z" fill="url(#goldGrad)"/><defs><linearGradient id="goldGrad" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="#fef08a"/><stop offset="100%" stop-color="#b45309"/></linearGradient></defs></svg>`;
                } else if (actor === 'player') {
                    if (isCrit) {
                        svgIcon = `<svg class="w-24 h-24 text-amber-300 drop-shadow-[0_0_25px_rgba(245,158,11,1)]" viewBox="0 0 100 100"><path d="M 10 50 Q 50 10 90 50 Q 50 90 10 50 Z" fill="url(#goldGrad)"/><defs><linearGradient id="goldGrad" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="#fef08a"/><stop offset="100%" stop-color="#b45309"/></linearGradient></defs></svg>`;
                    } else {
                        svgIcon = `<svg class="w-16 h-16 text-amber-400 drop-shadow-[0_0_15px_rgba(234,179,8,0.9)]" viewBox="0 0 100 100"><path d="M 20 10 Q 90 50 20 90 Q 50 50 20 10 Z" fill="currentColor"/></svg>`;
                    }
                } else {
                    if (isCrit) {
                        svgIcon = `<svg class="w-24 h-24 text-purple-400 drop-shadow-[0_0_25px_rgba(168,85,247,1)]" viewBox="0 0 100 100"><path d="M 80 10 Q 10 50 80 90 Q 50 50 80 10 Z" fill="url(#crimsonGrad)"/><defs><linearGradient id="crimsonGrad" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="#f43f5e"/><stop offset="100%" stop-color="#581c87"/></linearGradient></defs></svg>`;
                    } else {
                        svgIcon = `<svg class="w-16 h-16 text-red-500 drop-shadow-[0_0_15px_rgba(239,68,68,0.9)]" viewBox="0 0 100 100"><path d="M 80 10 Q 10 50 80 90 Q 50 50 80 10 Z" fill="currentColor"/></svg>`;
                    }
                }

                waveEl.innerHTML = svgIcon;
                fxOverlay.appendChild(waveEl);

                const deltaX = endX - startX;
                const deltaY = endY - startY;

                waveEl.animate([
                    { transform: `translate(-50%, -50%) scale(0.6) rotate(${actor === 'player' ? -15 : 15}deg)`, opacity: 1 },
                    { transform: `translate(calc(${deltaX * 0.5}px - 50%), calc(${deltaY * 0.5 - 20}px - 50%)) scale(1.3) rotate(0deg)`, opacity: 1 },
                    { transform: `translate(calc(${deltaX}px - 50%), calc(${deltaY}px - 50%)) scale(1.6) rotate(${actor === 'player' ? 25 : -25}deg)`, opacity: 0.2 }
                ], {
                    duration: 200,
                    easing: 'cubic-bezier(0.25, 0.46, 0.45, 0.94)',
                    fill: 'forwards'
                });

                setTimeout(() => {
                    if (waveEl.parentNode) waveEl.parentNode.removeChild(waveEl);
                }, 220);

                const dotDamage = data.dotDamage || 0;
                const dotType = data.dotType || null;

                // 5. AT IMPACT MOMENT (~170ms): Trigger Defender Hit Bounce, Audio, Particles & Floating Damage Text!
                setTimeout(() => {
                    if (type !== 'miss' || dotDamage > 0) {
                        defenderPanel.classList.add('anim-hit-bounce');
                        if (dotDamage > 0) {
                            spawnImpactParticles(defenderPanel, 'poison');
                        }
                        if (type !== 'miss') {
                            spawnImpactParticles(defenderPanel, isPoison ? 'poison' : (isFire ? 'fire' : (isCrit ? 'crit' : (type === 'skill' ? 'skill' : 'hit'))));
                        }

                        // Punch the HP bar itself so a landed hit feels tied to the exact bar losing HP
                        const defenderHpBarId = actor === 'player' ? 'enemy-hp-bar' : 'player-hp-bar';
                        if (type !== 'miss' && window.CombatBarFX) {
                            window.CombatBarFX.hit(defenderHpBarId);
                        }
                    }

                    // Skill cast: the caster's own mana bar took the hit, not the defender's HP bar
                    if (actor === 'player' && type === 'skill' && window.CombatBarFX) {
                        window.CombatBarFX.hit('player-mana-bar');
                    }

                    // Play Audio EXACTLY at impact!
                    Livewire.dispatch('play-audio', { type: audioType });

                    // Spawn Floating Damage Text (FCT) over Target Avatar
                    const fct = document.createElement('div');
                    fct.className = 'fct-damage-number';
                    fct.style.left = `${endX}px`;
                    fct.style.top = `${endY - 20}px`;

                    const dotBadge = dotDamage > 0 
                        ? `<span class="text-emerald-300 font-black text-2xl sm:text-3xl drop-shadow-[0_0_20px_rgba(52,211,153,1)] font-sans ml-1.5">(+${formatShortNum(dotDamage)})</span>`
                        : '';

                    if (type === 'miss') {
                        if (dotDamage > 0) {
                            fct.innerHTML = `<span class="text-blue-300 font-black text-2xl drop-shadow-[0_2px_10px_rgba(0,0,0,0.9)]">UNIK</span> ${dotBadge}`;
                        } else {
                            fct.innerHTML = `<span class="text-blue-300 font-black text-2xl drop-shadow-[0_2px_10px_rgba(0,0,0,0.9)]">UNIK!</span>`;
                        }
                    } else if (type === 'dot') {
                        fct.innerHTML = `<span class="text-emerald-400 font-black text-2xl">(+${formatShortNum(value)})</span>`;
                    } else if (isCrit) {
                        fct.innerHTML = `<span class="text-amber-300 font-black text-3xl sm:text-4xl drop-shadow-[0_0_25px_rgba(245,158,11,1)]">KRYTYK! -${formatShortNum(value)}</span>${dotBadge}`;
                    } else if (skillName) {
                        fct.innerHTML = `<span class="${isPoison ? 'text-emerald-300' : (isFire ? 'text-orange-300' : 'text-indigo-300')} font-black text-2xl sm:text-3xl drop-shadow-[0_0_20px_rgba(99,102,241,0.9)]">${skillName} -${formatShortNum(value)}</span>${dotBadge}`;
                    } else {
                        fct.innerHTML = `<span class="text-red-400 font-black text-2xl sm:text-3xl drop-shadow-[0_2px_10px_rgba(0,0,0,0.9)]">-${formatShortNum(value)}</span>${dotBadge}`;
                    }

                    fxOverlay.appendChild(fct);

                    setTimeout(() => {
                        if (fct.parentNode) fct.parentNode.removeChild(fct);
                    }, 850);
                }, 170);

                // 5. After turn animation settles (~500ms), schedule next turn sequentially!
                // Używamy setUnthrottledTimeout (Web Worker) zamiast zwykłego setTimeout,
                // żeby timer NIE był throttlowany przez przeglądarkę gdy karta jest ukryta.
                // Dzięki temu nie kumulują się opóźnione timery które po powrocie do karty
                // odpalałyby wszystkie naraz i wywoływały wielokrotny nextTurn (bug x5 speed).
                setUnthrottledTimeout(() => {
                    isExecutingTurn = false;
                    if (watchdogTimer) clearTimeout(watchdogTimer);
                    if (!isPaused) {
                        const basePause = currentSpeed === 5 ? 60 : (currentSpeed === 2 ? 200 : 550);
                        scheduleNextTurn(basePause);
                    }
                }, currentSpeed === 5 ? 120 : (currentSpeed === 2 ? 250 : 500), 'turnAnimTimer');
            });

            Livewire.on('auto-chain-next-battle', (event) => {
                clearUnthrottledTimeout('autoChainTimeout', autoChainTimeout);
                if (autoChainWatchdog) { clearTimeout(autoChainWatchdog); autoChainWatchdog = null; }
                if (isPaused) return;

                let delay = 700; // fast chain between battles after a win
                const payload = (event && event[0]) ? event[0] : event;
                if (payload && typeof payload.delay === 'number') {
                    delay = payload.delay; // e.g. 3000ms penalty after a loss
                }

                if (currentSpeed === 5) {
                    // Przy x5 skracamy opóźnienie, ale nie poniżej 300ms dla wygranej
                    // (i zachowujemy pełne 3000ms kary za przegraną - nie skracamy jej).
                    delay = delay <= 700 ? Math.min(delay, 300) : delay;
                }

                let chainTriggered = false;
                const triggerNextBattle = () => {
                    if (chainTriggered || isPaused) return;
                    chainTriggered = true;
                    if (autoChainWatchdog) { clearTimeout(autoChainWatchdog); autoChainWatchdog = null; }
                    const component = getComponent();
                    if (component) component.call('startBattle');
                };

                autoChainTimeout = setUnthrottledTimeout(triggerNextBattle, delay, 'autoChainTimeout');


                // Watchdog: gdyby normalny auto-chain z jakiegoś powodu "utknął" (zgubiony
                // event/timer przy przełączaniu kart, race w Livewire itp.), wymuś start
                // kolejnej walki 5s po planowanym momencie. startBattle() jest już i tak
                // zabezpieczony server-side (rate-limit/COMBAT_IN_PROGRESS w EncounterService),
                // więc ewentualne nadmiarowe wywołanie jest bezpiecznie odrzucane.
                autoChainWatchdog = setTimeout(() => {
                    autoChainWatchdog = null;
                    triggerNextBattle();
                }, delay + 5000);
            });

            // Event lokacji (Faza 2): auto-przejście do kolejnego potwora w łańcuchu po
            // pokonaniu poprzedniego - analogicznie do auto-chain-next-battle powyżej,
            // ale wywołuje fightNextEventStage() zamiast startBattle().
            Livewire.on('auto-chain-next-event-stage', (event) => {
                clearUnthrottledTimeout('autoChainTimeout', autoChainTimeout);
                if (autoChainWatchdog) { clearTimeout(autoChainWatchdog); autoChainWatchdog = null; }
                if (isPaused) return;

                let delay = 700;
                const payload = (event && event[0]) ? event[0] : event;
                if (payload && typeof payload.delay === 'number') {
                    delay = payload.delay;
                }
                if (currentSpeed === 5) {
                    delay = Math.min(delay, 300);
                }

                let chainTriggered = false;
                const triggerNextStage = () => {
                    if (chainTriggered || isPaused) return;
                    chainTriggered = true;
                    if (autoChainWatchdog) { clearTimeout(autoChainWatchdog); autoChainWatchdog = null; }
                    const component = getComponent();
                    if (component) component.call('fightNextEventStage');
                };

                autoChainTimeout = setUnthrottledTimeout(triggerNextStage, delay, 'autoChainTimeout');
                autoChainWatchdog = setTimeout(() => {
                    autoChainWatchdog = null;
                    triggerNextStage();
                }, delay + 5000);
            });

            Livewire.on('encounter-finished', () => {
                cleanUp();
            });
        }
    </script>
    {{-- Session Tracker --}}
    <div wire:key="session-tracker-widget" class="fixed bottom-20 lg:bottom-3 left-3 z-40 flex flex-col items-start gap-2 text-xs font-mono select-none"
         x-data="{
            startTime: {{ $sessionStartTime }},
            elapsed: '00:00:00',
            goldPerMin: 0,
            timer: null,
            expanded: false,
            updateTime() {
                if (!this.$el || !this.$el.isConnected) {
                    if (this.timer) {
                        clearInterval(this.timer);
                        this.timer = null;
                    }
                    return;
                }
                try {
                    let now = Math.floor(Date.now() / 1000);
                    let diff = Math.max(0, now - this.startTime);
                    let h = Math.floor(diff / 3600).toString().padStart(2, '0');
                    let m = Math.floor((diff % 3600) / 60).toString().padStart(2, '0');
                    let s = Math.floor(diff % 60).toString().padStart(2, '0');
                    this.elapsed = `${h}:${m}:${s}`;

                    let totalGold = 0;
                    if (typeof $wire !== 'undefined' && $wire && typeof $wire.sessionGoldEarned !== 'undefined') {
                        totalGold = $wire.sessionGoldEarned || 0;
                    }
                    if (diff > 0) {
                        this.goldPerMin = Math.round((totalGold / diff) * 60);
                    } else {
                        this.goldPerMin = 0;
                    }
                } catch (e) {
                    if (this.timer) {
                        clearInterval(this.timer);
                        this.timer = null;
                    }
                }
            }
         }"
         x-init="
            updateTime();
            timer = setInterval(() => updateTime(), 1000);
            // Gdy Livewire zaktualizuje sessionStartTime (nowa mapa), resetuj licznik
            $wire.$watch('sessionStartTime', (newVal) => {
                this.startTime = newVal;
                this.elapsed = '00:00:00';
                this.goldPerMin = 0;
                // Resetuj też licznik kills w tytule karty
                if (typeof farmSessionKills !== 'undefined') {
                    farmSessionKills = 0;
                    farmSessionStartedAt = Date.now();
                    if (typeof startFarmTitleInterval === 'function') startFarmTitleInterval();
                }
            });
         ">

        {{-- Expanded Panel: items collected during this session --}}
        <div x-show="expanded" x-transition.opacity.duration.150ms style="display: none;"
             class="w-64 max-h-72 overflow-y-auto bg-slate-950/95 text-amber-100 rounded-xl shadow-2xl border border-amber-600/60 backdrop-blur-md p-3 custom-scrollbar">
            <h4 class="font-bold text-amber-300 mb-2 medieval-font text-center text-sm border-b border-amber-800/50 pb-1.5">
                Zdobycz z Sesji
            </h4>

            <div class="flex items-center justify-between text-[11px] mb-1.5">
                <span class="text-amber-200"><i class="fa-solid fa-coins text-yellow-400 mr-1"></i>Złoto</span>
                <span class="font-bold text-yellow-300">{{ number_format($sessionGoldEarned) }}</span>
            </div>

            <div class="flex items-center justify-between text-[11px] mb-1.5">
                <span class="text-amber-200"><i class="fa-solid fa-star text-indigo-300 mr-1"></i>Exp</span>
                <span class="font-bold text-indigo-300">{{ number_format($sessionXpEarned) }}</span>
            </div>

            @if ($sessionGemsEarned > 0)
                <div class="flex items-center justify-between text-[11px] mb-1.5">
                    <span class="text-amber-200"><i class="fa-solid fa-gem text-blue-300 mr-1"></i>Klejnoty</span>
                    <span class="font-bold text-blue-300">{{ number_format($sessionGemsEarned) }}</span>
                </div>
            @endif

            @if (empty($sessionItemsCollected))
                <p class="text-amber-700/70 text-[11px] italic text-center mt-2">Brak przedmiotów w tej sesji.</p>
            @else
                <div class="mt-2 pt-2 border-t border-amber-800/50 space-y-1">
                    @foreach ($sessionItemsCollected as $item)
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="{{ $item['type'] === 'material' ? 'text-emerald-300' : 'text-purple-300' }} truncate font-semibold">{{ $item['name'] }}</span>
                            <span class="text-slate-300 shrink-0 ml-2">{{ $item['quantity'] }}x</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Summary Bar --}}
        <div @click="expanded = !expanded"
             class="bg-slate-950/90 text-amber-100 px-3 py-1.5 rounded-xl shadow-2xl border border-amber-600/60 backdrop-blur-md transition-all hover:bg-slate-900/90 flex items-center gap-3 cursor-pointer">
            <div class="flex items-center gap-1.5" title="Pokonani potwory">
                <span class="text-amber-400 font-sans"><i class="fa-solid fa-swords"></i></span>
                <span class="font-bold text-white">{{ $sessionMonstersDefeated }}</span>
            </div>
            <div class="h-3 w-px bg-amber-500/30"></div>
            <div class="flex items-center gap-1.5" title="Złoto na minutę">
                <span class="text-yellow-400 font-sans"><i class="fa-solid fa-coins"></i></span>
                <span class="font-bold text-yellow-300" x-text="(goldPerMin || 0) + '/m'"></span>
            </div>
            <div class="h-3 w-px bg-amber-500/30"></div>
            <div class="flex items-center gap-1.5" title="Czas sesji">
                <span class="text-indigo-300 font-sans"><i class="fa-solid fa-stopwatch"></i></span>
                <span class="font-bold text-slate-200" x-text="elapsed || '00:00:00'"></span>
            </div>
            <div class="h-3 w-px bg-amber-500/30"></div>
            <span class="text-amber-400 transition-transform" :class="{ 'rotate-180': expanded }" title="Rozwiń statystyki sesji">
                <i class="fa-solid fa-chevron-up text-[10px]"></i>
            </span>
        </div>
    </div>
</div>
