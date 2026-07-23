<div id="adventure-map-component" class="min-h-screen relative overflow-hidden" x-data="{ travelingTo: null }">
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
            <livewire:global.tutorial-overlay :step="13" :rewardItemTemplateId="'01k4jpx94j70x2vv10b835hlm1'" />
        @endif

        {{-- Header with navigation --}}
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-3xl font-bold text-amber-100 medieval-font drop-shadow-2xl">
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

        {{-- Classic RPG Battle Layout (Expanded ~30% larger on Desktop) --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8 max-w-[1700px] w-full mx-auto">

            {{-- Left Side - Player Panel --}}
            <div class="col-span-1 lg:col-span-1 order-2 lg:order-1" id="player-panel-container">
                <div id="player-panel"
                    class="relative rounded-2xl shadow-2xl overflow-hidden bg-slate-950/80 backdrop-blur-xl border border-amber-500/30 transition-all duration-300 {{ $this->isPlayerTurn() ? 'ring-2 ring-amber-400/80 shadow-[0_0_35px_rgba(245,158,11,0.4)] scale-[1.01]' : '' }}">
                    
                    {{-- Glossy Inner Ambient Glow --}}
                    <div class="absolute inset-0 bg-gradient-to-b from-amber-500/10 via-transparent to-black/70 pointer-events-none"></div>

                    <div class="relative p-4 lg:p-7 space-y-4 lg:space-y-6">
                        {{-- Player Header & Avatar --}}
                        <div class="text-center">
                            <div class="relative w-24 h-24 sm:w-28 sm:h-28 lg:w-32 lg:h-32 xl:w-36 xl:h-36 mx-auto">
                                <div class="w-full h-full rounded-2xl overflow-hidden ring-4 ring-amber-500/80 shadow-[0_0_25px_rgba(245,158,11,0.35)] bg-slate-900">
                                    @if (!empty($player) && !empty($player['avatar']))
                                        <img src="{{ $player['avatar'] }}" alt="{{ $player['name'] }}"
                                            class="w-full h-full object-cover">
                                    @else
                                        <img src="{{ $character->avatar ? asset('img/avatars/' . $character->avatar . '.png') : asset('img/avatars/default.png') }}" alt="{{ $character->name }}"
                                            class="w-full h-full object-cover">
                                    @endif
                                </div>
                                <span class="absolute -bottom-2.5 left-1/2 -translate-x-1/2 bg-gradient-to-r from-amber-600 to-amber-500 text-amber-950 text-xs sm:text-sm font-black px-3 py-0.5 rounded-full border border-amber-300 shadow-lg medieval-font">
                                    Lvl {{ $character->level }}
                                </span>
                            </div>

                            {{-- Player Name --}}
                            <h3 class="mt-4 text-lg lg:text-2xl xl:text-3xl font-extrabold text-amber-200 medieval-font drop-shadow-[0_2px_6px_rgba(0,0,0,0.9)] tracking-wide">
                                {{ !empty($player) ? $player['name'] : $character->name }}
                            </h3>
                            <p class="text-xs lg:text-sm text-amber-400/80 tracking-wider">
                                {{ $character->class ?? 'Bohater' }}
                            </p>
                        </div>

                        {{-- Player HP Bar (Always loaded!) --}}
                        <div class="space-y-2">
                            <div class="flex justify-between text-xs lg:text-sm font-bold text-amber-200 medieval-font drop-shadow">
                                <span>Życie</span>
                                <span class="font-mono text-emerald-300 text-sm lg:text-base">{{ $this->getCurrentPlayerHp() }}/{{ $this->player['maxHp'] ?? $character->getMaxHp() }}</span>
                            </div>
                            <div class="h-5 w-full rounded-full bg-black/80 ring-1 ring-amber-500/40 p-0.5 shadow-inner">
                                <div class="h-full rounded-full bg-gradient-to-r from-emerald-600 via-emerald-500 to-green-400 shadow-[0_0_12px_rgba(16,185,129,0.6)] transition-all duration-500"
                                    style="width: {{ $this->getPlayerHpPercent() }}%"></div>
                            </div>
                        </div>

                        {{-- Active Buffs --}}
                        @php $currentState = method_exists($this, 'getCurrentState') ? $this->getCurrentState() : null; @endphp
                        @if($currentState && !empty($currentState['buffs']))
                            <div class="flex flex-wrap gap-2 justify-center">
                                @foreach($currentState['buffs'] as $key => $buff)
                                    <div class="bg-blue-950/80 border border-blue-400/60 rounded-xl px-2.5 py-1 text-xs text-blue-200 flex items-center gap-1.5 shadow-md" title="Wzmocnienie">
                                        <span class="font-bold font-mono">{{ $buff['duration'] }}T</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        {{-- Equipped Active Skills --}}
                        @if($this->equippedSkills && count($this->equippedSkills) > 0)
                            <div class="bg-black/60 rounded-2xl p-2.5 border border-amber-500/20 shadow-inner">
                                <div class="flex gap-2.5 justify-center">
                                    @foreach($this->equippedSkills as $cs)
                                        @php $cd = $currentState['cooldowns'][$cs->id] ?? 0; @endphp
                                        <div class="relative w-11 h-11 sm:w-12 sm:h-12 lg:w-14 lg:h-14 rounded-xl border {{ $cd > 0 ? 'border-slate-700 bg-slate-900' : 'border-amber-500/80 bg-amber-950/80 shadow-[0_0_12px_rgba(245,158,11,0.4)]' }} flex items-center justify-center overflow-hidden transition-transform hover:scale-105" title="{{ $cs->skill->name }}">
                                            @if($cd > 0)
                                                <div class="absolute inset-0 bg-black/80 flex items-center justify-center z-10">
                                                    <span class="text-white font-extrabold text-sm sm:text-base drop-shadow font-mono">{{ $cd }}</span>
                                                </div>
                                            @endif
                                            @if($cs->skill->icon)
                                                <img src="{{ route('assets.skills.icons', ['filename' => $cs->skill->icon]) }}" class="w-full h-full object-contain p-1 {{ $cd > 0 ? 'opacity-30' : 'opacity-100' }}" alt="{{ $cs->skill->name }}">
                                            @else
                                                <span class="text-xs font-bold text-amber-200 {{ $cd > 0 ? 'opacity-40' : 'opacity-100' }}">{{ mb_substr($cs->skill->name, 0, 3) }}</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- XP Progress Bar --}}
                        <div class="space-y-1.5">
                            <div class="flex justify-between text-xs lg:text-sm font-semibold text-indigo-200">
                                <span>Doświadczenie</span>
                                <span class="font-mono text-indigo-300">{{ $character->xp }}/{{ $this->getXpToNextLevel() }}</span>
                            </div>
                            <div class="h-3 w-full rounded-full bg-indigo-950/70 ring-1 ring-indigo-700/40 p-0.5">
                                <div class="h-full rounded-full bg-gradient-to-r from-indigo-600 via-indigo-500 to-purple-400 shadow-[0_0_10px_rgba(99,102,241,0.5)] transition-all duration-500"
                                    style="width: {{ $this->getXpPercentage() }}%"></div>
                            </div>
                        </div>

                        {{-- Player Attributes Grid (Loaded immediately!) --}}
                        <div>
                            <h4 class="text-xs lg:text-sm font-bold text-amber-200/90 mb-2.5 medieval-font tracking-wide">
                                Atrybuty Bojowe
                            </h4>
                            @php
                                $pStats = $this->player['stats'] ?? $character->getTotalAttributes();
                            @endphp
                            <div class="grid grid-cols-2 gap-2.5">
                                <div class="bg-slate-900/90 border border-red-800/40 rounded-2xl p-2.5 lg:p-3.5 text-center shadow-md">
                                    <div class="text-xs font-semibold text-red-300 tracking-wider">STR (Siła)</div>
                                    <div class="text-base lg:text-xl xl:text-2xl font-black text-amber-100 font-mono">{{ $pStats['str'] ?? 0 }}</div>
                                </div>
                                <div class="bg-slate-900/90 border border-blue-800/40 rounded-2xl p-2.5 lg:p-3.5 text-center shadow-md">
                                    <div class="text-xs font-semibold text-blue-300 tracking-wider">INT (Wiedza)</div>
                                    <div class="text-base lg:text-xl xl:text-2xl font-black text-amber-100 font-mono">{{ $pStats['int'] ?? 0 }}</div>
                                </div>
                                <div class="bg-slate-900/90 border border-emerald-800/40 rounded-2xl p-2.5 lg:p-3.5 text-center shadow-md">
                                    <div class="text-xs font-semibold text-emerald-300 tracking-wider">VIT (Witalność)</div>
                                    <div class="text-base lg:text-xl xl:text-2xl font-black text-amber-100 font-mono">{{ $pStats['vit'] ?? 0 }}</div>
                                </div>
                                <div class="bg-slate-900/90 border border-amber-800/40 rounded-2xl p-2.5 lg:p-3.5 text-center shadow-md">
                                    <div class="text-xs font-semibold text-amber-300 tracking-wider">AGI (Zręczność)</div>
                                    <div class="text-base lg:text-xl xl:text-2xl font-black text-amber-100 font-mono">{{ $pStats['agi'] ?? 0 }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Center - Glossy Battle Log --}}
            <div class="col-span-2 lg:col-span-1 order-1 lg:order-2 mb-4 lg:mb-0">
                <section class="relative rounded-2xl shadow-2xl overflow-hidden bg-slate-950/80 backdrop-blur-xl border border-amber-500/30 h-[480px] lg:h-[620px] xl:h-[680px] flex flex-col">
                    
                    {{-- Header --}}
                    <header class="relative p-4 text-center bg-amber-950/40 border-b border-amber-500/20 backdrop-blur-md">
                        <h3 class="font-serif text-2xl lg:text-3xl text-amber-200 tracking-wider medieval-font drop-shadow">
                            Kronika Bitwy
                        </h3>
                    </header>

                    {{-- Battle Log Scroll Area --}}
                    <div class="relative flex-1 overflow-y-auto p-4 lg:p-5" @if($isCalculating) wire:poll.500ms="checkCombatStatus" @endif>
                        {{-- Loading Overlay during startBattle --}}
                        <div wire:loading.flex wire:target="startBattle" class="absolute inset-0 z-20 flex-col items-center justify-center bg-slate-950/90 backdrop-blur-md text-center">
                            <div class="relative w-24 h-24 mb-4">
                                <div class="absolute inset-0 rounded-full border-4 border-amber-500/30 border-t-amber-400 animate-[spin_1s_linear_infinite]"></div>
                            </div>
                            <h3 class="font-serif text-2xl text-amber-200 tracking-wider medieval-font drop-shadow animate-pulse">
                                Szukanie przeciwnika...
                            </h3>
                        </div>

                        @if($isCalculating)
                            <div class="h-full flex flex-col items-center justify-center text-center">
                                <div class="relative w-32 h-32 mb-6">
                                    <div class="absolute inset-0 rounded-full border-4 border-amber-500/30 border-t-amber-400 animate-spin"></div>
                                    <div class="absolute inset-2 rounded-full border-4 border-amber-700/30 border-b-amber-600 animate-[spin_1.5s_linear_infinite_reverse]"></div>
                                </div>
                                <h3 class="font-serif text-3xl text-amber-200 tracking-wider medieval-font drop-shadow animate-pulse">
                                    Obliczanie walki...
                                </h3>
                                <p class="text-amber-300/80 italic mt-3 font-semibold">Krzyżowanie mieczy...</p>
                            </div>
                        @else
                            <ul class="space-y-2.5 text-amber-100">
                                @if (empty($visibleTurns))
                                    @if ($isPlaying)
                                        <li class="text-center py-12 animate-pulse">
                                            <div class="text-amber-300/80 font-serif italic text-xl">
                                                Rozpoczynanie bitwy...
                                            </div>
                                        </li>
                                    @elseif (!$battleCompleted)
                                        <li class="text-center py-12">
                                            <div class="text-amber-300/80 font-serif italic text-xl">
                                                Naciśnij "Rozpocznij Walkę" aby rozpocząć przygodę...
                                            </div>
                                        </li>
                                    @endif
                            @else
                                @foreach ($visibleTurns as $index => $turn)
                                    <li class="leading-relaxed bg-slate-900/70 border border-amber-500/20 rounded-xl px-3.5 py-2.5 lg:px-4 lg:py-3 shadow-sm backdrop-blur-sm text-sm lg:text-base">
                                        <span
                                            class="inline-block w-9 text-center text-xs lg:text-sm font-bold bg-amber-900/80 text-amber-200 rounded-md border border-amber-600/40 px-1.5 py-0.5 mr-2 font-mono">
                                            T{{ $index + 1 }}
                                        </span>
                                        @if ($turn['type'] == 'miss')
                                            <span class="text-slate-300 italic font-semibold">
                                                <strong class="text-amber-200">{{ $turn['actor'] == 'player' ? $player['name'] : $enemy['name'] }}</strong>
                                                pudłuje atak!
                                            </span>
                                        @elseif ($turn['type'] == 'dot')
                                            <span class="text-purple-300 font-semibold italic">
                                                Zadano <strong class="text-purple-200 font-mono">{{ $turn['value'] }}</strong> obrażeń od statusów.
                                            </span>
                                        @elseif ($turn['type'] == 'skill')
                                            <span class="{{ $turn['actor'] == 'player' ? 'text-blue-300' : 'text-red-300' }} font-semibold">
                                                <strong class="text-amber-200">{{ $turn['actor'] == 'player' ? $player['name'] : $enemy['name'] }}</strong>
                                                używa <span class="text-indigo-300 font-bold uppercase">{{ $turn['skill_name'] }}</span> i zadaje <strong class="text-amber-300 font-mono">{{ $turn['value'] }}</strong> obrażeń
                                                @if (!empty($turn['crit'])) <span class="font-bold text-amber-400 drop-shadow-[0_0_10px_rgba(245,158,11,0.8)]">KRYTYK!</span> @endif
                                            </span>
                                        @else
                                            <span
                                                class="{{ $turn['actor'] == 'player' ? 'text-emerald-300' : 'text-rose-300' }} font-semibold">
                                                <strong class="text-amber-200">{{ $turn['actor'] == 'player' ? $player['name'] : $enemy['name'] }}</strong>
                                                zadaje 
                                                @if($turn['actor'] == 'player' && isset($turn['bonusDamage']) && $turn['bonusDamage'] > 0)
                                                    <strong class="text-amber-300 font-mono">{{ $turn['baseDamage'] }} (+{{ $turn['bonusDamage'] }})</strong>
                                                @elseif($turn['actor'] == 'enemy' && isset($turn['resistDamage']) && $turn['resistDamage'] > 0)
                                                    <strong class="text-amber-300 font-mono">{{ $turn['baseDamage'] }} (-{{ $turn['resistDamage'] }})</strong>
                                                @else
                                                    <strong class="text-amber-300 font-mono">{{ $turn['value'] }}</strong>
                                                @endif
                                                obrażeń
                                                @if ($turn['crit'])
                                                    <span class="font-bold text-amber-400 drop-shadow-[0_0_10px_rgba(245,158,11,0.8)]">KRYTYK!</span>
                                                @endif
                                            </span>
                                        @endif
                                    </li>
                                @endforeach

                                {{-- Battle Result & Rewards --}}
                                @if ($battleCompleted)
                                    <li
                                        class="text-center mt-6 p-5 rounded-2xl {{ $result == 'win' ? 'bg-emerald-950/80 border border-emerald-500/40 text-emerald-200 shadow-[0_0_25px_rgba(16,185,129,0.2)]' : ($result == 'finished' ? 'bg-purple-950/80 border border-purple-500/40 text-purple-200' : 'bg-red-950/80 border border-red-500/40 text-red-200') }} backdrop-blur-md">
                                        <div class="text-2xl font-bold medieval-font tracking-wide">
                                            {{ $result == 'win' ? 'TRIUMF!' : ($result == 'finished' ? 'WALKA ZAKOŃCZONA' : 'KLĘSKA!') }}
                                        </div>
                                        @if ($result == 'win' || $result == 'finished')
                                            @if($result == 'finished')
                                                <div class="text-lg mt-2 font-bold text-purple-200">
                                                    Łączny dmg: <span class="text-red-400 font-mono drop-shadow-md">{{ number_format($damageDealt) }}</span>
                                                </div>
                                            @endif

                                            {{-- Loot Display --}}
                                            @if (!empty($drops))
                                                <div class="mt-4 p-4 bg-slate-900/90 border border-amber-500/30 rounded-xl text-left">
                                                    <h4 class="font-bold text-amber-300 mb-2.5 medieval-font text-center text-base">Zdobycz Bitewna:</h4>
                                                    <div class="space-y-2 text-sm lg:text-base">
                                                        <div class="flex items-center space-x-2">
                                                            <span class="text-indigo-200">
                                                                +{{ !empty($xpData) ? $xpData['base'] : $xpGained }} XP (Doświadczenie)
                                                                @if (!empty($xpData) && isset($xpData['multiplier']) && $xpData['multiplier'] > 1.0)
                                                                    <span class="font-bold text-xs text-emerald-400">(+{{ $xpData['bonus'] }} z bonusu {{ round(($xpData['multiplier'] - 1) * 100) }}%)</span>
                                                                @endif
                                                            </span>
                                                        </div>

                                                        <div class="flex items-center space-x-2">
                                                            <span class="text-amber-200">
                                                                +{{ !empty($goldData) ? $goldData['base'] : $goldGained }} Złota
                                                                @if (!empty($goldData) && isset($goldData['multiplier']) && $goldData['multiplier'] > 1.0)
                                                                    <span class="font-bold text-xs text-yellow-400">(+{{ $goldData['bonus'] }} z bonusu {{ round(($goldData['multiplier'] - 1) * 100) }}%)</span>
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
                                                    </div>
                                                </div>
                                            @endif
                                        @else
                                            <div class="text-sm mt-1 font-semibold text-red-300">
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
                    <footer class="relative p-4 bg-amber-950/40 border-t border-amber-500/20 backdrop-blur-md"
                            x-data="{ 
                                isPaused: false, 
                                speed: 1, 
                                autoChain: {{ $autoChain ? 'true' : 'false' }} 
                            }">
                        <div class="flex flex-col gap-3">
                            {{-- Main Controls --}}
                            <div class="flex items-center justify-center gap-3">
                                @if ($isCalculating)
                                    <button wire:click="cancelBattle"
                                        class="rounded-xl px-6 py-3 bg-red-950/80 border border-red-500/40 text-red-200 font-bold hover:bg-red-900/80 transition-all medieval-font shadow-lg"
                                        wire:loading.attr="disabled"
                                        wire:target="cancelBattle">
                                        <span wire:loading.remove wire:target="cancelBattle">Przerwij walkę</span>
                                        <span wire:loading wire:target="cancelBattle">Przerywanie...</span>
                                    </button>
                                @elseif (empty($visibleTurns) && !$isPlaying && !$battleCompleted)
                                    <button wire:click="startBattle"
                                        class="rounded-xl px-7 py-3 bg-gradient-to-r from-emerald-700 via-emerald-600 to-green-600 border border-emerald-400/60 text-white font-extrabold text-base sm:text-lg medieval-font shadow-[0_0_20px_rgba(16,185,129,0.4)] hover:scale-105 active:scale-95 transition-all">
                                        Rozpocznij Walkę
                                    </button>
                                @elseif (!$battleCompleted)
                                    <button @click="isPaused = !isPaused; window.toggleCombatPlayback(isPaused)"
                                        class="rounded-xl px-6 py-3 bg-amber-900/70 border border-amber-500/50 text-amber-100 font-bold hover:bg-amber-800/80 active:scale-95 transition-all medieval-font shadow-md text-base">
                                        <span x-text="isPaused ? 'Wznów' : 'Pauza'"></span>
                                    </button>
                                @endif

                                {{-- Speed Controls --}}
                                @if (!empty($visibleTurns))
                                    <div class="flex gap-2">
                                        <button @click="speed = 1; window.setCombatSpeed(1)"
                                            :class="speed === 1 ? 'bg-amber-600/90 border-amber-300 text-white shadow-[0_0_12px_rgba(245,158,11,0.5)] scale-105' : 'bg-slate-900/80 border-slate-700 text-amber-200/70 hover:bg-slate-800'"
                                            class="rounded-xl px-4 py-2 text-sm font-bold medieval-font border transition-all">
                                            x1
                                        </button>
                                        <button @click="speed = 2; window.setCombatSpeed(2)"
                                            :class="speed === 2 ? 'bg-amber-600/90 border-amber-300 text-white shadow-[0_0_12px_rgba(245,158,11,0.5)] scale-105' : 'bg-slate-900/80 border-slate-700 text-amber-200/70 hover:bg-slate-800'"
                                            class="rounded-xl px-4 py-2 text-sm font-bold medieval-font border transition-all">
                                            x2
                                        </button>
                                    </div>
                                @endif

                                {{-- Reset Battle --}}
                                @if ($battleCompleted && !$isWorldBoss)
                                    <button wire:click="resetEncounter"
                                        class="rounded-xl px-6 py-3 bg-gradient-to-r from-amber-700 to-amber-600 border border-amber-400/60 text-white font-bold medieval-font shadow-lg hover:scale-105 active:scale-95 transition-all text-base">
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
                                        class="rounded-xl px-5 py-2 text-xs sm:text-sm font-bold medieval-font border transition-all active:scale-95 hover:brightness-110 {{ $gameStage <= 12 ? 'opacity-50 cursor-not-allowed' : '' }}"
                                        {{ $gameStage <= 12 ? 'disabled' : '' }}>
                                        <span x-text="'Auto: ' + (autoChain ? 'ON' : 'OFF')"></span>
                                    </button>
                                </div>
                            @endif
                        </div>
                    </footer>
                </section>
            </div>

            {{-- Right Side - Enemy Panel --}}
            <div class="col-span-1 lg:col-span-1 order-3 lg:order-3" id="enemy-panel-container">
                <div id="enemy-panel"
                    class="relative rounded-2xl shadow-2xl overflow-hidden bg-slate-950/80 backdrop-blur-xl border border-red-500/30 transition-all duration-300 {{ $this->isEnemyTurn() ? 'ring-2 ring-red-500/80 shadow-[0_0_35px_rgba(239,68,68,0.4)] scale-[1.01]' : '' }}">
                    
                    {{-- Glossy Inner Ambient Glow --}}
                    <div class="absolute inset-0 bg-gradient-to-b from-red-500/10 via-transparent to-black/70 pointer-events-none"></div>

                    <div class="relative p-4 lg:p-7 space-y-4 lg:space-y-6">
                        @if(!empty($enemy))
                            {{-- Enemy Header & Avatar --}}
                            <div class="text-center">
                                <div class="relative w-24 h-24 sm:w-28 sm:h-28 lg:w-32 lg:h-32 xl:w-36 xl:h-36 mx-auto">
                                    <div class="w-full h-full rounded-2xl overflow-hidden ring-4 ring-red-600/80 shadow-[0_0_25px_rgba(239,68,68,0.35)] bg-slate-900">
                                        @if(!empty($enemy['avatar']))
                                            <img src="{{ route('assets.monsters.avatars', ['filename' => $enemy['avatar']]) }}"
                                                alt="{{ $enemy['name'] }}"
                                                class="w-full h-full object-cover">
                                        @else
                                            <img src="{{ asset('img/monsters/placeholder.png') }}"
                                                alt="{{ $enemy['name'] }}"
                                                class="w-full h-full object-cover">
                                        @endif
                                    </div>
                                    <span class="absolute -bottom-2.5 left-1/2 -translate-x-1/2 bg-gradient-to-r from-red-700 to-rose-600 text-red-100 text-xs sm:text-sm font-black px-3 py-0.5 rounded-full border border-red-400 shadow-lg medieval-font">
                                        Lvl {{ $enemy['level'] }}
                                    </span>
                                </div>

                                {{-- Enemy Name --}}
                                <h3 class="mt-4 text-lg lg:text-2xl xl:text-3xl font-extrabold text-red-200 medieval-font drop-shadow-[0_2px_6px_rgba(0,0,0,0.9)] tracking-wide">
                                    {{ $enemy['name'] }}
                                </h3>
                                <p class="text-xs lg:text-sm text-red-400/80 tracking-wider">
                                    {{ $enemy['rank'] ?? 'Przeciwnik' }}
                                </p>
                            </div>

                            {{-- Enemy HP Bar --}}
                            <div class="space-y-2">
                                <div class="flex justify-between text-xs lg:text-sm font-bold text-red-200 medieval-font drop-shadow">
                                    <span>Życie Przeciwnika</span>
                                    <span class="font-mono text-red-300 text-sm lg:text-base">{{ $this->getCurrentEnemyHp() }}/{{ $enemy['maxHp'] }}</span>
                                </div>
                                <div class="h-5 w-full rounded-full bg-black/80 ring-1 ring-red-700/50 p-0.5 shadow-inner">
                                    <div class="h-full rounded-full bg-gradient-to-r from-red-700 via-red-500 to-rose-400 shadow-[0_0_12px_rgba(239,68,68,0.6)] transition-all duration-500"
                                        style="width: {{ $this->getEnemyHpPercent() }}%"></div>
                                </div>
                            </div>

                            {{-- Active DoTs --}}
                            @if(isset($currentState) && $currentState && !empty($currentState['dots']))
                                <div class="flex flex-wrap gap-2 justify-center">
                                    @foreach($currentState['dots'] as $dot)
                                        <div class="bg-purple-950/80 border border-purple-400/60 rounded-xl px-2.5 py-1 text-xs text-purple-200 flex items-center gap-1.5 shadow-md" title="Status: {{ $dot['type'] }}">
                                            <span class="font-bold font-mono">{{ $dot['duration'] }}T</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            {{-- Enemy Stats Grid --}}
                            <div>
                                <h4 class="text-xs lg:text-sm font-bold text-red-200/90 mb-2.5 medieval-font tracking-wide">
                                    Statystyki Bestii
                                </h4>
                                <div class="grid grid-cols-2 gap-2.5">
                                    <div class="bg-slate-900/90 border border-red-800/40 rounded-2xl p-2.5 lg:p-3.5 text-center shadow-md">
                                        <div class="text-xs font-semibold text-red-300 tracking-wider">ATK (Atak)</div>
                                        <div class="text-base lg:text-xl xl:text-2xl font-black text-red-100 font-mono">{{ $enemy['stats']['atk'] ?? 0 }}</div>
                                    </div>
                                    <div class="bg-slate-900/90 border border-slate-700/50 rounded-2xl p-2.5 lg:p-3.5 text-center shadow-md">
                                        <div class="text-xs font-semibold text-slate-300 tracking-wider">DEF (Obrona)</div>
                                        <div class="text-base lg:text-xl xl:text-2xl font-black text-slate-100 font-mono">{{ $enemy['stats']['def'] ?? 0 }}</div>
                                    </div>
                                    <div class="bg-slate-900/90 border border-amber-800/40 rounded-2xl p-2.5 lg:p-3.5 text-center shadow-md">
                                        <div class="text-xs font-semibold text-amber-300 tracking-wider">AGI (Zręczność)</div>
                                        <div class="text-base lg:text-xl xl:text-2xl font-black text-amber-100 font-mono">{{ $enemy['stats']['agi'] ?? 0 }}</div>
                                    </div>
                                    <div class="bg-slate-900/90 border border-blue-800/40 rounded-2xl p-2.5 lg:p-3.5 text-center shadow-md">
                                        <div class="text-xs font-semibold text-blue-300 tracking-wider">INT (Wiedza)</div>
                                        <div class="text-base lg:text-xl xl:text-2xl font-black text-blue-100 font-mono">{{ $enemy['stats']['int'] ?? 0 }}</div>
                                    </div>
                                </div>
                            </div>
                        @else
                            {{-- Pre-combat Mysterious Encounter Altar --}}
                            <div class="text-center py-10 space-y-5">
                                <div class="space-y-1.5">
                                    <h3 class="text-lg sm:text-xl lg:text-2xl font-bold text-amber-200 medieval-font tracking-wide">
                                        WYZWANIE MAPY
                                    </h3>
                                    <p class="text-xs sm:text-sm text-purple-200/85 max-w-[260px] mx-auto leading-relaxed">
                                        Eksploruj obszar <span class="text-amber-300 font-bold">{{ $map->name }}</span> i zmierz się z panoszącymi się tutaj potworami!
                                    </p>
                                </div>
                                <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-purple-900/50 border border-purple-500/40 text-xs sm:text-sm text-purple-200 font-semibold shadow-md">
                                    <span>Naciśnij "Rozpocznij Walkę"</span>
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
    </style>

    <script>
        document.addEventListener('livewire:navigated', () => {
            initMapStubComponent();
        });

        document.addEventListener('livewire:init', () => {
            initMapStubComponent();
        });

        function initMapStubComponent() {
            if (window._mapStubListenersBound) return;
            window._mapStubListenersBound = true;

            let turnTimer = null;
            let autoChainTimeout = null;
            let isExecutingTurn = false;
            let isPaused = false;
            let currentSpeed = 1;

            function cleanUp() {
                if (turnTimer) clearTimeout(turnTimer);
                if (autoChainTimeout) clearTimeout(autoChainTimeout);
                turnTimer = null;
                autoChainTimeout = null;
                isExecutingTurn = false;
            }

            window.addEventListener('beforeunload', cleanUp);

            function getComponent() {
                const el = document.getElementById('adventure-map-component');
                return el ? Livewire.find(el.getAttribute('wire:id')) : null;
            }

            function triggerNextTurn() {
                if (isExecutingTurn || isPaused) return;
                const component = getComponent();
                if (component) {
                    component.call('nextTurn');
                }
            }

            function scheduleNextTurn(delayMs) {
                if (isPaused) return;
                if (turnTimer) clearTimeout(turnTimer);
                turnTimer = setTimeout(() => {
                    triggerNextTurn();
                }, delayMs);
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
                    if (turnTimer) clearTimeout(turnTimer);
                    if (autoChainTimeout) clearTimeout(autoChainTimeout);
                    turnTimer = null;
                    autoChainTimeout = null;
                } else {
                    scheduleNextTurn(100);
                }

                const component = getComponent();
                if (component) component.call('togglePlayback');
            };

            window.toggleCombatAuto = function(active) {
                const component = getComponent();
                if (component) component.call('toggleAutoChain');
            };

            Livewire.on('start-playback', (event) => {
                cleanUp();
                isPaused = false;
                currentSpeed = (event && event[0] && event[0].speed) ? event[0].speed : (event && event.speed ? event.speed : 1);
                scheduleNextTurn(200);
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

                const isBuff = effectType === 'buff_phys_dmg' || (type === 'skill' && value === 0);
                const isPoison = effectType === 'poison' || (skillName && skillName.toLowerCase().includes('truj'));
                const isFire = effectType === 'fire' || (skillName && skillName.toLowerCase().includes('ogien'));

                // 1. Buff / Self Enhancement FX: Particles rise over Caster, Caster Glows!
                if (isBuff) {
                    spawnSelfParticles(attackerPanel, 'buff');
                    Livewire.dispatch('play-audio', { type: 'tab' });

                    const attackerRect = attackerPanel.getBoundingClientRect();
                    const fct = document.createElement('div');
                    fct.className = 'fct-damage-number';
                    fct.style.left = `${attackerRect.left + attackerRect.width / 2}px`;
                    fct.style.top = `${attackerRect.top + attackerRect.height / 3 - 20}px`;
                    fct.innerHTML = `<span class="text-emerald-300 font-extrabold text-2xl sm:text-3xl drop-shadow-[0_0_20px_rgba(52,211,153,1)]">WZMOCNIENIE! ${skillName ? skillName : ''}</span>`;
                    fxOverlay.appendChild(fct);

                    setTimeout(() => { if (fct.parentNode) fct.parentNode.removeChild(fct); }, 850);

                    setTimeout(() => {
                        isExecutingTurn = false;
                        if (!isPaused) {
                            const basePause = currentSpeed === 2 ? 200 : 550;
                            scheduleNextTurn(basePause);
                        }
                    }, 500);
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

                // 5. AT IMPACT MOMENT (~170ms): Trigger Defender Hit Bounce, Audio, Particles & Floating Damage Text!
                setTimeout(() => {
                    if (type !== 'miss') {
                        defenderPanel.classList.add('anim-hit-bounce');
                        spawnImpactParticles(defenderPanel, isPoison ? 'poison' : (isFire ? 'fire' : (isCrit ? 'crit' : (type === 'skill' ? 'skill' : 'hit'))));
                    }

                    // Play Audio EXACTLY at impact!
                    Livewire.dispatch('play-audio', { type: audioType });

                    // Spawn Floating Damage Text (FCT) over Target Avatar
                    const fct = document.createElement('div');
                    fct.className = 'fct-damage-number';
                    fct.style.left = `${endX}px`;
                    fct.style.top = `${endY - 20}px`;

                    if (type === 'miss') {
                        fct.innerHTML = `<span class="text-blue-300 font-black text-2xl drop-shadow-[0_2px_10px_rgba(0,0,0,0.9)]">UNIK!</span>`;
                    } else if (type === 'dot') {
                        fct.innerHTML = `<span class="text-purple-400 font-black text-2xl">-${value}</span>`;
                    } else if (isCrit) {
                        fct.innerHTML = `<span class="text-amber-300 font-black text-3xl sm:text-4xl drop-shadow-[0_0_25px_rgba(245,158,11,1)]">KRYTYK! -${value}</span>`;
                    } else if (skillName) {
                        fct.innerHTML = `<span class="${isPoison ? 'text-emerald-300' : (isFire ? 'text-orange-300' : 'text-indigo-300')} font-black text-2xl sm:text-3xl drop-shadow-[0_0_20px_rgba(99,102,241,0.9)]">${skillName} -${value}</span>`;
                    } else {
                        fct.innerHTML = `<span class="text-red-400 font-black text-2xl sm:text-3xl drop-shadow-[0_2px_10px_rgba(0,0,0,0.9)]">-${value}</span>`;
                    }

                    fxOverlay.appendChild(fct);

                    setTimeout(() => {
                        if (fct.parentNode) fct.parentNode.removeChild(fct);
                    }, 850);
                }, 170);

                // 5. After turn animation settles (~500ms), schedule next turn sequentially!
                setTimeout(() => {
                    isExecutingTurn = false;
                    if (!isPaused) {
                        const basePause = currentSpeed === 2 ? 200 : 550;
                        scheduleNextTurn(basePause);
                    }
                }, 500);
            });

            Livewire.on('auto-chain-next-battle', () => {
                if (autoChainTimeout) clearTimeout(autoChainTimeout);
                if (isPaused) return;

                autoChainTimeout = setTimeout(() => {
                    if (isPaused) return;
                    const component = getComponent();
                    if (component) component.call('startBattle');
                }, 600); // 600ms fast chain between battles!
            });

            Livewire.on('encounter-finished', () => {
                cleanUp();
            });
        }
    </script>
    {{-- Session Tracker --}}
    <div class="fixed bottom-20 md:bottom-4 left-2 md:left-4 z-50 bg-amber-900/80 text-amber-100 p-2 md:p-4 rounded-xl shadow-2xl border-2 border-amber-600 backdrop-blur-md transition-all hover:bg-amber-900/90 flex flex-row md:flex-col items-center md:items-stretch gap-3 md:gap-0"
         x-data="{ 
            startTime: {{ $sessionStartTime }},
            elapsed: '00:00:00',
            goldPerMin: 0,
            updateTime() {
                let now = Math.floor(Date.now() / 1000);
                let diff = now - this.startTime;
                let h = Math.floor(diff / 3600).toString().padStart(2, '0');
                let m = Math.floor((diff % 3600) / 60).toString().padStart(2, '0');
                let s = Math.floor(diff % 60).toString().padStart(2, '0');
                this.elapsed = `${h}:${m}:${s}`;

                let totalGold = $wire.sessionGoldEarned || 0;
                if (diff > 0) {
                    this.goldPerMin = Math.round((totalGold / diff) * 60);
                } else {
                    this.goldPerMin = 0;
                }
            }
         }"
         x-init="updateTime(); setInterval(() => updateTime(), 1000)">
        <h4 class="hidden md:block font-bold medieval-font text-lg mb-2 text-amber-300 drop-shadow-md border-b border-amber-700/50 pb-1">Statystyki Sesji</h4>
        <div class="flex items-center justify-between text-xs md:text-sm md:mb-1">
            <span class="text-amber-200"><span class="hidden md:inline">Pokonani:</span></span>
            <span class="font-bold text-white drop-shadow-md text-sm md:text-base ml-1 md:ml-4">{{ $sessionMonstersDefeated }}</span>
        </div>
        <div class="flex items-center justify-between text-xs md:text-sm md:mb-1">
            <span class="text-amber-200"><span class="hidden md:inline">Złoto/min:</span></span>
            <span class="font-bold text-yellow-400 drop-shadow-md text-sm md:text-base ml-1 md:ml-4" x-text="goldPerMin + '/min'"></span>
        </div>
        <div class="flex items-center justify-between text-xs md:text-sm">
            <span class="text-amber-200"><span class="hidden md:inline">Czas:</span></span>
            <span class="font-bold text-white drop-shadow-md font-mono ml-1 md:ml-4" x-text="elapsed"></span>
        </div>
    </div>
</div>
