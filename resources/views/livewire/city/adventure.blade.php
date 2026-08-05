<div x-data="{ travelingTo: null }"
    class="min-h-screen bg-slate-950 text-amber-100 relative overflow-hidden font-sans selection:bg-amber-500 selection:text-slate-950">
    
    {{-- Dynamic Adventure Background --}}
    <div class="absolute inset-0 bg-cover bg-center bg-no-repeat opacity-40 mix-blend-luminosity scale-105 transition-transform duration-1000"
        style="background-image: url('{{ asset('img/adventure-background.png') }}');">
    </div>

    {{-- Dark vignette & fog overlays --}}
    <div class="absolute inset-0 bg-gradient-to-b from-slate-950/90 via-slate-950/75 to-slate-950/95"></div>
    <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_center,_var(--tw-gradient-stops))] from-amber-900/10 via-transparent to-slate-950/80 pointer-events-none"></div>

    {{-- Floating Ember / Adventure particles --}}
    <div class="absolute inset-0 pointer-events-none overflow-hidden z-0">
        <div class="adventure-element adventure-element-1">⚔️</div>
        <div class="adventure-element adventure-element-2">🗡️</div>
        <div class="adventure-element adventure-element-3">🛡️</div>
        <div class="adventure-element adventure-element-4">💎</div>
        <div class="adventure-element adventure-element-5">🏹</div>
    </div>

    {{-- Map Travel Transition Modal Overlay --}}
    <div x-show="$data.travelingTo" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         class="fixed inset-0 z-[100] flex items-center justify-center bg-black/85 backdrop-blur-md"
         style="display: none;">
         
         <div class="relative w-full max-w-lg mx-auto p-8 text-center bg-slate-900/90 border-2 border-emerald-500/80 rounded-2xl shadow-[0_0_50px_rgba(16,185,129,0.3)]">
            <div class="relative z-10 flex flex-col items-center">
                <div class="text-6xl mb-4 animate-bounce filter drop-shadow-[0_0_15px_rgba(16,185,129,0.5)]">🗺️</div>
                <h2 class="text-3xl font-bold text-amber-200 medieval-font mb-2 tracking-wide drop-shadow-md">
                    Wyruszasz na wyprawę...
                </h2>
                <h3 class="text-2xl text-emerald-400 font-bold drop-shadow-md mb-6 medieval-font" x-text="$data.travelingTo"></h3>
                
                <div class="w-14 h-14 border-4 border-emerald-500 border-t-transparent rounded-full animate-spin shadow-lg"></div>
            </div>
         </div>
    </div>

    <div class="relative z-10 w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 min-h-screen flex flex-col">
        @php
            $gameStage = auth()->user()->game_stage;
        @endphp

        @if($gameStage == 9)
            <livewire:global.tutorial-overlay :step="10" />
        @endif



        {{-- Section Title & Subtitle --}}
        <div class="text-center mb-8">
            <h1 class="text-4xl sm:text-5xl font-black bg-gradient-to-r from-amber-200 via-emerald-300 to-amber-300 bg-clip-text text-transparent medieval-font drop-shadow-lg tracking-wider mb-2 flex items-center justify-center gap-3">
                <i class="fa-solid fa-map-location-dot text-amber-400"></i>
                <span>Wybierz Przygodę</span>
            </h1>
            <p class="text-sm sm:text-base text-slate-300 max-w-xl mx-auto">
                Twój poziom: <span class="text-emerald-400 font-bold">{{ $character->level }}</span> • Wybierz odpowiednią mapę lub loch, by zdobywać doświadczenie i cenny łup.
            </p>

            {{-- Tab Switcher --}}
            <div class="inline-flex bg-slate-900/90 rounded-xl p-1.5 border border-slate-800 mt-6 shadow-inner">
                @if(!$character->hasActiveMirror())
                    <button wire:click="setTab('maps')" 
                        class="px-6 py-2.5 rounded-lg font-bold text-sm sm:text-base transition-all duration-300 medieval-font flex items-center gap-2 {{ $tab === 'maps' ? 'bg-gradient-to-r from-emerald-700 to-emerald-600 text-white shadow-lg border border-emerald-500/50' : 'text-slate-400 hover:text-amber-200 hover:bg-slate-800/60' }}">
                        <i class="fa-solid fa-tree text-emerald-400"></i>
                        <span>Mapy</span>
                        <span class="text-xs px-2 py-0.5 rounded-full {{ $tab === 'maps' ? 'bg-emerald-950 text-emerald-200' : 'bg-slate-800 text-slate-400' }}">{{ $maps->count() }}</span>
                    </button>
                @endif
                <button wire:click="setTab('dungeons')" 
                    class="px-6 py-2.5 rounded-lg font-bold text-sm sm:text-base transition-all duration-300 medieval-font flex items-center gap-2 {{ $tab === 'dungeons' ? 'bg-gradient-to-r from-amber-700 to-amber-600 text-white shadow-lg border border-amber-500/50' : 'text-slate-400 hover:text-amber-200 hover:bg-slate-800/60' }}">
                    <i class="fa-solid fa-dungeon text-amber-400"></i>
                    <span>Lochy</span>
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $tab === 'dungeons' ? 'bg-amber-950 text-amber-200' : 'bg-slate-800 text-slate-400' }}">{{ $dungeonCount }}</span>
                </button>
                <button wire:click="setTab('worldboss')"
                    class="px-6 py-2.5 rounded-lg font-bold text-sm sm:text-base transition-all duration-300 medieval-font flex items-center gap-2 {{ $tab === 'worldboss' ? 'bg-gradient-to-r from-purple-700 to-purple-600 text-white shadow-lg border border-purple-500/50' : 'text-slate-400 hover:text-amber-200 hover:bg-slate-800/60' }}">
                    <i class="fa-solid fa-crown text-purple-400"></i>
                    <span>Worldboss</span>
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $tab === 'purple-950' ? 'bg-purple-950 text-purple-200' : 'bg-slate-800 text-slate-400' }}">3</span>
                </button>
            </div>

            @if($character->hasActiveMirror())
                <div class="mt-4 p-3 bg-purple-950/80 border border-purple-500/50 rounded-xl backdrop-blur-md max-w-xl mx-auto shadow-xl">
                    <p class="text-purple-200 font-semibold text-xs sm:text-sm text-center flex items-center justify-center gap-2">
                        <i class="fa-solid fa-wand-magic-sparkles text-purple-400"></i>
                        <span>Lustro jest aktywne! Zwykłe Mapy są ukryte &mdash; możesz walczyć w <strong>Lochach</strong> oraz z <strong>World Bossami</strong>.</span>
                    </p>
                </div>
            @endif

            {{-- Przycisk: Rankingi Tygodniowe --}}
            <div class="mt-5 flex justify-center">
                <button wire:click="openRankingsModal"
                    id="btn-weekly-rankings"
                    class="group relative inline-flex items-center gap-3 px-7 py-3 rounded-xl font-bold text-sm sm:text-base medieval-font
                           bg-gradient-to-r from-yellow-600/90 via-amber-500/90 to-yellow-600/90
                           border border-yellow-400/60 text-yellow-100
                           shadow-[0_0_20px_rgba(234,179,8,0.35)] hover:shadow-[0_0_35px_rgba(234,179,8,0.6)]
                           transition-all duration-300 hover:scale-105 hover:from-yellow-500 hover:via-amber-400 hover:to-yellow-500">
                    <i class="fa-solid fa-trophy text-yellow-300 text-lg group-hover:animate-bounce"></i>
                    <span>Rankingi Tygodniowe</span>
                    <span class="absolute -top-1 -right-1 flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-yellow-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-yellow-300"></span>
                    </span>
                </button>
            </div>
        </div>


        {{-- ====================================================== --}}
        {{-- MODAL: RANKINGI TYGODNIOWE                              --}}
        {{-- ====================================================== --}}
        @if($showRankingsModal)
        @php
            $categoryLabels = \App\Application\Rankings\WeeklyRankingService::CATEGORY_LABELS;
            $categoryIcons  = \App\Application\Rankings\WeeklyRankingService::CATEGORY_ICONS;
            $categoryColors = \App\Application\Rankings\WeeklyRankingService::CATEGORY_COLORS;
            $categories     = array_keys($categoryLabels);
            $rewardsMap     = [1 => 300, 2 => 250, 3 => 200];
        @endphp
        <div
            wire:key="weekly-rankings-modal"
            x-data="{
                activeTab: '{{ $categories[0] ?? 'monsters_killed' }}',
                init() {
                    document.addEventListener('keydown', (e) => { if(e.key === 'Escape') $wire.closeRankingsModal() })
                }
            }"
            class="fixed inset-0 z-[200] flex items-center justify-center p-3 sm:p-6"
            style="background: rgba(2,6,23,0.92); backdrop-filter: blur(8px);"
            wire:click.self="closeRankingsModal">

            <div class="relative w-full max-w-3xl max-h-[92vh] flex flex-col
                        bg-gradient-to-b from-slate-900 to-slate-950
                        border border-yellow-500/40 rounded-2xl shadow-[0_0_60px_rgba(234,179,8,0.2)] overflow-hidden">

                {{-- Header --}}
                <div class="flex items-center justify-between px-5 py-4 border-b border-yellow-500/20
                            bg-gradient-to-r from-yellow-900/30 via-slate-900 to-yellow-900/30 flex-shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg bg-yellow-500/20 border border-yellow-500/40 flex items-center justify-center">
                            <i class="fa-solid fa-trophy text-yellow-400"></i>
                        </div>
                        <div>
                            <h2 class="text-lg font-black text-amber-100 medieval-font tracking-wide">Rankingi Tygodniowe</h2>
                            @if($nextReset)
                            <p class="text-xs text-slate-400">
                                <i class="fa-regular fa-clock mr-1 text-amber-500/70"></i>
                                Reset: <span class="text-amber-400 font-semibold">{{ $nextReset->format('d.m H:i') }}</span>
                            </p>
                            @endif
                        </div>
                    </div>
                    <button wire:click="closeRankingsModal"
                        class="w-8 h-8 rounded-lg bg-slate-800 border border-slate-700 text-slate-400
                               hover:text-white hover:border-red-500/60 hover:bg-red-900/30 transition-all duration-200
                               flex items-center justify-center">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                {{-- Legenda nagród --}}
                <div class="flex items-center justify-center gap-2 sm:gap-4 px-4 py-2.5 border-b border-slate-800 flex-shrink-0
                            bg-slate-900/60 flex-wrap text-xs">
                    <span class="text-slate-500 font-semibold uppercase tracking-wider mr-1">Nagrody:</span>
                    <span class="flex items-center gap-1 text-yellow-300 font-bold">
                        <i class="fa-solid fa-medal text-yellow-400"></i>#1 — 300
                        <i class="fa-solid fa-gem text-purple-400 text-xs"></i>
                    </span>
                    <span class="text-slate-600">•</span>
                    <span class="flex items-center gap-1 text-slate-300 font-bold">
                        <i class="fa-solid fa-medal text-slate-400"></i>#2 — 250
                        <i class="fa-solid fa-gem text-purple-400 text-xs"></i>
                    </span>
                    <span class="text-slate-600">•</span>
                    <span class="flex items-center gap-1 text-amber-700 font-bold">
                        <i class="fa-solid fa-medal text-amber-800"></i>#3 — 200
                        <i class="fa-solid fa-gem text-purple-400 text-xs"></i>
                    </span>
                    <span class="text-slate-600">•</span>
                    <span class="flex items-center gap-1 text-slate-400 font-semibold">
                        #4-10 — 100
                        <i class="fa-solid fa-gem text-purple-400 text-xs"></i>
                    </span>
                </div>

                {{-- Category Tabs (2 rzędy, bez poziomego przewijania) --}}
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 p-3 flex-shrink-0 border-b border-slate-800/80 bg-slate-950/40">
                    @foreach($categories as $cat)
                    @php
                        $icon  = $categoryIcons[$cat] ?? 'fa-star';
                        $color = $categoryColors[$cat] ?? 'text-slate-400';
                        $label = $categoryLabels[$cat];
                    @endphp
                    <button
                        @click="activeTab = '{{ $cat }}'"
                        :class="activeTab === '{{ $cat }}'
                            ? 'bg-slate-800 border-yellow-400/80 text-amber-200 shadow-md shadow-amber-500/10 ring-1 ring-yellow-500/30'
                            : 'bg-slate-900/80 hover:bg-slate-800/80 border-slate-700/60 text-slate-400 hover:text-slate-200'"
                        class="flex items-center justify-center gap-2 px-3 py-2 text-xs font-bold transition-all duration-200 rounded-lg border text-center cursor-pointer">
                        <i class="fa-solid {{ $icon }} {{ $color }} text-xs flex-shrink-0"></i>
                        <span class="truncate">{{ $label }}</span>
                    </button>
                    @endforeach
                </div>

                {{-- Ranking content --}}
                <div class="flex-1 overflow-y-auto p-4 sm:p-5 space-y-3">
                    @foreach($categories as $cat)
                    @php
                        $data        = $weeklyRankings[$cat] ?? ['leaderboard' => collect(), 'player' => ['rank' => null, 'score' => 0]];
                        $leaderboard = $data['leaderboard'];
                        $playerRank  = $data['player'];
                        $icon        = $categoryIcons[$cat] ?? 'fa-star';
                        $color       = $categoryColors[$cat] ?? 'text-slate-400';
                        $label       = $categoryLabels[$cat];
                    @endphp
                    <div x-show="activeTab === '{{ $cat }}'" x-cloak>

                        {{-- Info o tytułach czasowych dla wybranej kategorii --}}
                        @php
                            $categoryTitleInfo = match($cat) {
                                'monsters_killed'    => ['top1' => 'Top 1 Łowca (+5% Dmg vs Potwory, +20 Atak)', 'top2' => 'Top 2 Łowca (+3%, +12 Atak)', 'top3' => 'Top 3 Łowca (+1%, +6 Atak)'],
                                'world_boss_damage'  => ['top1' => 'Top 1 Pogromca Bossów (+5% Dmg vs Boss, +2% Kryt)', 'top2' => 'Top 2 (+3%, +1% Kryt)', 'top3' => 'Top 3 (+1%, +0.5% Kryt)'],
                                'dungeons_completed' => ['top1' => 'Top 1 Zdobywca Lochów (+5% Przebicie Pancerza, +15 Def)', 'top2' => 'Top 2 (+3%, +10 Def)', 'top3' => 'Top 3 (+1%, +5 Def)'],
                                'levels_gained'      => ['top1' => 'Top 1 Mistrz Doświadczenia (+5% EXP, +10 Staty)', 'top2' => 'Top 2 (+3%, +6 Staty)', 'top3' => 'Top 3 (+1%, +3 Staty)'],
                                'map_bosses_killed'  => ['top1' => 'Top 1 Łowca Czempionów (+5% Podwójny Łup, +15 Atak)', 'top2' => 'Top 2 (+3%, +10 Atak)', 'top3' => 'Top 3 (+1%, +5 Atak)'],
                                'arena_wins'         => ['top1' => 'Top 1 Gladiator (+5% vs Bohaterowie, +2% Unik)', 'top2' => 'Top 2 (+3%, +1% Unik)', 'top3' => 'Top 3 (+1%, +0.5% Unik)'],
                                default => null,
                            };
                        @endphp

                        @if($categoryTitleInfo)
                        <div class="mb-3 p-2.5 rounded-xl bg-purple-950/40 border border-purple-500/30 text-xs flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 shadow-inner">
                            <div class="flex items-center gap-2 text-purple-300 font-bold flex-shrink-0">
                                <i class="fa-solid fa-crown text-purple-400"></i>
                                <span>Tytuły Czasowe (7 dni):</span>
                            </div>
                            <div class="flex flex-wrap items-center gap-1.5 text-[11px]">
                                <span class="bg-purple-900/60 border border-purple-500/40 text-purple-200 px-2 py-0.5 rounded font-semibold" title="{{ $categoryTitleInfo['top1'] }}">
                                    🥇 {{ $categoryTitleInfo['top1'] }}
                                </span>
                                <span class="bg-slate-800/80 border border-slate-600 text-slate-300 px-2 py-0.5 rounded font-medium" title="{{ $categoryTitleInfo['top2'] }}">
                                    🥈 {{ $categoryTitleInfo['top2'] }}
                                </span>
                                <span class="bg-amber-950/60 border border-amber-800 text-amber-300 px-2 py-0.5 rounded font-medium" title="{{ $categoryTitleInfo['top3'] }}">
                                    🥉 {{ $categoryTitleInfo['top3'] }}
                                </span>
                            </div>
                        </div>
                        @endif

                        {{-- Twoja pozycja --}}
                        @php
                            $myAvatarUrl = $character->getEffectiveAvatarUrl();
                        @endphp
                        <div class="mb-4 p-3 rounded-xl border
                            {{ $playerRank['rank'] === 1 ? 'bg-yellow-950/60 border-yellow-500/50' : ($playerRank['rank'] !== null ? 'bg-slate-800/60 border-slate-700' : 'bg-slate-900/40 border-slate-800') }}
                            flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg {{ $playerRank['rank'] === 1 ? 'bg-yellow-500/20 border border-yellow-500/50' : 'bg-slate-700/60 border border-slate-600' }} flex items-center justify-center flex-shrink-0 overflow-hidden">
                                    <img src="{{ $myAvatarUrl }}" class="w-full h-full object-cover" alt="avatar" onerror="this.src='{{ asset('img/avatars/plate.png') }}'">
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider flex items-center gap-1.5">
                                        Twoja pozycja
                                        <span class="text-amber-400 font-extrabold normal-case text-xs">[{{ $character->level }} lvl]</span>
                                    </p>
                                    <p class="text-sm font-black {{ $playerRank['rank'] === 1 ? 'text-yellow-300' : 'text-amber-100' }}">
                                        @if($playerRank['rank'] !== null)
                                            #{{ $playerRank['rank'] }}
                                        @else
                                            <span class="text-slate-500">Brak wpisu</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-slate-500">Wynik</p>
                                <p class="text-base font-black {{ $color }}">{{ number_format($playerRank['score']) }}</p>
                            </div>
                        </div>

                        {{-- Top 10 --}}
                        @if($leaderboard->isEmpty())
                        <div class="py-10 text-center text-slate-600">
                            <i class="fa-solid {{ $icon }} text-3xl mb-3 {{ $color }} opacity-30"></i>
                            <p class="text-sm font-semibold">Brak wyników w tym tygodniu</p>
                            <p class="text-xs mt-1">Bądź pierwszy!</p>
                        </div>
                        @else
                        <div class="space-y-1.5">
                            @foreach($leaderboard as $i => $entry)
                            @php
                                $pos         = $i + 1;
                                $isMe        = $entry->character_id === $character->id;
                                $gems        = $pos === 1 ? 300 : ($pos === 2 ? 250 : ($pos === 3 ? 200 : ($pos <= 10 ? 100 : 0)));
                                $rowBg       = $isMe ? 'bg-amber-900/25 border-amber-500/40' : 'bg-slate-800/40 border-slate-700/50';
                                $posBadge    = match($pos) {
                                    1 => 'bg-gradient-to-br from-yellow-400 to-amber-500 text-yellow-950 shadow-[0_0_10px_rgba(234,179,8,0.5)]',
                                    2 => 'bg-gradient-to-br from-slate-300 to-slate-400 text-slate-900',
                                    3 => 'bg-gradient-to-br from-amber-700 to-amber-600 text-amber-100',
                                    default => 'bg-slate-700 text-slate-300',
                                };
                                $entryAvatarUrl = $entry->character?->getEffectiveAvatarUrl() ?? asset('img/avatars/default.png');
                                $rankTitleName  = \App\Application\Rankings\WeeklyRankingService::getTitleNameForRank($cat, $pos);
                            @endphp
                            <div class="flex items-center gap-3 p-2.5 rounded-xl border {{ $rowBg }} transition-all duration-200
                                        {{ $isMe ? 'ring-1 ring-amber-500/30' : '' }}">
                                {{-- Pozycja --}}
                                <div class="w-7 h-7 rounded-md flex items-center justify-center text-xs font-black flex-shrink-0 {{ $posBadge }}">
                                    @if($pos <= 3)
                                        <i class="fa-solid fa-trophy text-[10px]"></i>
                                    @else
                                        {{ $pos }}
                                    @endif
                                </div>

                                {{-- Avatar + Imię [poziom lvl] --}}
                                <div class="flex items-center gap-2 flex-1 min-w-0">
                                    <div class="w-7 h-7 rounded-full bg-slate-700 border border-slate-600 flex items-center justify-center flex-shrink-0 overflow-hidden">
                                        <img src="{{ $entryAvatarUrl }}" class="w-full h-full object-cover" alt="avatar" onerror="this.src='{{ asset('img/avatars/plate.png') }}'">
                                    </div>
                                    <span class="text-sm font-bold truncate {{ $isMe ? 'text-amber-300' : 'text-slate-200' }}">
                                        {{ $entry->character?->name ?? 'Nieznany' }}
                                        @if($entry->character?->level)
                                            <span class="text-xs text-amber-400/90 font-extrabold ml-1">[{{ $entry->character->level }} lvl]</span>
                                        @endif
                                        @if($isMe)
                                            <span class="ml-1 text-[10px] text-amber-500 font-black">(Ty)</span>
                                        @endif
                                    </span>
                                </div>

                                {{-- Wynik + Nagrody (Gemy + Tytuł) --}}
                                <div class="text-right flex-shrink-0">
                                    <p class="text-sm font-black {{ $color }}">{{ number_format($entry->score) }}</p>
                                    <div class="flex flex-col items-end gap-0.5">
                                        @if($gems > 0)
                                        <p class="text-[10px] text-amber-500/80 font-semibold flex items-center justify-end gap-0.5">
                                            +{{ $gems }}
                                            <i class="fa-solid fa-gem text-purple-400 text-[10px]"></i>
                                        </p>
                                        @endif
                                        @if($rankTitleName)
                                        <span class="inline-flex items-center gap-1 text-[9px] text-purple-300 font-extrabold bg-purple-950/70 border border-purple-500/40 px-1.5 py-0.5 rounded">
                                            <i class="fa-solid fa-crown text-purple-400 text-[8px]"></i>
                                            {{ $rankTitleName }}
                                        </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>

                {{-- Footer --}}
                <div class="px-5 py-3 border-t border-slate-800 flex-shrink-0 text-center">
                    <p class="text-xs text-slate-600">
                        <i class="fa-solid fa-circle-info mr-1"></i>
                        Ranking resetuje się co poniedziałek o 00:01. Nagrody wysyłane są pocztą w grze.
                    </p>
                </div>
            </div>
        </div>
        @endif

        {{-- Map access error alert --}}
        @error('map_access')
            <div class="mb-6 p-4 bg-red-950/80 border-2 border-red-600 rounded-xl backdrop-blur-md max-w-2xl mx-auto shadow-2xl">
                <p class="text-red-200 font-semibold text-center flex items-center justify-center gap-2">
                    <i class="fa-solid fa-triangle-exclamation text-red-400"></i> {{ $message }}
                </p>
            </div>
        @enderror

        {{-- MAPS TAB --}}
        @if($tab === 'maps')
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 w-full">
            @foreach ($maps as $map)
                @php
                    $isAccessible = $map->isAccessibleBy($character);
                    $isCurrentLevel = $character->level >= $map->level_min && $character->level <= $map->level_max;

                    // Check map image path
                    $imagePath = null;
                    if ($map->image_path) {
                        if (str_starts_with($map->image_path, 'img/')) {
                            $imagePath = $map->image_path;
                        } else {
                            $imagePath = 'img/' . $map->image_path;
                        }
                    }

                    $imageExists = $imagePath && file_exists(public_path($imagePath));

                    if (!$imageExists) {
                        $hardcodedImages = [
                            'Mroczny Las' => 'img/maps/dark-forest.png',
                            'Stare Ruiny' => 'img/maps/old-ruins.png',
                            'Jaskinia Trolli' => 'img/maps/troll-cave.png',
                            'Pustkowia Orków' => 'img/maps/orc-wasteland.png',
                            'Bagna Grozy' => 'img/maps/horror-swamps.png',
                            'Góry Cienia' => 'img/maps/shadow-mountains.png',
                            'Wieża Magów' => 'img/maps/shadow-mountains.png',
                            'Skażone Miasto' => 'img/maps/corrupted-city.png',
                        ];

                        $fallbackPath = $hardcodedImages[$map->name] ?? null;
                        if ($fallbackPath && file_exists(public_path($fallbackPath))) {
                            $imagePath = $fallbackPath;
                            $imageExists = true;
                        }
                    }

                    $isFirstMapTutorial = $isAccessible && $gameStage == 10 && $map->level_min == 0;
                    $sortedMapMonsters = $map->monsters->sortBy('level')->values();
                @endphp

                <div class="relative group h-full flex flex-col" x-data="{
                    showBestiaryModal: false,
                    turningPage: false,
                    turnDirection: 'next',
                    monsterIds: [ @foreach($sortedMapMonsters as $m) '{{ $m->id }}', @endforeach ],
                    selectedMonsterId: '{{ $sortedMapMonsters->first()->id ?? '' }}',
                    selectMonster(id) {
                        if (this.selectedMonsterId === id || this.turningPage) return;
                        let currIdx = this.monsterIds.indexOf(this.selectedMonsterId);
                        let targetIdx = this.monsterIds.indexOf(id);
                        this.turnDirection = targetIdx >= currIdx ? 'next' : 'prev';

                        this.turningPage = true;
                        $dispatch('play-audio', { type: 'book_turn' });
                        setTimeout(() => {
                            this.selectedMonsterId = id;
                        }, 220);
                        setTimeout(() => {
                            this.turningPage = false;
                        }, 450);
                    }
                }">

                    <div class="bg-slate-900/90 border-2 {{ $isAccessible ? 'border-emerald-800/60 hover:border-emerald-400' : 'border-slate-800 opacity-60' }} rounded-2xl shadow-xl backdrop-blur-md transition-all duration-300 flex flex-col h-full overflow-hidden {{ $isAccessible ? 'hover:shadow-[0_10px_30px_rgba(16,185,129,0.2)] hover:-translate-y-1' : '' }} {{ $isFirstMapTutorial ? 'animate-[pulse_1.5s_ease-in-out_infinite] ring-4 ring-amber-500 shadow-[0_0_25px_rgba(245,158,11,0.6)] relative z-10' : '' }}">

                        {{-- Current Level / Over-level Badge --}}
                        @if ($isCurrentLevel)
                            <div class="absolute top-3 right-3 bg-gradient-to-r from-amber-500 to-yellow-500 text-slate-950 px-3 py-1 rounded-full text-xs font-black shadow-lg border border-yellow-300 z-20 flex items-center gap-1.5 animate-pulse">
                                <i class="fa-solid fa-star text-slate-950"></i> REKOMENDOWANA
                            </div>
                        @elseif ($map->isOverLevel($character))
                            <div class="absolute top-3 right-3 bg-gradient-to-r from-purple-600 to-indigo-600 text-white px-3 py-1 rounded-full text-xs font-black shadow-lg border border-purple-400 z-20 flex items-center gap-1.5">
                                <i class="fa-solid fa-users text-amber-300"></i> 3-4 POTWORY (-66% ŁUP)
                            </div>
                        @endif

                        {{-- Map Image Banner --}}
                        <div class="w-full h-48 relative overflow-hidden bg-slate-950 border-b border-slate-800">
                            @if ($imageExists)
                                <img src="{{ asset($imagePath) }}" alt="{{ $map->name }}"
                                    class="w-full h-full object-cover {{ $isAccessible ? 'group-hover:scale-105' : 'grayscale opacity-50' }} transition-transform duration-500 ease-out"
                                    loading="lazy">
                                <div class="absolute inset-0 bg-gradient-to-t from-slate-900 via-slate-900/20 to-transparent"></div>
                                
                                @if (!$isAccessible)
                                    <div class="absolute inset-0 bg-slate-950/80 backdrop-blur-[2px] flex flex-col items-center justify-center p-4 text-center">
                                        <div class="text-4xl text-amber-500/80 mb-2"><i class="fa-solid fa-lock"></i></div>
                                        <div class="text-xs font-bold text-slate-300">
                                            Wymagany poziom: {{ $map->level_min }}
                                        </div>
                                    </div>
                                @endif
                            @else
                                <div class="w-full h-full bg-gradient-to-br from-slate-900 via-slate-800 to-slate-950 flex items-center justify-center">
                                    <div class="text-6xl text-emerald-500/40 group-hover:scale-110 transition-transform">
                                        <i class="fa-solid fa-tree"></i>
                                    </div>
                                </div>
                                <div class="absolute inset-0 bg-gradient-to-t from-slate-900 via-transparent to-transparent"></div>
                            @endif

                            {{-- Map Name Overlay --}}
                            <div class="absolute bottom-3 left-4 right-4 z-10">
                                <h3 class="text-2xl font-bold text-amber-100 medieval-font drop-shadow-[0_2px_4px_rgba(0,0,0,0.8)]">
                                    {{ $map->name }}
                                </h3>
                                <div class="text-xs text-emerald-300 font-semibold flex items-center gap-2 mt-0.5">
                                    <span>Poziom {{ $map->level_range }}</span>
                                    @if (isset($map->tier))
                                        <span>•</span>
                                        <span>Tier {{ $map->tier }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Card Body & Actions --}}
                        <div class="p-5 flex flex-col flex-1 justify-between gap-4">

                            {{-- Action buttons --}}
                            <div class="space-y-2.5 mt-auto">
                                @if ($isAccessible)
                                    <button @click="travelingTo = '{{ addslashes($map->name) }}'; $dispatch('play-audio', { type: 'combat' }); setTimeout(() => $wire.enterMap('{{ $map->id }}'), 400)"
                                        class="w-full bg-gradient-to-r from-emerald-600 via-emerald-500 to-emerald-600 hover:from-emerald-500 hover:to-emerald-400 text-white font-bold py-3 px-4 rounded-xl transition-all duration-200 transform hover:scale-[1.02] shadow-lg shadow-emerald-950/50 border border-emerald-400/50 medieval-font flex items-center justify-center gap-2">
                                        <i class="fa-solid fa-khanda text-white"></i>
                                        <span>WEJDŹ NA MAPĘ</span>
                                    </button>
                                    
                                    <button @click="showBestiaryModal = true" 
                                        class="w-full bg-slate-800/90 hover:bg-slate-700/90 text-amber-200 font-bold py-2.5 px-4 rounded-xl transition-all duration-200 text-xs sm:text-sm border border-amber-600/40 hover:border-amber-400/80 shadow-md medieval-font flex items-center justify-center gap-2 group/btn">
                                        <span class="group-hover/btn:rotate-12 transition-transform inline-block"><i class="fa-solid fa-book-bookmark text-amber-400"></i></span> KSIĘGA BESTII
                                    </button>
                                @else
                                    <button disabled
                                        class="w-full bg-slate-800/60 text-slate-500 font-bold py-3 px-4 rounded-xl cursor-not-allowed border border-slate-800 medieval-font text-center flex items-center justify-center gap-2">
                                        <i class="fa-solid fa-lock text-slate-500"></i> Niedostępne
                                    </button>
                                @endif
                            </div>

                        </div>

                        {{-- BESTIARY MODAL: ANCIENT HANDWRITTEN TOME WITH 3D PAGE TURN ANIMATION --}}
                        <template x-teleport="body">
                            <div x-show="showBestiaryModal" style="display: none;" 
                                 x-transition:enter="transition ease-out duration-300"
                                 x-transition:enter-start="opacity-0 scale-95"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-200"
                                 x-transition:leave-start="opacity-100 scale-100"
                                 x-transition:leave-end="opacity-0 scale-95"
                                 class="fixed inset-0 z-[200] flex items-center justify-center p-2 sm:p-4 bg-slate-950/85 backdrop-blur-md overflow-y-auto">
                                
                                <div @click.outside="showBestiaryModal = false" 
                                     class="relative w-full max-w-5xl bg-gradient-to-r from-amber-950 via-yellow-950 to-amber-950 p-3 sm:p-6 rounded-2xl border-4 border-amber-800/90 shadow-[0_25px_60px_rgba(0,0,0,0.95)] flex flex-col max-h-[92vh] overflow-hidden my-auto">
                                    
                                    {{-- Grimoire Filigree Gold Corners --}}
                                    <div class="absolute top-0 left-0 w-8 h-8 border-t-4 border-l-4 border-amber-500 rounded-tl-xl pointer-events-none z-30"></div>
                                    <div class="absolute top-0 right-0 w-8 h-8 border-t-4 border-r-4 border-amber-500 rounded-tr-xl pointer-events-none z-30"></div>
                                    <div class="absolute bottom-0 left-0 w-8 h-8 border-b-4 border-l-4 border-amber-500 rounded-bl-xl pointer-events-none z-30"></div>
                                    <div class="absolute bottom-0 right-0 w-8 h-8 border-b-4 border-r-4 border-amber-500 rounded-br-xl pointer-events-none z-30"></div>

                                    {{-- Close Book Button --}}
                                    <button @click="showBestiaryModal = false" 
                                        class="absolute top-3 right-4 z-40 text-amber-200 hover:text-red-400 text-3xl font-bold drop-shadow-md transition-colors">
                                        &times;
                                    </button>
                                    
                                    {{-- Grimoire Title Header --}}
                                    <div class="relative z-20 text-center mb-3 border-b-2 border-amber-800/60 pb-3 flex items-center justify-between px-2">
                                        <div class="text-left hidden sm:block">
                                            <span class="text-xs text-amber-400/80 font-bold uppercase tracking-widest">Księga Bestii</span>
                                            <h4 class="text-sm font-bold text-amber-200 medieval-font">{{ $map->name }}</h4>
                                        </div>
                                        <h2 class="text-2xl sm:text-3xl font-black text-amber-200 medieval-font tracking-wide drop-shadow-md mx-auto flex items-center gap-2">
                                            <i class="fa-solid fa-scroll text-amber-400"></i>
                                            <span>Kodeks Bestii: {{ $map->name }}</span>
                                        </h2>
                                        <div class="text-right hidden sm:block w-24"></div>
                                    </div>

                                    @if($sortedMapMonsters->isEmpty())
                                        <div class="flex items-center justify-center py-16 bg-[#f4e4bc] rounded-xl text-amber-950">
                                            <p class="italic font-bold text-lg">Brak informacji o przeciwnikach na tej mapie...</p>
                                        </div>
                                    @else
                                        {{-- Monster Bookmark Tabs --}}
                                        <div class="relative z-20 flex overflow-x-auto gap-1.5 mb-2 pb-2 custom-scrollbar">
                                            @foreach($sortedMapMonsters as $monster)
                                                <button @click="selectMonster('{{ $monster->id }}')"
                                                    :class="selectedMonsterId == '{{ $monster->id }}' ? 'bg-[#f4e4bc] text-amber-950 border-amber-700 shadow-lg -translate-y-1 font-black' : 'bg-amber-900/80 text-amber-200 hover:bg-amber-800 hover:-translate-y-0.5 font-bold'"
                                                    class="px-3 py-1.5 rounded-t-xl text-xs sm:text-sm border-t-2 border-x-2 border-amber-800/60 whitespace-nowrap transition-all duration-200 flex items-center gap-1.5 medieval-font">
                                                    @if($monster->type && $monster->type->value === 'undead') <i class="fa-solid fa-skull text-stone-900"></i>
                                                    @elseif($monster->type && $monster->type->value === 'demon') <i class="fa-solid fa-dragon text-purple-900"></i>
                                                    @elseif($monster->type && $monster->type->value === 'beast') <i class="fa-solid fa-paw text-amber-900"></i>
                                                    @elseif($monster->type && $monster->type->value === 'orc') <i class="fa-solid fa-ghost text-emerald-900"></i>
                                                    @else <i class="fa-solid fa-skull text-stone-900"></i> @endif
                                                    <span>{{ $monster->name }}</span>
                                                    <span class="text-[10px] opacity-75">(Lvl {{ $monster->level }})</span>
                                                    @if($monster->rank && $monster->rank->value === 'worldboss')
                                                        <span class="px-1.5 py-0.5 rounded bg-purple-700 text-white text-[9px] font-black uppercase tracking-wide">World Boss</span>
                                                    @elseif($monster->rank && $monster->rank->value === 'boss')
                                                        <span class="px-1.5 py-0.5 rounded bg-red-700 text-white text-[9px] font-black uppercase tracking-wide">Boss</span>
                                                    @endif
                                                </button>
                                            @endforeach
                                        </div>

                                        {{-- REALISTIC DUAL-PAGE PARCHMENT TOME WITH REAL 3D FLIP ANIMATION --}}
                                        <div class="relative flex-1 bg-[#f4e4bc] text-amber-950 border-2 border-amber-900/50 rounded-xl shadow-inner overflow-y-auto custom-scrollbar p-4 sm:p-6 min-h-[480px]">
                                            
                                            {{-- Grimoire Center Spine Shadow Fold --}}
                                            <div class="hidden md:block absolute top-0 bottom-0 left-1/2 -translate-x-1/2 w-10 bg-gradient-to-r from-amber-950/25 via-amber-950/5 to-amber-950/25 pointer-events-none z-20"></div>

                                            {{-- REAL 3D BOOK PAGE TURN ANIMATION LAYER --}}
                                            <div x-show="turningPage" 
                                                 class="absolute inset-0 pointer-events-none z-30 overflow-hidden rounded-xl"
                                                 style="perspective: 1600px;">
                                                
                                                {{-- Direction NEXT: Right page turns left --}}
                                                <template x-if="turnDirection === 'next'">
                                                    <div class="hidden md:block absolute top-0 bottom-0 right-0 w-1/2 origin-left animate-page-flip-next rounded-r-xl border-l-2 border-amber-900/40 bg-[#ebd7a7] shadow-[0_15px_35px_rgba(0,0,0,0.5)]">
                                                        {{-- Front face of turning page --}}
                                                        <div class="absolute inset-0 bg-gradient-to-r from-amber-950/30 via-amber-900/5 to-amber-950/20 p-6 flex flex-col justify-between" style="backface-visibility: hidden;">
                                                            <div class="w-full border-b border-amber-900/20 pb-2 flex justify-between items-center opacity-40">
                                                                <span class="text-xs font-bold medieval-font text-amber-950">Grimoire Codex</span>
                                                                <i class="fa-solid fa-scroll text-amber-950 text-xs"></i>
                                                            </div>
                                                            <div class="text-center text-5xl opacity-25 text-amber-950 my-auto"><i class="fa-solid fa-scroll"></i></div>
                                                            <div class="w-full border-t border-amber-900/20 pt-2 text-right opacity-40">
                                                                <span class="text-[10px] font-bold medieval-font text-amber-950">Berserk Rush</span>
                                                            </div>
                                                        </div>
                                                        {{-- Back face of turning page --}}
                                                        <div class="absolute inset-0 bg-gradient-to-l from-amber-950/40 via-amber-900/10 to-amber-950/20 p-6 flex flex-col justify-between" style="backface-visibility: hidden; transform: rotateY(180deg);">
                                                            <div class="w-full border-b border-amber-900/20 pb-2 flex justify-between items-center opacity-40">
                                                                <i class="fa-solid fa-scroll text-amber-950 text-xs"></i>
                                                                <span class="text-xs font-bold medieval-font text-amber-950">Grimoire Codex</span>
                                                            </div>
                                                            <div class="text-center text-5xl opacity-20 text-amber-950 my-auto"><i class="fa-solid fa-book-open"></i></div>
                                                            <div class="w-full border-t border-amber-900/20 pt-2 text-left opacity-40">
                                                                <span class="text-[10px] font-bold medieval-font text-amber-950">Berserk Rush</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </template>

                                                {{-- Direction PREV: Left page turns right --}}
                                                <template x-if="turnDirection === 'prev'">
                                                    <div class="hidden md:block absolute top-0 bottom-0 left-0 w-1/2 origin-right animate-page-flip-prev rounded-l-xl border-r-2 border-amber-900/40 bg-[#ebd7a7] shadow-[0_15px_35px_rgba(0,0,0,0.5)]">
                                                        <div class="absolute inset-0 bg-gradient-to-l from-amber-950/30 via-amber-900/5 to-amber-950/20 p-6 flex flex-col justify-between" style="backface-visibility: hidden;">
                                                            <div class="w-full border-b border-amber-900/20 pb-2 flex justify-between items-center opacity-40">
                                                                <i class="fa-solid fa-scroll text-amber-950 text-xs"></i>
                                                                <span class="text-xs font-bold medieval-font text-amber-950">Grimoire Codex</span>
                                                            </div>
                                                            <div class="text-center text-5xl opacity-25 text-amber-950 my-auto"><i class="fa-solid fa-scroll"></i></div>
                                                            <div class="w-full border-t border-amber-900/20 pt-2 text-left opacity-40">
                                                                <span class="text-[10px] font-bold medieval-font text-amber-950">Berserk Rush</span>
                                                            </div>
                                                        </div>
                                                        <div class="absolute inset-0 bg-gradient-to-r from-amber-950/40 via-amber-900/10 to-amber-950/20 p-6 flex flex-col justify-between" style="backface-visibility: hidden; transform: rotateY(180deg);">
                                                            <div class="w-full border-b border-amber-900/20 pb-2 flex justify-between items-center opacity-40">
                                                                <span class="text-xs font-bold medieval-font text-amber-950">Grimoire Codex</span>
                                                                <i class="fa-solid fa-scroll text-amber-950 text-xs"></i>
                                                            </div>
                                                            <div class="text-center text-5xl opacity-20 text-amber-950 my-auto"><i class="fa-solid fa-book-open"></i></div>
                                                            <div class="w-full border-t border-amber-900/20 pt-2 text-right opacity-40">
                                                                <span class="text-[10px] font-bold medieval-font text-amber-950">Berserk Rush</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </template>

                                                {{-- Fallback Mobile 3D Fade & Fold --}}
                                                <div class="md:hidden absolute inset-0 bg-[#e8d5a7] z-30 animate-book-shadow-pulse border-2 border-amber-900/30 rounded-xl flex items-center justify-center">
                                                    <div class="text-amber-950 font-bold medieval-font text-3xl animate-bounce">
                                                        <i class="fa-solid fa-scroll"></i>
                                                    </div>
                                                </div>

                                                {{-- Center Spine Shadow Pulse --}}
                                                <div class="hidden md:block absolute top-0 bottom-0 left-1/2 -translate-x-1/2 w-12 bg-gradient-to-r from-amber-950/60 via-amber-950/10 to-amber-950/60 animate-book-shadow-pulse"></div>
                                            </div>

                                            @foreach($sortedMapMonsters as $monster)
                                                <div x-show="selectedMonsterId == '{{ $monster->id }}'"
                                                     class="flex flex-col md:flex-row w-full gap-6 sm:gap-8 h-full">
                                                    
                                                    {{-- LEFT PAGE: MONSTER CODEX & STATS --}}
                                                    <div class="w-full md:w-1/2 flex flex-col items-center border-b md:border-b-0 md:border-r border-amber-900/30 pb-6 md:pb-0 md:pr-6">
                                                        
                                                        {{-- Monster Frame & Avatar --}}
                                                        <div class="relative w-36 h-36 sm:w-48 sm:h-48 rounded-2xl overflow-hidden ring-4 ring-amber-900/70 shadow-2xl mb-4 bg-amber-950 flex-shrink-0">
                                                            @if(!empty($monster->avatar))
                                                                <img src="{{ route('assets.monsters.avatars', ['filename' => $monster->avatar]) }}?v={{ @filemtime(public_path('assets/monsters/avatars/' . $monster->avatar)) }}"
                                                                    alt="{{ $monster->name }}"
                                                                    class="w-full h-full object-cover">
                                                            @else
                                                                <img src="{{ asset('img/monsters/placeholder.png') }}"
                                                                    alt="{{ $monster->name }}"
                                                                    class="w-full h-full object-cover">
                                                            @endif
                                                            <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-transparent to-transparent"></div>
                                                            <div class="absolute bottom-2 left-0 w-full text-center text-amber-200 font-black medieval-font text-lg sm:text-xl drop-shadow-[0_2px_4px_rgba(0,0,0,0.9)]">
                                                                Poziom {{ $monster->level }}
                                                            </div>
                                                        </div>
                                                        
                                                        <h3 class="text-2xl sm:text-3xl font-black text-amber-950 medieval-font mb-2 text-center tracking-wide">
                                                            {{ $monster->name }}
                                                        </h3>

                                                        <div class="flex items-center gap-2 flex-wrap justify-center mb-4">
                                                            @if($monster->type)
                                                                <div class="bg-amber-900 text-amber-100 px-3 py-1 rounded-full text-xs font-bold shadow-md border border-amber-700">
                                                                    Rasa: {{ $monster->type->label() }}
                                                                </div>
                                                            @endif
                                                            @if($monster->rank && $monster->rank->value === 'worldboss')
                                                                <div class="bg-purple-700 text-white px-3 py-1 rounded-full text-xs font-black uppercase shadow-md border border-purple-500 flex items-center gap-1">
                                                                    <i class="fa-solid fa-crown"></i> World Boss
                                                                </div>
                                                            @elseif($monster->rank && $monster->rank->value === 'boss')
                                                                <div class="bg-red-700 text-white px-3 py-1 rounded-full text-xs font-black uppercase shadow-md border border-red-500 flex items-center gap-1">
                                                                    <i class="fa-solid fa-crown"></i> Boss
                                                                </div>
                                                            @endif
                                                        </div>

                                                        {{-- Monster Combat Attributes Box --}}
                                                        <div class="w-full bg-amber-100/70 rounded-xl p-4 border border-amber-900/40 shadow-sm mt-auto">
                                                            <h4 class="font-bold text-amber-950 mb-3 border-b border-amber-900/30 pb-1 flex items-center justify-between text-sm">
                                                                <span><i class="fa-solid fa-bolt text-amber-600 mr-1"></i> Atrybuty Bojowe</span>
                                                                <span class="text-xs text-amber-800">Przeciwnik</span>
                                                            </h4>
                                                            <div class="grid grid-cols-2 gap-2.5 text-xs font-semibold">
                                                                <div class="flex justify-between items-center bg-amber-200/60 p-2 rounded-lg border border-amber-900/20">
                                                                    <span class="text-amber-900 font-bold"><i class="fa-solid fa-heart text-red-600 mr-1"></i> Punkty Życia</span>
                                                                    <span class="text-red-700 font-bold text-sm">{{ number_format($monster->stats['hp'] ?? $monster->level * 20) }}</span>
                                                                </div>
                                                                <div class="flex justify-between items-center bg-amber-200/60 p-2 rounded-lg border border-amber-900/20">
                                                                    <span class="text-amber-900 font-bold"><i class="fa-solid fa-khanda text-amber-700 mr-1"></i> Atak</span>
                                                                    <span class="text-amber-950 font-bold text-sm">{{ $monster->stats['atk'] ?? '?' }}</span>
                                                                </div>
                                                                <div class="flex justify-between items-center bg-amber-200/60 p-2 rounded-lg border border-amber-900/20">
                                                                    <span class="text-amber-900 font-bold"><i class="fa-solid fa-shield-halved text-slate-700 mr-1"></i> Obrona</span>
                                                                    <span class="text-slate-800 font-bold text-sm">{{ $monster->stats['def'] ?? '?' }}</span>
                                                                </div>
                                                                <div class="flex justify-between items-center bg-amber-200/60 p-2 rounded-lg border border-amber-900/20">
                                                                    <span class="text-amber-900 font-bold"><i class="fa-solid fa-wind text-emerald-700 mr-1"></i> Zręczność</span>
                                                                    <span class="text-emerald-800 font-bold text-sm">{{ $monster->stats['agi'] ?? '?' }}</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    {{-- RIGHT PAGE: LOOT TABLE & DROPS CODEX --}}
                                                    <div class="w-full md:w-1/2 flex flex-col">
                                                        <h4 class="text-xl font-black text-amber-950 medieval-font mb-4 border-b-2 border-amber-900/30 pb-2 flex items-center justify-between">
                                                            <span><i class="fa-solid fa-gift text-amber-600 mr-1"></i> Tabela Zdobyczy</span>
                                                            <span class="text-xs font-bold text-amber-800">Szansa na łup</span>
                                                        </h4>
                                                        
                                                        <div class="space-y-2.5 overflow-y-auto max-h-[360px] pr-1 custom-scrollbar">
                                                            @if($monster->lootTable && $monster->lootTable->entries->isNotEmpty())
                                                                @php
                                                                    $totalWeight = max(1, $monster->lootTable->entries->sum('weight'));
                                                                @endphp
                                                                @foreach($monster->lootTable->entries->sortByDesc('weight') as $entry)
                                                                    @php
                                                                        $chance = round(($entry->weight / $totalWeight) * 100, 1);
                                                                        $isQuestItem = false;
                                                                        if (in_array($entry->reward_type, ['item', 'material']) && $entry->itemTemplate) {
                                                                            if ($entry->itemTemplate->type === 'quest_item') {
                                                                                if (!$entry->itemTemplate->quest_id || !in_array($entry->itemTemplate->quest_id, $activeQuestIds)) {
                                                                                    continue;
                                                                                }
                                                                                $isQuestItem = true;
                                                                            }
                                                                        }
                                                                    @endphp
                                                                    <div class="bg-amber-100/80 rounded-xl p-3 border border-amber-900/30 shadow-sm relative overflow-hidden group hover:bg-amber-100 transition-colors">
                                                                        {{-- Progress fill background --}}
                                                                        <div class="absolute inset-y-0 left-0 bg-amber-300/40 pointer-events-none transition-all duration-500" style="width: {{ min(100, $chance) }}%"></div>
                                                                        
                                                                        <div class="relative z-10 flex items-center justify-between gap-3">
                                                                            <div class="flex items-center gap-3">
                                                                                <div class="w-10 h-10 rounded-lg bg-amber-900/10 border border-amber-900/30 flex items-center justify-center text-xl flex-shrink-0 shadow-inner">
                                                                                    @if($entry->reward_type === 'gold') <i class="fa-solid fa-coins text-yellow-600"></i>
                                                                                    @elseif($entry->reward_type === 'xp') <i class="fa-solid fa-sparkles text-amber-600"></i>
                                                                                    @elseif(in_array($entry->reward_type, ['item', 'material']) && $entry->itemTemplate)
                                                                                        <img src="{{ route('assets.items', ['filename' => $entry->itemTemplate->icon]) }}" 
                                                                                            onerror="this.src='{{ route('assets.items', ['filename' => 'default.png']) }}'" 
                                                                                            class="w-7 h-7 object-contain">
                                                                                    @endif
                                                                                </div>
                                                                                <div>
                                                                                    <div class="font-bold text-amber-950 text-sm">
                                                                                        @if($entry->reward_type === 'gold') Złoto
                                                                                        @elseif($entry->reward_type === 'xp') Doświadczenie
                                                                                        @elseif(in_array($entry->reward_type, ['item', 'material']) && $entry->itemTemplate)
                                                                                            <span class="{{ $entry->itemTemplate->rarity === 'legendary' ? 'text-amber-700 font-extrabold' : ($entry->itemTemplate->rarity === 'epic' ? 'text-purple-900 font-bold' : ($entry->itemTemplate->rarity === 'rare' ? 'text-blue-900 font-bold' : 'text-amber-950 font-bold')) }}">
                                                                                                {{ $entry->itemTemplate->name }}
                                                                                            </span>
                                                                                            @if($isQuestItem) <span class="text-[10px] bg-yellow-400 text-yellow-950 px-1.5 py-0.5 rounded font-bold ml-1">Zadanie</span> @endif
                                                                                        @endif
                                                                                    </div>
                                                                                    <div class="text-xs text-amber-800 font-semibold">
                                                                                        Ilość: {{ $entry->min_qty }}{{ $entry->min_qty != $entry->max_qty ? ' - ' . $entry->max_qty : '' }}
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                            
                                                                            <div class="text-right flex-shrink-0">
                                                                                <div class="text-base font-black text-amber-950">{{ $chance }}%</div>
                                                                                <div class="text-[9px] text-amber-800 font-bold uppercase tracking-wider">Prawdopodobieństwo</div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                @endforeach
                                                            @else
                                                                <div class="text-center py-10">
                                                                    <div class="text-4xl mb-2 text-amber-900/60"><i class="fa-solid fa-spider"></i></div>
                                                                    <p class="text-amber-900 italic font-bold text-sm">Przeciwnik nie posiada znanych łupów...</p>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </template>

                    </div>
                </div>
            @endforeach
        </div>
        @endif

        {{-- DUNGEONS TAB --}}
        @if($tab === 'dungeons')
        <div class="w-full px-4 md:px-0">
            @if($activeRun)
                <div class="bg-gradient-to-r from-amber-950/90 via-slate-900 to-amber-950/90 border-2 border-amber-500/80 rounded-2xl p-6 mb-8 flex flex-col sm:flex-row items-center justify-between gap-4 shadow-2xl max-w-5xl mx-auto backdrop-blur-md">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 rounded-2xl bg-amber-500/20 flex items-center justify-center text-amber-500 text-3xl border border-amber-500/30">
                            <i class="fa-solid fa-dungeon"></i>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold text-amber-200 medieval-font">Aktywna Wyprawa</h3>
                            <p class="text-slate-300 text-sm">Twoja drużyna walczy w głębinach lochu: {{ $activeRun->dungeon->name }}</p>
                            <span class="inline-block px-2 py-0.5 mt-2 rounded bg-amber-900/50 text-amber-300 text-xs font-bold border border-amber-700">Etap {{ $activeRun->current_stage }} / {{ $activeRun->dungeon->stages->count() }}</span>
                        </div>
                    </div>
                    <button wire:click="enterDungeon({{ $activeRun->dungeon_id }})" 
                        class="w-full sm:w-auto bg-amber-600 hover:bg-amber-500 text-white font-bold py-4 px-8 rounded-xl transition-all transform hover:scale-105 shadow-xl border border-amber-400 medieval-font">
                        Powrót do walki
                    </button>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 max-w-7xl mx-auto">
                @foreach($dungeons as $dungeon)
                    @php
                        $canEnter = $dungeon->canCharacterEnter($character);
                        $keysOwned = $keyCounts[$dungeon->id] ?? 0;
                        $hasKey = $dungeon->entry_item_template_id ? ($keysOwned >= 1) : true;
                        $isInProgress = $activeRun && $activeRun->dungeon_id === $dungeon->id;
                        $dungeonMonsters = $dungeon->stages->filter(fn($s) => $s->monster)->map(fn($s) => $s->monster)->unique('id')->sortBy('level')->values();
                    @endphp
                    <div x-data="{
                        showBestiaryModal: false,
                        showBossModal: false,
                        turningPage: false,
                        turnDirection: 'next',
                        selectedMultiplier: 1,
                        monsterIds: [ @foreach($dungeonMonsters as $dm) '{{ $dm->id }}', @endforeach ],
                        selectedMonsterId: '{{ $dungeonMonsters->first()?->id ?? '' }}',
                        selectMonster(id) {
                            if (this.selectedMonsterId === id || this.turningPage) return;
                            let currIdx = this.monsterIds.indexOf(this.selectedMonsterId);
                            let targetIdx = this.monsterIds.indexOf(id);
                            this.turnDirection = targetIdx >= currIdx ? 'next' : 'prev';
                            this.turningPage = true;
                            $dispatch('play-audio', { type: 'book_turn' });
                            setTimeout(() => { this.selectedMonsterId = id; }, 220);
                            setTimeout(() => { this.turningPage = false; }, 450);
                        }
                    }" class="group bg-slate-900/80 backdrop-blur-md border border-slate-700 hover:border-amber-500/50 rounded-2xl overflow-hidden transition-all duration-300 flex flex-col hover:shadow-[0_0_30px_-5px_rgba(217,119,6,0.2)]">

                        @php
                            $dungeonImages = [
                                'Zapomniane Katakumby' => 'img/dungeons/katakumby.png',
                                'Krypta Przeklętych' => 'img/dungeons/krypta.png',
                                'Pustkowia Zarazy' => 'img/dungeons/pustkowia.png',
                                'Cytadela Cienia' => 'img/dungeons/cytadela.png',
                                'Otchłań Zniszczenia' => 'img/dungeons/otchlan.png',
                            ];
                            $dungeonBg = $dungeonImages[$dungeon->name] ?? 'img/maps/old-ruins.png';
                        @endphp

                        {{-- Dungeon Banner --}}
                        <div class="h-44 relative border-b border-amber-900/40 overflow-hidden">
                            <img src="{{ asset($dungeonBg) }}" alt="{{ $dungeon->name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700 brightness-75">
                            <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/40 to-transparent"></div>
                            <div class="absolute bottom-3 left-3 bg-black/70 backdrop-blur-md px-3 py-1 rounded-lg border border-amber-500/40 text-xs font-bold text-amber-200 tracking-widest uppercase">
                                Wym. Lvl {{ $dungeon->min_level }}
                            </div>
                            @if($dungeon->entryItemTemplate)
                                <div class="absolute top-3 right-3 bg-slate-950/85 backdrop-blur-md px-2.5 py-1 rounded-lg border {{ $hasKey ? 'border-amber-500/50 text-amber-300' : 'border-red-500/50 text-red-300' }} text-[11px] font-bold flex items-center gap-1.5 shadow-lg">
                                    <i class="fa-solid fa-key {{ $hasKey ? 'text-amber-400' : 'text-red-400' }}"></i>
                                    <span>{{ $dungeon->entryItemTemplate->name }} ({{ $keysOwned }} szt.)</span>
                                </div>
                            @endif
                        </div>

                        <div class="p-5 flex-grow">
                            <h3 class="text-xl font-bold text-amber-100 medieval-font mb-2">{{ $dungeon->name }}</h3>
                            <p class="text-slate-400 text-xs mb-4 line-clamp-2 h-8">{{ $dungeon->description }}</p>
                            
                            {{-- Bestiary Preview --}}
                            <div class="bg-slate-950/50 rounded-xl p-3 border border-slate-800 space-y-2">
                                <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Przeciwnicy lochu</div>
                                @foreach($dungeon->stages->take(3) as $stage)
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="text-slate-300"><span class="text-amber-600 mr-2">#{{ $stage->stage_order }}</span> {{ $stage->monster->name }}</span>
                                        <span class="text-slate-600 font-mono">Lv. {{ $stage->monster->level }}</span>
                                    </div>
                                @endforeach
                                @if($dungeon->stages->count() > 3)
                                    <div class="text-[10px] text-amber-600 text-center font-bold pt-1 border-t border-slate-800">+ {{ $dungeon->stages->count() - 3 }} więcej...</div>
                                @endif
                                @if($dungeon->entryItemTemplate)
                                    <div class="mt-2 pt-2 border-t border-slate-800 flex items-center justify-between text-xs">
                                        <span class="text-slate-400 font-semibold flex items-center gap-1.5">
                                            <i class="fa-solid fa-key text-amber-500/80"></i> Posiadane klucze:
                                        </span>
                                        <span class="font-bold {{ $hasKey ? 'text-amber-300' : 'text-red-400' }}">
                                            {{ $keysOwned }} szt.
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="p-4 pt-0 space-y-2.5">
                            @if($isInProgress)
                                <button wire:click="enterDungeon({{ $dungeon->id }})" class="w-full bg-amber-600 text-white py-3 rounded-xl font-bold medieval-font hover:bg-amber-500 transition-colors border border-amber-500">
                                    Kontynuuj Wyprawę
                                </button>
                            @elseif(!$canEnter)
                                <button disabled class="w-full bg-slate-800 text-slate-500 py-3 rounded-xl font-bold medieval-font cursor-not-allowed border border-slate-700">
                                    Zbyt niski poziom
                                </button>
                            @elseif(!$hasKey)
                                <button disabled class="w-full bg-slate-900/90 text-red-400 py-3 px-2 rounded-xl font-bold medieval-font cursor-not-allowed border border-red-900/60 flex items-center justify-center gap-2 shadow-inner text-xs sm:text-sm">
                                    <i class="fa-solid fa-lock text-red-400"></i>
                                    <span>Brak klucza (0/1 szt.)</span>
                                </button>
                            @else
                                {{-- MULTI-DUNGEON SELECTOR --}}
                                <div class="bg-slate-950/70 p-2.5 rounded-xl border border-amber-900/40 space-y-2">
                                    <div class="flex items-center justify-between text-[11px] font-bold text-amber-200/80 uppercase px-1">
                                        <span class="flex items-center gap-1"><i class="fa-solid fa-layer-group text-amber-400"></i> Tryb Wyprawy:</span>
                                        <span x-text="selectedMultiplier === 1 ? '1 Klucz (Standard)' : (selectedMultiplier === 3 ? '3 Klucze (+35% Trudności, x3 Łup)' : '5 Kluczy (+70% Trudności, x5 Łup)')" class="text-amber-400 font-extrabold text-[10px]"></span>
                                    </div>
                                    <div class="grid grid-cols-3 gap-1.5">
                                        <button @click="selectedMultiplier = 1" 
                                            :class="selectedMultiplier === 1 ? 'bg-amber-600 text-white border-amber-400 shadow-md font-black' : 'bg-slate-800 text-slate-300 border-slate-700 hover:bg-slate-700'"
                                            class="py-1.5 px-2 rounded-lg text-xs border transition-all medieval-font flex items-center justify-center gap-1">
                                            <span>1x</span>
                                        </button>
                                        <button @click="selectedMultiplier = 3" 
                                            :disabled="{{ $keysOwned }} < 3"
                                            :class="selectedMultiplier === 3 ? 'bg-amber-600 text-white border-amber-400 shadow-md font-black' : ({{ $keysOwned }} < 3 ? 'bg-slate-900/60 text-slate-600 border-slate-850 cursor-not-allowed' : 'bg-slate-800 text-slate-300 border-slate-700 hover:bg-slate-700')"
                                            class="py-1.5 px-2 rounded-lg text-xs border transition-all medieval-font flex items-center justify-center gap-1">
                                            <span>3x</span>
                                            <span class="text-[9px] px-1 rounded bg-amber-950/80 text-amber-300">Multi</span>
                                        </button>
                                        <button @click="selectedMultiplier = 5" 
                                            :disabled="{{ $keysOwned }} < 5"
                                            :class="selectedMultiplier === 5 ? 'bg-amber-600 text-white border-amber-400 shadow-md font-black' : ({{ $keysOwned }} < 5 ? 'bg-slate-900/60 text-slate-600 border-slate-850 cursor-not-allowed' : 'bg-slate-800 text-slate-300 border-slate-700 hover:bg-slate-700')"
                                            class="py-1.5 px-2 rounded-lg text-xs border transition-all medieval-font flex items-center justify-center gap-1">
                                            <span>5x</span>
                                            <span class="text-[9px] px-1 rounded bg-amber-950/80 text-amber-300">Mega</span>
                                        </button>
                                    </div>
                                </div>

                                <button @click="$wire.enterDungeon({{ $dungeon->id }}, selectedMultiplier)" class="w-full bg-slate-800 text-amber-200 hover:text-white py-3 rounded-xl font-bold medieval-font hover:bg-amber-900/50 transition-all border border-slate-600 hover:border-amber-600 flex items-center justify-center gap-2">
                                    <i class="fa-solid fa-dungeon text-amber-400"></i>
                                    <span>Rozpocznij Ekspedycję</span>
                                    <span x-show="selectedMultiplier > 1" class="text-xs text-amber-400 font-extrabold" x-text="'(' + selectedMultiplier + 'x)'"></span>
                                </button>
                            @endif

                            {{-- KSIĘGA BESTII BUTTON --}}
                            <button @click="showBestiaryModal = true"
                                class="w-full bg-slate-800/90 hover:bg-slate-700/90 text-amber-200 font-bold py-2.5 px-4 rounded-xl transition-all duration-200 text-xs sm:text-sm border border-amber-600/40 hover:border-amber-400/80 shadow-md medieval-font flex items-center justify-center gap-2 group/btn">
                                <span class="group-hover/btn:rotate-12 transition-transform inline-block"><i class="fa-solid fa-book-bookmark text-amber-400"></i></span>
                                KSIĘGA BESTII
                            </button>
                        </div>

                        {{-- BESTIARY MODAL --}}
                        <template x-teleport="body">
                            <div x-show="showBestiaryModal" style="display: none;"
                                 x-transition:enter="transition ease-out duration-300"
                                 x-transition:enter-start="opacity-0 scale-95"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-200"
                                 x-transition:leave-start="opacity-100 scale-100"
                                 x-transition:leave-end="opacity-0 scale-95"
                                 class="fixed inset-0 z-[200] flex items-center justify-center p-2 sm:p-4 bg-slate-950/85 backdrop-blur-md overflow-y-auto">

                                <div @click.outside="showBestiaryModal = false"
                                     class="relative w-full max-w-5xl bg-gradient-to-r from-amber-950 via-yellow-950 to-amber-950 p-3 sm:p-6 rounded-2xl border-4 border-amber-800/90 shadow-[0_25px_60px_rgba(0,0,0,0.95)] flex flex-col max-h-[92vh] overflow-hidden my-auto">

                                    {{-- Gold Corners --}}
                                    <div class="absolute top-0 left-0 w-8 h-8 border-t-4 border-l-4 border-amber-500 rounded-tl-xl pointer-events-none z-30"></div>
                                    <div class="absolute top-0 right-0 w-8 h-8 border-t-4 border-r-4 border-amber-500 rounded-tr-xl pointer-events-none z-30"></div>
                                    <div class="absolute bottom-0 left-0 w-8 h-8 border-b-4 border-l-4 border-amber-500 rounded-bl-xl pointer-events-none z-30"></div>
                                    <div class="absolute bottom-0 right-0 w-8 h-8 border-b-4 border-r-4 border-amber-500 rounded-br-xl pointer-events-none z-30"></div>

                                    <button @click="showBestiaryModal = false"
                                        class="absolute top-3 right-4 z-40 text-amber-200 hover:text-red-400 text-3xl font-bold drop-shadow-md transition-colors">
                                        &times;
                                    </button>

                                    <div class="relative z-20 text-center mb-3 border-b-2 border-amber-800/60 pb-3 flex items-center justify-between px-2">
                                        <div class="text-left hidden sm:block">
                                            <span class="text-xs text-amber-400/80 font-bold uppercase tracking-widest">Księga Bestii</span>
                                            <h4 class="text-sm font-bold text-amber-200 medieval-font">{{ $dungeon->name }}</h4>
                                        </div>
                                        <h2 class="text-2xl sm:text-3xl font-black text-amber-200 medieval-font tracking-wide drop-shadow-md mx-auto flex items-center gap-2">
                                            <i class="fa-solid fa-scroll text-amber-400"></i>
                                            <span>Kodeks Bestii: {{ $dungeon->name }}</span>
                                        </h2>
                                        <div class="text-right hidden sm:block w-24"></div>
                                    </div>

                                    @if($dungeonMonsters->isEmpty())
                                        <div class="flex items-center justify-center py-16 bg-[#f4e4bc] rounded-xl text-amber-950">
                                            <p class="italic font-bold text-lg">Brak informacji o przeciwnikach w tym lochu...</p>
                                        </div>
                                    @else
                                        {{-- Monster Tabs --}}
                                        <div class="relative z-20 flex overflow-x-auto gap-1.5 mb-2 pb-2 custom-scrollbar">
                                            @foreach($dungeonMonsters as $dm)
                                                <button @click="selectMonster('{{ $dm->id }}')"
                                                    :class="selectedMonsterId == '{{ $dm->id }}' ? 'bg-[#f4e4bc] text-amber-950 border-amber-700 shadow-lg -translate-y-1 font-black' : 'bg-amber-900/80 text-amber-200 hover:bg-amber-800 hover:-translate-y-0.5 font-bold'"
                                                    class="px-3 py-1.5 rounded-t-xl text-xs sm:text-sm border-t-2 border-x-2 border-amber-800/60 whitespace-nowrap transition-all duration-200 flex items-center gap-1.5 medieval-font">
                                                    @if($dm->type && $dm->type->value === 'undead') <i class="fa-solid fa-skull text-stone-900"></i>
                                                    @elseif($dm->type && $dm->type->value === 'demon') <i class="fa-solid fa-dragon text-purple-900"></i>
                                                    @elseif($dm->type && $dm->type->value === 'beast') <i class="fa-solid fa-paw text-amber-900"></i>
                                                    @else <i class="fa-solid fa-skull text-stone-900"></i> @endif
                                                    <span>{{ $dm->name }}</span>
                                                    <span class="text-[10px] opacity-75">(Lvl {{ $dm->level }})</span>
                                                    @if($dm->rank && $dm->rank->value === 'worldboss')
                                                        <span class="px-1.5 py-0.5 rounded bg-purple-700 text-white text-[9px] font-black uppercase tracking-wide">World Boss</span>
                                                    @elseif($dm->rank && $dm->rank->value === 'boss')
                                                        <span class="px-1.5 py-0.5 rounded bg-red-700 text-white text-[9px] font-black uppercase tracking-wide">Boss</span>
                                                    @endif
                                                </button>
                                            @endforeach
                                        </div>

                                        {{-- Parchment Pages --}}
                                        <div class="relative flex-1 bg-[#f4e4bc] text-amber-950 border-2 border-amber-900/50 rounded-xl shadow-inner overflow-y-auto custom-scrollbar p-4 sm:p-6 min-h-[480px]">
                                            <div class="hidden md:block absolute top-0 bottom-0 left-1/2 -translate-x-1/2 w-10 bg-gradient-to-r from-amber-950/25 via-amber-950/5 to-amber-950/25 pointer-events-none z-20"></div>

                                            {{-- 3D Page Flip --}}
                                            <div x-show="turningPage" class="absolute inset-0 pointer-events-none z-30 overflow-hidden rounded-xl" style="perspective: 1600px;">
                                                <template x-if="turnDirection === 'next'">
                                                    <div class="hidden md:block absolute top-0 bottom-0 right-0 w-1/2 origin-left animate-page-flip-next rounded-r-xl border-l-2 border-amber-900/40 bg-[#ebd7a7] shadow-[0_15px_35px_rgba(0,0,0,0.5)]">
                                                        <div class="absolute inset-0 bg-gradient-to-r from-amber-950/30 via-amber-900/5 to-amber-950/20 p-6 flex flex-col justify-between" style="backface-visibility: hidden;">
                                                            <div class="text-center text-5xl opacity-25 text-amber-950 my-auto"><i class="fa-solid fa-scroll"></i></div>
                                                        </div>
                                                    </div>
                                                </template>
                                                <template x-if="turnDirection === 'prev'">
                                                    <div class="hidden md:block absolute top-0 bottom-0 left-0 w-1/2 origin-right animate-page-flip-prev rounded-l-xl border-r-2 border-amber-900/40 bg-[#ebd7a7] shadow-[0_15px_35px_rgba(0,0,0,0.5)]">
                                                        <div class="absolute inset-0 bg-gradient-to-l from-amber-950/30 via-amber-900/5 to-amber-950/20 p-6 flex flex-col justify-between" style="backface-visibility: hidden;">
                                                            <div class="text-center text-5xl opacity-25 text-amber-950 my-auto"><i class="fa-solid fa-scroll"></i></div>
                                                        </div>
                                                    </div>
                                                </template>
                                                <div class="md:hidden absolute inset-0 bg-[#e8d5a7] z-30 animate-book-shadow-pulse border-2 border-amber-900/30 rounded-xl flex items-center justify-center">
                                                    <div class="text-amber-950 font-bold medieval-font text-3xl animate-bounce"><i class="fa-solid fa-scroll"></i></div>
                                                </div>
                                                <div class="hidden md:block absolute top-0 bottom-0 left-1/2 -translate-x-1/2 w-12 bg-gradient-to-r from-amber-950/60 via-amber-950/10 to-amber-950/60 animate-book-shadow-pulse"></div>
                                            </div>

                                            @foreach($dungeonMonsters as $dm)
                                                <div x-show="selectedMonsterId == '{{ $dm->id }}'" class="flex flex-col md:flex-row w-full gap-6 sm:gap-8 h-full">

                                                    {{-- LEFT PAGE: Monster info --}}
                                                    <div class="w-full md:w-1/2 flex flex-col items-center border-b md:border-b-0 md:border-r border-amber-900/30 pb-6 md:pb-0 md:pr-6">
                                                        <div class="relative w-36 h-36 sm:w-48 sm:h-48 rounded-2xl overflow-hidden ring-4 ring-amber-900/70 shadow-2xl mb-4 bg-amber-950 flex-shrink-0">
                                                            @if(!empty($dm->avatar))
                                                                <img src="{{ route('assets.monsters.avatars', ['filename' => $dm->avatar]) }}?v={{ @filemtime(public_path('assets/monsters/avatars/' . $dm->avatar)) }}" alt="{{ $dm->name }}" class="w-full h-full object-cover">
                                                            @else
                                                                <img src="{{ asset('img/monsters/placeholder.png') }}" alt="{{ $dm->name }}" class="w-full h-full object-cover">
                                                            @endif
                                                            <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-transparent to-transparent"></div>
                                                            <div class="absolute bottom-2 left-0 w-full text-center text-amber-200 font-black medieval-font text-lg drop-shadow-[0_2px_4px_rgba(0,0,0,0.9)]">
                                                                Poziom {{ $dm->level }}
                                                            </div>
                                                        </div>

                                                        <h3 class="text-2xl sm:text-3xl font-black text-amber-950 medieval-font mb-2 text-center tracking-wide">{{ $dm->name }}</h3>

                                                        <div class="flex items-center gap-2 flex-wrap justify-center mb-4">
                                                            @if($dm->type)
                                                                <div class="bg-amber-900 text-amber-100 px-3 py-1 rounded-full text-xs font-bold shadow-md border border-amber-700">
                                                                    Rasa: {{ $dm->type->label() }}
                                                                </div>
                                                            @endif
                                                            @if($dm->rank && $dm->rank->value === 'worldboss')
                                                                <div class="bg-purple-700 text-white px-3 py-1 rounded-full text-xs font-black uppercase shadow-md border border-purple-500 flex items-center gap-1">
                                                                    <i class="fa-solid fa-crown"></i> World Boss
                                                                </div>
                                                            @elseif($dm->rank && $dm->rank->value === 'boss')
                                                                <div class="bg-red-700 text-white px-3 py-1 rounded-full text-xs font-black uppercase shadow-md border border-red-500 flex items-center gap-1">
                                                                    <i class="fa-solid fa-crown"></i> Boss
                                                                </div>
                                                            @endif
                                                        </div>

                                                        {{-- Stage badge --}}
                                                        @php $dmStage = $dungeon->stages->firstWhere('monster_id', $dm->id); @endphp
                                                        @if($dmStage)
                                                            <div class="mb-3 bg-red-900/60 text-red-200 px-3 py-1 rounded-full text-xs font-bold border border-red-700/50">
                                                                Etap #{{ $dmStage->stage_order }} lochu
                                                            </div>
                                                        @endif

                                                        <div class="w-full bg-amber-100/70 rounded-xl p-4 border border-amber-900/40 shadow-sm mt-auto">
                                                            <h4 class="font-bold text-amber-950 mb-3 border-b border-amber-900/30 pb-1 flex items-center justify-between text-sm">
                                                                <span><i class="fa-solid fa-bolt text-amber-600 mr-1"></i> Atrybuty Bojowe</span>
                                                                <span class="text-xs text-amber-800">Przeciwnik</span>
                                                            </h4>
                                                            <div class="grid grid-cols-2 gap-2.5 text-xs font-semibold">
                                                                <div class="flex justify-between items-center bg-amber-200/60 p-2 rounded-lg border border-amber-900/20">
                                                                    <span class="text-amber-900 font-bold"><i class="fa-solid fa-heart text-red-600 mr-1"></i> Punkty Życia</span>
                                                                    <span class="text-red-700 font-bold text-sm">{{ number_format($dm->stats['hp'] ?? $dm->level * 20) }}</span>
                                                                </div>
                                                                <div class="flex justify-between items-center bg-amber-200/60 p-2 rounded-lg border border-amber-900/20">
                                                                    <span class="text-amber-900 font-bold"><i class="fa-solid fa-khanda text-amber-700 mr-1"></i> Atak</span>
                                                                    <span class="text-amber-950 font-bold text-sm">{{ $dm->stats['atk'] ?? '?' }}</span>
                                                                </div>
                                                                <div class="flex justify-between items-center bg-amber-200/60 p-2 rounded-lg border border-amber-900/20">
                                                                    <span class="text-amber-900 font-bold"><i class="fa-solid fa-shield-halved text-slate-700 mr-1"></i> Obrona</span>
                                                                    <span class="text-slate-800 font-bold text-sm">{{ $dm->stats['def'] ?? '?' }}</span>
                                                                </div>
                                                                <div class="flex justify-between items-center bg-amber-200/60 p-2 rounded-lg border border-amber-900/20">
                                                                    <span class="text-amber-900 font-bold"><i class="fa-solid fa-wind text-emerald-700 mr-1"></i> Zręczność</span>
                                                                    <span class="text-emerald-800 font-bold text-sm">{{ $dm->stats['agi'] ?? '?' }}</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    {{-- RIGHT PAGE: Loot table --}}
                                                    <div class="w-full md:w-1/2 flex flex-col">
                                                        <h4 class="text-xl font-black text-amber-950 medieval-font mb-4 border-b-2 border-amber-900/30 pb-2 flex items-center justify-between">
                                                            <span><i class="fa-solid fa-gift text-amber-600 mr-1"></i> Tabela Zdobyczy</span>
                                                            <span class="text-xs font-bold text-amber-800">Szansa na łup</span>
                                                        </h4>
                                                        <div class="space-y-2.5 overflow-y-auto max-h-[360px] pr-1 custom-scrollbar">
                                                            @if($dm->lootTable && $dm->lootTable->entries->isNotEmpty())
                                                                @php $totalWeight = max(1, $dm->lootTable->entries->sum('weight')); @endphp
                                                                @foreach($dm->lootTable->entries->sortByDesc('weight') as $entry)
                                                                    @php
                                                                        $chance = round(($entry->weight / $totalWeight) * 100, 1);
                                                                        if (in_array($entry->reward_type, ['item', 'material']) && $entry->itemTemplate && $entry->itemTemplate->type === 'quest_item') {
                                                                            if (!$entry->itemTemplate->quest_id || !in_array($entry->itemTemplate->quest_id, $activeQuestIds)) {
                                                                                continue;
                                                                            }
                                                                        }
                                                                    @endphp
                                                                    <div class="bg-amber-100/80 rounded-xl p-3 border border-amber-900/30 shadow-sm relative overflow-hidden group hover:bg-amber-100 transition-colors">
                                                                        <div class="absolute inset-y-0 left-0 bg-amber-300/40 pointer-events-none transition-all duration-500" style="width: {{ min(100, $chance) }}%"></div>
                                                                        <div class="relative z-10 flex items-center justify-between gap-3">
                                                                            <div class="flex items-center gap-3">
                                                                                <div class="w-10 h-10 rounded-lg bg-amber-900/10 border border-amber-900/30 flex items-center justify-center text-xl flex-shrink-0 shadow-inner">
                                                                                    @if($entry->reward_type === 'gold') <i class="fa-solid fa-coins text-yellow-600"></i>
                                                                                    @elseif($entry->reward_type === 'xp') <i class="fa-solid fa-sparkles text-amber-600"></i>
                                                                                    @elseif(in_array($entry->reward_type, ['item', 'material']) && $entry->itemTemplate)
                                                                                        <img src="{{ route('assets.items', ['filename' => $entry->itemTemplate->icon]) }}" onerror="this.src='{{ route('assets.items', ['filename' => 'default.png']) }}'" class="w-7 h-7 object-contain">
                                                                                    @endif
                                                                                </div>
                                                                                <div>
                                                                                    <div class="font-bold text-amber-950 text-sm">
                                                                                        @if($entry->reward_type === 'gold') Złoto
                                                                                        @elseif($entry->reward_type === 'xp') Doświadczenie
                                                                                        @elseif(in_array($entry->reward_type, ['item', 'material']) && $entry->itemTemplate)
                                                                                            <span class="{{ $entry->itemTemplate->rarity === 'legendary' ? 'text-amber-700 font-extrabold' : ($entry->itemTemplate->rarity === 'epic' ? 'text-purple-900 font-bold' : ($entry->itemTemplate->rarity === 'rare' ? 'text-blue-900 font-bold' : 'text-amber-950 font-bold')) }}">
                                                                                                {{ $entry->itemTemplate->name }}
                                                                                            </span>
                                                                                        @endif
                                                                                    </div>
                                                                                    <div class="text-xs text-amber-800 font-semibold">
                                                                                        Ilość: {{ $entry->min_qty }}{{ $entry->min_qty != $entry->max_qty ? ' - ' . $entry->max_qty : '' }}
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                            <div class="text-right flex-shrink-0">
                                                                                <div class="text-base font-black text-amber-950">{{ $chance }}%</div>
                                                                                <div class="text-[9px] text-amber-800 font-bold uppercase tracking-wider">Prawdopodobieństwo</div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                @endforeach
                                                            @else
                                                                <div class="text-center py-10">
                                                                    <div class="text-4xl mb-2 text-amber-900/60"><i class="fa-solid fa-spider"></i></div>
                                                                    <p class="text-amber-900 italic font-bold text-sm">Przeciwnik nie posiada znanych łupów...</p>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </template>

                    </div>
                @endforeach
            </div>

            @if($dungeons->isEmpty())
                <div class="text-center py-20">
                    <div class="text-6xl text-slate-800 mb-4"><i class="fa-solid fa-scroll"></i></div>
                    <h3 class="text-xl font-bold text-slate-400 medieval-font">Brak dostupnych lochów</h3>
                    <p class="text-slate-600 text-sm">Wróć później, gdy pojawią się nowe wyzwania w krainie.</p>
                </div>
            @endif
        </div>
        @endif

        {{-- WORLDBOSS TAB --}}
        @if($tab === 'worldboss')
        {{-- Licznik resetu i cały panel są odświeżane co 10s przez wire:poll zamiast
             klientowego setInterval-a spiętego z Alpine init() - po morphach Livewire (np.
             kliknięcie w inny element strony) init() bywał niewywoływany ponownie, przez co
             licznik czasami w ogóle nie renderował się na nowo. Serwerowe polling jest
             bezstanowe i zawsze poprawne. --}}
        <div class="w-full px-4 md:px-0" wire:poll.10s>
            <div class="text-center mb-8 max-w-2xl mx-auto">
                <p class="text-sm text-slate-300">Trzej najeźdźcy spustoszyli krainę - po jednym na każdy przedział poziomowy. Ich pula HP jest wspólna dla całego serwera - liczy się suma wszystkich zadanych obrażeń, a wystarczająco duży łączny (lub pojedynczy) dmg może ich realnie pokonać - wtedy ranking zamyka się do najbliższego resetu. Ranking i nagrody (gemy oraz klucze do lochów) rozliczane są co godzinę, niezależnie od tego czy boss padł.</p>
                <p class="text-sm text-purple-300 font-bold mt-3">Reset rankingu za: <span class="text-white">{{ $resetCountdownLabel }}</span></p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 max-w-6xl mx-auto">
                @foreach(['low', 'mid', 'high'] as $bracket)
                    @php
                        $boss = $worldBosses[$bracket] ?? null;
                    @endphp
                    <div class="bg-slate-900/90 border-2 border-purple-800/60 rounded-2xl shadow-xl backdrop-blur-md overflow-hidden flex flex-col">
                        <div class="p-4 border-b border-purple-900/50 bg-gradient-to-r from-purple-950/80 to-slate-900/80 flex items-center justify-between">
                            <span class="text-xs font-black text-purple-300 uppercase tracking-wider">Poziom {{ $bracketLabels[$bracket] }}</span>
                            <i class="fa-solid fa-crown text-amber-400"></i>
                        </div>

                        @if(!$boss)
                            <div class="p-8 text-center text-slate-500 italic text-sm flex-1 flex items-center justify-center">Brak aktywnego bossa w tym przedziale.</div>
                        @else
                            @php
                                $hpPercent = max(0, min(100, ($boss->current_hp / max(1, $boss->total_hp)) * 100));
                                $hasParticipated = in_array($bracket, $participatedBrackets);
                                $topDmg = $topDamageDealers[$boss->id] ?? collect();
                                // Map::isAccessibleBy() sprawdza tylko dolny próg poziomu (przekroczenie
                                // level_max na zwykłej mapie to kara "over-level", nie blokada) - dla
                                // world bossów trzeba twardo dopasować przedział do poziomu postaci,
                                // inaczej wysoko-poziomowa postać zdominowałaby ranking niskiego
                                // przedziału. Patrz też EncounterService::start() (ta sama walidacja
                                // po stronie serwera).
                                $bossAccessible = \App\Application\Combat\WorldBossService::bracketForLevel($character->level) === $bracket;
                                $isDefeated = $boss->current_hp <= 0;
                            @endphp
                            <div class="p-5 flex flex-col flex-1 gap-4">
                                <div>
                                    <h3 class="text-xl font-bold text-amber-100 medieval-font">{{ $boss->monster->name }}</h3>
                                    <p class="text-xs text-slate-400">{{ $boss->map->name ?? '' }} &bull; Poziom {{ $boss->monster->level }}</p>
                                </div>

                                <div>
                                    <div class="flex justify-between text-xs mb-1 font-bold">
                                        <span class="text-slate-300">HP (wspólna pula)</span>
                                        <span class="{{ $isDefeated ? 'text-amber-400' : 'text-red-400' }}">{{ number_format($boss->current_hp) }} / {{ number_format($boss->total_hp) }}</span>
                                    </div>
                                    <div class="w-full bg-slate-950 rounded-full h-3 border border-slate-700 overflow-hidden p-0.5">
                                        <div class="h-full {{ $isDefeated ? 'bg-gradient-to-r from-amber-600 to-yellow-500' : 'bg-gradient-to-r from-red-600 to-red-500' }} rounded-full transition-all duration-1000" style="width: {{ $isDefeated ? 100 : $hpPercent }}%"></div>
                                    </div>
                                    @if($isDefeated)
                                        <p class="text-[11px] text-amber-400 font-bold mt-1.5 flex items-center gap-1.5">
                                            <i class="fa-solid fa-trophy"></i> Pokonany! Ranking zamknięty do resetu.
                                        </p>
                                    @endif
                                </div>

                                <div class="space-y-1.5 max-h-40 overflow-y-auto pr-1 custom-scrollbar">
                                    <div class="flex items-center justify-between mb-1">
                                        <p class="text-[11px] font-bold text-purple-300 uppercase tracking-wide">Top 10 wojowników</p>
                                    </div>
                                    <div class="flex flex-wrap gap-x-3 gap-y-1 text-[10px] text-slate-400 bg-black/30 rounded-lg px-2 py-1.5 mb-1.5 border border-purple-900/40">
                                        <span><b class="text-amber-400">#1</b> 50 <i class="fa-solid fa-gem text-purple-400"></i> + 5 <i class="fa-solid fa-key text-amber-400"></i></span>
                                        <span><b class="text-slate-300">#2-3</b> 30 <i class="fa-solid fa-gem text-purple-400"></i> + 5 <i class="fa-solid fa-key text-amber-400"></i></span>
                                        <span><b class="text-amber-700">#4-6</b> 3 <i class="fa-solid fa-key text-amber-400"></i></span>
                                        <span><b class="text-slate-500">#7-9</b> 1 <i class="fa-solid fa-key text-amber-400"></i></span>
                                    </div>
                                    @if($topDmg->isEmpty())
                                        <p class="text-slate-500 italic text-center py-3 text-xs">Brak uczestników. Bądź pierwszy!</p>
                                    @else
                                        @foreach($topDmg as $index => $log)
                                            @php $reward = \App\Jobs\WorldBossRewardJob::rewardForPlace($index + 1); @endphp
                                            <div class="flex justify-between items-center text-xs {{ $log->character_id === $character->id ? 'bg-purple-950/80 border border-purple-500/60' : 'bg-slate-950/60' }} p-1.5 rounded-lg">
                                                <div class="flex items-center gap-2">
                                                    <span class="font-bold {{ $index === 0 ? 'text-yellow-400' : ($index === 1 ? 'text-slate-300' : ($index === 2 ? 'text-amber-600' : 'text-slate-500')) }}">#{{ $index + 1 }}</span>
                                                    <span class="{{ $log->character_id === $character->id ? 'text-purple-200 font-bold' : 'text-slate-300' }}">{{ $log->character->name }}</span>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    @if($reward['gems'] > 0 || $reward['keys'] > 0)
                                                        <span class="text-[10px] text-slate-400 flex items-center gap-1">
                                                            @if($reward['gems'] > 0)
                                                                {{ $reward['gems'] }}<i class="fa-solid fa-gem text-purple-400"></i>
                                                            @endif
                                                            @if($reward['keys'] > 0)
                                                                {{ $reward['keys'] }}<i class="fa-solid fa-key text-amber-400"></i>
                                                            @endif
                                                        </span>
                                                    @endif
                                                    <span class="text-red-400 font-bold">{{ number_format($log->damage) }}</span>
                                                </div>
                                            </div>
                                        @endforeach
                                    @endif
                                </div>

                                <div class="mt-auto pt-3 border-t border-purple-900/40">
                                    @if($isDefeated)
                                        <button disabled class="w-full bg-amber-950/60 text-amber-500 font-bold py-3 rounded-xl cursor-not-allowed border border-amber-700/60 text-sm medieval-font flex items-center justify-center gap-2">
                                            <i class="fa-solid fa-trophy"></i> Boss pokonany
                                        </button>
                                    @elseif(!$bossAccessible)
                                        <button disabled class="w-full bg-slate-800 text-slate-400 font-bold py-3 rounded-xl cursor-not-allowed border border-slate-700 text-sm medieval-font flex items-center justify-center gap-2">
                                            <i class="fa-solid fa-lock text-amber-500"></i> Niedostępne dla Twojego poziomu
                                        </button>
                                    @elseif($hasParticipated)
                                        <button disabled class="w-full bg-slate-800 text-slate-400 font-bold py-3 rounded-xl cursor-not-allowed border border-slate-700 text-sm medieval-font">
                                            Już walczyłeś w tej godzinie
                                        </button>
                                    @else
                                        <a href="{{ route('adventure.map', ['character' => $character, 'map' => $boss->map, 'world_boss' => $boss->monster_id]) }}"
                                            wire:navigate
                                            class="block w-full text-center bg-gradient-to-r from-red-700 via-purple-600 to-red-700 hover:from-red-600 hover:via-purple-500 hover:to-red-600 text-white font-bold py-3 rounded-xl shadow-lg shadow-red-950/60 border border-red-500 transition-all transform hover:scale-[1.01] text-sm medieval-font flex items-center justify-center gap-2">
                                            <i class="fa-solid fa-khanda"></i> Atakuj
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    {{-- CUSTOM MEDIEVAL TYPOGRAPHY & FLOATING PARTICLES STYLES --}}
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700;900&display=swap');

        .medieval-font {
            font-family: 'Cinzel', serif;
        }

        /* Floating Ember & Weapon Particles */
        .adventure-element {
            position: absolute;
            font-size: 1.5rem;
            opacity: 0.4;
            pointer-events: none;
            animation: float-adventure 18s infinite linear;
        }

        .adventure-element-1 { left: 8%; animation-delay: 0s; animation-duration: 22s; }
        .adventure-element-2 { left: 28%; animation-delay: 4s; animation-duration: 19s; }
        .adventure-element-3 { left: 52%; animation-delay: 8s; animation-duration: 25s; }
        .adventure-element-4 { left: 74%; animation-delay: 12s; animation-duration: 17s; }
        .adventure-element-5 { left: 90%; animation-delay: 15s; animation-duration: 21s; }

        @keyframes float-adventure {
            0% {
                transform: translateY(105vh) translateX(0px) rotate(0deg);
                opacity: 0;
            }
            15% {
                opacity: 0.5;
            }
            85% {
                opacity: 0.5;
            }
            100% {
                transform: translateY(-100px) translateX(40px) rotate(360deg);
                opacity: 0;
            }
        }

        /* Real 3D Book Page Turn Keyframes */
        @keyframes pageFlipNext {
            0% {
                transform: rotateY(0deg) rotateZ(0deg) scaleY(1);
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
            }
            50% {
                transform: rotateY(-90deg) rotateZ(-2.5deg) scaleY(1.02);
                box-shadow: -20px 25px 45px rgba(0, 0, 0, 0.5);
            }
            100% {
                transform: rotateY(-180deg) rotateZ(0deg) scaleY(1);
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            }
        }

        @keyframes pageFlipPrev {
            0% {
                transform: rotateY(0deg) rotateZ(0deg) scaleY(1);
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
            }
            50% {
                transform: rotateY(90deg) rotateZ(2.5deg) scaleY(1.02);
                box-shadow: 20px 25px 45px rgba(0, 0, 0, 0.5);
            }
            100% {
                transform: rotateY(180deg) rotateZ(0deg) scaleY(1);
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            }
        }

        @keyframes bookShadowPulse {
            0% { opacity: 0.2; }
            50% { opacity: 0.7; }
            100% { opacity: 0.2; }
        }

        .animate-page-flip-next {
            animation: pageFlipNext 0.45s ease-in-out forwards;
            transform-style: preserve-3d;
        }

        .animate-page-flip-prev {
            animation: pageFlipPrev 0.45s ease-in-out forwards;
            transform-style: preserve-3d;
        }

        .animate-book-shadow-pulse {
            animation: bookShadowPulse 0.45s ease-in-out forwards;
        }

        /* Scrollbar Styling for Bestiary & Dungeon Cards */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.1);
            border-radius: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(180, 83, 9, 0.4);
            border-radius: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(180, 83, 9, 0.7);
        }
    </style>
</div>
