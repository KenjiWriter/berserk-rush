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
            <h1 class="text-4xl sm:text-5xl font-black bg-gradient-to-r from-amber-200 via-emerald-300 to-amber-300 bg-clip-text text-transparent medieval-font drop-shadow-lg tracking-wider mb-2">
                🗺️ Wybierz Przygodę
            </h1>
            <p class="text-sm sm:text-base text-slate-300 max-w-xl mx-auto">
                Twój poziom: <span class="text-emerald-400 font-bold">{{ $character->level }}</span> • Wybierz odpowiednią mapę lub loch, by zdobywać doświadczenie i cenny łup.
            </p>

            {{-- Tab Switcher --}}
            <div class="inline-flex bg-slate-900/90 rounded-xl p-1.5 border border-slate-800 mt-6 shadow-inner">
                <button wire:click="setTab('maps')" 
                    class="px-6 py-2.5 rounded-lg font-bold text-sm sm:text-base transition-all duration-300 medieval-font flex items-center gap-2 {{ $tab === 'maps' ? 'bg-gradient-to-r from-emerald-700 to-emerald-600 text-white shadow-lg border border-emerald-500/50' : 'text-slate-400 hover:text-amber-200 hover:bg-slate-800/60' }}">
                    <span>🌲 Mapy</span>
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $tab === 'maps' ? 'bg-emerald-950 text-emerald-200' : 'bg-slate-800 text-slate-400' }}">{{ $maps->count() }}</span>
                </button>
                <button wire:click="setTab('dungeons')" 
                    class="px-6 py-2.5 rounded-lg font-bold text-sm sm:text-base transition-all duration-300 medieval-font flex items-center gap-2 {{ $tab === 'dungeons' ? 'bg-gradient-to-r from-amber-700 to-amber-600 text-white shadow-lg border border-amber-500/50' : 'text-slate-400 hover:text-amber-200 hover:bg-slate-800/60' }}">
                    <span>🏰 Lochy</span>
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $tab === 'dungeons' ? 'bg-amber-950 text-amber-200' : 'bg-slate-800 text-slate-400' }}">{{ count($dungeons) }}</span>
                </button>
            </div>
        </div>

        {{-- Map access error alert --}}
        @error('map_access')
            <div class="mb-6 p-4 bg-red-950/80 border-2 border-red-600 rounded-xl backdrop-blur-md max-w-2xl mx-auto shadow-2xl">
                <p class="text-red-200 font-semibold text-center flex items-center justify-center gap-2">
                    ⚠️ {{ $message }}
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
                @endphp

                <div class="relative group h-full flex flex-col" x-data="{ 
                    showBestiaryModal: false, 
                    showBossModal: false, 
                    turningPage: false,
                    turnDirection: 'next',
                    monsterIds: [ @foreach($map->monsters as $m) '{{ $m->id }}', @endforeach ],
                    selectedMonsterId: '{{ $map->monsters->first()->id ?? '' }}',
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

                        {{-- Current Level Badge --}}
                        @if ($isCurrentLevel)
                            <div class="absolute top-3 right-3 bg-gradient-to-r from-amber-500 to-yellow-500 text-slate-950 px-3 py-1 rounded-full text-xs font-black shadow-lg border border-yellow-300 z-20 flex items-center gap-1 animate-pulse">
                                🌟 REKOMENDOWANA
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
                                        <div class="text-4xl text-slate-400 mb-2">🔒</div>
                                        <div class="text-xs font-bold text-slate-300">
                                            @if ($character->level < $map->level_min)
                                                Wymagany poziom: {{ $map->level_min }}
                                            @else
                                                Przekroczony limit poziomu (max: {{ $map->level_max }})
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            @else
                                <div class="w-full h-full bg-gradient-to-br from-slate-900 via-slate-800 to-slate-950 flex items-center justify-center">
                                    <div class="text-6xl text-emerald-500/40 group-hover:scale-110 transition-transform">
                                        🌲
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

                            {{-- Active World Boss Banner if present --}}
                            @if(isset($activeWorldBosses[$map->id]))
                                <div class="bg-gradient-to-r from-purple-950/90 to-purple-900/90 border border-purple-600/80 rounded-xl p-3 shadow-lg shadow-purple-950/40 relative overflow-hidden">
                                    <div class="flex items-center justify-between mb-1.5">
                                        <span class="text-xs font-black text-purple-300 tracking-wider uppercase flex items-center gap-1">
                                            👑 World Boss
                                        </span>
                                        <span class="text-xs font-bold text-red-400 animate-pulse">Aktywny!</span>
                                    </div>
                                    <div class="text-sm font-bold text-amber-100 mb-2">
                                        {{ $activeWorldBosses[$map->id]->monster->name }}
                                    </div>
                                    <button @click="showBossModal = true" class="w-full bg-purple-700 hover:bg-purple-600 text-white font-bold py-1.5 px-3 rounded-lg text-xs transition-colors medieval-font border border-purple-400 shadow-md">
                                        🗡️ Sprawdź & Dołącz do Walce
                                    </button>
                                </div>
                            @elseif(isset($defeatedWorldBosses[$map->id]))
                                <div class="bg-slate-950/60 border border-slate-800 rounded-xl p-2.5 text-center">
                                    <p class="text-slate-400 font-semibold text-xs">👑 Boss odnowi się o: <span class="text-amber-400 font-bold">{{ now()->addHour()->startOfHour()->format('H:i') }}</span></p>
                                </div>
                            @endif

                            {{-- Action buttons --}}
                            <div class="space-y-2.5 mt-auto">
                                @if ($isAccessible)
                                    <button @click="travelingTo = '{{ addslashes($map->name) }}'; $dispatch('play-audio', { type: 'combat' }); setTimeout(() => $wire.enterMap('{{ $map->id }}'), 400)"
                                        class="w-full bg-gradient-to-r from-emerald-600 via-emerald-500 to-emerald-600 hover:from-emerald-500 hover:to-emerald-400 text-white font-bold py-3 px-4 rounded-xl transition-all duration-200 transform hover:scale-[1.02] shadow-lg shadow-emerald-950/50 border border-emerald-400/50 medieval-font flex items-center justify-center gap-2">
                                        <span>⚔️ WEJDŹ NA MAPĘ</span>
                                    </button>
                                    
                                    <button @click="showBestiaryModal = true" 
                                        class="w-full bg-slate-800/90 hover:bg-slate-700/90 text-amber-200 font-bold py-2.5 px-4 rounded-xl transition-all duration-200 text-xs sm:text-sm border border-amber-600/40 hover:border-amber-400/80 shadow-md medieval-font flex items-center justify-center gap-2 group/btn">
                                        <span class="group-hover/btn:rotate-12 transition-transform">📖</span> KSIĘGA BESTII
                                    </button>
                                @else
                                    <button disabled
                                        class="w-full bg-slate-800/60 text-slate-500 font-bold py-3 px-4 rounded-xl cursor-not-allowed border border-slate-800 medieval-font text-center">
                                        🔒 Niedostępne
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
                                        <h2 class="text-2xl sm:text-3xl font-black text-amber-200 medieval-font tracking-wide drop-shadow-md mx-auto">
                                            📜 Kodeks Bestii: {{ $map->name }}
                                        </h2>
                                        <div class="text-right hidden sm:block w-24"></div>
                                    </div>

                                    @if($map->monsters->isEmpty())
                                        <div class="flex items-center justify-center py-16 bg-[#f4e4bc] rounded-xl text-amber-950">
                                            <p class="italic font-bold text-lg">Brak informacji o przeciwnikach na tej mapie...</p>
                                        </div>
                                    @else
                                        {{-- Monster Bookmark Tabs --}}
                                        <div class="relative z-20 flex overflow-x-auto gap-1.5 mb-2 pb-2 custom-scrollbar">
                                            @foreach($map->monsters as $monster)
                                                <button @click="selectMonster('{{ $monster->id }}')" 
                                                    :class="selectedMonsterId == '{{ $monster->id }}' ? 'bg-[#f4e4bc] text-amber-950 border-amber-700 shadow-lg -translate-y-1 font-black' : 'bg-amber-900/80 text-amber-200 hover:bg-amber-800 hover:-translate-y-0.5 font-bold'"
                                                    class="px-3 py-1.5 rounded-t-xl text-xs sm:text-sm border-t-2 border-x-2 border-amber-800/60 whitespace-nowrap transition-all duration-200 flex items-center gap-1.5 medieval-font">
                                                    @if($monster->type && $monster->type->value === 'undead') 💀
                                                    @elseif($monster->type && $monster->type->value === 'demon') 👹
                                                    @elseif($monster->type && $monster->type->value === 'beast') 🐺
                                                    @elseif($monster->type && $monster->type->value === 'orc') 🧌
                                                    @else 👹 @endif
                                                    <span>{{ $monster->name }}</span>
                                                    <span class="text-[10px] opacity-75">(Lvl {{ $monster->level }})</span>
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
                                                                <span class="text-xs font-bold medieval-font text-amber-950">📜</span>
                                                            </div>
                                                            <div class="text-center text-5xl opacity-25 text-amber-950 my-auto">📜</div>
                                                            <div class="w-full border-t border-amber-900/20 pt-2 text-right opacity-40">
                                                                <span class="text-[10px] font-bold medieval-font text-amber-950">Berserk Rush</span>
                                                            </div>
                                                        </div>
                                                        {{-- Back face of turning page --}}
                                                        <div class="absolute inset-0 bg-gradient-to-l from-amber-950/40 via-amber-900/10 to-amber-950/20 p-6 flex flex-col justify-between" style="backface-visibility: hidden; transform: rotateY(180deg);">
                                                            <div class="w-full border-b border-amber-900/20 pb-2 flex justify-between items-center opacity-40">
                                                                <span class="text-xs font-bold medieval-font text-amber-950">📜</span>
                                                                <span class="text-xs font-bold medieval-font text-amber-950">Grimoire Codex</span>
                                                            </div>
                                                            <div class="text-center text-5xl opacity-20 text-amber-950 my-auto">📖</div>
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
                                                                <span class="text-xs font-bold medieval-font text-amber-950">📜</span>
                                                                <span class="text-xs font-bold medieval-font text-amber-950">Grimoire Codex</span>
                                                            </div>
                                                            <div class="text-center text-5xl opacity-25 text-amber-950 my-auto">📜</div>
                                                            <div class="w-full border-t border-amber-900/20 pt-2 text-left opacity-40">
                                                                <span class="text-[10px] font-bold medieval-font text-amber-950">Berserk Rush</span>
                                                            </div>
                                                        </div>
                                                        <div class="absolute inset-0 bg-gradient-to-r from-amber-950/40 via-amber-900/10 to-amber-950/20 p-6 flex flex-col justify-between" style="backface-visibility: hidden; transform: rotateY(180deg);">
                                                            <div class="w-full border-b border-amber-900/20 pb-2 flex justify-between items-center opacity-40">
                                                                <span class="text-xs font-bold medieval-font text-amber-950">Grimoire Codex</span>
                                                                <span class="text-xs font-bold medieval-font text-amber-950">📜</span>
                                                            </div>
                                                            <div class="text-center text-5xl opacity-20 text-amber-950 my-auto">📖</div>
                                                            <div class="w-full border-t border-amber-900/20 pt-2 text-right opacity-40">
                                                                <span class="text-[10px] font-bold medieval-font text-amber-950">Berserk Rush</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </template>

                                                {{-- Fallback Mobile 3D Fade & Fold --}}
                                                <div class="md:hidden absolute inset-0 bg-[#e8d5a7] z-30 animate-book-shadow-pulse border-2 border-amber-900/30 rounded-xl flex items-center justify-center">
                                                    <div class="text-amber-950 font-bold medieval-font text-3xl animate-bounce">
                                                        📜
                                                    </div>
                                                </div>

                                                {{-- Center Spine Shadow Pulse --}}
                                                <div class="hidden md:block absolute top-0 bottom-0 left-1/2 -translate-x-1/2 w-12 bg-gradient-to-r from-amber-950/60 via-amber-950/10 to-amber-950/60 animate-book-shadow-pulse"></div>
                                            </div>

                                            @foreach($map->monsters as $monster)
                                                <div x-show="selectedMonsterId == '{{ $monster->id }}'" 
                                                     class="flex flex-col md:flex-row w-full gap-6 sm:gap-8 h-full">
                                                    
                                                    {{-- LEFT PAGE: MONSTER CODEX & STATS --}}
                                                    <div class="w-full md:w-1/2 flex flex-col items-center border-b md:border-b-0 md:border-r border-amber-900/30 pb-6 md:pb-0 md:pr-6">
                                                        
                                                        {{-- Monster Frame & Avatar --}}
                                                        <div class="relative w-36 h-36 sm:w-48 sm:h-48 rounded-2xl overflow-hidden ring-4 ring-amber-900/70 shadow-2xl mb-4 bg-amber-950 flex-shrink-0">
                                                            @if(!empty($monster->avatar))
                                                                <img src="{{ route('assets.monsters.avatars', ['filename' => $monster->avatar]) }}"
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
                                                        
                                                        @if($monster->type)
                                                            <div class="bg-amber-900 text-amber-100 px-3 py-1 rounded-full text-xs font-bold shadow-md mb-4 border border-amber-700">
                                                                Rasa: {{ $monster->type->label() }}
                                                            </div>
                                                        @endif

                                                        {{-- Monster Combat Attributes Box --}}
                                                        <div class="w-full bg-amber-100/70 rounded-xl p-4 border border-amber-900/40 shadow-sm mt-auto">
                                                            <h4 class="font-bold text-amber-950 mb-3 border-b border-amber-900/30 pb-1 flex items-center justify-between text-sm">
                                                                <span>⚡ Atrybuty Bojowe</span>
                                                                <span class="text-xs text-amber-800">Przeciwnik</span>
                                                            </h4>
                                                            <div class="grid grid-cols-2 gap-2.5 text-xs font-semibold">
                                                                <div class="flex justify-between items-center bg-amber-200/60 p-2 rounded-lg border border-amber-900/20">
                                                                    <span class="text-amber-900 font-bold">❤️ Punkty Życia</span>
                                                                    <span class="text-red-700 font-bold text-sm">{{ number_format($monster->stats['hp'] ?? $monster->level * 20) }}</span>
                                                                </div>
                                                                <div class="flex justify-between items-center bg-amber-200/60 p-2 rounded-lg border border-amber-900/20">
                                                                    <span class="text-amber-900 font-bold">🗡️ Atak</span>
                                                                    <span class="text-amber-950 font-bold text-sm">{{ $monster->stats['atk'] ?? '?' }}</span>
                                                                </div>
                                                                <div class="flex justify-between items-center bg-amber-200/60 p-2 rounded-lg border border-amber-900/20">
                                                                    <span class="text-amber-900 font-bold">🛡️ Obrona</span>
                                                                    <span class="text-slate-800 font-bold text-sm">{{ $monster->stats['def'] ?? '?' }}</span>
                                                                </div>
                                                                <div class="flex justify-between items-center bg-amber-200/60 p-2 rounded-lg border border-amber-900/20">
                                                                    <span class="text-amber-900 font-bold">🍃 Zręczność</span>
                                                                    <span class="text-emerald-800 font-bold text-sm">{{ $monster->stats['agi'] ?? '?' }}</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    {{-- RIGHT PAGE: LOOT TABLE & DROPS CODEX --}}
                                                    <div class="w-full md:w-1/2 flex flex-col">
                                                        <h4 class="text-xl font-black text-amber-950 medieval-font mb-4 border-b-2 border-amber-900/30 pb-2 flex items-center justify-between">
                                                            <span>🎁 Tabela Zdobyczy</span>
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
                                                                                    @if($entry->reward_type === 'gold') 💰
                                                                                    @elseif($entry->reward_type === 'xp') ✨
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
                                                                    <div class="text-4xl mb-2 opacity-50">🕸️</div>
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

                        {{-- WORLD BOSS MODAL --}}
                        @if(isset($activeWorldBosses[$map->id]))
                            @php
                                $boss = $activeWorldBosses[$map->id];
                                $hpPercent = max(0, min(100, ($boss->current_hp / max(1, $boss->total_hp)) * 100));
                                $hasParticipated = in_array($boss->id, $participatedBosses);
                                $deadlineTimestamp = now()->endOfHour()->timestamp * 1000;
                                $topDmg = $topDamageDealers[$boss->id] ?? collect();
                            @endphp
                            <template x-teleport="body">
                                <div x-show="showBossModal" style="display: none;" 
                                     x-data="{ 
                                         timeLeftStr: 'Obliczanie...', 
                                         deadline: {{ $deadlineTimestamp }},
                                         init() {
                                             this.updateTimer();
                                             setInterval(() => this.updateTimer(), 1000);
                                         },
                                         updateTimer() {
                                             let now = new Date().getTime();
                                             let diff = this.deadline - now;
                                             if (diff <= 0) {
                                                 this.timeLeftStr = '00:00';
                                                 return;
                                             }
                                             let minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                                             let seconds = Math.floor((diff % (1000 * 60)) / 1000);
                                             this.timeLeftStr = (minutes < 10 ? '0' : '') + minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
                                         }
                                     }"
                                     class="fixed inset-0 z-[200] flex items-center justify-center p-4 bg-slate-950/85 backdrop-blur-md overflow-y-auto">
                                    <div @click.outside="showBossModal = false" class="bg-gradient-to-br from-slate-900 to-purple-950 border-2 border-purple-500 rounded-2xl max-w-4xl w-full p-6 shadow-2xl relative text-left my-auto">
                                        <button @click="showBossModal = false" class="absolute top-4 right-4 text-slate-400 hover:text-white text-3xl font-bold">&times;</button>
                                        
                                        <h2 class="text-3xl font-bold text-center text-purple-300 medieval-font mb-6 border-b border-purple-700/50 pb-4">
                                            👑 Najeźdźca: {{ $boss->monster->name }}
                                        </h2>
                                        
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            {{-- Boss HP & Info --}}
                                            <div class="space-y-4 bg-black/40 p-5 rounded-xl border border-purple-900/50">
                                                <h3 class="text-lg font-bold text-purple-200">Stan Rajdu</h3>
                                                <p class="text-red-300 font-bold text-sm">Pozostały czas: <span x-text="timeLeftStr" class="text-white"></span></p>
                                            
                                                <div class="mt-4">
                                                    <div class="flex justify-between text-xs mb-1 font-bold">
                                                        <span class="text-slate-300">Punkty Życia (HP)</span>
                                                        <span class="text-red-400">{{ number_format($boss->current_hp) }} / {{ number_format($boss->total_hp) }}</span>
                                                    </div>
                                                    <div class="w-full bg-slate-950 rounded-full h-4 border border-slate-700 overflow-hidden p-0.5">
                                                        <div class="h-full bg-gradient-to-r from-red-600 to-red-500 transition-all duration-1000 rounded-full" style="width: {{ $hpPercent }}%"></div>
                                                    </div>
                                                    <div class="text-right text-xs text-red-400 font-bold mt-1">{{ round($hpPercent, 1) }}%</div>
                                                </div>
                                                
                                                <div class="mt-4 pt-3 border-t border-purple-900/50 text-xs text-slate-300 leading-relaxed">
                                                    Zadane obrażenia sumują się w skali całej serwera! Dołącz do starcia, by zgarnąć nagrody za miejsce w rankingu (Klucze do lochów dla TOP 10).
                                                </div>
                                            </div>
                                            
                                            {{-- Top DMG Leaderboard --}}
                                            <div class="space-y-4 bg-black/40 p-5 rounded-xl border border-purple-900/50">
                                                <h3 class="text-lg font-bold text-purple-200 flex items-center justify-between">
                                                    <span>Top 10 Wojowników</span>
                                                    <span class="text-xs text-purple-400 bg-purple-950/60 px-2 py-0.5 rounded border border-purple-800">Zadany DMG</span>
                                                </h3>
                                                <div class="space-y-2 max-h-48 overflow-y-auto pr-2 custom-scrollbar">
                                                    @if($topDmg->isEmpty())
                                                        <p class="text-slate-500 italic text-center py-6 text-xs">Brak uczestników. Bądź pierwszy!</p>
                                                    @else
                                                        @foreach($topDmg as $index => $log)
                                                            <div class="flex justify-between items-center text-xs {{ $log->character_id === $character->id ? 'bg-purple-950/80 border border-purple-500/60' : 'bg-slate-900/60' }} p-2 rounded-lg">
                                                                <div class="flex items-center gap-2">
                                                                    <span class="font-bold {{ $index === 0 ? 'text-yellow-400' : ($index === 1 ? 'text-slate-300' : ($index === 2 ? 'text-amber-600' : 'text-slate-500')) }}">
                                                                        #{{ $index + 1 }}
                                                                    </span>
                                                                    <span class="{{ $log->character_id === $character->id ? 'text-purple-200 font-bold' : 'text-slate-300' }}">
                                                                        {{ $log->character->name }}
                                                                    </span>
                                                                </div>
                                                                <span class="text-red-400 font-bold">{{ number_format($log->damage) }}</span>
                                                            </div>
                                                        @endforeach
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        
                                        {{-- Action CTA --}}
                                        <div class="mt-6 pt-4 border-t border-purple-800/50">
                                            @if($hasParticipated)
                                                <button disabled class="w-full bg-slate-800 text-slate-400 font-bold py-3 rounded-xl cursor-not-allowed border border-slate-700 text-base medieval-font">
                                                    Już brałeś udział w tym starciu
                                                </button>
                                            @else
                                                <a href="{{ route('adventure.map', ['character' => $character, 'map' => $map, 'world_boss' => $boss->monster_id]) }}" 
                                                    wire:navigate 
                                                    class="block w-full text-center bg-gradient-to-r from-red-700 via-purple-600 to-red-700 hover:from-red-600 hover:via-purple-500 hover:to-red-600 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-red-950/60 border border-red-500 transition-all transform hover:scale-[1.01] text-xl medieval-font">
                                                    ⚔️ DOŁĄCZ DO WALKI! ⚔️
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </template>
                        @endif

                    </div>
                </div>
            @endforeach
        </div>
        @endif

        {{-- DUNGEONS TAB --}}
        @if($tab === 'dungeons')
        <div class="w-full">
            @if($activeRun)
                <div class="bg-gradient-to-r from-amber-950/90 via-slate-900 to-amber-950/90 border-2 border-amber-500/80 rounded-2xl p-6 mb-8 flex flex-col sm:flex-row items-center justify-between gap-4 shadow-2xl max-w-4xl mx-auto backdrop-blur-md">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-xl">🏃</span>
                            <h3 class="text-2xl font-bold text-amber-200 medieval-font">Trwająca Ekspedycja</h3>
                        </div>
                        <p class="text-slate-300 text-sm">Jesteś w trakcie pokonywania lochu. Kontynuuj swoją przygodę!</p>
                        <p class="text-xs text-amber-400 mt-1 font-bold">Obecny etap: {{ $activeRun->current_stage }}</p>
                    </div>
                    <button wire:click="enterDungeon({{ $activeRun->dungeon_id }})" 
                        class="w-full sm:w-auto bg-gradient-to-r from-amber-600 to-amber-500 hover:from-amber-500 hover:to-amber-400 text-white font-bold py-3 px-8 rounded-xl transition-all duration-200 transform hover:scale-105 shadow-lg border border-amber-400 medieval-font whitespace-nowrap">
                        ➡️ Kontynuuj Ekspedycję
                    </button>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 max-w-7xl mx-auto">
                @foreach($dungeons as $dungeon)
                    @php
                        $canEnter = $dungeon->canCharacterEnter($character);
                        $hasKey = $dungeon->entry_item_template_id ? $character->inventoryItems()->where('template_id', $dungeon->entry_item_template_id)->exists() : true;
                        $isInProgress = $activeRun && $activeRun->dungeon_id === $dungeon->id;
                    @endphp
                    <div x-data="{ showMonsters: false }" 
                         class="bg-slate-900/90 backdrop-blur-md border-2 {{ $isInProgress ? 'border-amber-500 ring-2 ring-amber-500/40 shadow-[0_0_20px_rgba(245,158,11,0.2)]' : ($canEnter && $hasKey ? 'border-slate-800 hover:border-slate-600' : 'border-slate-800 opacity-70') }} rounded-2xl p-5 transition-all duration-300 flex flex-col justify-between h-full group">
                        
                        <div>
                            <div class="flex justify-between items-start mb-3">
                                <h3 class="text-2xl font-bold text-amber-100 medieval-font tracking-wide">{{ $dungeon->name }}</h3>
                                <div class="bg-slate-950 text-slate-300 text-xs font-bold px-2.5 py-1 rounded-full border border-slate-700 flex-shrink-0">
                                    Wym. Poz. {{ $dungeon->min_level }}
                                </div>
                            </div>

                            <p class="text-slate-400 text-xs leading-relaxed mb-4 line-clamp-3">{{ $dungeon->description }}</p>

                            <div class="space-y-2 mb-4 bg-slate-950/70 p-3 rounded-xl border border-slate-800/80 text-xs">
                                <div class="flex justify-between">
                                    <span class="text-slate-400">Liczba etapów:</span>
                                    <span class="text-amber-200 font-bold">{{ $dungeon->stages->count() }} Etapów</span>
                                </div>
                                
                                @if($dungeon->entryItemTemplate)
                                    <div class="flex justify-between items-center border-t border-slate-800/80 pt-2 mt-2">
                                        <span class="text-slate-400">Klucz wstępu:</span>
                                        <span class="font-bold {{ $hasKey ? 'text-emerald-400' : 'text-red-400' }} flex items-center gap-1">
                                            @if($hasKey) ✓ @else ✗ @endif 
                                            {{ $dungeon->entryItemTemplate->name }}
                                        </span>
                                    </div>
                                @endif
                            </div>

                            <button @click="showMonsters = !showMonsters" 
                                class="w-full mb-4 bg-slate-800/80 hover:bg-slate-700 text-slate-200 font-semibold py-2 px-3 rounded-xl transition-colors text-xs border border-slate-700 flex items-center justify-center gap-2">
                                <span>👁️</span>
                                <span x-text="showMonsters ? 'Ukryj listę przeciwników' : 'Pokaż listę przeciwników'"></span>
                            </button>
                            
                            {{-- Expandable Monster List --}}
                            <div x-show="showMonsters" 
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 max-h-0"
                                 x-transition:enter-end="opacity-100 max-h-64"
                                 class="mb-4 text-left border-t border-slate-800 pt-3 space-y-2 max-h-64 overflow-y-auto pr-1 custom-scrollbar">
                                <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Przeciwnicy lochu:</h4>
                                @foreach($dungeon->stages as $stage)
                                    @php $monster = $stage->monster; @endphp
                                    @if($monster)
                                    <div class="bg-slate-950/80 rounded-lg p-2 border border-slate-800 text-xs flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <span class="text-amber-500 font-bold">#{{ $stage->stage_order }}</span>
                                            <span class="font-bold text-red-300">{{ $monster->name }}</span>
                                        </div>
                                        <span class="text-slate-400 font-semibold text-[11px]">(Lvl {{ $monster->level }})</span>
                                    </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>

                        <div class="mt-auto">
                            @if($isInProgress)
                                <button wire:click="enterDungeon({{ $dungeon->id }})" class="w-full bg-amber-600 hover:bg-amber-500 text-white font-bold py-3 px-4 rounded-xl transition-colors border border-amber-400 medieval-font shadow-lg">
                                    Kontynuuj Ekspedycję
                                </button>
                            @elseif($canEnter && $hasKey && !$activeRun)
                                <button wire:click="enterDungeon({{ $dungeon->id }})" class="w-full bg-slate-800 hover:bg-slate-700 text-amber-200 hover:text-white font-bold py-3 px-4 rounded-xl transition-colors border border-slate-600 medieval-font shadow-md">
                                    Rozpocznij Ekspedycję
                                </button>
                            @else
                                <button disabled class="w-full bg-slate-800/60 text-slate-500 font-bold py-3 px-4 rounded-xl cursor-not-allowed border border-slate-800 medieval-font text-center">
                                    @if($activeRun)
                                        Inna ekspedycja w toku
                                    @elseif(!$canEnter)
                                        Wymagany poziom {{ $dungeon->min_level }}
                                    @else
                                        Brak klucza wstępu
                                    @endif
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
            
            @if($dungeons && $dungeons->isEmpty())
                <div class="text-center py-16 bg-slate-900/60 rounded-2xl border border-slate-800 max-w-xl mx-auto">
                    <div class="text-6xl mb-3 opacity-40">🏰</div>
                    <h3 class="text-2xl font-bold text-slate-400 medieval-font mb-2">Brak dostępnych lochów</h3>
                    <p class="text-sm text-slate-500">Wróć później, gdy pojawią się nowe wyzwania w krainie.</p>
                </div>
            @endif
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
