<div id="adventure-map-component" class="min-h-screen relative overflow-hidden" x-data="{ travelingTo: null }">
    {{-- Dynamic background per map --}}
    <div class="absolute inset-0 bg-cover bg-center bg-no-repeat" style="background-image: url('{{ $background }}');">
    </div>

    {{-- Dark overlay for depth --}}
    <div class="absolute inset-0 bg-black/60"></div>

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

    {{-- Warning message --}}
    @if (session('warning'))
        <div class="absolute top-4 left-1/2 transform -translate-x-1/2 z-50">
            <div class="bg-amber-100 border-2 border-amber-600 rounded-lg px-4 py-2 shadow-lg ring-1 ring-amber-800/50">
                <p class="text-amber-800 font-semibold text-sm">{{ session('warning') }}</p>
            </div>
        </div>
    @endif

    {{-- Battle error message --}}
    @error('battle')
        <div class="absolute top-16 left-1/2 transform -translate-x-1/2 z-50">
            <div class="bg-red-100 border-2 border-red-600 rounded-lg px-4 py-2 shadow-lg">
                <p class="text-red-800 font-semibold text-sm">{{ $message }}</p>
            </div>
        </div>
    @enderror

    <div class="relative z-10 container mx-auto px-4 py-6 min-h-screen">
        @php
            $gameStage = auth()->user()->game_stage;
        @endphp

        @if($gameStage == 11)
            <livewire:global.tutorial-overlay :step="12" />
        @elseif($gameStage == 12 && $battleCompleted)
            <livewire:global.tutorial-overlay :step="13" rewardItemTemplateId="01KX9NE31YJ98KTT8AAG6061AG" />
        @endif

        {{-- Header with navigation --}}
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-3xl font-bold text-amber-100 medieval-font drop-shadow-2xl">
                🗺️ {{ $map->name }}
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
                        🗺️ Mapy
                    </span>
                </button>
                <button @click="travelingTo = 'Miasto'; $dispatch('play-audio', { type: 'tab' }); setTimeout(() => $wire.backToHub(), 500)" 
                    class="relative rounded-lg px-4 py-2 shadow-lg overflow-hidden group">
                    <img src="{{ asset('img/avatars/plate.png') }}" alt=""
                        class="absolute inset-0 w-full h-full object-cover rounded-lg">
                    <div class="absolute inset-0 bg-amber-900/20 rounded-lg"></div>
                    <span
                        class="relative text-amber-100 font-bold medieval-font drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)]">
                        🏰 Miasto
                    </span>
                </button>
            </div>
        </div>

        {{-- Classic RPG Battle Layout --}}
        <div class="grid grid-cols-2 lg:grid-cols-3 gap-4 lg:gap-8 max-w-7xl mx-auto">

            {{-- Left Side - Player Panel --}}
            <div class="col-span-1 lg:col-span-1 order-2 lg:order-1" id="player-panel-container">
                <div id="player-panel"
                    class="relative rounded-xl shadow-2xl overflow-hidden {{ $this->isPlayerTurn() ? 'ring-4 ring-amber-300 shadow-[0_0_30px_rgba(255,200,60,.4)]' : '' }}">
                    {{-- Wooden background --}}
                    <img src="{{ asset('img/avatars/plate.png') }}" alt=""
                        class="absolute inset-0 w-full h-full object-cover">

                    {{-- Wooden overlay for better contrast --}}
                    <div class="absolute inset-0 bg-amber-900/25"></div>

                    <div class="relative p-3 lg:p-6 space-y-2 lg:space-y-4">
                        {{-- Player Portrait --}}
                        <div class="text-center">
                            <div
                                class="w-16 h-16 sm:w-20 sm:h-20 lg:w-28 lg:h-28 mx-auto rounded-xl overflow-hidden ring-4 ring-amber-800/80 shadow-xl">
                                @if (!empty($player) && $player['avatar'])
                                    <img src="{{ $player['avatar'] }}" alt="{{ $player['name'] }}"
                                        class="w-full h-full object-cover">
                                @elseif (!empty($player))
                                    <div
                                        class="w-full h-full bg-gradient-to-b from-amber-200 to-amber-300 flex items-center justify-center text-4xl text-amber-800">
                                        ⚔️
                                    </div>
                                @else
                                    <div
                                        class="w-full h-full bg-gradient-to-b from-amber-200 to-amber-300 flex items-center justify-center text-4xl text-amber-800">
                                        👤
                                    </div>
                                @endif
                            </div>

                            {{-- Name & Level --}}
                            <h3
                                class="mt-2 lg:mt-3 text-base lg:text-xl font-bold text-amber-100 medieval-font drop-shadow-[0_2px_4px_rgba(0,0,0,0.8)] leading-tight">
                                {{ !empty($player) ? $player['name'] : $character->name }}
                            </h3>
                            <p class="text-[10px] lg:text-sm text-amber-200 tracking-wide drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)]">
                                Poziom {{ $character->level }}
                            </p>
                        </div>

                        {{-- Player HP Bar --}}
                        @if (!empty($player))
                            <div class="space-y-1 lg:space-y-2">
                                <div
                                    class="flex justify-between text-[10px] lg:text-sm font-semibold text-amber-100 drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)]">
                                    <span>❤️ Życie</span>
                                    <span>{{ $this->getCurrentPlayerHp() }}/{{ $player['maxHp'] }}</span>
                                </div>
                                <div class="h-4 w-full rounded-full bg-black/40 ring-2 ring-amber-800/60 shadow-inner">
                                    <div class="h-full rounded-full bg-gradient-to-r from-red-500 via-red-600 to-red-500 shadow-sm transition-all duration-500"
                                        style="width: {{ $this->getPlayerHpPercent() }}%"></div>
                                </div>
                            </div>

                            {{-- XP Progress Bar (NEW) --}}
                            <div class="space-y-1 mt-1 hidden sm:block">
                                <div
                                    class="flex justify-between text-[10px] lg:text-sm font-semibold text-indigo-100 drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)]">
                                    <span>⭐ Dośw.</span>
                                    <span>{{ $character->xp }}/{{ $this->getXpToNextLevel() }}</span>
                                </div>
                                <div class="h-3 w-full rounded-full bg-indigo-900/40 ring-1 ring-indigo-950/50">
                                    <div class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-indigo-400 shadow-sm transition-all duration-500"
                                        style="width: {{ $this->getXpPercentage() }}%"></div>
                                </div>
                            </div>

                            {{-- Player Stats --}}
                            <div>
                                <h4
                                    class="hidden lg:block text-sm font-semibold text-amber-100 mb-2 lg:mb-3 medieval-font drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)]">
                                    📊 Atrybuty
                                </h4>
                                <div class="grid grid-cols-2 gap-1 lg:gap-3">
                                    <div class="relative rounded-lg overflow-hidden shadow-lg">
                                        <img src="{{ asset('img/avatars/plate.png') }}" alt=""
                                            class="absolute inset-0 w-full h-full object-cover">
                                        <div class="absolute inset-0 bg-red-900/30"></div>
                                        <div class="relative px-1 py-1 lg:px-3 lg:py-3 text-center">
                                            <div class="text-[9px] lg:text-xs font-medium text-amber-200 tracking-wider">STR</div>
                                            <div
                                                class="text-sm lg:text-xl font-bold text-amber-100 drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)]">
                                                {{ $player['stats']['str'] ?? 0 }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="relative rounded-lg overflow-hidden shadow-lg">
                                        <img src="{{ asset('img/avatars/plate.png') }}" alt=""
                                            class="absolute inset-0 w-full h-full object-cover">
                                        <div class="absolute inset-0 bg-blue-900/30"></div>
                                        <div class="relative px-1 py-1 lg:px-3 lg:py-3 text-center">
                                            <div class="text-[9px] lg:text-xs font-medium text-amber-200 tracking-wider">INT</div>
                                            <div
                                                class="text-sm lg:text-xl font-bold text-amber-100 drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)]">
                                                {{ $player['stats']['int'] ?? 0 }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="relative rounded-lg overflow-hidden shadow-lg">
                                        <img src="{{ asset('img/avatars/plate.png') }}" alt=""
                                            class="absolute inset-0 w-full h-full object-cover">
                                        <div class="absolute inset-0 bg-green-900/30"></div>
                                        <div class="relative px-1 py-1 lg:px-3 lg:py-3 text-center">
                                            <div class="text-[9px] lg:text-xs font-medium text-amber-200 tracking-wider">VIT</div>
                                            <div
                                                class="text-sm lg:text-xl font-bold text-amber-100 drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)]">
                                                {{ $player['stats']['vit'] ?? 0 }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="relative rounded-lg overflow-hidden shadow-lg">
                                        <img src="{{ asset('img/avatars/plate.png') }}" alt=""
                                            class="absolute inset-0 w-full h-full object-cover">
                                        <div class="absolute inset-0 bg-yellow-900/30"></div>
                                        <div class="relative px-1 py-1 lg:px-3 lg:py-3 text-center">
                                            <div class="text-[9px] lg:text-xs font-medium text-amber-200 tracking-wider">AGI</div>
                                            <div
                                                class="text-sm lg:text-xl font-bold text-amber-100 drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)]">
                                                {{ $player['stats']['agi'] ?? 0 }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Center - Parchment Battle Log --}}
            <div class="col-span-2 lg:col-span-1 order-1 lg:order-2 mb-4 lg:mb-0">
                <section class="relative rounded-2xl shadow-2xl overflow-hidden h-[400px] lg:h-[500px] flex flex-col">
                    {{-- Parchment background --}}
                    <img src="{{ asset('img/avatars/plate.png') }}" alt=""
                        class="absolute inset-0 w-full h-full object-cover">

                    {{-- Parchment overlay --}}
                    <div class="absolute inset-0 bg-amber-100/80"></div>

                    {{-- Header --}}
                    <header class="relative p-4 text-center border-b-2 border-amber-800/30">
                        <h3 class="font-serif text-2xl text-amber-900 tracking-wider medieval-font drop-shadow-sm">
                            ⚔️ Kronika Bitwy
                        </h3>
                    </header>

                    {{-- Battle Log Scroll Area --}}
                    <div class="relative flex-1 overflow-y-auto p-4" @if($isCalculating) wire:poll.500ms="checkCombatStatus" @endif>
                        {{-- Loading Overlay during startBattle --}}
                        <div wire:loading.flex wire:target="startBattle" class="absolute inset-0 z-20 flex-col items-center justify-center bg-amber-50/90 backdrop-blur-sm text-center">
                            <div class="relative w-24 h-24 mb-4">
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <div class="text-5xl drop-shadow-md animate-pulse">👁️</div>
                                </div>
                                <div class="absolute inset-0 rounded-full border-4 border-amber-600/30 border-t-amber-600 animate-[spin_1s_linear_infinite]"></div>
                            </div>
                            <h3 class="font-serif text-2xl text-amber-900 tracking-wider medieval-font drop-shadow-sm animate-pulse">
                                Szukanie przeciwnika...
                            </h3>
                        </div>

                        @if($isCalculating)
                            <div class="h-full flex flex-col items-center justify-center text-center">
                                <div class="relative w-32 h-32 mb-6">
                                    <div class="absolute inset-0 flex items-center justify-center transform transition-transform duration-500 animate-[spin_2s_ease-in-out_infinite_alternate]">
                                        <div class="text-6xl drop-shadow-xl" style="transform: rotate(45deg);">⚔️</div>
                                    </div>
                                    <div class="absolute inset-0 rounded-full border-4 border-amber-600/30 border-t-amber-600 animate-spin"></div>
                                    <div class="absolute inset-2 rounded-full border-4 border-amber-800/30 border-b-amber-800 animate-[spin_1.5s_linear_infinite_reverse]"></div>
                                </div>
                                <h3 class="font-serif text-3xl text-amber-900 tracking-wider medieval-font drop-shadow-sm animate-pulse">
                                    Obliczanie walki...
                                </h3>
                                <p class="text-amber-800 italic mt-3 font-semibold">Krzyżowanie mieczy...</p>
                            </div>
                        @else
                            <ul class="space-y-3 text-amber-900">
                                @if (empty($visibleTurns))
                                    @if ($isPlaying)
                                        <li class="text-center py-8 animate-pulse">
                                            <div class="text-4xl mb-3">⚔️</div>
                                            <div class="text-amber-800 font-serif italic text-lg">
                                                Rozpoczynanie bitwy...
                                            </div>
                                        </li>
                                    @elseif (!$battleCompleted)
                                        <li class="text-center py-8">
                                            <div class="text-4xl mb-3">⚔️</div>
                                            <div class="text-amber-800 font-serif italic text-lg">
                                                Naciśnij "Rozpocznij Walkę" aby rozpocząć przygodę...
                                            </div>
                                        </li>
                                    @endif
                            @else
                                @foreach ($visibleTurns as $index => $turn)
                                    <li class="leading-relaxed bg-white/30 rounded-lg px-3 py-2 shadow-sm">
                                        <span
                                            class="inline-block w-8 text-center text-xs font-bold bg-amber-800 text-amber-100 rounded px-1 mr-2">
                                            T{{ $index + 1 }}
                                        </span>
                                        @if ($turn['type'] == 'miss')
                                            <span class="text-slate-700 italic font-semibold">
                                                <strong>{{ $turn['actor'] == 'player' ? $player['name'] : $enemy['name'] }}</strong>
                                                pudłuje atak!
                                            </span>
                                        @else
                                            <span
                                                class="{{ $turn['actor'] == 'player' ? 'text-emerald-800' : 'text-red-800' }} font-semibold">
                                                <strong>{{ $turn['actor'] == 'player' ? $player['name'] : $enemy['name'] }}</strong>
                                                zadaje 
                                                @if($turn['actor'] == 'player' && isset($turn['bonusDamage']) && $turn['bonusDamage'] > 0)
                                                    <strong class="text-amber-900">{{ $turn['baseDamage'] }} (+{{ $turn['bonusDamage'] }})</strong>
                                                @elseif($turn['actor'] == 'enemy' && isset($turn['resistDamage']) && $turn['resistDamage'] > 0)
                                                    <strong class="text-amber-900">{{ $turn['baseDamage'] }} (-{{ $turn['resistDamage'] }})</strong>
                                                @else
                                                    <strong class="text-amber-900">{{ $turn['value'] }}</strong>
                                                @endif
                                                obrażeń
                                                @if ($turn['crit'])
                                                    <span class="font-bold text-red-900">✨ KRYTYK!</span>
                                                @endif
                                            </span>
                                        @endif
                                    </li>
                                @endforeach

                                {{-- Battle Result & Rewards --}}
                                @if ($battleCompleted)
                                    <li
                                        class="text-center mt-6 p-4 rounded-xl {{ $result == 'win' ? 'bg-green-200/90 border-2 border-green-600 text-green-800' : ($result == 'finished' ? 'bg-purple-200/90 border-2 border-purple-600 text-purple-900' : 'bg-red-200/90 border-2 border-red-600 text-red-800') }} shadow-lg">
                                        <div class="text-4xl mb-2">{{ $result == 'win' ? '🏆' : ($result == 'finished' ? '⚔️' : '💀') }}</div>
                                        <div class="text-2xl font-bold medieval-font">
                                            {{ $result == 'win' ? 'TRIUMF!' : ($result == 'finished' ? 'WALKA ZAKOŃCZONA' : 'KLĘSKA!') }}
                                        </div>
                                        @if ($result == 'win' || $result == 'finished')
                                            @if($result == 'finished')
                                                <div class="text-lg mt-2 font-bold text-purple-900">
                                                    Łączny dmg: <span class="text-red-700 drop-shadow-md">{{ number_format($damageDealt) }}</span>
                                                </div>
                                            @endif

                                            {{-- Loot Display --}}
                                            @if (!empty($drops))
                                                <div
                                                    class="mt-4 p-3 bg-amber-100/90 border-2 border-amber-600 rounded-lg">
                                                    <h4 class="font-bold text-amber-900 mb-2">🎁 Łup z walki:</h4>
                                                    <div class="space-y-1 text-sm">
                                                        <div class="flex items-center space-x-2">
                                                            <span class="text-blue-600">✨</span>
                                                            <span class="text-amber-800">
                                                                +{{ !empty($xpData) ? $xpData['base'] : $xpGained }} Doświadczenia
                                                                @if (!empty($xpData) && isset($xpData['multiplier']) && $xpData['multiplier'] > 1.0)
                                                                    <span class="font-bold text-xs text-emerald-700">(+{{ $xpData['bonus'] }} z bonusu {{ round(($xpData['multiplier'] - 1) * 100) }}%)</span>
                                                                @endif
                                                            </span>
                                                        </div>

                                                        <div class="flex items-center space-x-2">
                                                            <span class="text-yellow-600">💰</span>
                                                            <span class="text-amber-800">
                                                                +{{ !empty($goldData) ? $goldData['base'] : $goldGained }} Złota
                                                                @if (!empty($goldData) && isset($goldData['multiplier']) && $goldData['multiplier'] > 1.0)
                                                                    <span class="font-bold text-xs text-yellow-700">(+{{ $goldData['bonus'] }} z bonusu {{ round(($goldData['multiplier'] - 1) * 100) }}%)</span>
                                                                @endif
                                                            </span>
                                                        </div>

                                                        @if (isset($drops['gems']) && $drops['gems'] > 0)
                                                            <div class="flex items-center space-x-2">
                                                                <span class="text-blue-600">💎</span>
                                                                <span class="text-amber-800">+{{ $drops['gems'] }}
                                                                    klejnotów</span>
                                                            </div>
                                                        @endif

                                                        @foreach ($drops['items'] ?? [] as $item)
                                                            <div class="flex items-center space-x-2">
                                                                <span class="text-purple-600">⚔️</span>
                                                                <span class="text-amber-800">{{ $item['name'] }}
                                                                    ({{ $item['quantity'] }}x)
                                                                </span>
                                                            </div>
                                                        @endforeach

                                                        @foreach ($drops['materials'] ?? [] as $material)
                                                            <div class="flex items-center space-x-2">
                                                                <span class="text-green-600">🌿</span>
                                                                <span class="text-amber-800">{{ $material['name'] }}
                                                                    ({{ $material['quantity'] }}x)
                                                                </span>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
                                        @else
                                            <div class="text-sm mt-1 font-semibold">
                                                Zostałeś pokonany w walce...
                                            </div>
                                        @endif
                                    </li>
                                @endif
                            @endif
                        </ul>
                        @endif
                    </div>

                    {{-- Battle Controls --}}
                    <footer class="relative p-4 border-t-2 border-amber-800/30">
                        <div class="flex flex-col gap-3">
                            {{-- Main Controls --}}
                            <div class="flex items-center justify-center gap-3">
                                @if ($isCalculating)
                                    <button wire:click="cancelBattle"
                                        class="relative rounded-lg px-6 py-3 shadow-lg overflow-hidden group"
                                        wire:loading.attr="disabled"
                                        wire:target="cancelBattle">
                                        <img src="{{ asset('img/avatars/plate.png') }}" alt=""
                                            class="absolute inset-0 w-full h-full object-cover">
                                        <div class="absolute inset-0 bg-red-800/60 group-hover:bg-red-700/60 transition-colors"></div>
                                        
                                        <span wire:loading.remove wire:target="cancelBattle"
                                            class="relative text-white font-bold medieval-font drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)]">
                                            🛑 Przerwij walkę
                                        </span>
                                        <span wire:loading wire:target="cancelBattle"
                                            class="relative text-white font-bold medieval-font drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)]">
                                            ⏳ Przerywanie...
                                        </span>
                                    </button>
                                @elseif (empty($visibleTurns) && !$isPlaying && !$battleCompleted)
                                    <button wire:click="startBattle"
                                        class="relative rounded-lg px-6 py-3 shadow-lg overflow-hidden {{ $gameStage == 12 ? 'animate-[pulse_1.5s_ease-in-out_infinite] ring-4 ring-amber-500 scale-105 shadow-[0_0_20px_rgba(245,158,11,0.6)]' : '' }}">
                                        <img src="{{ asset('img/avatars/plate.png') }}" alt=""
                                            class="absolute inset-0 w-full h-full object-cover">
                                        <div class="absolute inset-0 bg-emerald-800/40"></div>
                                        <span
                                            class="relative text-white font-bold medieval-font drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)]">
                                            🗡️ Rozpocznij Walkę
                                        </span>
                                    </button>
                                @elseif (!$battleCompleted)
                                    <button wire:click="togglePlayback"
                                        class="relative rounded-lg px-6 py-3 shadow-lg overflow-hidden">
                                        <img src="{{ asset('img/avatars/plate.png') }}" alt=""
                                            class="absolute inset-0 w-full h-full object-cover">
                                        <div class="absolute inset-0 bg-emerald-800/40"></div>
                                        <span
                                            class="relative text-white font-bold medieval-font drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)]">
                                            {{ $isPlaying ? '⏸️ Pauza' : '▶️ Wznów' }}
                                        </span>
                                    </button>
                                @endif

                                {{-- Speed Controls --}}
                                @if (!empty($visibleTurns))
                                    <div class="flex gap-2">
                                        <button wire:click="setPlaybackSpeed(1)"
                                            class="relative rounded-lg px-4 py-2 shadow-lg overflow-hidden">
                                            <img src="{{ asset('img/avatars/plate.png') }}" alt=""
                                                class="absolute inset-0 w-full h-full object-cover">
                                            <div
                                                class="absolute inset-0 {{ $playbackSpeed == 1 ? 'bg-amber-700/60' : 'bg-amber-900/40' }}">
                                            </div>
                                            <span
                                                class="relative {{ $playbackSpeed == 1 ? 'text-amber-100' : 'text-amber-200' }} font-bold medieval-font drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)]">
                                                ⏯️ x1
                                            </span>
                                        </button>
                                        <button wire:click="setPlaybackSpeed(2)"
                                            class="relative rounded-lg px-4 py-2 shadow-lg overflow-hidden">
                                            <img src="{{ asset('img/avatars/plate.png') }}" alt=""
                                                class="absolute inset-0 w-full h-full object-cover">
                                            <div
                                                class="absolute inset-0 {{ $playbackSpeed == 2 ? 'bg-amber-700/60' : 'bg-amber-900/40' }}">
                                            </div>
                                            <span
                                                class="relative {{ $playbackSpeed == 2 ? 'text-amber-100' : 'text-amber-200' }} font-bold medieval-font drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)]">
                                                ⏩ x2
                                            </span>
                                        </button>
                                    </div>
                                @endif

                                {{-- Reset Battle --}}
                                @if ($battleCompleted && !$isWorldBoss)
                                    <button wire:click="resetEncounter"
                                        class="relative rounded-lg px-6 py-3 shadow-lg overflow-hidden">
                                        <img src="{{ asset('img/avatars/plate.png') }}" alt=""
                                            class="absolute inset-0 w-full h-full object-cover">
                                        <div class="absolute inset-0 bg-slate-700/60"></div>
                                        <span
                                            class="relative text-slate-100 font-bold medieval-font drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)]">
                                            🔄 Kolejna Walka
                                        </span>
                                    </button>
                                @endif
                            </div>

                            {{-- Auto Chain Controls --}}
                            @if (!empty($visibleTurns))
                                <div class="flex items-center justify-center gap-3">
                                    <button wire:click="toggleAutoChain"
                                        class="relative rounded-lg px-4 py-2 shadow-lg overflow-hidden {{ $gameStage <= 12 ? 'opacity-50 cursor-not-allowed' : '' }}"
                                        {{ $gameStage <= 12 ? 'disabled' : '' }}>
                                        <img src="{{ asset('img/avatars/plate.png') }}" alt=""
                                            class="absolute inset-0 w-full h-full object-cover">
                                        <div
                                            class="absolute inset-0 {{ $autoChain ? 'bg-green-800/60' : 'bg-red-800/60' }}">
                                        </div>
                                        <span
                                            class="relative text-white font-bold medieval-font drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)]">
                                            🔗 Auto: {{ $autoChain ? 'ON' : 'OFF' }}
                                        </span>
                                    </button>

                                    @if ($autoChain && ($isPlaying || $battleCompleted))
                                        <button wire:click="stopAuto"
                                            class="relative rounded-lg px-4 py-2 shadow-lg overflow-hidden">
                                            <img src="{{ asset('img/avatars/plate.png') }}" alt=""
                                                class="absolute inset-0 w-full h-full object-cover">
                                            <div class="absolute inset-0 bg-red-700/60"></div>
                                            <span
                                                class="relative text-white font-bold medieval-font drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)]">
                                                ⏹️ Stop Auto
                                            </span>
                                        </button>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </footer>
                </section>
            </div>

            {{-- Right Side - Enemy Panel --}}
            <div class="col-span-1 lg:col-span-1 order-3 lg:order-3" id="enemy-panel-container">
                <div id="enemy-panel"
                    class="relative rounded-xl shadow-2xl overflow-hidden {{ $this->isEnemyTurn() ? 'ring-4 ring-red-300 shadow-[0_0_30px_rgba(255,100,100,.4)]' : '' }}">
                    {{-- Wooden background --}}
                    <img src="{{ asset('img/avatars/plate.png') }}" alt=""
                        class="absolute inset-0 w-full h-full object-cover">

                    {{-- Wooden overlay for better contrast --}}
                    <div class="absolute inset-0 bg-red-900/20"></div>

                    <div class="relative p-3 lg:p-6 space-y-2 lg:space-y-4">
                        {{-- Enemy Portrait --}}
                        <div class="text-center">
                            <div class="w-16 h-16 sm:w-20 sm:h-20 lg:w-28 lg:h-28 mx-auto rounded-xl overflow-hidden ring-4 ring-red-800/80 shadow-xl">
                                @if(!empty($enemy) && !empty($enemy['avatar']))
                                    <img src="{{ route('assets.monsters.avatars', ['filename' => $enemy['avatar']]) }}"
                                        alt="{{ $enemy['name'] }}"
                                        class="w-full h-full object-cover">
                                @else
                                    <img src="{{ asset('img/monsters/placeholder.png') }}"
                                        alt="{{ !empty($enemy) ? $enemy['name'] : 'Potwór' }}"
                                        class="w-full h-full object-cover">
                                @endif
                            </div>

                            {{-- Name & Level --}}
                            <h3
                                class="mt-2 lg:mt-3 text-base lg:text-xl font-bold text-amber-100 medieval-font drop-shadow-[0_2px_4px_rgba(0,0,0,0.8)] leading-tight">
                                {{ !empty($enemy) ? $enemy['name'] : 'Oczekuje...' }}
                            </h3>
                            <p class="text-[10px] lg:text-sm text-amber-200 tracking-wide drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)]">
                                @if (!empty($enemy))
                                    Poziom {{ $enemy['level'] }}
                                @else
                                    ---
                                @endif
                            </p>
                        </div>

                        {{-- Enemy HP Bar --}}
                        @if (!empty($enemy))
                            <div class="space-y-1 lg:space-y-2">
                                <div
                                    class="flex justify-between text-[10px] lg:text-sm font-semibold text-amber-100 drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)]">
                                    <span>❤️ Życie</span>
                                    <span>{{ $this->getCurrentEnemyHp() }}/{{ $enemy['maxHp'] }}</span>
                                </div>
                                <div class="h-4 w-full rounded-full bg-black/40 ring-2 ring-red-800/60 shadow-inner">
                                    <div class="h-full rounded-full bg-gradient-to-r from-red-500 via-red-600 to-red-500 shadow-sm transition-all duration-500"
                                        style="width: {{ $this->getEnemyHpPercent() }}%"></div>
                                </div>
                            </div>

                            {{-- Enemy Stats --}}
                            <div>
                                <h4
                                    class="hidden lg:block text-sm font-semibold text-amber-100 mb-2 lg:mb-3 medieval-font drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)]">
                                    ⚡ Statystyki
                                </h4>
                                <div class="grid grid-cols-2 gap-1 lg:gap-3">
                                    <div class="relative rounded-lg overflow-hidden shadow-lg">
                                        <img src="{{ asset('img/avatars/plate.png') }}" alt=""
                                            class="absolute inset-0 w-full h-full object-cover">
                                        <div class="absolute inset-0 bg-red-900/40"></div>
                                        <div class="relative px-1 py-1 lg:px-3 lg:py-3 text-center">
                                            <div class="text-[9px] lg:text-xs font-medium text-red-200 tracking-wider">ATK</div>
                                            <div
                                                class="text-sm lg:text-xl font-bold text-amber-100 drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)]">
                                                {{ $enemy['stats']['atk'] ?? 0 }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="relative rounded-lg overflow-hidden shadow-lg">
                                        <img src="{{ asset('img/avatars/plate.png') }}" alt=""
                                            class="absolute inset-0 w-full h-full object-cover">
                                        <div class="absolute inset-0 bg-slate-900/40"></div>
                                        <div class="relative px-1 py-1 lg:px-3 lg:py-3 text-center">
                                            <div class="text-[9px] lg:text-xs font-medium text-slate-200 tracking-wider">DEF</div>
                                            <div
                                                class="text-sm lg:text-xl font-bold text-amber-100 drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)]">
                                                {{ $enemy['stats']['def'] ?? 0 }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="relative rounded-lg overflow-hidden shadow-lg">
                                        <img src="{{ asset('img/avatars/plate.png') }}" alt=""
                                            class="absolute inset-0 w-full h-full object-cover">
                                        <div class="absolute inset-0 bg-yellow-900/40"></div>
                                        <div class="relative px-1 py-1 lg:px-3 lg:py-3 text-center">
                                            <div class="text-[9px] lg:text-xs font-medium text-yellow-200 tracking-wider">AGI</div>
                                            <div
                                                class="text-sm lg:text-xl font-bold text-amber-100 drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)]">
                                                {{ $enemy['stats']['agi'] ?? 0 }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="relative rounded-lg overflow-hidden shadow-lg">
                                        <img src="{{ asset('img/avatars/plate.png') }}" alt=""
                                            class="absolute inset-0 w-full h-full object-cover">
                                        <div class="absolute inset-0 bg-blue-900/40"></div>
                                        <div class="relative px-1 py-1 lg:px-3 lg:py-3 text-center">
                                            <div class="text-[9px] lg:text-xs font-medium text-blue-200 tracking-wider">INT</div>
                                            <div
                                                class="text-sm lg:text-xl font-bold text-amber-100 drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)]">
                                                {{ $enemy['stats']['int'] ?? 0 }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&display=swap');

        .medieval-font {
            font-family: 'Cinzel', serif;
        }

        /* Combat Animations */
        @keyframes bounce-attack-right {
            0% { transform: translateX(0); }
            40% { transform: translateX(40px) scale(1.05); }
            60% { transform: translateX(40px) scale(1.05); }
            100% { transform: translateX(0) scale(1); }
        }

        @keyframes bounce-attack-left {
            0% { transform: translateX(0); }
            40% { transform: translateX(-40px) scale(1.05); }
            60% { transform: translateX(-40px) scale(1.05); }
            100% { transform: translateX(0) scale(1); }
        }

        @keyframes damage-shake {
            0%, 100% { transform: translateX(0); filter: brightness(1); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); filter: brightness(1.5) sepia(1) hue-rotate(-50deg) saturate(5); }
            20%, 40%, 60%, 80% { transform: translateX(5px); filter: brightness(1.5) sepia(1) hue-rotate(-50deg) saturate(5); }
        }

        .anim-attack-player {
            animation: bounce-attack-right 300ms cubic-bezier(0.175, 0.885, 0.32, 1.275);
            z-index: 50;
        }

        .anim-attack-enemy {
            animation: bounce-attack-left 300ms cubic-bezier(0.175, 0.885, 0.32, 1.275);
            z-index: 50;
        }

        .anim-damage {
            animation: damage-shake 400ms ease-in-out;
        }
    </style>

    <script>
        document.addEventListener('livewire:navigated', () => {
            initMapStubComponent();
        });

        document.addEventListener('livewire:init', () => {
            initMapStubComponent();
        });

        function initMapStubComponent() {
            let playbackInterval = null;
            let autoChainTimeout = null;

            function cleanUp() {
                if (playbackInterval) clearInterval(playbackInterval);
                if (autoChainTimeout) clearTimeout(autoChainTimeout);
                playbackInterval = null;
                autoChainTimeout = null;
            }

            cleanUp();
            window.addEventListener('beforeunload', cleanUp);

            function getComponent() {
                const el = document.getElementById('adventure-map-component');
                return el ? Livewire.find(el.getAttribute('wire:id')) : null;
            }

            Livewire.on('start-playback', (event) => {
                cleanUp();
                const speed = (event && event[0] && event[0].speed) ? event[0].speed : (event && event.speed ? event.speed : 1);
                const delay = Math.floor(1600 / speed);

                playbackInterval = setInterval(() => {
                    const component = getComponent();
                    if (component) component.call('nextTurn');
                }, delay);
            });

            Livewire.on('stop-playback', () => {
                if (playbackInterval) clearInterval(playbackInterval);
                playbackInterval = null;
            });

            Livewire.on('update-playback-speed', (event) => {
                if (playbackInterval) {
                    clearInterval(playbackInterval);
                    const speed = (event && event[0] && event[0].speed) ? event[0].speed : (event && event.speed ? event.speed : 1);
                    const delay = Math.floor(1600 / speed);

                    playbackInterval = setInterval(() => {
                        const component = getComponent();
                        if (component) component.call('nextTurn');
                    }, delay);
                }
            });

            Livewire.on('turn-played', (event) => {
                const data = (event && event[0]) ? event[0] : event;
                const actor = data.actor;
                const type = data.type;
                
                const playerPanel = document.getElementById('player-panel-container');
                const enemyPanel = document.getElementById('enemy-panel-container');
                
                if (!playerPanel || !enemyPanel) return;

                // Remove existing animation classes to re-trigger
                playerPanel.classList.remove('anim-attack-player', 'anim-damage');
                enemyPanel.classList.remove('anim-attack-enemy', 'anim-damage');
                
                // Force reflow
                void playerPanel.offsetWidth;
                void enemyPanel.offsetWidth;

                if (actor === 'player') {
                    playerPanel.classList.add('anim-attack-player');
                    if (type !== 'miss') {
                        setTimeout(() => enemyPanel.classList.add('anim-damage'), 150);
                    }
                } else {
                    enemyPanel.classList.add('anim-attack-enemy');
                    if (type !== 'miss') {
                        setTimeout(() => playerPanel.classList.add('anim-damage'), 150);
                    }
                }
            });

            Livewire.on('auto-chain-next-battle', () => {
                if (autoChainTimeout) clearTimeout(autoChainTimeout);

                autoChainTimeout = setTimeout(() => {
                    const component = getComponent();
                    if (component) component.call('startBattle');
                }, 2000);
            });

            Livewire.on('encounter-finished', () => {
                cleanUp();
            });

            setTimeout(() => {
                const battleButton = document.querySelector('[wire\\:click="startBattle"]');
                if (battleButton) {
                    battleButton.addEventListener('click', function() {
                        const component = getComponent();
                        if (component) component.call('startBattle');
                    });
                }
            }, 100);
        }
    </script>
    {{-- Session Tracker --}}
    <div class="fixed bottom-20 md:bottom-4 left-2 md:left-4 z-50 bg-amber-900/80 text-amber-100 p-2 md:p-4 rounded-xl shadow-2xl border-2 border-amber-600 backdrop-blur-md transition-all hover:bg-amber-900/90 flex flex-row md:flex-col items-center md:items-stretch gap-3 md:gap-0"
         x-data="{ 
            startTime: {{ $sessionStartTime }},
            elapsed: '00:00:00',
            updateTime() {
                let now = Math.floor(Date.now() / 1000);
                let diff = now - this.startTime;
                let h = Math.floor(diff / 3600).toString().padStart(2, '0');
                let m = Math.floor((diff % 3600) / 60).toString().padStart(2, '0');
                let s = Math.floor(diff % 60).toString().padStart(2, '0');
                this.elapsed = `${h}:${m}:${s}`;
            }
         }"
         x-init="updateTime(); setInterval(() => updateTime(), 1000)">
        <h4 class="hidden md:block font-bold medieval-font text-lg mb-2 text-amber-300 drop-shadow-md border-b border-amber-700/50 pb-1">📊 Statystyki Sesji</h4>
        <div class="flex items-center justify-between text-xs md:text-sm md:mb-1">
            <span class="text-amber-200">⚔️ <span class="hidden md:inline">Pokonani:</span></span>
            <span class="font-bold text-white drop-shadow-md text-sm md:text-base ml-1 md:ml-4">{{ $sessionMonstersDefeated }}</span>
        </div>
        <div class="flex items-center justify-between text-xs md:text-sm">
            <span class="text-amber-200">⏱️ <span class="hidden md:inline">Czas:</span></span>
            <span class="font-bold text-white drop-shadow-md font-mono ml-1 md:ml-4" x-text="elapsed"></span>
        </div>
    </div>
</div>
