<div id="arena-combat-component" class="min-h-screen relative overflow-hidden" x-data="{ travelingTo: null }" wire:init="startPlayback">
    {{-- Dynamic background --}}
    <div class="absolute inset-0 bg-cover bg-center bg-no-repeat" style="background-image: url('{{ asset('img/maps/shadow-mountains.png') }}');">
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
                <div class="text-6xl mb-4 animate-bounce" x-text="$data.travelingTo === 'Gildia' ? '🚩' : '⚔️'"></div>
                <h2 class="text-3xl font-bold text-amber-100 medieval-font mb-4 drop-shadow-lg">
                    Powrót do...
                </h2>
                <h3 class="text-2xl text-amber-300 font-bold drop-shadow-md mb-6" x-text="$data.travelingTo"></h3>
                
                <div class="w-12 h-12 border-4 border-amber-500 border-t-transparent rounded-full animate-spin"></div>
            </div>
         </div>
    </div>

    {{-- Error message --}}
    @error('battle')
        <div class="absolute top-16 left-1/2 transform -translate-x-1/2 z-50">
            <div class="bg-red-100 border-2 border-red-600 rounded-lg px-4 py-2 shadow-lg">
                <p class="text-red-800 font-semibold text-sm">{{ $message }}</p>
            </div>
        </div>
    @enderror

    <div class="relative z-10 container mx-auto px-4 py-6 min-h-screen">
        {{-- Header with navigation --}}
        <div class="flex flex-col md:flex-row items-center md:justify-between mb-6 gap-4 text-center md:text-left">
            <h1 class="text-2xl md:text-3xl font-bold text-amber-100 medieval-font drop-shadow-2xl">
                ⚔️ Odtwarzacz Walki {{ $type === 'pvp' ? 'PvP' : 'GvG' }}
            </h1>

            <div class="flex flex-col sm:flex-row items-center space-y-3 sm:space-y-0 sm:space-x-3">
                {{-- Character info --}}
                <div class="text-amber-100 text-sm medieval-font sm:mr-4">
                    <div>{{ $character->name }}</div>
                </div>

                @if($type === 'pvp')
                    <button @click="travelingTo = 'Arena'; setTimeout(() => $wire.backToArena(), 500)"
                        class="relative rounded-lg px-4 py-2 shadow-lg overflow-hidden group">
                        <img src="{{ asset('img/avatars/plate.png') }}" alt=""
                            class="absolute inset-0 w-full h-full object-cover rounded-lg">
                        <div class="absolute inset-0 bg-amber-900/20 rounded-lg"></div>
                        <span class="relative text-amber-100 font-bold medieval-font drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)]">
                            🏟️ Powrót na Arenę
                        </span>
                    </button>
                @else
                    <button @click="travelingTo = 'Gildia'; setTimeout(() => $wire.backToGuild(), 500)"
                        class="relative rounded-lg px-4 py-2 shadow-lg overflow-hidden group">
                        <img src="{{ asset('img/avatars/plate.png') }}" alt=""
                            class="absolute inset-0 w-full h-full object-cover rounded-lg">
                        <div class="absolute inset-0 bg-amber-900/20 rounded-lg"></div>
                        <span class="relative text-amber-100 font-bold medieval-font drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)]">
                            🚩 Powrót do Gildii
                        </span>
                    </button>
                @endif
            </div>
        </div>

        {{-- Classic RPG Battle Layout --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4 lg:gap-8 max-w-7xl mx-auto pb-24 lg:pb-6">

            {{-- Left Side - Player Panel --}}
            <div class="col-span-1 md:col-span-1 lg:col-span-1 order-2 lg:order-1" id="player-panel-container">
                <div id="player-panel"
                    class="relative rounded-xl shadow-2xl overflow-hidden {{ $this->isPlayerTurn() ? 'ring-4 ring-amber-300 shadow-[0_0_30px_rgba(255,200,60,.4)]' : '' }}">
                    <img src="{{ asset('img/avatars/plate.png') }}" alt="" class="absolute inset-0 w-full h-full object-cover">
                    <div class="absolute inset-0 bg-amber-900/25"></div>

                    <div class="relative p-3 lg:p-6 space-y-2 lg:space-y-4">
                        <div class="text-center">
                            <div class="w-16 h-16 sm:w-20 sm:h-20 lg:w-28 lg:h-28 mx-auto rounded-xl overflow-hidden ring-4 ring-amber-800/80 shadow-xl">
                                @if (!empty($player) && $player['avatar'])
                                    <img src="{{ $player['avatar'] }}" alt="{{ $player['name'] }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full bg-gradient-to-b from-amber-200 to-amber-300 flex items-center justify-center text-4xl text-amber-800">👤</div>
                                @endif
                            </div>
                            <h3 class="mt-2 lg:mt-3 text-base lg:text-xl font-bold text-amber-100 medieval-font drop-shadow-[0_2px_4px_rgba(0,0,0,0.8)] leading-tight">
                                {{ $player['name'] ?? '?' }}
                            </h3>
                            <p class="text-[10px] lg:text-sm text-amber-200 tracking-wide drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)]">
                                Poziom {{ $player['level'] ?? '?' }}
                            </p>
                        </div>

                        @if (!empty($player))
                            @php $currentState = method_exists($this, 'getCurrentState') ? $this->getCurrentState() : null; @endphp
                            <div class="space-y-1 lg:space-y-2">
                                <div class="flex justify-between text-[10px] lg:text-sm font-semibold text-amber-100 drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)]">
                                    <span>❤️ Życie</span>
                                    <span>{{ $this->getCurrentPlayerHp() }}/{{ $player['maxHp'] }}</span>
                                </div>
                                <div class="h-4 w-full rounded-full bg-black/40 ring-2 ring-amber-800/60 shadow-inner">
                                    <div class="h-full rounded-full bg-gradient-to-r from-red-500 via-red-600 to-red-500 shadow-sm transition-all duration-500"
                                        style="width: {{ $this->getPlayerHpPercent() }}%"></div>
                                </div>
                            </div>
                            @if($currentState && !empty($currentState['buffs']))
                                <div class="flex flex-wrap gap-2 justify-center mt-2">
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
                        @endif
                    </div>
                </div>
            </div>

            {{-- Center - Parchment Battle Log --}}
            <div class="col-span-1 md:col-span-2 lg:col-span-1 order-1 lg:order-2 mb-4 lg:mb-0">
                <section class="relative rounded-2xl shadow-2xl overflow-hidden h-[400px] lg:h-[500px] flex flex-col">
                    <img src="{{ asset('img/avatars/plate.png') }}" alt="" class="absolute inset-0 w-full h-full object-cover">
                    <div class="absolute inset-0 bg-amber-100/80"></div>

                    <header class="relative p-4 text-center border-b-2 border-amber-800/30">
                        <h3 class="font-serif text-2xl text-amber-900 tracking-wider medieval-font drop-shadow-sm">
                            ⚔️ Przebieg Starcia
                        </h3>
                    </header>

                    <div class="relative flex-1 overflow-y-auto p-4" @if($isCalculating) wire:poll.500ms="checkCombatStatus" @endif>
                        @if($isCalculating)
                            <div class="h-full flex flex-col items-center justify-center text-center">
                                <div class="relative w-32 h-32 mb-6">
                                    <div class="absolute inset-0 flex items-center justify-center transform transition-transform duration-500 animate-[spin_2s_ease-in-out_infinite_alternate]">
                                        <div class="text-6xl drop-shadow-xl" style="transform: rotate(45deg);">⚔️</div>
                                    </div>
                                    <div class="absolute inset-0 rounded-full border-4 border-amber-600/30 border-t-amber-600 animate-spin"></div>
                                </div>
                                <h3 class="font-serif text-3xl text-amber-900 tracking-wider medieval-font drop-shadow-sm animate-pulse">
                                    Oczekiwanie na wynik...
                                </h3>
                            </div>
                        @else
                            <ul class="space-y-3 text-amber-900">
                                @if (empty($visibleTurns))
                                    @if ($isPlaying)
                                        <li class="text-center py-8 animate-pulse">
                                            <div class="text-4xl mb-3">⚔️</div>
                                            <div class="text-amber-800 font-serif italic text-lg">Przygotowanie do starcia...</div>
                                        </li>
                                    @else
                                        <li class="text-center py-8">
                                            <div class="text-4xl mb-3">⚔️</div>
                                            <div class="text-amber-800 font-serif italic text-lg">Pauza...</div>
                                        </li>
                                    @endif
                                @else
                                    @foreach ($visibleTurns as $index => $turn)
                                        <li class="leading-relaxed bg-white/30 rounded-lg px-3 py-2 shadow-sm">
                                            <span class="inline-block w-8 text-center text-xs font-bold bg-amber-800 text-amber-100 rounded px-1 mr-2">
                                                T{{ $index + 1 }}
                                            </span>
                                            @if ($turn['type'] == 'miss')
                                                <span class="text-slate-700 italic font-semibold">
                                                    <strong>{{ $turn['actor'] == 'player' ? $player['name'] : $enemy['name'] }}</strong>
                                                    pudłuje atak!
                                                    @if (!empty($turn['dotDamage']))
                                                        <span class="text-emerald-600 font-bold ml-1">(+{{ $turn['dotDamage'] }})</span>
                                                    @endif
                                                </span>
                                            @elseif ($turn['type'] == 'dot')
                                                <span class="text-purple-700 font-semibold italic">
                                                    Zadano <strong class="text-purple-900">{{ $turn['value'] }}</strong> obrażeń od statusów.
                                                </span>
                                            @elseif ($turn['type'] == 'skill')
                                                <span class="{{ $turn['actor'] == 'player' ? 'text-blue-800' : 'text-red-800' }} font-semibold">
                                                    <strong>{{ $turn['actor'] == 'player' ? $player['name'] : $enemy['name'] }}</strong>
                                                    używa <span class="text-indigo-600 font-bold uppercase">{{ $turn['skill_name'] }}</span> i zadaje <strong class="text-amber-900">{{ $turn['value'] }}</strong>
                                                    @if (!empty($turn['dotDamage']))
                                                        <span class="text-emerald-600 font-bold ml-1">(+{{ $turn['dotDamage'] }})</span>
                                                    @endif
                                                    obrażeń
                                                    @if ($turn['crit']) <span class="font-bold text-red-900">✨ KRYTYK!</span> @endif
                                                </span>
                                            @else
                                                <span class="{{ $turn['actor'] == 'player' ? 'text-emerald-800' : 'text-red-800' }} font-semibold">
                                                    <strong>{{ $turn['actor'] == 'player' ? $player['name'] : $enemy['name'] }}</strong>
                                                    zadaje <strong class="text-amber-900">{{ $turn['value'] }}</strong>
                                                    @if (!empty($turn['dotDamage']))
                                                        <span class="text-emerald-600 font-bold ml-1">(+{{ $turn['dotDamage'] }})</span>
                                                    @endif
                                                    obrażeń
                                                    @if ($turn['crit']) <span class="font-bold text-red-900">✨ KRYTYK!</span> @endif
                                                </span>
                                            @endif
                                        </li>
                                    @endforeach
                                @endif

                                    @if ($battleCompleted)
                                        <li class="text-center mt-6 p-4 rounded-xl {{ $result == 'win' ? 'bg-green-200/90 border-2 border-green-600 text-green-800' : 'bg-red-200/90 border-2 border-red-600 text-red-800' }} shadow-lg">
                                            <div class="text-4xl mb-2">{{ $result == 'win' ? '🏆' : '💀' }}</div>
                                            <div class="text-2xl font-bold medieval-font">
                                                {{ $result == 'win' ? 'ZWYCIĘSTWO!' : 'PORAŻKA!' }}
                                            </div>
                                            
                                            @if($type === 'pvp')
                                                <div class="mt-4 p-3 bg-amber-100/90 border-2 border-amber-600 rounded-lg text-left">
                                                    <h4 class="font-bold text-amber-900 mb-2">🎁 Podsumowanie PvP:</h4>
                                                    <div class="space-y-1 text-sm">
                                                        <div class="flex items-center space-x-2">
                                                            <span class="text-blue-600">📈</span>
                                                            <span class="text-amber-800 font-bold">
                                                                Ranking ELO: {{ $eloChange > 0 ? '+'.$eloChange : $eloChange }}
                                                            </span>
                                                        </div>
                                                        <div class="flex items-center space-x-2">
                                                            <span class="text-yellow-600">🪙</span>
                                                            <span class="text-amber-800 font-bold">
                                                                Żetony Areny: +{{ $tokensReward }}
                                                            </span>
                                                        </div>
                                                    </div>
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
                            <div class="flex items-center justify-center gap-3">
                                @if (!$battleCompleted && !$isCalculating)
                                    <button wire:click="togglePlayback"
                                        class="relative rounded-lg px-6 py-3 shadow-lg overflow-hidden">
                                        <img src="{{ asset('img/avatars/plate.png') }}" alt="" class="absolute inset-0 w-full h-full object-cover">
                                        <div class="absolute inset-0 bg-emerald-800/40"></div>
                                        <span class="relative text-white font-bold medieval-font drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)]">
                                            {{ $isPlaying ? '⏸️ Pauza' : '▶️ Odtwarzaj' }}
                                        </span>
                                    </button>
                                @endif

                                @if (!empty($visibleTurns))
                                    <div class="flex gap-2">
                                        <button wire:click="setPlaybackSpeed(1)" class="relative rounded-lg px-4 py-2 shadow-lg overflow-hidden">
                                            <img src="{{ asset('img/avatars/plate.png') }}" alt="" class="absolute inset-0 w-full h-full object-cover">
                                            <div class="absolute inset-0 {{ $playbackSpeed == 1 ? 'bg-amber-700/60' : 'bg-amber-900/40' }}"></div>
                                            <span class="relative {{ $playbackSpeed == 1 ? 'text-amber-100' : 'text-amber-200' }} font-bold medieval-font drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)]">⏯️ x1</span>
                                        </button>
                                        <button wire:click="setPlaybackSpeed(2)" class="relative rounded-lg px-4 py-2 shadow-lg overflow-hidden">
                                            <img src="{{ asset('img/avatars/plate.png') }}" alt="" class="absolute inset-0 w-full h-full object-cover">
                                            <div class="absolute inset-0 {{ $playbackSpeed == 2 ? 'bg-amber-700/60' : 'bg-amber-900/40' }}"></div>
                                            <span class="relative {{ $playbackSpeed == 2 ? 'text-amber-100' : 'text-amber-200' }} font-bold medieval-font drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)]">⏩ x2</span>
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </footer>
                </section>
            </div>

            {{-- Right Side - Enemy Panel --}}
            <div class="col-span-1 md:col-span-1 lg:col-span-1 order-3 lg:order-3" id="enemy-panel-container">
                <div id="enemy-panel"
                    class="relative rounded-xl shadow-2xl overflow-hidden {{ $this->isEnemyTurn() ? 'ring-4 ring-red-300 shadow-[0_0_30px_rgba(255,100,100,.4)]' : '' }}">
                    <img src="{{ asset('img/avatars/plate.png') }}" alt="" class="absolute inset-0 w-full h-full object-cover">
                    <div class="absolute inset-0 bg-red-900/20"></div>

                    <div class="relative p-3 lg:p-6 space-y-2 lg:space-y-4">
                        <div class="text-center">
                            {{-- WIDMO GRACZA EFEKT --}}
                            <div class="w-16 h-16 sm:w-20 sm:h-20 lg:w-28 lg:h-28 mx-auto rounded-xl overflow-hidden ring-4 ring-red-800/80 shadow-xl bg-black">
                                <img src="{{ $enemy['avatar'] ?? asset('img/avatars/default.png') }}"
                                    alt="{{ $enemy['name'] ?? '?' }}"
                                    class="w-full h-full object-cover opacity-70 mix-blend-luminosity filter sepia hue-rotate-180 drop-shadow-[0_0_15px_rgba(255,50,50,0.8)]">
                            </div>
                            <h3 class="mt-2 lg:mt-3 text-base lg:text-xl font-bold text-red-200 medieval-font drop-shadow-[0_2px_4px_rgba(0,0,0,0.8)] leading-tight">
                                {{ $enemy['name'] ?? '?' }}
                            </h3>
                            <p class="text-[10px] lg:text-sm text-red-300 tracking-wide drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)]">
                                Poziom {{ $enemy['level'] ?? '?' }}
                            </p>
                        </div>

                        @if (!empty($enemy))
                            <div class="space-y-1 lg:space-y-2">
                                <div class="flex justify-between text-[10px] lg:text-sm font-semibold text-amber-100 drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)]">
                                    <span>❤️ Życie</span>
                                    <span>{{ $this->getCurrentEnemyHp() }}/{{ $enemy['maxHp'] }}</span>
                                </div>
                                <div class="h-4 w-full rounded-full bg-black/40 ring-2 ring-red-800/60 shadow-inner">
                                    <div class="h-full rounded-full bg-gradient-to-r from-red-500 via-red-600 to-red-500 shadow-sm transition-all duration-500"
                                        style="width: {{ $this->getEnemyHpPercent() }}%"></div>
                                </div>
                            </div>
                            @if(isset($currentState) && $currentState && !empty($currentState['dots']))
                                <div class="flex flex-wrap gap-2 justify-center mt-2">
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
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('livewire:initialized', () => {
            let playbackTimeout;

            Livewire.on('start-playback', (data) => {
                clearTimeout(playbackTimeout);
                const speed = data.speed || (data[0] && data[0].speed) || 1;
                playNextTurn(speed);
            });

            Livewire.on('stop-playback', () => {
                clearTimeout(playbackTimeout);
            });

            Livewire.on('update-playback-speed', (data) => {
                clearTimeout(playbackTimeout);
                const speed = data.speed || (data[0] && data[0].speed) || 1;
                playNextTurn(speed);
            });

            Livewire.on('turn-played', (event) => {
                const data = (event && event[0]) ? event[0] : event;
                const actor = data.actor;
                const type = data.type;
                const dotDamage = data.dotDamage || 0;
                const isPlayer = actor === 'player';
                const playerPanel = document.getElementById('player-panel');
                const enemyPanel = document.getElementById('enemy-panel');
                
                if (isPlayer) {
                    playerPanel.classList.add('anim-attack-player');
                    setTimeout(() => playerPanel.classList.remove('anim-attack-player'), 300);
                    
                    if (type !== 'miss' || dotDamage > 0) {
                        setTimeout(() => {
                            enemyPanel.classList.add('anim-damage');
                            setTimeout(() => enemyPanel.classList.remove('anim-damage'), 400);
                        }, 150);
                    }
                } else {
                    enemyPanel.classList.add('anim-attack-enemy');
                    setTimeout(() => enemyPanel.classList.remove('anim-attack-enemy'), 300);
                    
                    if (type !== 'miss' || dotDamage > 0) {
                        setTimeout(() => {
                            playerPanel.classList.add('anim-damage');
                            setTimeout(() => playerPanel.classList.remove('anim-damage'), 400);
                        }, 150);
                    }
                }
            });

            function playNextTurn(speed) {
                const ms = speed === 2 ? 800 : 1500;
                playbackTimeout = setTimeout(() => {
                    @this.nextTurn();
                    playNextTurn(speed);
                }, ms);
            }
        });
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&display=swap');
        .medieval-font { font-family: 'Cinzel', serif; }
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
        .anim-attack-player { animation: bounce-attack-right 300ms cubic-bezier(0.175, 0.885, 0.32, 1.275); z-index: 50; }
        .anim-attack-enemy { animation: bounce-attack-left 300ms cubic-bezier(0.175, 0.885, 0.32, 1.275); z-index: 50; }
        .anim-damage { animation: damage-shake 400ms cubic-bezier(.36,.07,.19,.97) both; }
    </style>
</div>
