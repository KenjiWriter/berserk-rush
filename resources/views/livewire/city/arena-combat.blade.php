<div id="arena-combat-component" 
     class="min-h-screen relative overflow-hidden" 
     x-data="{ travelingTo: null }"
     @if($isPlaying && !$battleCompleted && !$isCalculating)
         wire:poll.{{ $playbackSpeed == 2 ? '800ms' : '1500ms' }}="nextTurn"
     @elseif($isCalculating)
         wire:poll.1000ms="checkCombatStatus"
     @endif>
    {{-- Dynamic background --}}
    <div class="absolute inset-0 bg-cover bg-center bg-no-repeat" style="background-image: url('{{ asset('img/maps/shadow-mountains.png') }}');">
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
                <div class="text-5xl mb-4 text-amber-300 animate-bounce">
                    <template x-if="$data.travelingTo === 'Gildia'">
                        <i class="fa-solid fa-flag"></i>
                    </template>
                    <template x-if="$data.travelingTo !== 'Gildia'">
                        <i class="fa-solid fa-chess-board"></i>
                    </template>
                </div>
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

    <div class="relative z-10 container mx-auto px-4 py-2 lg:py-3 min-h-screen">
        {{-- Header with navigation --}}
        <div class="flex items-center justify-between mb-2 lg:mb-3">
            <h1 class="text-2xl sm:text-3xl font-bold text-amber-100 medieval-font drop-shadow-2xl flex items-center gap-2">
                <i class="fa-solid fa-hand-fist text-amber-400"></i> Odtwarzacz Walki {{ $type === 'pvp' ? 'PvP' : 'GvG' }}
            </h1>

            <div class="flex items-center space-x-3">
                {{-- Character info --}}
                <div class="text-amber-100 text-sm medieval-font">
                    <div>{{ $character->name }}</div>
                </div>

                @if($type === 'pvp')
                    <button @click="travelingTo = 'Arena'; setTimeout(() => $wire.backToArena(), 500)"
                        class="relative rounded-lg px-4 py-2 shadow-lg overflow-hidden group">
                        <img src="{{ asset('img/avatars/plate.png') }}" alt=""
                            class="absolute inset-0 w-full h-full object-cover rounded-lg">
                        <div class="absolute inset-0 bg-amber-900/20 rounded-lg"></div>
                        <span class="relative text-amber-100 font-bold medieval-font drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)] flex items-center gap-1.5">
                            <i class="fa-solid fa-chess-board text-amber-300"></i> Powrót na Arenę
                        </span>
                    </button>
                @else
                    <button @click="travelingTo = 'Gildia'; setTimeout(() => $wire.backToGuild(), 500)"
                        class="relative rounded-lg px-4 py-2 shadow-lg overflow-hidden group">
                        <img src="{{ asset('img/avatars/plate.png') }}" alt=""
                            class="absolute inset-0 w-full h-full object-cover rounded-lg">
                        <div class="absolute inset-0 bg-amber-900/20 rounded-lg"></div>
                        <span class="relative text-amber-100 font-bold medieval-font drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)] flex items-center gap-1.5">
                            <i class="fa-solid fa-flag text-amber-300"></i> Powrót do Gildii
                        </span>
                    </button>
                @endif
            </div>
        </div>

        {{-- Classic RPG Battle Layout --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4 lg:gap-5 xl:gap-6 max-w-[1600px] w-full mx-auto pb-24 lg:pb-6">

            {{-- Left Side - Player Panel / GvG 5 Cards Stack --}}
            <div class="col-span-1 md:col-span-1 lg:col-span-1 order-2 lg:order-1" id="player-panel-container">
                @if($type === 'pvp')
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
                                            <img src="{{ asset('img/avatars/default.png') }}" alt="{{ $player['name'] ?? 'Gracz' }}"
                                                class="w-full h-full object-cover">
                                        @endif
                                    </div>
                                    <span class="absolute -bottom-2 left-1/2 -translate-x-1/2 bg-gradient-to-r from-amber-600 to-amber-500 text-amber-950 text-[10px] sm:text-xs font-black px-2.5 py-0.5 rounded-full border border-amber-300 shadow-lg medieval-font">
                                        Lvl {{ $player['level'] ?? '?' }}
                                    </span>
                                </div>

                                {{-- Player Name --}}
                                <h3 class="mt-2 text-sm sm:text-base lg:text-lg xl:text-xl font-extrabold text-amber-200 medieval-font drop-shadow-[0_2px_6px_rgba(0,0,0,0.9)] tracking-wide">
                                    {{ $player['name'] ?? '?' }}
                                </h3>
                                <p class="text-[11px] sm:text-xs text-amber-400/80 tracking-wider">
                                    Gracz
                                </p>
                            </div>

                            {{-- Player HP Bar --}}
                            <div class="space-y-1">
                                <div class="flex justify-between text-xs font-bold text-amber-200 medieval-font drop-shadow">
                                    <span>Życie</span>
                                    <span class="font-mono text-emerald-300 text-xs sm:text-sm">{{ $this->getCurrentPlayerHp() }}/{{ $player['maxHp'] ?? 1 }}</span>
                                </div>
                                <div class="h-3.5 sm:h-4 w-full rounded-full bg-black/80 ring-1 ring-amber-500/40 p-0.5 shadow-inner">
                                    <div class="h-full rounded-full bg-gradient-to-r from-emerald-600 via-emerald-500 to-green-400 shadow-[0_0_12px_rgba(16,185,129,0.6)] transition-all duration-500"
                                        style="width: {{ $this->getPlayerHpPercent() }}%"></div>
                                </div>
                            </div>

                            {{-- Player Attributes Grid --}}
                            <div>
                                <h4 class="text-[11px] sm:text-xs font-bold text-amber-200/90 mb-1.5 medieval-font tracking-wide">
                                    Atrybuty Bojowe
                                </h4>
                                @php
                                    $pStats = $player['stats'] ?? [];
                                @endphp
                                <div class="grid grid-cols-2 gap-1.5 lg:gap-2">
                                    <div class="bg-slate-900/90 border border-red-800/40 rounded-xl p-1.5 lg:p-2 text-center shadow-md">
                                        <div class="text-[10px] sm:text-[11px] font-semibold text-red-300 tracking-wider">STR (Siła)</div>
                                        <div class="text-xs sm:text-sm lg:text-base font-black text-amber-100 font-mono">{{ $pStats['str'] ?? 0 }}</div>
                                    </div>
                                    <div class="bg-slate-900/90 border border-blue-800/40 rounded-xl p-1.5 lg:p-2 text-center shadow-md">
                                        <div class="text-[10px] sm:text-[11px] font-semibold text-blue-300 tracking-wider">INT (Wiedza)</div>
                                        <div class="text-xs sm:text-sm lg:text-base font-black text-amber-100 font-mono">{{ $pStats['int'] ?? 0 }}</div>
                                    </div>
                                    <div class="bg-slate-900/90 border border-emerald-800/40 rounded-xl p-1.5 lg:p-2 text-center shadow-md">
                                        <div class="text-[10px] sm:text-[11px] font-semibold text-emerald-300 tracking-wider">VIT (Witalność)</div>
                                        <div class="text-xs sm:text-sm lg:text-base font-black text-amber-100 font-mono">{{ $pStats['vit'] ?? 0 }}</div>
                                    </div>
                                    <div class="bg-slate-900/90 border border-amber-800/40 rounded-xl p-1.5 lg:p-2 text-center shadow-md">
                                        <div class="text-[10px] sm:text-[11px] font-semibold text-amber-300 tracking-wider">AGI (Zręczność)</div>
                                        <div class="text-xs sm:text-sm lg:text-base font-black text-amber-100 font-mono">{{ $pStats['agi'] ?? 0 }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    {{-- GvG 5 Cards Stack --}}
                    <div id="player-panel" class="relative rounded-2xl shadow-2xl overflow-hidden bg-slate-950/80 backdrop-blur-xl border border-amber-500/30 p-3 sm:p-4 space-y-3">
                        {{-- Guild Header --}}
                        <div class="flex items-center justify-between pb-2 border-b border-amber-500/30">
                            <div>
                                <h3 class="font-extrabold text-amber-200 medieval-font text-base sm:text-lg flex items-center gap-2 drop-shadow">
                                    <i class="fa-solid fa-shield-halved text-amber-400"></i> {{ $player['name'] }}
                                </h3>
                                <p class="text-[10px] text-amber-400/80 uppercase tracking-widest font-semibold">Drużyna Wojenna (5 Graczy)</p>
                            </div>
                            <span class="text-xs text-emerald-300 font-mono font-bold bg-amber-950/80 border border-amber-600/40 px-2 py-1 rounded-lg shadow-inner">
                                {{ $this->getCurrentPlayerHp() }}/{{ $player['maxHp'] }} HP
                            </span>
                        </div>

                        {{-- Total Team HP Bar --}}
                        <div class="space-y-1">
                            <div class="h-3 w-full rounded-full bg-black/80 ring-1 ring-amber-500/40 p-0.5 shadow-inner">
                                <div class="h-full rounded-full bg-gradient-to-r from-emerald-600 via-emerald-500 to-green-400 shadow-[0_0_12px_rgba(16,185,129,0.6)] transition-all duration-500"
                                     style="width: {{ $this->getPlayerHpPercent() }}%"></div>
                            </div>
                        </div>

                        {{-- 5 Player Cards Stack --}}
                        <div class="flex flex-col gap-2.5 relative mt-3">
                            @foreach($this->getCurrentTeamMembers('player') as $member)
                                <div class="relative rounded-xl p-2.5 transition-all duration-300 backdrop-blur-md border shadow-xl {{ $member['is_active_actor'] ? 'bg-slate-900/95 border-amber-400 ring-2 ring-amber-400 shadow-[0_0_30px_rgba(245,158,11,0.6)] scale-[1.03] z-30 anim-attack-player' : ($member['is_active_target'] ? 'bg-slate-900/95 border-rose-500 ring-2 ring-rose-500 shadow-[0_0_25px_rgba(244,63,94,0.6)] scale-[1.01] z-20' : ($member['alive'] ? 'bg-slate-950/80 border-amber-500/30 hover:border-amber-400/60 z-10' : 'bg-slate-950/90 border-slate-800/80 opacity-50 grayscale z-0')) }}">
                                    
                                    {{-- Active Status Badge --}}
                                    @if($member['is_active_actor'])
                                        <span class="absolute -top-2.5 right-3 bg-gradient-to-r from-amber-500 to-yellow-400 text-slate-950 text-[10px] font-black px-2.5 py-0.5 rounded-full border border-yellow-200 shadow-md uppercase tracking-wider medieval-font animate-bounce z-40">
                                            ⚔️ ATAKUJE!
                                        </span>
                                    @elseif($member['is_active_target'])
                                        <span class="absolute -top-2.5 right-3 bg-gradient-to-r from-rose-600 to-red-500 text-white text-[10px] font-black px-2.5 py-0.5 rounded-full border border-red-300 shadow-md uppercase tracking-wider medieval-font animate-pulse z-40">
                                            💥 CEL!
                                        </span>
                                    @endif

                                    <div class="flex items-center gap-3">
                                        {{-- Avatar & Level --}}
                                        <div class="relative w-12 h-12 sm:w-14 sm:h-14 flex-shrink-0">
                                            <div class="w-full h-full rounded-xl overflow-hidden ring-2 {{ $member['is_active_actor'] ? 'ring-amber-400' : 'ring-amber-500/50' }} bg-slate-900 shadow-md">
                                                <img src="{{ $member['avatar'] }}" alt="{{ $member['name'] }}" class="w-full h-full object-cover">
                                            </div>
                                            <span class="absolute -bottom-1.5 left-1/2 -translate-x-1/2 bg-amber-950 text-amber-300 text-[9px] font-black px-1.5 py-0.2 rounded-full border border-amber-500/60 font-mono">
                                                Lvl {{ $member['level'] }}
                                            </span>
                                        </div>

                                        {{-- Member Info & HP --}}
                                        <div class="flex-1 min-w-0 space-y-1">
                                            <div class="flex items-center justify-between">
                                                <h4 class="text-xs sm:text-sm font-extrabold text-amber-200 medieval-font truncate">
                                                    {{ $member['name'] }}
                                                </h4>
                                                <span class="font-mono text-[11px] font-bold {{ $member['alive'] ? 'text-emerald-300' : 'text-red-400' }}">
                                                    {{ $member['hp'] }}/{{ $member['maxHp'] }}
                                                </span>
                                            </div>

                                            {{-- HP Bar --}}
                                            <div class="h-2.5 w-full rounded-full bg-black/80 p-0.5 shadow-inner border border-amber-500/20">
                                                <div class="h-full rounded-full transition-all duration-500 {{ $member['hpPercent'] > 50 ? 'bg-gradient-to-r from-emerald-600 to-green-400' : ($member['hpPercent'] > 20 ? 'bg-gradient-to-r from-yellow-600 to-yellow-400' : 'bg-gradient-to-r from-red-700 to-red-500') }}"
                                                     style="width: {{ $member['hpPercent'] }}%"></div>
                                            </div>

                                            {{-- Attributes mini badges --}}
                                            <div class="flex items-center gap-1.5 text-[9px] text-amber-300/80 font-mono pt-0.5">
                                                <span class="bg-red-950/70 border border-red-800/40 rounded px-1.5 py-0.2 text-red-300">STR {{ $member['stats']['str'] ?? 0 }}</span>
                                                <span class="bg-blue-950/70 border border-blue-800/40 rounded px-1.5 py-0.2 text-blue-300">INT {{ $member['stats']['int'] ?? 0 }}</span>
                                                <span class="bg-emerald-950/70 border border-emerald-800/40 rounded px-1.5 py-0.2 text-emerald-300">VIT {{ $member['stats']['vit'] ?? 0 }}</span>
                                                <span class="bg-amber-950/70 border border-amber-800/40 rounded px-1.5 py-0.2 text-amber-300">AGI {{ $member['stats']['agi'] ?? 0 }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- Center - Battle Log --}}
            <div class="col-span-1 md:col-span-2 lg:col-span-1 order-1 lg:order-2 mb-2 lg:mb-0">
                <section class="relative rounded-2xl shadow-2xl overflow-hidden bg-slate-950/80 backdrop-blur-xl border border-amber-500/30 h-[340px] sm:h-[370px] md:h-[400px] lg:h-[420px] xl:h-[480px] 2xl:h-[540px] flex flex-col">
                    
                    {{-- Header --}}
                    <header class="relative p-2 text-center bg-amber-950/40 border-b border-amber-500/20 backdrop-blur-md">
                        <h3 class="font-serif text-base sm:text-lg lg:text-xl text-amber-200 tracking-wider medieval-font drop-shadow flex items-center justify-center gap-2">
                            <i class="fa-solid fa-swords text-amber-400"></i> Kronika Bitwy
                        </h3>
                    </header>

                    {{-- Battle Log Scroll Area --}}
                    <div id="arena-combat-log-container" class="relative flex-1 overflow-y-auto p-3 lg:p-4 custom-scrollbar">
                        @if($isCalculating)
                            <div class="h-full flex flex-col items-center justify-center text-center">
                                <div class="relative w-24 h-24 sm:w-28 sm:h-28 mb-4">
                                    <div class="absolute inset-0 rounded-full border-4 border-amber-500/30 border-t-amber-400 animate-spin"></div>
                                    <div class="absolute inset-2 rounded-full border-4 border-amber-700/30 border-b-amber-600 animate-[spin_1.5s_linear_infinite_reverse]"></div>
                                </div>
                                <h3 class="font-serif text-2xl sm:text-3xl text-amber-200 tracking-wider medieval-font drop-shadow animate-pulse">
                                    Oczekiwanie na wynik...
                                </h3>
                                <p class="text-amber-300/80 italic mt-2 font-semibold text-sm">Krzyżowanie mieczy...</p>
                            </div>
                        @else
                            <ul class="space-y-2 text-amber-100">
                                @if (empty($visibleTurns))
                                    @if ($isPlaying)
                                        <li class="text-center py-10 animate-pulse">
                                            <div class="text-amber-300/80 font-serif italic text-lg sm:text-xl flex items-center justify-center gap-2">
                                                <i class="fa-solid fa-swords text-amber-400 animate-bounce"></i> Rozpoczynanie bitwy...
                                            </div>
                                        </li>
                                    @else
                                        <li class="text-center py-10">
                                            <div class="text-amber-300/80 font-serif italic text-lg sm:text-xl">
                                                Pauza odtwarzacza...
                                            </div>
                                        </li>
                                    @endif
                                @else
                                    @foreach ($visibleTurns as $index => $turn)
                                        <li class="leading-relaxed bg-slate-900/70 border border-amber-500/20 rounded-xl px-3 py-2 lg:px-3.5 lg:py-2.5 shadow-sm backdrop-blur-sm text-xs sm:text-sm lg:text-sm xl:text-base">
                                            <span class="inline-block w-8 sm:w-9 text-center text-xs font-bold bg-amber-900/80 text-amber-200 rounded-md border border-amber-600/40 px-1 py-0.5 mr-1.5 font-mono">
                                                T{{ $turn['round'] ?? ($index + 1) }}
                                            </span>
                                            @if ($turn['type'] == 'miss')
                                                <span class="text-slate-300 italic font-semibold">
                                                    <strong class="text-amber-200">{{ $turn['actor_name'] ?? ($turn['actor'] == 'player' ? $player['name'] : $enemy['name']) }}</strong>
                                                    pudłuje atak
                                                    @if(!empty($turn['target_name'])) w <strong>{{ $turn['target_name'] }}</strong>@endif!
                                                    @if (!empty($turn['dotDamage']))
                                                        <span class="text-emerald-400 font-mono font-bold ml-1">(+{{ $turn['dotDamage'] }})</span>
                                                    @endif
                                                </span>
                                            @elseif ($turn['type'] == 'dot')
                                                <span class="text-purple-300 font-semibold italic">
                                                    Zadano <strong class="text-purple-200 font-mono">{{ \App\Helpers\FormatHelper::short($turn['value']) }}</strong> obrażeń od statusów.
                                                </span>
                                            @elseif ($turn['type'] == 'crowd_controlled')
                                                <span class="text-amber-300 font-semibold italic">
                                                    <strong class="text-amber-200">{{ $turn['actor_name'] ?? ($turn['actor'] == 'player' ? $player['name'] : $enemy['name']) }}</strong>
                                                    jest ogłuszony/a i traci turę!
                                                </span>
                                            @elseif ($turn['type'] == 'skill_heal')
                                                <span class="text-emerald-300 font-semibold">
                                                    <strong class="text-amber-200">{{ $turn['actor_name'] ?? ($turn['actor'] == 'player' ? $player['name'] : $enemy['name']) }}</strong>
                                                    używa <span class="text-indigo-300 font-bold uppercase">{{ $turn['skill_name'] ?? 'Leczenie' }}</span> i odnawia <strong class="text-emerald-400 font-mono">{{ \App\Helpers\FormatHelper::short($turn['value']) }}</strong> HP!
                                                </span>
                                            @elseif ($turn['type'] == 'skill')
                                                <span class="{{ $turn['actor'] == 'player' ? 'text-blue-300' : 'text-red-300' }} font-semibold">
                                                    <strong class="text-amber-200">{{ $turn['actor_name'] ?? ($turn['actor'] == 'player' ? $player['name'] : $enemy['name']) }}</strong>
                                                    używa <span class="text-indigo-300 font-bold uppercase">{{ $turn['skill_name'] }}</span>
                                                    @if(!empty($turn['target_name'])) na <strong>{{ $turn['target_name'] }}</strong>@endif
                                                    i zadaje <strong class="text-amber-300 font-mono">{{ \App\Helpers\FormatHelper::short($turn['value']) }}</strong>
                                                    @if (!empty($turn['dotDamage']))
                                                        <span class="text-emerald-400 font-mono font-bold ml-1">(+{{ \App\Helpers\FormatHelper::short($turn['dotDamage']) }})</span>
                                                    @endif
                                                    obrażeń
                                                    @if (!empty($turn['crit'])) <span class="font-bold text-amber-400 drop-shadow-[0_0_10px_rgba(245,158,11,0.8)]">KRYTYK!</span> @endif
                                                </span>
                                            @else
                                                <span class="{{ $turn['actor'] == 'player' ? 'text-emerald-300' : 'text-rose-300' }} font-semibold">
                                                    <strong class="text-amber-200">{{ $turn['actor_name'] ?? ($turn['actor'] == 'player' ? $player['name'] : $enemy['name']) }}</strong>
                                                    zadaje <strong class="text-amber-300 font-mono">{{ \App\Helpers\FormatHelper::short($turn['value']) }}</strong>
                                                    @if(!empty($turn['target_name'])) obrażeń w <strong>{{ $turn['target_name'] }}</strong>@else obrażeń@endif
                                                    @if (!empty($turn['dotDamage']))
                                                        <span class="text-emerald-400 font-mono font-bold ml-1">(+{{ \App\Helpers\FormatHelper::short($turn['dotDamage']) }})</span>
                                                    @endif
                                                    @if (!empty($turn['crit']))
                                                        <span class="font-bold text-amber-400 drop-shadow-[0_0_10px_rgba(245,158,11,0.8)]">KRYTYK!</span>
                                                    @endif
                                                </span>
                                            @endif
                                        </li>
                                    @endforeach

                                    {{-- Battle Result & Rewards --}}
                                    @if ($battleCompleted)
                                        <li class="text-center mt-4 p-4 rounded-2xl {{ $result == 'win' ? 'bg-emerald-950/80 border border-emerald-500/40 text-emerald-200 shadow-[0_0_25px_rgba(16,185,129,0.2)]' : 'bg-red-950/80 border border-red-500/40 text-red-200 shadow-[0_0_25px_rgba(239,68,68,0.2)]' }} backdrop-blur-md">
                                            <div class="mb-2">
                                                @if ($result == 'win')
                                                    <i class="fa-solid fa-trophy text-amber-400 text-3xl drop-shadow-[0_0_15px_rgba(245,158,11,0.6)]"></i>
                                                @else
                                                    <i class="fa-solid fa-skull text-red-500 text-3xl drop-shadow-[0_0_15px_rgba(239,68,68,0.6)]"></i>
                                                @endif
                                            </div>
                                            <div class="text-xl sm:text-2xl font-bold medieval-font tracking-wide">
                                                {{ $result == 'win' ? 'TRIUMF!' : 'KLĘSKA!' }}
                                            </div>
                                            
                                            @if($type === 'pvp')
                                                <div class="mt-3 p-3 bg-slate-900/90 border border-amber-500/30 rounded-xl text-left">
                                                    <h4 class="font-bold text-amber-300 mb-2 medieval-font text-center text-sm sm:text-base flex items-center justify-center gap-2">
                                                        <i class="fa-solid fa-gift text-amber-400"></i> Podsumowanie PvP:
                                                    </h4>
                                                    <div class="space-y-1.5 text-xs sm:text-sm">
                                                        <div class="flex items-center space-x-2">
                                                            <i class="fa-solid fa-chart-line text-blue-400 w-4 text-center"></i>
                                                            <span class="text-amber-200">
                                                                Ranking ELO: <span class="font-bold text-blue-300 font-mono">{{ $eloChange > 0 ? '+'.$eloChange : $eloChange }}</span>
                                                            </span>
                                                        </div>
                                                        <div class="flex items-center space-x-2">
                                                            <i class="fa-solid fa-coins text-yellow-400 w-4 text-center"></i>
                                                            <span class="text-amber-200">
                                                                Żetony Areny: <span class="font-bold text-yellow-300 font-mono">+{{ $tokensReward }}</span>
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
                    <footer class="relative p-3 lg:p-3.5 bg-amber-950/40 border-t border-amber-500/20 backdrop-blur-md">
                        <div class="flex flex-col gap-2.5">
                            <div class="flex items-center justify-center gap-2.5">
                                @if (!$battleCompleted && !$isCalculating)
                                    <button wire:click="togglePlayback"
                                        class="rounded-xl px-5 py-2.5 {{ $isPlaying ? 'bg-amber-950/80 border-amber-500/40 text-amber-200 hover:bg-amber-900/80' : 'bg-emerald-950/80 border-emerald-500/40 text-emerald-200 hover:bg-emerald-900/80' }} border font-bold transition-all medieval-font shadow-lg text-xs sm:text-sm flex items-center gap-2">
                                        @if ($isPlaying)
                                            <i class="fa-solid fa-pause text-amber-400"></i>
                                            <span>Pauza</span>
                                        @else
                                            <i class="fa-solid fa-play text-emerald-400"></i>
                                            <span>Odtwarzaj</span>
                                        @endif
                                    </button>
                                @endif

                                @if (!empty($visibleTurns))
                                    <div class="flex gap-2">
                                        <button wire:click="setPlaybackSpeed(1)" 
                                            class="rounded-xl px-3.5 py-2 {{ $playbackSpeed == 1 ? 'bg-amber-900/90 text-amber-200 border-amber-400/80 font-black' : 'bg-slate-900/80 text-amber-300/70 border-slate-700 hover:bg-slate-800' }} border font-mono text-xs flex items-center gap-1.5 transition-all shadow-md">
                                            <i class="fa-solid fa-play text-[10px]"></i> x1
                                        </button>
                                        <button wire:click="setPlaybackSpeed(2)" 
                                            class="rounded-xl px-3.5 py-2 {{ $playbackSpeed == 2 ? 'bg-amber-900/90 text-amber-200 border-amber-400/80 font-black' : 'bg-slate-900/80 text-amber-300/70 border-slate-700 hover:bg-slate-800' }} border font-mono text-xs flex items-center gap-1.5 transition-all shadow-md">
                                            <i class="fa-solid fa-forward text-[10px]"></i> x2
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </footer>
                </section>
            </div>

            {{-- Right Side - Enemy Panel / GvG 5 Cards Stack --}}
            <div class="col-span-1 md:col-span-1 lg:col-span-1 order-3 lg:order-3" id="enemy-panel-container">
                @if($type === 'pvp')
                    <div id="enemy-panel"
                        class="relative rounded-2xl shadow-2xl overflow-hidden bg-slate-950/80 backdrop-blur-xl border border-red-500/30 transition-all duration-300 {{ $this->isEnemyTurn() ? 'ring-2 ring-red-400/80 shadow-[0_0_35px_rgba(239,68,68,0.4)] scale-[1.01]' : '' }}">
                        
                        {{-- Glossy Inner Ambient Glow --}}
                        <div class="absolute inset-0 bg-gradient-to-b from-red-500/10 via-transparent to-black/70 pointer-events-none"></div>

                        <div class="relative p-3 sm:p-3.5 lg:p-3.5 xl:p-5 space-y-2.5 lg:space-y-3 xl:space-y-4">
                            {{-- Enemy Header & Avatar --}}
                            <div class="text-center">
                                <div class="relative w-16 h-16 sm:w-20 sm:h-20 lg:w-20 lg:h-20 xl:w-24 xl:h-24 2xl:w-28 2xl:h-28 mx-auto">
                                    <div class="w-full h-full rounded-2xl overflow-hidden ring-4 ring-red-500/80 shadow-[0_0_25px_rgba(239,68,68,0.35)] bg-slate-900">
                                        <img src="{{ $enemy['avatar'] ?? asset('img/avatars/default.png') }}"
                                            alt="{{ $enemy['name'] ?? '?' }}"
                                            class="w-full h-full object-cover">
                                    </div>
                                    <span class="absolute -bottom-2 left-1/2 -translate-x-1/2 bg-gradient-to-r from-red-700 to-red-600 text-red-100 text-[10px] sm:text-xs font-black px-2.5 py-0.5 rounded-full border border-red-400 shadow-lg medieval-font">
                                        Lvl {{ $enemy['level'] ?? '?' }}
                                    </span>
                                </div>

                                {{-- Enemy Name --}}
                                <h3 class="mt-2 text-sm sm:text-base lg:text-lg xl:text-xl font-extrabold text-red-200 medieval-font drop-shadow-[0_2px_6px_rgba(0,0,0,0.9)] tracking-wide">
                                    {{ $enemy['name'] ?? '?' }}
                                </h3>
                                <p class="text-[11px] sm:text-xs text-red-400/80 tracking-wider">
                                    Przeciwnik (Widmo)
                                </p>
                            </div>

                            {{-- Enemy HP Bar --}}
                            <div class="space-y-1">
                                <div class="flex justify-between text-xs font-bold text-red-200 medieval-font drop-shadow">
                                    <span>Życie</span>
                                    <span class="font-mono text-emerald-300 text-xs sm:text-sm">{{ $this->getCurrentEnemyHp() }}/{{ $enemy['maxHp'] ?? 1 }}</span>
                                </div>
                                <div class="h-3.5 sm:h-4 w-full rounded-full bg-black/80 ring-1 ring-red-500/40 p-0.5 shadow-inner">
                                    <div class="h-full rounded-full bg-gradient-to-r from-emerald-600 via-emerald-500 to-green-400 shadow-[0_0_12px_rgba(16,185,129,0.6)] transition-all duration-500"
                                        style="width: {{ $this->getEnemyHpPercent() }}%"></div>
                                </div>
                            </div>

                            {{-- Enemy Attributes Grid --}}
                            <div>
                                <h4 class="text-[11px] sm:text-xs font-bold text-red-200/90 mb-1.5 medieval-font tracking-wide">
                                    Atrybuty Przeciwnika
                                </h4>
                                @php
                                    $eStats = $enemy['stats'] ?? [];
                                @endphp
                                <div class="grid grid-cols-2 gap-1.5 lg:gap-2">
                                    <div class="bg-slate-900/90 border border-red-800/40 rounded-xl p-1.5 lg:p-2 text-center shadow-md">
                                        <div class="text-[10px] sm:text-[11px] font-semibold text-red-300 tracking-wider">STR (Siła)</div>
                                        <div class="text-xs sm:text-sm lg:text-base font-black text-amber-100 font-mono">{{ $eStats['str'] ?? 0 }}</div>
                                    </div>
                                    <div class="bg-slate-900/90 border border-blue-800/40 rounded-xl p-1.5 lg:p-2 text-center shadow-md">
                                        <div class="text-[10px] sm:text-[11px] font-semibold text-blue-300 tracking-wider">INT (Wiedza)</div>
                                        <div class="text-xs sm:text-sm lg:text-base font-black text-amber-100 font-mono">{{ $eStats['int'] ?? 0 }}</div>
                                    </div>
                                    <div class="bg-slate-900/90 border border-emerald-800/40 rounded-xl p-1.5 lg:p-2 text-center shadow-md">
                                        <div class="text-[10px] sm:text-[11px] font-semibold text-emerald-300 tracking-wider">VIT (Witalność)</div>
                                        <div class="text-xs sm:text-sm lg:text-base font-black text-amber-100 font-mono">{{ $eStats['vit'] ?? 0 }}</div>
                                    </div>
                                    <div class="bg-slate-900/90 border border-amber-800/40 rounded-xl p-1.5 lg:p-2 text-center shadow-md">
                                        <div class="text-[10px] sm:text-[11px] font-semibold text-amber-300 tracking-wider">AGI (Zręczność)</div>
                                        <div class="text-xs sm:text-sm lg:text-base font-black text-amber-100 font-mono">{{ $eStats['agi'] ?? 0 }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    {{-- GvG 5 Cards Stack - Enemy Team --}}
                    <div id="enemy-panel" class="relative rounded-2xl shadow-2xl overflow-hidden bg-slate-950/80 backdrop-blur-xl border border-red-500/30 p-3 sm:p-4 space-y-3">
                        {{-- Guild Header --}}
                        <div class="flex items-center justify-between pb-2 border-b border-red-500/30">
                            <div>
                                <h3 class="font-extrabold text-red-200 medieval-font text-base sm:text-lg flex items-center gap-2 drop-shadow">
                                    <i class="fa-solid fa-skull text-red-400"></i> {{ $enemy['name'] }}
                                </h3>
                                <p class="text-[10px] text-red-400/80 uppercase tracking-widest font-semibold">Wroga Drużyna (5 Graczy)</p>
                            </div>
                            <span class="text-xs text-red-300 font-mono font-bold bg-red-950/80 border border-red-600/40 px-2 py-1 rounded-lg shadow-inner">
                                {{ $this->getCurrentEnemyHp() }}/{{ $enemy['maxHp'] }} HP
                            </span>
                        </div>

                        {{-- Total Team HP Bar --}}
                        <div class="space-y-1">
                            <div class="h-3 w-full rounded-full bg-black/80 ring-1 ring-red-500/40 p-0.5 shadow-inner">
                                <div class="h-full rounded-full bg-gradient-to-r from-emerald-600 via-emerald-500 to-green-400 shadow-[0_0_12px_rgba(16,185,129,0.6)] transition-all duration-500"
                                     style="width: {{ $this->getEnemyHpPercent() }}%"></div>
                            </div>
                        </div>

                        {{-- 5 Enemy Cards Stack --}}
                        <div class="flex flex-col gap-2.5 relative mt-3">
                            @foreach($this->getCurrentTeamMembers('enemy') as $member)
                                <div class="relative rounded-xl p-2.5 transition-all duration-300 backdrop-blur-md border shadow-xl {{ $member['is_active_actor'] ? 'bg-slate-900/95 border-red-400 ring-2 ring-red-400 shadow-[0_0_30px_rgba(239,68,68,0.6)] scale-[1.03] z-30 anim-attack-enemy' : ($member['is_active_target'] ? 'bg-slate-900/95 border-rose-500 ring-2 ring-rose-500 shadow-[0_0_25px_rgba(244,63,94,0.6)] scale-[1.01] z-20' : ($member['alive'] ? 'bg-slate-950/80 border-red-500/30 hover:border-red-400/60 z-10' : 'bg-slate-950/90 border-slate-800/80 opacity-50 grayscale z-0')) }}">
                                    
                                    {{-- Active Status Badge --}}
                                    @if($member['is_active_actor'])
                                        <span class="absolute -top-2.5 right-3 bg-gradient-to-r from-red-600 to-rose-500 text-white text-[10px] font-black px-2.5 py-0.5 rounded-full border border-red-300 shadow-md uppercase tracking-wider medieval-font animate-bounce z-40">
                                            ⚔️ ATAKUJE!
                                        </span>
                                    @elseif($member['is_active_target'])
                                        <span class="absolute -top-2.5 right-3 bg-gradient-to-r from-amber-500 to-yellow-400 text-slate-950 text-[10px] font-black px-2.5 py-0.5 rounded-full border border-yellow-200 shadow-md uppercase tracking-wider medieval-font animate-pulse z-40">
                                            💥 CEL!
                                        </span>
                                    @endif

                                    <div class="flex items-center gap-3">
                                        {{-- Avatar & Level --}}
                                        <div class="relative w-12 h-12 sm:w-14 sm:h-14 flex-shrink-0">
                                            <div class="w-full h-full rounded-xl overflow-hidden ring-2 {{ $member['is_active_actor'] ? 'ring-red-400' : 'ring-red-500/50' }} bg-slate-900 shadow-md">
                                                <img src="{{ $member['avatar'] }}" alt="{{ $member['name'] }}" class="w-full h-full object-cover">
                                            </div>
                                            <span class="absolute -bottom-1.5 left-1/2 -translate-x-1/2 bg-red-950 text-red-200 text-[9px] font-black px-1.5 py-0.2 rounded-full border border-red-500/60 font-mono">
                                                Lvl {{ $member['level'] }}
                                            </span>
                                        </div>

                                        {{-- Member Info & HP --}}
                                        <div class="flex-1 min-w-0 space-y-1">
                                            <div class="flex items-center justify-between">
                                                <h4 class="text-xs sm:text-sm font-extrabold text-red-200 medieval-font truncate">
                                                    {{ $member['name'] }}
                                                </h4>
                                                <span class="font-mono text-[11px] font-bold {{ $member['alive'] ? 'text-emerald-300' : 'text-red-400' }}">
                                                    {{ $member['hp'] }}/{{ $member['maxHp'] }}
                                                </span>
                                            </div>

                                            {{-- HP Bar --}}
                                            <div class="h-2.5 w-full rounded-full bg-black/80 p-0.5 shadow-inner border border-red-500/20">
                                                <div class="h-full rounded-full transition-all duration-500 {{ $member['hpPercent'] > 50 ? 'bg-gradient-to-r from-emerald-600 to-green-400' : ($member['hpPercent'] > 20 ? 'bg-gradient-to-r from-yellow-600 to-yellow-400' : 'bg-gradient-to-r from-red-700 to-red-500') }}"
                                                     style="width: {{ $member['hpPercent'] }}%"></div>
                                            </div>

                                            {{-- Attributes mini badges --}}
                                            <div class="flex items-center gap-1.5 text-[9px] text-red-300/80 font-mono pt-0.5">
                                                <span class="bg-red-950/70 border border-red-800/40 rounded px-1.5 py-0.2 text-red-300">STR {{ $member['stats']['str'] ?? 0 }}</span>
                                                <span class="bg-blue-950/70 border border-blue-800/40 rounded px-1.5 py-0.2 text-blue-300">INT {{ $member['stats']['int'] ?? 0 }}</span>
                                                <span class="bg-emerald-950/70 border border-emerald-800/40 rounded px-1.5 py-0.2 text-emerald-300">VIT {{ $member['stats']['vit'] ?? 0 }}</span>
                                                <span class="bg-amber-950/70 border border-amber-800/40 rounded px-1.5 py-0.2 text-amber-300">AGI {{ $member['stats']['agi'] ?? 0 }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        (function() {
            let userHasScrolledUp = false;

            function scrollArenaLogToBottom(force = false) {
                const container = document.getElementById('arena-combat-log-container');
                if (!container) return;

                if (force) {
                    userHasScrolledUp = false;
                }

                const distanceFromBottom = container.scrollHeight - container.scrollTop - container.clientHeight;
                if (force || !userHasScrolledUp || distanceFromBottom < 150) {
                    container.scrollTop = container.scrollHeight;
                }
            }

            const arenaLogContainer = document.getElementById('arena-combat-log-container');
            if (arenaLogContainer) {
                if (window._arenaLogObserver) {
                    window._arenaLogObserver.disconnect();
                }
                window._arenaLogObserver = new MutationObserver(() => {
                    scrollArenaLogToBottom();
                });
                window._arenaLogObserver.observe(arenaLogContainer, { childList: true, subtree: true });

                arenaLogContainer.addEventListener('scroll', () => {
                    const distanceFromBottom = arenaLogContainer.scrollHeight - arenaLogContainer.scrollTop - arenaLogContainer.clientHeight;
                    if (distanceFromBottom > 150) {
                        userHasScrolledUp = true;
                    } else {
                        userHasScrolledUp = false;
                    }
                }, { passive: true });
            }

            const registerTurnPlayed = () => {
                if (typeof Livewire !== 'undefined') {
                    Livewire.on('turn-played', (event) => {
                        scrollArenaLogToBottom();
                        const data = (event && event[0]) ? event[0] : event;
                        const actor = data.actor;
                        const type = data.type;
                        const dotDamage = data.dotDamage || 0;
                        const isPlayer = actor === 'player';
                        const playerPanel = document.getElementById('player-panel');
                        const enemyPanel = document.getElementById('enemy-panel');
                        
                        if (playerPanel && enemyPanel) {
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
                        }
                    });
                }
            };

            if (document.readyState === 'complete' || document.readyState === 'interactive') {
                registerTurnPlayed();
            } else {
                document.addEventListener('DOMContentLoaded', registerTurnPlayed);
            }
        })();
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
