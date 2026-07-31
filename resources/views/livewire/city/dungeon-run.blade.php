<div id="dungeon-run-component" class="min-h-screen relative overflow-hidden"
     x-data="{ isPaused: false, speed: 1 }">

    {{-- Dynamic Background --}}
    <div class="absolute inset-0 bg-cover bg-center bg-no-repeat opacity-30"
         style="background-image: url('{{ asset('img/adventure-background.png') }}');"></div>

    {{-- Dark overlay + red dungeon tint --}}
    <div class="absolute inset-0 bg-black/80"></div>
    <div class="absolute inset-0 bg-gradient-to-b from-red-950/30 via-transparent to-red-950/40"></div>

    {{-- Dynamic Attack FX Layer --}}
    <div id="dungeon-combat-fx-overlay" class="fixed inset-0 pointer-events-none z-[150] overflow-hidden"></div>

    <div class="relative z-10 container mx-auto px-4 py-2 lg:py-3 min-h-screen max-w-[1600px]">

        {{-- Header --}}
        <div class="flex items-center justify-between mb-2 lg:mb-3">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-amber-100 medieval-font drop-shadow-2xl flex items-center gap-2 flex-wrap">
                    <span>{{ $dungeon->name }}</span>
                    @php $activeMult = $run ? ($run->key_multiplier ?? 1) : $multiplier; @endphp
                    @if($activeMult > 1)
                        <span class="px-2.5 py-0.5 rounded-lg bg-amber-900/90 border border-amber-500/60 text-amber-300 font-black text-xs shadow-md flex items-center gap-1">
                            <i class="fa-solid fa-layer-group text-amber-400"></i> {{ $activeMult }}x Multi
                        </span>
                    @endif
                </h1>
                @if($run)
                    <p class="text-xs text-red-300/80 mt-0.5">Etap <span class="font-bold text-amber-300">{{ $currentStage }}</span> / {{ $totalStages }}</p>
                @endif
            </div>
            <button wire:click="backToDungeonList"
                class="relative rounded-lg px-4 py-2 shadow-lg bg-slate-900/80 border border-red-800/40 hover:border-red-500 transition-all">
                <span class="text-amber-100 font-bold medieval-font">
                    <i class="fa-solid fa-dungeon mr-1"></i> Lochy
                </span>
            </button>
        </div>

        {{-- Error message --}}
        @if($errorMessage)
            <div class="mb-3 p-3 bg-red-950/90 border border-red-600/60 rounded-xl backdrop-blur-md text-center">
                <p class="text-red-300 font-semibold text-sm flex items-center justify-center gap-2">
                    <i class="fa-solid fa-triangle-exclamation text-red-400"></i>
                    {{ $errorMessage }}
                </p>
            </div>
        @endif

        {{-- NO ACTIVE RUN - Start screen --}}
        @if(!$run)
            <div class="max-w-2xl mx-auto mt-8">
                <div class="relative rounded-2xl shadow-2xl overflow-hidden bg-slate-950/80 backdrop-blur-xl border border-red-500/30">
                    <div class="absolute inset-0 bg-gradient-to-b from-red-500/10 via-transparent to-black/70 pointer-events-none"></div>
                    <div class="relative p-8 text-center">
                        <div class="text-7xl mb-4 drop-shadow-[0_0_30px_rgba(239,68,68,0.5)]">🏚️</div>
                        <h2 class="text-3xl font-bold text-amber-200 medieval-font mb-2 tracking-wide">{{ $dungeon->name }}</h2>
                        <div class="text-slate-300 mb-6 space-y-1 text-sm">
                            <p>Etapy: <strong class="text-amber-300">{{ $totalStages }}</strong></p>
                            <p>Wymagany poziom: <strong class="text-amber-300">{{ $dungeon->min_level }}</strong></p>
                            @if($multiplier > 1)
                                <div class="mt-3 p-2 bg-amber-950/80 border border-amber-500/50 rounded-xl inline-block text-amber-200 text-xs font-bold">
                                    <i class="fa-solid fa-layer-group text-amber-400"></i> Tryb Multi {{ $multiplier }}x: +{{ ($multiplier - 1) * 25 }}% Trudności potworów | x{{ $multiplier }} Złoto, XP & Skrzynie
                                </div>
                            @endif
                        </div>
                        <button wire:click="startRun"
                            class="rounded-xl px-8 py-3.5 bg-gradient-to-r from-red-700 via-red-600 to-red-700 border border-red-400/60 text-white font-extrabold medieval-font shadow-[0_0_25px_rgba(239,68,68,0.4)] hover:scale-105 active:scale-95 transition-all"
                            wire:loading.attr="disabled" wire:loading.class="opacity-50 cursor-not-allowed">
                            <span wire:loading.remove wire:target="startRun">
                                <i class="fa-solid fa-dungeon mr-2"></i>Rozpocznij Ekspedycję {{ $multiplier > 1 ? "({$multiplier}x)" : "" }}
                            </span>
                            <span wire:loading wire:target="startRun">Rozpoczynanie...</span>
                        </button>
                    </div>
                </div>
            </div>

        {{-- DUNGEON COMPLETE --}}
        @elseif($battleResult && ($battleResult['result'] ?? '') === 'dungeon_complete' && !$showBattle)
            <div class="max-w-2xl mx-auto mt-8">
            <div class="relative rounded-2xl shadow-2xl overflow-hidden bg-slate-950/80 backdrop-blur-xl border border-amber-500/60">
                <div class="absolute inset-0 bg-gradient-to-b from-amber-500/15 via-transparent to-black/70 pointer-events-none"></div>
                <div class="relative p-8 text-center">
                <div class="text-7xl mb-4 animate-bounce">🏆</div>
                <h2 class="text-3xl font-bold text-amber-300 medieval-font mb-2">Gratulacje!</h2>
                <p class="text-slate-300 text-lg mb-2">Ukończyłeś loch <strong class="text-amber-400">{{ $dungeon->name }}</strong>!</p>
                <p class="text-slate-400 mb-6">Przetrwałeś wszystkie {{ $totalStages }} etapów.</p>
                
                @if(isset($battleResult['total_loot']))
                    <div class="max-w-lg mx-auto bg-slate-950/90 border-2 border-amber-500/60 rounded-2xl p-5 mb-8 text-left shadow-2xl backdrop-blur-md">
                        <h4 class="font-bold text-amber-400 mb-4 text-center uppercase tracking-widest text-sm medieval-font flex items-center justify-center gap-2">
                            <span>🎁</span> Zgromadzony Łup
                        </h4>
                        
                        {{-- Currencies --}}
                        <div class="grid grid-cols-2 gap-3 mb-5">
                            <div class="bg-indigo-950/50 border border-indigo-500/30 rounded-xl p-3 flex items-center justify-between shadow-inner">
                                <div class="flex items-center space-x-2">
                                    <span class="text-xl">✨</span>
                                    <span class="text-indigo-200 font-bold text-xs sm:text-sm medieval-font">Doświadczenie</span>
                                </div>
                                <span class="text-indigo-300 font-extrabold text-sm sm:text-base font-mono">+{{ number_format($battleResult['total_loot']['xp'] ?? 0) }}</span>
                            </div>
                            <div class="bg-amber-950/50 border border-amber-500/30 rounded-xl p-3 flex items-center justify-between shadow-inner">
                                <div class="flex items-center space-x-2">
                                    <span class="text-xl">💰</span>
                                    <span class="text-amber-200 font-bold text-xs sm:text-sm medieval-font">Złoto</span>
                                </div>
                                <span class="text-amber-400 font-extrabold text-sm sm:text-base font-mono">+{{ number_format($battleResult['total_loot']['gold'] ?? 0) }}</span>
                            </div>
                        </div>

                        {{-- Acquired Items & Chests Grid --}}
                        @if(isset($battleResult['total_loot']['items']) && count($battleResult['total_loot']['items']) > 0)
                            @php
                                $groupedItems = [];
                                foreach ($battleResult['total_loot']['items'] as $item) {
                                    $name = $item['name'];
                                    if (!isset($groupedItems[$name])) {
                                        $icon = $item['icon'] ?? null;
                                        if (!$icon && !empty($item['ref_ulid'])) {
                                            $icon = \App\Infrastructure\Persistence\ItemTemplate::where('id', $item['ref_ulid'])->value('icon');
                                        }
                                        if (!$icon) {
                                            $icon = \App\Infrastructure\Persistence\ItemTemplate::where('name', $name)->value('icon');
                                        }
                                        $isChest = (str_contains(mb_strtolower($name), 'skrzyn') || ($item['type'] ?? '') === 'chest');
                                        $groupedItems[$name] = [
                                            'name' => $name,
                                            'type' => $item['type'] ?? 'item',
                                            'quantity' => 0,
                                            'icon' => $icon,
                                            'is_chest' => $isChest,
                                        ];
                                    }
                                    $groupedItems[$name]['quantity'] += $item['quantity'];
                                }
                            @endphp

                            <div class="border-t border-amber-900/40 pt-4">
                                <h5 class="text-xs font-extrabold text-amber-300 uppercase tracking-wider mb-3 medieval-font text-center flex items-center justify-center gap-1.5">
                                    <i class="fa-solid fa-box-open text-amber-400"></i> Zdobyte Przedmioty i Skrzynie
                                </h5>

                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                    @foreach($groupedItems as $name => $data)
                                        <div class="relative bg-slate-900/90 border-2 {{ $data['is_chest'] ? 'border-amber-500/80 shadow-[0_0_20px_rgba(245,158,11,0.35)] bg-gradient-to-b from-amber-950/40 via-slate-900/90 to-slate-950/95 ring-1 ring-amber-400/40' : 'border-slate-700/80 hover:border-amber-500/40' }} rounded-xl p-3 flex flex-col items-center justify-center text-center transition-all duration-300 hover:scale-105 group">
                                            
                                            {{-- Chest badge indicator --}}
                                            @if($data['is_chest'])
                                                <span class="absolute -top-2.5 left-1/2 -translate-x-1/2 bg-gradient-to-r from-amber-600 to-amber-500 text-amber-950 text-[10px] font-black px-2 py-0.5 rounded-full border border-amber-300 shadow-md uppercase tracking-wider medieval-font whitespace-nowrap">
                                                    Skrzynia
                                                </span>
                                            @endif

                                            {{-- Quantity Badge --}}
                                            <span class="absolute -top-2 -right-2 bg-gradient-to-r from-amber-500 to-yellow-400 text-slate-950 font-black text-xs px-2 py-0.5 rounded-full border border-yellow-200 shadow-lg font-mono z-10">
                                                {{ $data['quantity'] }}x
                                            </span>

                                            {{-- Item Icon --}}
                                            <div class="w-14 h-14 my-1 flex items-center justify-center relative">
                                                @if($data['icon'])
                                                    <img src="{{ route('assets.items', ['filename' => $data['icon']]) }}"
                                                         class="w-full h-full object-contain drop-shadow-[0_4px_8px_rgba(0,0,0,0.8)] transition-transform duration-300 group-hover:scale-110"
                                                         alt="{{ $name }}">
                                                @else
                                                    <div class="text-3xl">
                                                        @if($data['is_chest']) 📦
                                                        @elseif($data['type'] === 'gems') 💎
                                                        @elseif($data['type'] === 'material') 🌿
                                                        @else ⚔️
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>

                                            {{-- Item Name --}}
                                            <h6 class="text-xs font-bold {{ $data['is_chest'] ? 'text-amber-200' : 'text-slate-300' }} line-clamp-2 leading-tight medieval-font mt-1">
                                                {{ $name }}
                                            </h6>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

                <button wire:click="backToDungeonList"
                    class="rounded-xl px-8 py-3 bg-gradient-to-r from-amber-700 to-amber-600 border border-amber-400/60 text-white font-bold medieval-font shadow-lg hover:scale-105 active:scale-95 transition-all">
                    <i class="fa-solid fa-dungeon mr-2"></i> Powrót do listy lochów
                </button>
                </div>
            </div>
            </div>

        {{-- LOSS --}}
        @elseif($battleResult && ($battleResult['result'] ?? '') === 'loss' && !$showBattle)
            <div class="max-w-2xl mx-auto mt-8">
            <div class="relative rounded-2xl shadow-2xl overflow-hidden bg-slate-950/80 backdrop-blur-xl border border-red-700/60">
                <div class="absolute inset-0 bg-gradient-to-b from-red-900/20 via-transparent to-black/70 pointer-events-none"></div>
                <div class="relative p-8 text-center">
                <div class="text-7xl mb-4 drop-shadow-[0_0_30px_rgba(239,68,68,0.5)]">💀</div>
                <h2 class="text-3xl font-bold text-red-400 medieval-font mb-2">Poległeś!</h2>
                <p class="text-slate-300 text-lg mb-2">Zostałeś pokonany na etapie <strong class="text-red-300">{{ $battleResult['stage'] ?? $currentStage }}</strong>.</p>
                <p class="text-slate-500 mb-6">Twoja ekspedycja dobiegła końca... Wszystkie łupy przepadły.</p>
                <button wire:click="backToDungeonList"
                    class="rounded-xl px-8 py-3 bg-slate-800 border border-slate-600 text-white font-bold medieval-font shadow-lg hover:scale-105 active:scale-95 transition-all hover:bg-slate-700">
                    <i class="fa-solid fa-dungeon mr-2"></i> Powrót do listy lochów
                </button>
                </div>
            </div>
            </div>

        {{-- ACTIVE RUN --}}
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4 lg:gap-5 xl:gap-6 pb-24 lg:pb-6">

                {{-- Left: Player Panel --}}
                <div class="col-span-1 md:col-span-1 lg:col-span-1 order-2 lg:order-1" id="dungeon-player-panel-container">
                    <div id="dungeon-player-panel"
                        class="relative rounded-2xl shadow-2xl overflow-hidden bg-slate-950/80 backdrop-blur-xl border border-amber-500/30 transition-all duration-300">
                        <div class="absolute inset-0 bg-gradient-to-b from-amber-500/10 via-transparent to-black/70 pointer-events-none"></div>

                        <div class="relative p-3 sm:p-4 lg:p-4 xl:p-5 space-y-3 lg:space-y-4">

                            {{-- Player Header & Avatar --}}
                            <div class="text-center">
                                <div class="relative w-20 h-20 sm:w-24 sm:h-24 mx-auto">
                                    <div class="w-full h-full rounded-2xl overflow-hidden ring-4 ring-amber-500/80 shadow-[0_0_25px_rgba(245,158,11,0.35)] bg-slate-900">
                                        <img src="{{ $character->avatar ? asset('img/avatars/' . $character->avatar . '.png') : asset('img/avatars/default.png') }}" alt="{{ $character->name }}" class="w-full h-full object-cover">
                                    </div>
                                    <span class="absolute -bottom-2 left-1/2 -translate-x-1/2 bg-gradient-to-r from-amber-600 to-amber-500 text-amber-950 text-xs font-black px-2.5 py-0.5 rounded-full border border-amber-300 shadow-lg medieval-font">Lvl {{ $character->level }}</span>
                                </div>
                                <h3 class="mt-3 text-base sm:text-lg font-extrabold text-amber-200 medieval-font drop-shadow-[0_2px_6px_rgba(0,0,0,0.9)]">{{ $character->name }}</h3>
                                <p class="text-xs text-amber-400/80">{{ $character->class ?? 'Bohater' }}</p>
                            </div>

                            {{-- HP Bar --}}
                            <div class="space-y-1">
                                <div class="flex justify-between text-xs font-bold text-amber-200 medieval-font">
                                    <span>Życie</span>
                                    @php $displayPlayerHp = $showBattle ? $animatedPlayerHp : $currentHp; @endphp
                                    <span class="font-mono text-emerald-300">{{ $displayPlayerHp }}/{{ $maxHp }}</span>
                                </div>
                                <div class="h-4 w-full rounded-full bg-black/80 ring-1 ring-amber-500/40 p-0.5 shadow-inner">
                                    @php $hpPercent = $maxHp > 0 ? ($displayPlayerHp / $maxHp) * 100 : 0; @endphp
                                    <div class="h-full rounded-full transition-all duration-500 {{ $hpPercent > 50 ? 'bg-gradient-to-r from-emerald-600 via-emerald-500 to-green-400 shadow-[0_0_12px_rgba(16,185,129,0.6)]' : ($hpPercent > 25 ? 'bg-gradient-to-r from-yellow-600 to-yellow-500' : 'bg-gradient-to-r from-red-600 to-red-500') }}"
                                         style="width: {{ $hpPercent }}%"></div>
                                </div>
                            </div>

                            {{-- Mana Bar --}}
                            <div class="space-y-1">
                                <div class="flex justify-between text-xs font-bold text-cyan-200 medieval-font">
                                    <span>Mana</span>
                                    <span class="font-mono text-cyan-300" title="{{ number_format($this->getCurrentPlayerMana()) }}/{{ number_format($character->getMaxMana()) }}">{{ \App\Helpers\FormatHelper::short($this->getCurrentPlayerMana()) }}/{{ \App\Helpers\FormatHelper::short($character->getMaxMana()) }}</span>
                                </div>
                                <div class="h-3 sm:h-3.5 w-full rounded-full bg-black/80 ring-1 ring-cyan-500/40 p-0.5 shadow-inner">
                                    <div class="h-full rounded-full bg-gradient-to-r from-blue-600 via-cyan-500 to-teal-400 shadow-[0_0_12px_rgba(6,182,212,0.6)] transition-all duration-500"
                                         style="width: {{ $this->getPlayerManaPercent() }}%"></div>
                                </div>
                            </div>

                            {{-- Stage Progress --}}
                            <div class="space-y-1">
                                <div class="flex justify-between text-xs font-semibold text-amber-200/80 medieval-font">
                                    <span>Postęp Lochu</span>
                                    <span class="font-mono text-amber-300">Etap {{ $currentStage }}/{{ $totalStages }}</span>
                                </div>
                                <div class="h-2.5 w-full rounded-full bg-red-950/70 ring-1 ring-red-700/40 p-0.5">
                                    @php $stagePercent = $totalStages > 0 ? (($currentStage - 1) / $totalStages) * 100 : 0; @endphp
                                    <div class="h-full rounded-full bg-gradient-to-r from-red-700 via-red-600 to-orange-500 shadow-[0_0_10px_rgba(239,68,68,0.5)] transition-all duration-500" style="width: {{ $stagePercent }}%"></div>
                                </div>
                            </div>

                            {{-- Attributes --}}
                            <div>
                                <h4 class="text-xs font-bold text-amber-200/90 mb-1.5 medieval-font tracking-wide">Atrybuty Bojowe</h4>
                                @php $pStats = $character->getTotalAttributes(); @endphp
                                <div class="grid grid-cols-2 gap-1.5">
                                    <div class="bg-slate-900/90 border border-red-800/40 rounded-xl p-1.5 text-center">
                                        <div class="text-[10px] font-semibold text-red-300">STR (Siła)</div>
                                        <div class="text-sm font-black text-amber-100 font-mono">{{ $pStats['str'] ?? 0 }}</div>
                                    </div>
                                    <div class="bg-slate-900/90 border border-blue-800/40 rounded-xl p-1.5 text-center">
                                        <div class="text-[10px] font-semibold text-blue-300">INT (Wiedza)</div>
                                        <div class="text-sm font-black text-amber-100 font-mono">{{ $pStats['int'] ?? 0 }}</div>
                                    </div>
                                    <div class="bg-slate-900/90 border border-emerald-800/40 rounded-xl p-1.5 text-center">
                                        <div class="text-[10px] font-semibold text-emerald-300">VIT (Witalność)</div>
                                        <div class="text-sm font-black text-amber-100 font-mono">{{ $pStats['vit'] ?? 0 }}</div>
                                    </div>
                                    <div class="bg-slate-900/90 border border-amber-800/40 rounded-xl p-1.5 text-center">
                                        <div class="text-[10px] font-semibold text-amber-300">AGI (Zręczność)</div>
                                        <div class="text-sm font-black text-amber-100 font-mono">{{ $pStats['agi'] ?? 0 }}</div>
                                    </div>
                                </div>
                            </div>

                            {{-- Potions --}}
                            @if($potions->count() > 0)
                                <div class="border-t border-amber-900/40 pt-3">
                                    <h4 class="text-xs font-bold text-amber-200/90 mb-2 medieval-font flex items-center gap-1.5">
                                        <i class="fa-solid fa-flask text-purple-400"></i> Mikstury
                                    </h4>
                                    <div class="space-y-1.5">
                                        @foreach($potions as $potion)
                                            <div class="flex items-center justify-between bg-slate-900/80 rounded-xl px-3 py-2 border border-purple-900/40">
                                                <div>
                                                    <p class="text-xs font-bold text-purple-200">{{ $potion->template->name }}</p>
                                                    <p class="text-[10px] text-slate-500">Leczy: {{ $potion->template->base_stats['heal_amount'] ?? 50 }} HP @if($potion->stack_size > 1)• x{{ $potion->stack_size }}@endif</p>
                                                </div>
                                                <button wire:click="usePotion('{{ $potion->id }}')"
                                                    class="bg-purple-700 hover:bg-purple-600 text-white text-[10px] font-bold py-1.5 px-3 rounded-lg transition-colors border border-purple-500/50"
                                                    wire:loading.attr="disabled" wire:target="usePotion('{{ $potion->id }}')">
                                                    <span wire:loading.remove wire:target="usePotion('{{ $potion->id }}')">Użyj</span>
                                                    <span wire:loading wire:target="usePotion('{{ $potion->id }}')">...</span>
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Center: Kronika Bitwy --}}
                <div class="col-span-1 md:col-span-2 lg:col-span-1 order-1 lg:order-2 mb-2 lg:mb-0">
                <section class="relative rounded-2xl shadow-2xl overflow-hidden bg-slate-950/80 backdrop-blur-xl border border-red-500/30 h-[340px] sm:h-[380px] md:h-[430px] lg:h-[460px] xl:h-[520px] flex flex-col"
                         @if($isCalculating) wire:poll.1s="checkCombatStatus" @endif>

                    <header class="relative p-2 text-center bg-red-950/40 border-b border-red-500/20 backdrop-blur-md">
                        <h3 class="font-serif text-base sm:text-lg lg:text-xl text-amber-200 tracking-wider medieval-font drop-shadow">Kronika Bitwy</h3>
                        @if($run && !$isCalculating)
                            <p class="text-xs text-red-300/80 mt-0.5">Etap {{ $currentStage }} / {{ $totalStages }}</p>
                        @endif
                    </header>

                    <div id="dungeon-battle-log-container" class="relative flex-1 overflow-y-auto p-3 lg:p-4 custom-scrollbar">

                    @if($isCalculating)
                        <div class="h-full flex flex-col items-center justify-center text-center">
                            <div class="relative w-24 h-24 mb-4">
                                <div class="absolute inset-0 rounded-full border-4 border-red-500/30 border-t-red-400 animate-spin"></div>
                                <div class="absolute inset-2 rounded-full border-4 border-red-700/30 border-b-red-600 animate-[spin_1.5s_linear_infinite_reverse]"></div>
                            </div>
                            <h3 class="font-serif text-2xl sm:text-3xl text-amber-200 tracking-wider medieval-font animate-pulse">Obliczanie walki...</h3>
                            <p class="text-amber-300/80 italic mt-2 font-semibold text-sm">Krzyżowanie mieczy...</p>
                        </div>
                    @elseif($showBattle && $battleResult)
                        <ul class="space-y-2 text-amber-100">
                            @if($isPlaying)
                                <li class="text-center py-3">
                                    <button wire:click="skipBattle" class="text-slate-400 hover:text-amber-300 text-sm italic transition-colors font-semibold medieval-font">
                                        <i class="fa-solid fa-forward-fast mr-1"></i> Pomiń animację walki
                                    </button>
                                </li>
                            @endif

                            {{-- Battle log turns --}}
                            <div class="">
                            @foreach($visibleTurns as $index => $turn)
                                <li class="leading-relaxed bg-slate-900/70 border border-red-500/20 rounded-xl px-3 py-2 shadow-sm backdrop-blur-sm text-xs sm:text-sm">
                                    <span class="inline-block w-8 sm:w-9 text-center text-xs font-bold bg-red-900/80 text-red-200 rounded-md border border-red-600/40 px-1 py-0.5 mr-1.5 font-mono">T{{ $index + 1 }}</span>
                                    @if(($turn['type'] ?? '') === 'miss')
                                        <span class="text-slate-300 italic font-semibold">
                                            <strong class="text-amber-200">{{ $turn['actor'] === 'player' ? '🛡️ Ty' : ('👹 ' . ($monster->name ?? 'Potwór')) }}</strong>
                                            pudłuje atak!
                                            @if (!empty($turn['dotDamage']))
                                                <span class="text-purple-400 font-mono font-bold ml-1">(+{{ $turn['dotDamage'] }})</span>
                                            @endif
                                        </span>
                                    @elseif(($turn['type'] ?? '') === 'crowd_controlled')
                                        <span class="text-purple-300 font-semibold italic">
                                            <strong class="text-amber-200">👹 {{ $monster->name ?? 'Potwór' }}</strong> jest zamrożony/ogłuszony i traci turę!
                                        </span>
                                    @elseif(($turn['type'] ?? '') === 'skill_heal')
                                        <span class="text-emerald-300 font-semibold">
                                            <strong class="text-amber-200">🛡️ Ty</strong> używasz <span class="text-indigo-300 font-bold uppercase">{{ $turn['skill_name'] ?? 'Umiejętność' }}</span> i leczysz <strong class="text-emerald-300 font-mono">{{ \App\Helpers\FormatHelper::short($turn['value']) }} HP</strong>!
                                            @if (!empty($turn['dotDamage']))
                                                <span class="text-purple-400 font-mono font-bold ml-1">(+{{ \App\Helpers\FormatHelper::short($turn['dotDamage']) }})</span>
                                            @endif
                                        </span>
                                    @elseif(($turn['type'] ?? '') === 'skill')
                                        <span class="text-blue-300 font-semibold">
                                            <strong class="text-amber-200">{{ $turn['actor'] === 'player' ? '🛡️ Ty' : ('👹 ' . ($monster->name ?? 'Potwór')) }}</strong>
                                            używa <span class="text-indigo-300 font-bold uppercase">{{ $turn['skill_name'] ?? 'Umiejętność' }}</span> i zadaje <strong class="text-amber-300 font-mono">{{ \App\Helpers\FormatHelper::short($turn['value']) }}</strong>
                                            @if (!empty($turn['dotDamage']))
                                                <span class="text-purple-400 font-mono font-bold ml-1">(+{{ \App\Helpers\FormatHelper::short($turn['dotDamage']) }})</span>
                                            @endif
                                            obrażeń
                                            @if (!empty($turn['crit'])) <span class="font-bold text-amber-400">KRYTYK!</span> @endif
                                        </span>
                                    @else
                                        <span class="{{ $turn['actor'] === 'player' ? 'text-emerald-300' : 'text-rose-300' }} font-semibold">
                                            <strong class="text-amber-200">{{ $turn['actor'] === 'player' ? '🛡️ Ty' : ('👹 ' . ($monster->name ?? 'Potwór')) }}</strong>
                                            zadaje <strong class="text-amber-300 font-mono">{{ \App\Helpers\FormatHelper::short($turn['value']) }}</strong> obrażeń
                                            @if (!empty($turn['dotDamage']))
                                                <span class="text-purple-400 font-mono font-bold ml-1">(+{{ \App\Helpers\FormatHelper::short($turn['dotDamage']) }})</span>
                                            @endif
                                            @if(!empty($turn['crit'])) <span class="font-bold text-amber-400">KRYTYK!</span> @endif
                                        </span>
                                    @endif
                                    <span class="text-slate-600 text-xs ml-2 font-mono">[HP: {{ \App\Helpers\FormatHelper::short($turn['playerHp']) }} | Potwór: {{ \App\Helpers\FormatHelper::short($turn['enemyHp']) }}]</span>
                                </li>
                            @endforeach
                            @if($isPlaying)
                                <li class="text-center text-slate-500 text-xs italic py-2 animate-pulse">Trwa walka...</li>
                            @endif

                            @if(!$isPlaying && $battleResult)
                                <li class="text-center mt-4 p-4 rounded-2xl backdrop-blur-md {{ ($battleResult['result'] ?? '') === 'loss' ? 'bg-red-950/80 border border-red-500/40 text-red-200' : (($battleResult['result'] ?? '') === 'dungeon_complete' ? 'bg-amber-950/80 border border-amber-500/40 text-amber-200' : 'bg-emerald-950/80 border border-emerald-500/40 text-emerald-200') }}">
                                    <div class="text-xl font-bold medieval-font">
                                        @if(($battleResult['result'] ?? '') === 'loss') KLĘSKA!
                                        @elseif(($battleResult['result'] ?? '') === 'dungeon_complete') LOCH UKOŃCZONY!
                                        @else ETAP ZALICZONY! @endif
                                    </div>
                                    <p class="text-xs mt-1 opacity-75">Twoje HP po walce: {{ $battleResult['player_hp'] ?? 0 }}</p>
                                </li>
                            @endif
                        </ul>
                    @elseif($monster && !$showBattle)
                        <div class="h-full flex flex-col items-center justify-center text-center p-4">
                            @if(($currentStageModel->stage_type ?? '') === 'gate')
                                <div class="text-6xl mb-3 animate-pulse">🚪</div>
                                <span class="px-3 py-1 bg-amber-500/20 border border-amber-500/50 rounded-full text-amber-300 text-xs font-bold medieval-font uppercase tracking-widest mb-2">
                                    🛡️ Wrota Lochu
                                </span>
                                <h2 class="text-2xl font-bold text-amber-200 mb-2 medieval-font">Etap {{ $currentStage }} z {{ $totalStages }}</h2>
                                <div class="p-3 bg-red-950/60 border border-red-500/40 rounded-xl max-w-sm mb-3">
                                    <p class="text-xs text-red-200 font-semibold">
                                        ⚠️ Gracz musi zniszczyć wrota w <strong>{{ $currentStageModel->max_turns ?? 10 }}</strong> tur! Wrota nie zadają obrażeń, ale posiadają potężny pancerz i zdrowie.
                                    </p>
                                </div>
                            @elseif(($currentStageModel->stage_type ?? '') === 'group_mob')
                                <div class="text-6xl mb-3">⚔️</div>
                                <span class="px-3 py-1 bg-red-500/20 border border-red-500/50 rounded-full text-red-300 text-xs font-bold medieval-font uppercase tracking-widest mb-2">
                                    ⚔️ Grupa Przeciwników (x{{ $currentStageModel->monster_count ?? 2 }})
                                </span>
                                <h2 class="text-2xl font-bold text-amber-200 mb-2 medieval-font">Etap {{ $currentStage }} z {{ $totalStages }}</h2>
                                <p class="text-slate-400 italic text-sm">Przed Tobą stoi grupa niebezpiecznych wrogów. Przygotuj się do walki z wieloma przeciwnikami naraz!</p>
                            @elseif(($currentStageModel->stage_type ?? '') === 'boss')
                                <div class="text-6xl mb-3">👑</div>
                                <span class="px-3 py-1 bg-amber-500/30 border border-amber-400 rounded-full text-amber-200 text-xs font-bold medieval-font uppercase tracking-widest mb-2 animate-bounce">
                                    👑 Władca Lochu (Boss)
                                </span>
                                <h2 class="text-2xl font-bold text-amber-200 mb-2 medieval-font">Ostatni Etap {{ $currentStage }} z {{ $totalStages }}</h2>
                                <p class="text-slate-300 italic text-sm">Finałowe starcie! Tylko pokonanie Bossa gwarantuje otrzymanie zebranego łupu, Jajka Chowańca i materiałów!</p>
                            @elseif(($currentStageModel->stage_type ?? '') === 'miniboss')
                                <div class="text-6xl mb-3">🔥</div>
                                <span class="px-3 py-1 bg-orange-500/20 border border-orange-500/50 rounded-full text-orange-300 text-xs font-bold medieval-font uppercase tracking-widest mb-2">
                                    🔥 Mini-Boss Etapu
                                </span>
                                <h2 class="text-2xl font-bold text-amber-200 mb-2 medieval-font">Etap {{ $currentStage }} z {{ $totalStages }}</h2>
                                <p class="text-slate-400 italic text-sm">Potężniejszy strażnik lochu zagraża Twojej ekspedycji. Bądź ostrożny!</p>
                            @else
                                <div class="text-5xl mb-4">🚪</div>
                                <h3 class="text-lg text-amber-400/80 uppercase tracking-widest mb-2 font-bold medieval-font">Wyzwanie Etapu</h3>
                                <h2 class="text-3xl font-bold text-amber-200 mb-3 medieval-font">Etap {{ $currentStage }} z {{ $totalStages }}</h2>
                                <p class="text-slate-400 italic text-sm">Z mroku wyłania się kolejny przeciwnik. Przygotuj się do walki!</p>
                            @endif
                        </div>
                    @endif

                    </div>

                    {{-- Battle Controls --}}
                    <footer class="relative p-3 lg:p-3.5 bg-red-950/40 border-t border-red-500/20 backdrop-blur-md">
                        <div class="flex items-center justify-center gap-2.5">
                            @if($isCalculating)
                                <span class="text-red-300/60 text-xs medieval-font animate-pulse">Obliczanie walki w tle...</span>
                            @elseif($showBattle && $isPlaying)
                                <button wire:click="skipBattle"
                                    class="rounded-xl px-5 py-2.5 bg-slate-800/80 border border-slate-600 text-amber-200 font-bold hover:bg-slate-700 transition-all medieval-font text-xs sm:text-sm">
                                    <i class="fa-solid fa-forward-fast mr-1"></i> Pomiń animację
                                </button>
                            @elseif($showBattle && !$isPlaying && $battleResult)
                                <button wire:click="dismissBattle"
                                    class="rounded-xl px-6 py-2.5 sm:px-7 sm:py-3 bg-gradient-to-r from-red-700 via-red-600 to-red-700 border border-red-400/60 text-white font-extrabold text-sm sm:text-base medieval-font shadow-[0_0_20px_rgba(239,68,68,0.4)] hover:scale-105 active:scale-95 transition-all">
                                    @if(($battleResult['result'] ?? '') === 'stage_clear')
                                        <i class="fa-solid fa-arrow-right mr-1"></i> Następny Etap
                                    @elseif(($battleResult['result'] ?? '') === 'dungeon_complete')
                                        <i class="fa-solid fa-trophy mr-1"></i> Zobacz Podsumowanie
                                    @else
                                        <i class="fa-solid fa-skull mr-1"></i> Podsumowanie Porażki
                                    @endif
                                </button>
                            @elseif($monster && !$showBattle && !$isCalculating)
                                <button wire:click="fight"
                                    class="rounded-xl px-6 py-2.5 sm:px-7 sm:py-3 bg-gradient-to-r from-red-700 via-red-600 to-red-700 border border-red-400/60 text-white font-extrabold text-sm sm:text-base medieval-font shadow-[0_0_20px_rgba(239,68,68,0.4)] hover:scale-105 active:scale-95 transition-all"
                                    wire:loading.attr="disabled" wire:loading.class="opacity-50 cursor-not-allowed">
                                    <span wire:loading.remove wire:target="fight"><i class="fa-solid fa-swords mr-1"></i> Rozpocznij Walkę</span>
                                    <span wire:loading wire:target="fight">Uruchamianie...</span>
                                </button>
                            @endif
                        </div>
                    </footer>
                </section>
                </div>

                {{-- Right: Enemy Panel --}}
                <div class="col-span-1 md:col-span-1 lg:col-span-1 order-3 lg:order-3" id="dungeon-enemy-panel-container">
                    @if($monster)
                        <div id="dungeon-enemy-panel"
                            class="relative rounded-2xl shadow-2xl overflow-hidden bg-slate-950/80 backdrop-blur-xl border border-red-500/30 transition-all duration-300">
                            <div class="absolute inset-0 bg-gradient-to-b from-red-500/10 via-transparent to-black/70 pointer-events-none"></div>

                            <div class="relative p-3.5 sm:p-4 lg:p-4 xl:p-6 space-y-3 lg:space-y-3.5 xl:space-y-5">

                                {{-- Enemy Header & Avatar --}}
                                <div class="text-center">
                                    <div class="relative w-20 h-20 sm:w-24 sm:h-24 mx-auto">
                                        <div class="w-full h-full rounded-2xl overflow-hidden ring-4 ring-red-600/80 shadow-[0_0_25px_rgba(239,68,68,0.35)] bg-slate-900">
                                            @if(!empty($monster->avatar))
                                                <img src="{{ route('assets.monsters.avatars', ['filename' => $monster->avatar]) }}" alt="{{ $monster->name }}" class="w-full h-full object-cover">
                                            @else
                                                <img src="{{ asset('img/monsters/placeholder.png') }}" alt="{{ $monster->name }}" class="w-full h-full object-cover">
                                            @endif
                                        </div>
                                        <span class="absolute -bottom-2 left-1/2 -translate-x-1/2 bg-gradient-to-r from-red-700 to-rose-600 text-red-100 text-xs font-black px-2.5 py-0.5 rounded-full border border-red-400 shadow-lg medieval-font">Lvl {{ $monster->level }}</span>
                                    </div>
                                    <h3 class="mt-3 text-base sm:text-lg font-extrabold text-red-200 medieval-font drop-shadow-[0_2px_6px_rgba(0,0,0,0.9)]">{{ $monster->name }}</h3>
                                    <p class="text-xs text-red-400/80">Etap {{ $currentStage }} / {{ $totalStages }} @if($monster->rank) • {{ $monster->rank?->label() }} @endif</p>
                                </div>

                                {{-- Enemy HP Bar --}}
                                <div class="space-y-1">
                                    <div class="flex justify-between text-xs font-bold text-red-200 medieval-font">
                                        <span>Życie</span>
                                        @php
                                            $baseMonsterHp = $monster->stats['hp'] ?? $monster->level * 20;
                                            $displayMonsterHp = ($showBattle && !$isCalculating) ? $animatedEnemyHp : $baseMonsterHp;
                                        @endphp
                                        <span class="font-mono text-red-300">
                                            @if($isCalculating)<span class="animate-pulse">?</span>@else{{ $displayMonsterHp }}@endif / {{ $baseMonsterHp }}
                                        </span>
                                    </div>
                                    <div class="h-4 w-full rounded-full bg-black/80 ring-1 ring-red-500/40 p-0.5 shadow-inner">
                                        @php $enemyHpPercent = $baseMonsterHp > 0 ? ($displayMonsterHp / $baseMonsterHp) * 100 : 0; @endphp
                                        <div class="h-full rounded-full bg-gradient-to-r from-red-700 via-red-500 to-rose-400 shadow-[0_0_12px_rgba(239,68,68,0.6)] transition-all duration-500" style="width: {{ $enemyHpPercent }}%"></div>
                                    </div>
                                </div>

                                {{-- Enemy Stats --}}
                                <div>
                                    <h4 class="text-xs font-bold text-red-200/90 mb-1.5 medieval-font">Statystyki Potwora</h4>
                                    <div class="grid grid-cols-2 gap-1.5">
                                        <div class="bg-slate-900/90 border border-red-900/40 rounded-xl p-1.5 text-center">
                                            <div class="text-[10px] font-semibold text-red-400 flex items-center justify-center gap-1"><i class="fa-solid fa-crosshairs"></i> ATK</div>
                                            <div class="text-sm font-black text-red-200 font-mono">{{ $monster->stats['atk'] ?? $monster->level * 2 }}</div>
                                        </div>
                                        <div class="bg-slate-900/90 border border-blue-900/40 rounded-xl p-1.5 text-center">
                                            <div class="text-[10px] font-semibold text-blue-400 flex items-center justify-center gap-1"><i class="fa-solid fa-shield"></i> DEF</div>
                                            <div class="text-sm font-black text-blue-200 font-mono">{{ $monster->stats['def'] ?? $monster->level }}</div>
                                        </div>
                                        <div class="bg-slate-900/90 border border-emerald-900/40 rounded-xl p-1.5 text-center">
                                            <div class="text-[10px] font-semibold text-emerald-400 flex items-center justify-center gap-1"><i class="fa-solid fa-wind"></i> AGI</div>
                                            <div class="text-sm font-black text-emerald-200 font-mono">{{ $monster->stats['agi'] ?? $monster->level }}</div>
                                        </div>
                                        <div class="bg-slate-900/90 border border-rose-900/40 rounded-xl p-1.5 text-center">
                                            <div class="text-[10px] font-semibold text-rose-400 flex items-center justify-center gap-1"><i class="fa-solid fa-heart"></i> MAX HP</div>
                                            <div class="text-sm font-black text-rose-200 font-mono">{{ \App\Helpers\FormatHelper::short($baseMonsterHp) }}</div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Accumulated Loot Preview --}}
                                @if($run && $run->accumulated_loot && (($run->accumulated_loot['xp'] ?? 0) > 0 || ($run->accumulated_loot['gold'] ?? 0) > 0 || !empty($run->accumulated_loot['items'])))
                                    <div class="border-t border-amber-900/40 pt-3">
                                        <h4 class="text-xs font-bold text-amber-200/90 mb-2 medieval-font flex items-center gap-1.5">
                                            <i class="fa-solid fa-bag-shopping text-amber-400"></i> Skumulowany Łup
                                        </h4>
                                        <div class="grid grid-cols-2 gap-1.5 mb-2">
                                            @if(($run->accumulated_loot['xp'] ?? 0) > 0)
                                                <div class="bg-slate-900/80 border border-indigo-900/40 rounded-xl p-1.5 text-center">
                                                    <div class="text-[10px] text-indigo-400">XP</div>
                                                    <div class="text-xs font-bold text-indigo-200 font-mono">+{{ \App\Helpers\FormatHelper::short($run->accumulated_loot['xp']) }}</div>
                                                </div>
                                            @endif
                                            @if(($run->accumulated_loot['gold'] ?? 0) > 0)
                                                <div class="bg-slate-900/80 border border-yellow-900/40 rounded-xl p-1.5 text-center">
                                                    <div class="text-[10px] text-yellow-400">Złoto</div>
                                                    <div class="text-xs font-bold text-yellow-200 font-mono">+{{ \App\Helpers\FormatHelper::short($run->accumulated_loot['gold']) }}</div>
                                                </div>
                                            @endif
                                        </div>

                                        @if(!empty($run->accumulated_loot['items']))
                                            @php
                                                $previewGrouped = [];
                                                foreach ($run->accumulated_loot['items'] as $item) {
                                                    $name = $item['name'];
                                                    if (!isset($previewGrouped[$name])) {
                                                        $icon = $item['icon'] ?? null;
                                                        if (!$icon && !empty($item['ref_ulid'])) {
                                                            $icon = \App\Infrastructure\Persistence\ItemTemplate::where('id', $item['ref_ulid'])->value('icon');
                                                        }
                                                        if (!$icon) {
                                                            $icon = \App\Infrastructure\Persistence\ItemTemplate::where('name', $name)->value('icon');
                                                        }
                                                        $previewGrouped[$name] = [
                                                            'name' => $name,
                                                            'quantity' => 0,
                                                            'icon' => $icon,
                                                            'is_chest' => (str_contains(mb_strtolower($name), 'skrzyn') || ($item['type'] ?? '') === 'chest'),
                                                        ];
                                                    }
                                                    $previewGrouped[$name]['quantity'] += $item['quantity'];
                                                }
                                            @endphp
                                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-1.5 pt-1">
                                                @foreach($previewGrouped as $pData)
                                                    <div class="relative bg-slate-900/90 border {{ $pData['is_chest'] ? 'border-amber-500/80 shadow-[0_0_12px_rgba(245,158,11,0.3)] bg-amber-950/20' : 'border-slate-800' }} rounded-lg p-1.5 flex flex-col items-center justify-center text-center">
                                                        <span class="absolute -top-1.5 -right-1 bg-gradient-to-r from-amber-500 to-yellow-400 text-slate-950 font-black text-[9px] px-1 rounded-full border border-yellow-200 font-mono z-10">
                                                            {{ $pData['quantity'] }}x
                                                        </span>
                                                        <div class="w-8 h-8 my-0.5 flex items-center justify-center">
                                                            @if($pData['icon'])
                                                                <img src="{{ route('assets.items', ['filename' => $pData['icon']]) }}" class="w-full h-full object-contain drop-shadow" alt="{{ $pData['name'] }}">
                                                            @else
                                                                <span class="text-xs">{{ $pData['is_chest'] ? '📦' : '⚔️' }}</span>
                                                            @endif
                                                        </div>
                                                        <span class="text-[9px] {{ $pData['is_chest'] ? 'text-amber-300 font-bold' : 'text-slate-300 font-semibold' }} truncate w-full px-0.5 medieval-font">{{ $pData['name'] }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>

            </div>
        @endif
    </div>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700;900&display=swap');

        .medieval-font { font-family: 'Cinzel', serif; }

        .fct-damage-number-dungeon {
            position: fixed;
            pointer-events: none;
            z-index: 200;
            font-family: 'Cinzel', serif;
            animation: fct-float 0.85s ease-out forwards;
            text-shadow: 0 2px 12px rgba(0,0,0,0.9);
        }
        @keyframes fct-float {
            0%   { transform: translateY(0) scale(1); opacity: 1; }
            60%  { transform: translateY(-50px) scale(1.15); opacity: 1; }
            100% { transform: translateY(-85px) scale(0.7); opacity: 0; }
        }

        .anim-lunge-player-d { animation: lunge-player-d 0.35s cubic-bezier(0.25, 0.46, 0.45, 0.94) both; }
        .anim-lunge-enemy-d  { animation: lunge-enemy-d 0.35s cubic-bezier(0.25, 0.46, 0.45, 0.94) both; }
        .anim-hit-bounce-d   { animation: hit-bounce-d 0.45s cubic-bezier(.36,.07,.19,.97) both; }

        @keyframes lunge-player-d {
            0%   { transform: translateX(0) scale(1); }
            40%  { transform: translateX(14px) scale(1.03); filter: brightness(1.3); }
            70%  { transform: translateX(-4px) scale(0.98); }
            100% { transform: translateX(0) scale(1); filter: brightness(1); }
        }
        @keyframes lunge-enemy-d {
            0%   { transform: translateX(0) scale(1); }
            40%  { transform: translateX(-14px) scale(1.03); filter: brightness(1.3); }
            70%  { transform: translateX(4px) scale(0.98); }
            100% { transform: translateX(0) scale(1); filter: brightness(1); }
        }
        @keyframes hit-bounce-d {
            10%, 90% { transform: translate3d(-2px, 0, 0); border-color: rgba(239, 68, 68, 0.8); }
            20%, 80% { transform: translate3d(3px, 0, 0); }
            30%, 50%, 70% { transform: translate3d(-5px, 0, 0); }
            40%, 60% { transform: translate3d(5px, 0, 0); }
        }

        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: rgba(17,24,39,0.5); border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(185,28,28,0.5); border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(220,38,38,0.7); }
    </style>

    @script
    <script>
        let dungeonPlaybackInterval = null;
        let userHasScrolledUp = false;

        function scrollDungeonLogToBottom(force = false) {
            const container = document.getElementById('dungeon-battle-log-container');
            if (!container) return;
            if (force) userHasScrolledUp = false;
            const distanceFromBottom = container.scrollHeight - container.scrollTop - container.clientHeight;
            if (force || !userHasScrolledUp || distanceFromBottom < 150) {
                container.scrollTop = container.scrollHeight;
            }
        }

        const dungeonLogContainer = document.getElementById('dungeon-battle-log-container');
        if (dungeonLogContainer) {
            if (window._dungeonLogObserver) window._dungeonLogObserver.disconnect();
            window._dungeonLogObserver = new MutationObserver(() => scrollDungeonLogToBottom());
            window._dungeonLogObserver.observe(dungeonLogContainer, { childList: true, subtree: true });
            dungeonLogContainer.addEventListener('scroll', () => {
                const dist = dungeonLogContainer.scrollHeight - dungeonLogContainer.scrollTop - dungeonLogContainer.clientHeight;
                userHasScrolledUp = dist > 150;
            }, { passive: true });
        }

        function spawnDungeonImpactParticles(targetPanel, pType) {
            const rect = targetPanel.getBoundingClientRect();
            const fxOverlay = document.getElementById('dungeon-combat-fx-overlay');
            if (!fxOverlay) return;
            const centerX = rect.left + rect.width / 2;
            const centerY = rect.top + rect.height / 3;
            const particleCount = pType === 'crit' ? 16 : 10;
            const color = pType === 'crit' ? '#fbbf24' : '#f87171';
            const shadowColor = pType === 'crit' ? 'rgba(245,158,11,0.9)' : 'rgba(239,68,68,0.8)';
            for (let i = 0; i < particleCount; i++) {
                const p = document.createElement('div');
                p.className = 'fixed pointer-events-none z-[195] rounded-full';
                const size = Math.floor(Math.random() * 10) + 6;
                p.style.width = `${size}px`; p.style.height = `${size}px`;
                p.style.left = `${centerX}px`; p.style.top = `${centerY}px`;
                p.style.backgroundColor = color;
                p.style.boxShadow = `0 0 12px ${shadowColor}`;
                fxOverlay.appendChild(p);
                const angle = (i / particleCount) * Math.PI * 2 + (Math.random() * 0.4 - 0.2);
                const distance = Math.floor(Math.random() * 70) + 30;
                p.animate([
                    { transform: 'translate(-50%,-50%) scale(1.2)', opacity: 1 },
                    { transform: `translate(calc(${Math.cos(angle)*distance}px - 50%),calc(${Math.sin(angle)*distance}px - 50%)) scale(0)`, opacity: 0 }
                ], { duration: 450 + Math.random() * 250, easing: 'cubic-bezier(0.1,0.8,0.3,1)', fill: 'forwards' });
                setTimeout(() => { if (p.parentNode) p.parentNode.removeChild(p); }, 750);
            }
        }

        $wire.on('start-playback', (e) => {
            userHasScrolledUp = false;
            scrollDungeonLogToBottom(true);
            let speedMultiplier = e.speed || 1;
            let intervalTime = 600 / speedMultiplier;
            if (dungeonPlaybackInterval) clearInterval(dungeonPlaybackInterval);
            dungeonPlaybackInterval = setInterval(() => {
                $wire.dispatch('resume-playback');
                scrollDungeonLogToBottom();
            }, intervalTime);
        });

        $wire.on('stop-playback', () => {
            if (dungeonPlaybackInterval) { clearInterval(dungeonPlaybackInterval); dungeonPlaybackInterval = null; }
        });

        $wire.on('turn-played', (event) => {
            scrollDungeonLogToBottom();
            const data = (event && event[0]) ? event[0] : event;
            const actor = data.actor;
            const type = data.type;
            const isCrit = data.crit || false;
            const value = data.value || 0;

            const playerPanel = document.getElementById('dungeon-player-panel-container');
            const enemyPanel  = document.getElementById('dungeon-enemy-panel-container');
            const fxOverlay   = document.getElementById('dungeon-combat-fx-overlay');

            if (!playerPanel || !enemyPanel) return;

            playerPanel.classList.remove('anim-lunge-player-d','anim-lunge-enemy-d','anim-hit-bounce-d');
            enemyPanel.classList.remove('anim-lunge-player-d','anim-lunge-enemy-d','anim-hit-bounce-d');
            void playerPanel.offsetWidth;
            void enemyPanel.offsetWidth;

            const attackerPanel = actor === 'player' ? playerPanel : enemyPanel;
            const defenderPanel = actor === 'player' ? enemyPanel : playerPanel;

            if (actor === 'player') playerPanel.classList.add('anim-lunge-player-d');
            else enemyPanel.classList.add('anim-lunge-enemy-d');

            const defenderRect = defenderPanel.getBoundingClientRect();
            setTimeout(() => {
                if (type !== 'miss') {
                    defenderPanel.classList.add('anim-hit-bounce-d');
                    spawnDungeonImpactParticles(defenderPanel, isCrit ? 'crit' : 'hit');
                }
                if (fxOverlay) {
                    const fct = document.createElement('div');
                    fct.className = 'fct-damage-number-dungeon';
                    fct.style.left = `${defenderRect.left + defenderRect.width / 2}px`;
                    fct.style.top = `${defenderRect.top + defenderRect.height / 3 - 20}px`;
                    function formatShortNum(num) {
                        if (num === null || num === undefined) return '0';
                        const abs = Math.abs(num);
                        const sign = num < 0 ? '-' : '';
                        if (abs >= 1000000000) { let v = (abs / 1000000000).toFixed(1); return sign + (v.endsWith('.0') ? v.slice(0, -2) : v) + 'B'; }
                        if (abs >= 1000000) { let v = (abs / 1000000).toFixed(1); return sign + (v.endsWith('.0') ? v.slice(0, -2) : v) + 'M'; }
                        if (abs >= 1000) { let v = (abs / 1000).toFixed(1); return sign + (v.endsWith('.0') ? v.slice(0, -2) : v) + 'k'; }
                        return sign + Math.floor(abs);
                    }
                    if (type === 'miss') fct.innerHTML = `<span class="text-blue-300 font-black text-2xl">UNIK!</span>`;
                    else if (isCrit) fct.innerHTML = `<span class="text-amber-300 font-black text-3xl drop-shadow-[0_0_25px_rgba(245,158,11,1)]">KRYTYK! -${formatShortNum(value)}</span>`;
                    else fct.innerHTML = `<span class="text-red-400 font-black text-2xl drop-shadow-[0_2px_10px_rgba(0,0,0,0.9)]">-${formatShortNum(value)}</span>`;
                    fxOverlay.appendChild(fct);
                    setTimeout(() => { if (fct.parentNode) fct.parentNode.removeChild(fct); }, 900);
                }
            }, 170);
        });

        $wire.on('scroll-to-bottom', () => { setTimeout(() => scrollDungeonLogToBottom(true), 50); });

        document.addEventListener('livewire:navigating', () => {
            if (dungeonPlaybackInterval) clearInterval(dungeonPlaybackInterval);
        });
    </script>
    @endscript
</div>
