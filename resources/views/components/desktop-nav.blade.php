@if (session('active_character'))
    @php
        $charId = session('active_character');
        $character = \App\Infrastructure\Persistence\Character::with('activeTitle')->find($charId);

        $claimableQuests = $character ? $character->characterQuests()->where('status', 'completed')->count() : 0;
        $availableQuests = $character ? app(\App\Application\Quests\QuestService::class)->getAvailableQuests($character)->count() : 0;
        $questBadgeCount = $claimableQuests + $availableQuests;

        $unassignedStatPoints = $character ? max(0, (int) ($character->character_points ?? 0)) : 0;
        $profileBadgeCount = $unassignedStatPoints;

        $skillPointsCount = $character ? ($character->skill_points ?? 0) : 0;

        $unreadMailCount = $character ? \App\Infrastructure\Persistence\Mail::where('to_character_id', $character->id)->where('claimed', false)->count() : 0;
        if (auth()->check()) {
            auth()->user()->checkAndRepairTutorialStage($character);
        }
        $hubTutorialStages = [3, 4, 8, 15, 16, 20, 22, 30, 35, 38, 41];
        $currentGameStage = auth()->check() ? auth()->user()->game_stage : null;
        $hubTutorialPending = $character && $currentGameStage !== null && (
            in_array($currentGameStage, $hubTutorialStages, true)
            || ($currentGameStage == 13 && $character->character_points > 0)
        );
        $isQuestsLocked = auth()->check() && (!$character || $character->level < 5);
        $isGuildLocked = auth()->check() && (!$character || $character->level < 10);
        $isArenaLocked = auth()->check() && (!$character || $character->level < 15);

        // Podświetlenie linku w nawigacji, gdy aktywny krok samouczka wskazuje na dany ekran
        // (te same warunki co pulsowanie kafelków w hub.blade.php).
        $navTutorialPulse = [
            'adventure' => $currentGameStage == 9,
            'profile' => in_array($currentGameStage, [5, 14], true),
            'quests' => !$isQuestsLocked && $currentGameStage == 23,
            'merchant' => $currentGameStage == 17,
            'witch' => in_array($currentGameStage, [31, 42], true),
            'arena' => !$isArenaLocked && $currentGameStage == 39,
            'guild' => !$isGuildLocked && $currentGameStage == 36,
        ];
    @endphp

    <aside x-data="{ collapsed: localStorage.getItem('desktop_nav_collapsed') === 'true' }"
           :class="collapsed ? 'w-24' : 'w-72'"
           class="hidden lg:flex flex-col shrink-0 h-screen sticky top-0 bg-stone-950 border-r-4 border-amber-800/80 shadow-[10px_0_30px_rgba(0,0,0,0.9)] z-40 overflow-y-auto overflow-x-hidden select-none custom-scrollbar relative transition-[width] duration-300 ease-[cubic-bezier(0.4,0,0.2,1)]" 
           style="background: radial-gradient(circle at 50% 0%, #1c1917 0%, #0c0a09 70%, #050505 100%); font-family: 'Cinzel', serif;">
        
        {{-- Gothic / Metallic Filigree Border Trim --}}
        <div class="absolute inset-y-0 right-0 w-1 bg-gradient-to-b from-amber-600 via-yellow-500 to-amber-900 shadow-[0_0_10px_rgba(245,158,11,0.5)]"></div>
        <div class="absolute top-0 inset-x-0 h-32 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-amber-600/20 via-transparent to-transparent pointer-events-none"></div>

        {{-- Character Mini Profile Header Widget --}}
        <div :class="collapsed ? 'p-2' : 'p-3'" class="border-b-2 border-amber-900/60 bg-gradient-to-b from-amber-950/50 via-stone-900/80 to-stone-950/90 relative transition-all duration-300">
            {{-- Toggle Collapse Button --}}
            <button @click="collapsed = !collapsed; localStorage.setItem('desktop_nav_collapsed', collapsed); $dispatch('play-audio', { type: 'hover' })"
                    :class="collapsed ? 'right-1.5 top-1.5 w-5 h-5 text-[9px]' : 'right-2.5 top-3 w-6 h-6 text-xs'"
                    class="absolute rounded-full bg-amber-800 hover:bg-amber-600 border border-amber-400 text-stone-950 flex items-center justify-center shadow-lg transition-all duration-300 z-50 hover:scale-110 cursor-pointer group"
                    :title="collapsed ? 'Rozwiń nawigację' : 'Zwiń nawigację'">
                <i class="fa-solid fa-angles-left transition-transform duration-300 ease-out" :class="collapsed ? 'rotate-180' : ''"></i>
            </button>

            @if($character)
                @php
                    $initialXpReq = app(\App\Application\Characters\LevelUpService::class)->xpToNext($character->level);
                    $initialXpPct = $initialXpReq > 0 ? number_format(min(100, max(0, ($character->xp / $initialXpReq) * 100)), 1) : '0.0';
                @endphp
                <div x-data="{
                        navLevel: {{ $character->level }},
                        navXp: {{ $character->xp }},
                        navXpReq: {{ $initialXpReq }},
                        navGold: {{ $character->gold }},
                        navGems: {{ $character->gems }},
                        get navXpPct() {
                            return (Math.min(100, Math.max(0, (this.navXp / Math.max(1, this.navXpReq)) * 100))).toFixed(1);
                        },

                        handleStatsUpdate(detail) {
                            let data = Array.isArray(detail) ? detail[0] : detail;
                            if (!data) return;

                            if (data.newStats) {
                                if (data.newStats.level !== undefined) this.navLevel = Number(data.newStats.level);
                                if (data.newStats.experience !== undefined) this.navXp = Number(data.newStats.experience);
                                if (data.newStats.xp !== undefined) this.navXp = Number(data.newStats.xp);
                                if (data.newStats.experience_required !== undefined) this.navXpReq = Number(data.newStats.experience_required);
                                if (data.newStats.gold !== undefined) this.navGold = Number(data.newStats.gold);
                                if (data.newStats.gems !== undefined) this.navGems = Number(data.newStats.gems);
                            } else {
                                if (data.level !== undefined) this.navLevel = Number(data.level);
                                if (data.xp !== undefined) this.navXp = Number(data.xp);
                                if (data.experience !== undefined) this.navXp = Number(data.experience);
                                if (data.gold !== undefined) this.navGold = Number(data.gold);
                                if (data.gems !== undefined) this.navGems = Number(data.gems);

                                if (data.goldAdded) this.navGold += Number(data.goldAdded);
                                if (data.goldDeducted) this.navGold -= Number(data.goldDeducted);
                                if (data.xpAdded) this.navXp += Number(data.xpAdded);
                                if (data.gemsAdded) this.navGems += Number(data.gemsAdded);
                                if (data.gemsDeducted) this.navGems -= Number(data.gemsDeducted);

                                let req = 15 * Math.pow(this.navLevel, 2) + 50 * this.navLevel + 0.15 * Math.pow(this.navLevel, 4.1);
                                if (this.navLevel > 85) req += 0.025 * Math.pow(this.navLevel - 85, 5.5);
                                this.navXpReq = Math.round(req * 6.0);
                            }
                        }
                     }"
                     @stats-updated.window="handleStatsUpdate($event.detail)"
                     @open-level-up-modal.window="
                        let data = Array.isArray($event.detail) ? $event.detail[0] : $event.detail;
                        let lvl = typeof data === 'number' ? data : (data && data.level ? Number(data.level) : null);
                        if (lvl) {
                            navLevel = lvl;
                            let req = 15 * Math.pow(lvl, 2) + 50 * lvl + 0.15 * Math.pow(lvl, 4.1);
                            if (lvl > 85) req += 0.025 * Math.pow(lvl - 85, 5.5);
                            navXpReq = Math.round(req * 6.0);
                        }
                     "
                     :class="collapsed ? 'p-1.5 text-center' : 'p-2.5'"
                     class="rounded-xl bg-stone-950 border-2 border-amber-700/60 transition-all duration-300 shadow-[inset_0_2px_4px_rgba(0,0,0,0.8),0_4px_10px_rgba(0,0,0,0.5)]">

                    {{-- Top Player Row --}}
                    <a href="{{ route('city.profile', $charId) }}" wire:navigate
                       @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                       :class="collapsed ? 'justify-center' : 'pr-5 mb-2.5'"
                       class="flex items-center gap-3 group cursor-pointer">
                        <div :class="collapsed ? 'w-10 h-10 mx-auto' : 'w-11 h-11'" class="rounded-lg bg-stone-900 border-2 border-amber-500/80 overflow-hidden shrink-0 shadow-[0_0_12px_rgba(245,158,11,0.3)] group-hover:scale-105 transition-all duration-300 relative">
                             @if(auth()->check() && auth()->user()->hasCustomAvatar())
                                 <img src="{{ auth()->user()->getCustomAvatarUrl() }}" alt="Avatar" class="w-full h-full object-cover">
                             @elseif($character->avatar && file_exists(public_path('img/avatars/' . $character->avatar . '.png')))
                                 <img src="{{ asset('img/avatars/' . $character->avatar . '.png') }}" alt="Avatar" class="w-full h-full object-cover">
                             @else
                                 <div class="w-full h-full flex items-center justify-center text-amber-400 text-xs font-bold">HERO</div>
                             @endif
                            <div x-show="collapsed" class="absolute bottom-0 inset-x-0 bg-stone-950/90 text-amber-400 text-[8px] font-bold text-center leading-tight">
                                Lvl <span x-text="navLevel">{{ $character->level }}</span>
                            </div>
                        </div>

                        <div x-show="!collapsed"
                             x-transition:enter="transition-opacity ease-out duration-200 delay-100"
                             x-transition:enter-start="opacity-0"
                             x-transition:enter-end="opacity-100"
                             x-transition:leave="transition-opacity ease-in duration-75"
                             x-transition:leave-start="opacity-100"
                             x-transition:leave-end="opacity-0"
                             class="min-w-0 flex-1">
                            @if($character->activeTitle)
                                <p class="text-[9px] text-amber-400 font-bold uppercase tracking-wider truncate leading-tight drop-shadow">
                                    {{ $character->activeTitle->prefix }}
                                </p>
                            @endif
                            <h3 class="text-xs font-extrabold text-amber-100 group-hover:text-amber-300 truncate leading-snug tracking-wide">
                                {{ $character->name }}
                            </h3>
                            <div class="flex items-center justify-between text-[10px] text-amber-300/80 font-bold mt-0.5">
                                <span>Lvl <span x-text="navLevel">{{ $character->level }}</span></span>
                                <span class="text-yellow-400 font-bold">⚡ {{ number_format($character->getTotalCombatPower()) }}</span>
                            </div>
                        </div>
                    </a>

                    {{-- Dynamic Stats Section (EXP Bar, Gold, Gems) when not collapsed --}}
                    <div x-show="!collapsed"
                         x-transition:enter="transition-opacity ease-out duration-200 delay-100"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         x-transition:leave="transition-opacity ease-in duration-75"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         class="space-y-2 pt-1 border-t border-amber-900/40">

                        {{-- EXP Progress Bar --}}
                        <div>
                            <div class="flex justify-between text-[9px] font-bold text-stone-400 mb-1 font-sans">
                                <span class="text-amber-500/90 tracking-wider">EXP <span class="text-amber-300/90 font-semibold ml-0.5">(<span x-text="navXpPct + '%'">{{ $initialXpPct }}%</span>)</span></span>
                                <span><span x-text="Number(navXp).toLocaleString()">{{ number_format($character->xp) }}</span> / <span x-text="Number(navXpReq).toLocaleString()">{{ number_format($initialXpReq) }}</span></span>
                            </div>
                            <div class="w-full h-2 bg-stone-900 rounded-full overflow-hidden border border-stone-800 shadow-inner">
                                <div class="h-full bg-gradient-to-r from-blue-600 via-indigo-500 to-cyan-400 transition-all duration-700 relative rounded-full"
                                     :style="`width: ${Math.min(100, Math.max(0, (navXp / Math.max(1, navXpReq)) * 100))}%`">
                                    <div class="absolute inset-0 bg-white/20 animate-pulse"></div>
                                </div>
                            </div>
                        </div>

                        {{-- Currencies: Gold & Diamonds --}}
                        <div class="grid grid-cols-2 gap-1.5 text-xs font-sans">
                            {{-- Gold --}}
                            <div class="bg-stone-900/90 border border-amber-900/60 rounded-lg p-1.5 flex items-center justify-between shadow-inner"
                                 title="Złoto">
                                <span class="text-xs">🪙</span>
                                <span class="font-bold text-yellow-400 text-[11px] truncate ml-1 drop-shadow"
                                      x-text="Number(navGold).toLocaleString()"></span>
                            </div>

                            {{-- Diamonds / Gems --}}
                            <div class="bg-stone-900/90 border border-purple-900/60 rounded-lg p-1.5 flex items-center justify-between shadow-inner"
                                 title="Gemy / Diamenty">
                                <span class="text-xs">💎</span>
                                <span class="font-bold text-purple-400 text-[11px] truncate ml-1 drop-shadow"
                                      x-text="Number(navGems).toLocaleString()"></span>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Navigation Menu Links --}}
        <div class="p-3 space-y-4 flex-1">
            
            {{-- Sekcja: Eksploracja --}}
            <div>
                <div class="flex items-center gap-2 mb-2 px-1" x-show="!collapsed"
                     x-transition:enter="transition-opacity ease-out duration-200 delay-100"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition-opacity ease-in duration-75"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0">
                    <div class="h-0.5 bg-gradient-to-r from-amber-700/60 to-transparent flex-1"></div>
                    <span class="text-[10px] font-extrabold uppercase tracking-widest text-amber-500/80 whitespace-nowrap">Eksploracja</span>
                    <div class="h-0.5 bg-gradient-to-l from-amber-700/60 to-transparent flex-1"></div>
                </div>

                <div class="space-y-1.5">
                    {{-- Miasto --}}
                    <a href="{{ route('city.hub', $charId) }}" wire:navigate
                       @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                       @click="$dispatch('play-audio', { type: 'tab' }); $dispatch('location-leave', { text: 'Podróż do Miasta...', icon: 'fa-solid fa-archway', url: $el.href })"
                       :title="collapsed ? 'Miasto' : ''"
                       :class="collapsed ? 'justify-center px-0' : 'px-3 gap-3'"
                       class="flex items-center h-11 rounded-lg text-xs tracking-widest font-extrabold uppercase transition-all duration-300 ease-out relative group border-2 {{ request()->routeIs('city.hub') ? 'bg-gradient-to-b from-amber-800 via-amber-900 to-amber-950 text-yellow-200 border-amber-400 shadow-[0_0_15px_rgba(245,158,11,0.5),inset_0_1px_0_rgba(254,240,138,0.4),inset_0_-2px_0_rgba(0,0,0,0.9)] scale-[1.02]' : 'bg-gradient-to-b from-slate-800 via-slate-900 to-stone-950 text-slate-300 border-slate-700 hover:border-amber-600/80 hover:text-amber-200 hover:bg-gradient-to-b hover:from-slate-700 hover:to-slate-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.1),inset_0_-2px_0_rgba(0,0,0,0.8),0_3px_6px_rgba(0,0,0,0.6)]' }}">
                        <span :class="collapsed ? 'w-full text-amber-400' : 'w-5 text-amber-400 group-hover:scale-110 transition-transform'" class="text-base shrink-0 flex items-center justify-center transition-all duration-300">
                            <i class="fa-solid fa-archway"></i>
                        </span>
                        <span x-show="!collapsed"
                              x-transition:enter="transition-opacity ease-out duration-200 delay-100"
                              x-transition:enter-start="opacity-0"
                              x-transition:enter-end="opacity-100"
                              x-transition:leave="transition-opacity ease-in duration-75"
                              x-transition:leave-start="opacity-100"
                              x-transition:leave-end="opacity-0"
                              class="truncate">MIASTO</span>
                        @if($hubTutorialPending)
                            <span :class="collapsed ? 'absolute top-1 right-1' : 'absolute right-3 top-1/2 -translate-y-1/2'" class="w-4 h-4 bg-amber-500 rounded-full flex items-center justify-center text-[10px] text-slate-950 font-black animate-bounce z-10 transition-all duration-300">!</span>
                        @endif
                    </a>

                    {{-- Postać & Ekwipunek --}}
                    <a href="{{ route('city.profile', $charId) }}" wire:navigate
                       @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                       @click="$dispatch('play-audio', { type: 'tab' }); $dispatch('location-leave', { text: 'Otwieranie Ekwipunku...', icon: 'fa-solid fa-user-shield', url: $el.href })"
                       :title="collapsed ? 'Postać & Ekwipunek' : ''"
                       :class="collapsed ? 'justify-center px-0' : 'px-3 gap-3'"
                       class="flex items-center h-11 rounded-lg text-xs tracking-widest font-extrabold uppercase transition-all duration-300 ease-out relative group border-2 {{ request()->routeIs('city.profile') ? 'bg-gradient-to-b from-amber-800 via-amber-900 to-amber-950 text-yellow-200 border-amber-400 shadow-[0_0_15px_rgba(245,158,11,0.5),inset_0_1px_0_rgba(254,240,138,0.4),inset_0_-2px_0_rgba(0,0,0,0.9)] scale-[1.02]' : 'bg-gradient-to-b from-slate-800 via-slate-900 to-stone-950 text-slate-300 border-slate-700 hover:border-amber-600/80 hover:text-amber-200 hover:bg-gradient-to-b hover:from-slate-700 hover:to-slate-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.1),inset_0_-2px_0_rgba(0,0,0,0.8),0_3px_6px_rgba(0,0,0,0.6)]' }} {{ $navTutorialPulse['profile'] ? 'ring-4 ring-amber-500 animate-pulse' : '' }}">
                        <span :class="collapsed ? 'w-full text-amber-400' : 'w-5 text-amber-400 group-hover:scale-110 transition-transform'" class="text-base shrink-0 flex items-center justify-center transition-all duration-300">
                            <i class="fa-solid fa-user-shield"></i>
                        </span>
                        <span x-show="!collapsed"
                              x-transition:enter="transition-opacity ease-out duration-200 delay-100"
                              x-transition:enter-start="opacity-0"
                              x-transition:enter-end="opacity-100"
                              x-transition:leave="transition-opacity ease-in duration-75"
                              x-transition:leave-start="opacity-100"
                              x-transition:leave-end="opacity-0"
                              class="truncate">POSTAĆ & EKWIPUNEK</span>
                        @if($profileBadgeCount > 0)
                            <span :class="collapsed ? 'absolute top-1 right-1' : 'absolute right-3 top-1/2 -translate-y-1/2'" class="w-4 h-4 bg-amber-500 rounded-full flex items-center justify-center text-[10px] text-slate-950 font-black animate-bounce z-10 transition-all duration-300">!</span>
                        @endif
                    </a>

                    {{-- Wyprawy --}}
                    @php $hasActiveMirror = $character ? $character->hasActiveMirror() : false; @endphp
                    <a href="{{ route('city.adventure', $charId) }}" wire:navigate
                       @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                       @click="$dispatch('play-audio', { type: 'tab' }); $dispatch('location-leave', { text: 'Wyruszanie na Wyprawę...', icon: 'fa-solid fa-map-location-dot', url: $el.href })"
                       :title="collapsed ? 'Wyprawy' + ('{{ $hasActiveMirror }}' ? ' (Lustro aktywne)' : '') : ''"
                       :class="collapsed ? 'justify-center px-0' : 'px-3 gap-3'"
                       class="flex items-center h-11 rounded-lg text-xs tracking-widest font-extrabold uppercase transition-all duration-300 ease-out relative group border-2 {{ request()->routeIs('city.adventure*') || request()->routeIs('adventure.*') ? 'bg-gradient-to-b from-amber-800 via-amber-900 to-amber-950 text-yellow-200 border-amber-400 shadow-[0_0_15px_rgba(245,158,11,0.5),inset_0_1px_0_rgba(254,240,138,0.4),inset_0_-2px_0_rgba(0,0,0,0.9)] scale-[1.02]' : 'bg-gradient-to-b from-slate-800 via-slate-900 to-stone-950 text-slate-300 border-slate-700 hover:border-amber-600/80 hover:text-amber-200 hover:bg-gradient-to-b hover:from-slate-700 hover:to-slate-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.1),inset_0_-2px_0_rgba(0,0,0,0.8),0_3px_6px_rgba(0,0,0,0.6)]' }} {{ $navTutorialPulse['adventure'] ? 'ring-4 ring-amber-500 animate-pulse' : '' }}">
                        <span :class="collapsed ? 'w-full text-amber-400' : 'w-5 text-amber-400 group-hover:scale-110 transition-transform'" class="text-base shrink-0 flex items-center justify-center transition-all duration-300">
                            <i class="fa-solid fa-map-location-dot"></i>
                        </span>
                        <span x-show="!collapsed"
                              x-transition:enter="transition-opacity ease-out duration-200 delay-100"
                              x-transition:enter-start="opacity-0"
                              x-transition:enter-end="opacity-100"
                              x-transition:leave="transition-opacity ease-in duration-75"
                              x-transition:leave-start="opacity-100"
                              x-transition:leave-end="opacity-0"
                              class="truncate">WYPRAWY</span>
                        @if($hasActiveMirror)
                            <span :class="collapsed ? 'absolute top-1 right-1' : 'absolute right-2 top-1/2 -translate-y-1/2'" class="px-1.5 py-0.5 bg-purple-600 rounded text-[9px] text-white font-black animate-pulse z-10 shadow-[0_0_8px_rgba(168,85,247,0.8)]" title="Lustro aktywne">LUSTRO</span>
                        @endif
                    </a>

                    {{-- Zadania & Karczma --}}
                    @if($isQuestsLocked)
                        <a href="javascript:void(0)"
                           @click.prevent="$dispatch('notify', { type: 'error', message: 'Tablica Wyzwań jest zablokowana! Wbij 5 poziom postaci i wyczekuj rozkazów Kapitana.' })"
                           :title="collapsed ? 'Zadania & Karczma (Zablokowane)' : ''"
                           :class="collapsed ? 'justify-center px-0' : 'px-3 gap-3'"
                           class="flex items-center h-11 rounded-lg text-xs tracking-widest font-extrabold uppercase transition-all duration-300 ease-out relative group border-2 bg-stone-900/90 text-stone-500 border-stone-800 opacity-60 grayscale cursor-not-allowed">
                            <span :class="collapsed ? 'w-full text-amber-500/40' : 'w-5 text-amber-500/40'" class="text-base shrink-0 flex items-center justify-center transition-all duration-300">
                                <i class="fa-solid fa-beer-mug-empty"></i>
                            </span>
                            <span x-show="!collapsed" class="truncate">ZADANIA & KARCZMA</span>
                            <span :class="collapsed ? 'absolute top-1 right-1' : 'absolute right-2 top-1/2 -translate-y-1/2'" class="text-amber-500 text-xs">
                                <i class="fa-solid fa-lock"></i>
                            </span>
                        </a>
                    @else
                        <a href="{{ route('city.quests', $charId) }}" wire:navigate
                           @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                           @click="$dispatch('play-audio', { type: 'tab' }); $dispatch('location-leave', { text: 'Podróż do Karczmy...', icon: 'fa-solid fa-beer-mug-empty', url: $el.href })"
                           :title="collapsed ? 'Zadania & Karczma' : ''"
                           :class="collapsed ? 'justify-center px-0' : 'px-3 gap-3'"
                           class="flex items-center h-11 rounded-lg text-xs tracking-widest font-extrabold uppercase transition-all duration-300 ease-out relative group border-2 {{ request()->routeIs('city.quests') ? 'bg-gradient-to-b from-amber-800 via-amber-900 to-amber-950 text-yellow-200 border-amber-400 shadow-[0_0_15px_rgba(245,158,11,0.5),inset_0_1px_0_rgba(254,240,138,0.4),inset_0_-2px_0_rgba(0,0,0,0.9)] scale-[1.02]' : 'bg-gradient-to-b from-slate-800 via-slate-900 to-stone-950 text-slate-300 border-slate-700 hover:border-amber-600/80 hover:text-amber-200 hover:bg-gradient-to-b hover:from-slate-700 hover:to-slate-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.1),inset_0_-2px_0_rgba(0,0,0,0.8),0_3px_6px_rgba(0,0,0,0.6)]' }} {{ $navTutorialPulse['quests'] ? 'ring-4 ring-amber-500 animate-pulse' : '' }}">
                            <span :class="collapsed ? 'w-full text-amber-400' : 'w-5 text-amber-400 group-hover:scale-110 transition-transform'" class="text-base shrink-0 flex items-center justify-center transition-all duration-300">
                                <i class="fa-solid fa-beer-mug-empty"></i>
                            </span>
                            <span x-show="!collapsed"
                                  x-transition:enter="transition-opacity ease-out duration-200 delay-100"
                                  x-transition:enter-start="opacity-0"
                                  x-transition:enter-end="opacity-100"
                                  x-transition:leave="transition-opacity ease-in duration-75"
                                  x-transition:leave-start="opacity-100"
                                  x-transition:leave-end="opacity-0"
                                  class="truncate">ZADANIA & KARCZMA</span>
                            @if($questBadgeCount > 0)
                                <span :class="collapsed ? 'absolute top-1 right-1' : 'absolute right-3 top-1/2 -translate-y-1/2'" class="w-4 h-4 bg-amber-500 rounded-full flex items-center justify-center text-[10px] text-slate-950 font-black animate-bounce z-10 transition-all duration-300">!</span>
                            @endif
                        </a>
                    @endif
                </div>
            </div>

            {{-- Sekcja: Sklepy & Rzemiosło --}}
            <div>
                <div class="flex items-center gap-2 mb-2 px-1" x-show="!collapsed"
                     x-transition:enter="transition-opacity ease-out duration-200 delay-100"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition-opacity ease-in duration-75"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0">
                    <div class="h-0.5 bg-gradient-to-r from-amber-700/60 to-transparent flex-1"></div>
                    <span class="text-[10px] font-extrabold uppercase tracking-widest text-amber-500/80 whitespace-nowrap">Sklepy & Rzemiosło</span>
                    <div class="h-0.5 bg-gradient-to-l from-amber-700/60 to-transparent flex-1"></div>
                </div>

                <div class="space-y-1.5">
                    {{-- Handlarz --}}
                    <a href="{{ route('city.merchant', $charId) }}" wire:navigate
                       @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                       @click="$dispatch('play-audio', { type: 'tab' }); $dispatch('location-leave', { text: 'Wizyta u Handlarza...', icon: 'fa-solid fa-shop', url: $el.href })"
                       :title="collapsed ? 'Handlarz' : ''"
                       :class="collapsed ? 'justify-center px-0' : 'px-3 gap-3'"
                       class="flex items-center h-11 rounded-lg text-xs tracking-widest font-extrabold uppercase transition-all duration-300 ease-out relative group border-2 {{ request()->routeIs('city.merchant') ? 'bg-gradient-to-b from-amber-800 via-amber-900 to-amber-950 text-yellow-200 border-amber-400 shadow-[0_0_15px_rgba(245,158,11,0.5),inset_0_1px_0_rgba(254,240,138,0.4),inset_0_-2px_0_rgba(0,0,0,0.9)] scale-[1.02]' : 'bg-gradient-to-b from-slate-800 via-slate-900 to-stone-950 text-slate-300 border-slate-700 hover:border-amber-600/80 hover:text-amber-200 hover:bg-gradient-to-b hover:from-slate-700 hover:to-slate-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.1),inset_0_-2px_0_rgba(0,0,0,0.8),0_3px_6px_rgba(0,0,0,0.6)]' }} {{ $navTutorialPulse['merchant'] ? 'ring-4 ring-amber-500 animate-pulse' : '' }}">
                        <span :class="collapsed ? 'w-full text-amber-400' : 'w-5 text-amber-400 group-hover:scale-110 transition-transform'" class="text-base shrink-0 flex items-center justify-center transition-all duration-300">
                            <i class="fa-solid fa-shop"></i>
                        </span>
                        <span x-show="!collapsed"
                              x-transition:enter="transition-opacity ease-out duration-200 delay-100"
                              x-transition:enter-start="opacity-0"
                              x-transition:enter-end="opacity-100"
                              x-transition:leave="transition-opacity ease-in duration-75"
                              x-transition:leave-start="opacity-100"
                              x-transition:leave-end="opacity-0"
                              class="truncate">HANDLARZ</span>
                    </a>

                    {{-- Kowal --}}
                    <a href="{{ route('city.blacksmith', $charId) }}" wire:navigate
                       @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                       @click="$dispatch('play-audio', { type: 'tab' }); $dispatch('location-leave', { text: 'Podróż do Kowala...', icon: 'fa-solid fa-hammer', url: $el.href })"
                       :title="collapsed ? 'Kowal' : ''"
                       :class="collapsed ? 'justify-center px-0' : 'px-3 gap-3'"
                       class="flex items-center h-11 rounded-lg text-xs tracking-widest font-extrabold uppercase transition-all duration-300 ease-out relative group border-2 {{ request()->routeIs('city.blacksmith') ? 'bg-gradient-to-b from-amber-800 via-amber-900 to-amber-950 text-yellow-200 border-amber-400 shadow-[0_0_15px_rgba(245,158,11,0.5),inset_0_1px_0_rgba(254,240,138,0.4),inset_0_-2px_0_rgba(0,0,0,0.9)] scale-[1.02]' : 'bg-gradient-to-b from-slate-800 via-slate-900 to-stone-950 text-slate-300 border-slate-700 hover:border-amber-600/80 hover:text-amber-200 hover:bg-gradient-to-b hover:from-slate-700 hover:to-slate-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.1),inset_0_-2px_0_rgba(0,0,0,0.8),0_3px_6px_rgba(0,0,0,0.6)]' }}">
                        <span :class="collapsed ? 'w-full text-amber-400' : 'w-5 text-amber-400 group-hover:scale-110 transition-transform'" class="text-base shrink-0 flex items-center justify-center transition-all duration-300">
                            <i class="fa-solid fa-hammer"></i>
                        </span>
                        <span x-show="!collapsed"
                              x-transition:enter="transition-opacity ease-out duration-200 delay-100"
                              x-transition:enter-start="opacity-0"
                              x-transition:enter-end="opacity-100"
                              x-transition:leave="transition-opacity ease-in duration-75"
                              x-transition:leave-start="opacity-100"
                              x-transition:leave-end="opacity-0"
                              class="truncate">KOWAL</span>
                    </a>

                    {{-- Wiedźma --}}
                    <a href="{{ route('city.witch', $charId) }}" wire:navigate
                       @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                       @click="$dispatch('play-audio', { type: 'tab' }); $dispatch('location-leave', { text: 'Wizyta u Wiedźmy...', icon: 'fa-solid fa-wand-magic-sparkles', url: $el.href })"
                       :title="collapsed ? 'Wiedźma' : ''"
                       :class="collapsed ? 'justify-center px-0' : 'px-3 gap-3'"
                       class="flex items-center h-11 rounded-lg text-xs tracking-widest font-extrabold uppercase transition-all duration-300 ease-out relative group border-2 {{ request()->routeIs('city.witch') ? 'bg-gradient-to-b from-amber-800 via-amber-900 to-amber-950 text-yellow-200 border-amber-400 shadow-[0_0_15px_rgba(245,158,11,0.5),inset_0_1px_0_rgba(254,240,138,0.4),inset_0_-2px_0_rgba(0,0,0,0.9)] scale-[1.02]' : 'bg-gradient-to-b from-slate-800 via-slate-900 to-stone-950 text-slate-300 border-slate-700 hover:border-amber-600/80 hover:text-amber-200 hover:bg-gradient-to-b hover:from-slate-700 hover:to-slate-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.1),inset_0_-2px_0_rgba(0,0,0,0.8),0_3px_6px_rgba(0,0,0,0.6)]' }} {{ $navTutorialPulse['witch'] ? 'ring-4 ring-amber-500 animate-pulse' : '' }}">
                        <span :class="collapsed ? 'w-full text-amber-400' : 'w-5 text-amber-400 group-hover:scale-110 transition-transform'" class="text-base shrink-0 flex items-center justify-center transition-all duration-300">
                            <i class="fa-solid fa-wand-magic-sparkles"></i>
                        </span>
                        <span x-show="!collapsed"
                              x-transition:enter="transition-opacity ease-out duration-200 delay-100"
                              x-transition:enter-start="opacity-0"
                              x-transition:enter-end="opacity-100"
                              x-transition:leave="transition-opacity ease-in duration-75"
                              x-transition:leave-start="opacity-100"
                              x-transition:leave-end="opacity-0"
                              class="truncate">WIEDŹMA</span>
                    </a>

                    {{-- Pety --}}
                    <a href="{{ route('city.pets', $charId) }}" wire:navigate
                       @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                       @click="$dispatch('play-audio', { type: 'tab' }); $dispatch('location-leave', { text: 'Wizyta u Petów...', icon: 'fa-solid fa-paw', url: $el.href })"
                       :title="collapsed ? 'Pety' : ''"
                       :class="collapsed ? 'justify-center px-0' : 'px-3 gap-3'"
                       class="flex items-center h-11 rounded-lg text-xs tracking-widest font-extrabold uppercase transition-all duration-300 ease-out relative group border-2 {{ request()->routeIs('city.pets') ? 'bg-gradient-to-b from-amber-800 via-amber-900 to-amber-950 text-yellow-200 border-amber-400 shadow-[0_0_15px_rgba(245,158,11,0.5),inset_0_1px_0_rgba(254,240,138,0.4),inset_0_-2px_0_rgba(0,0,0,0.9)] scale-[1.02]' : 'bg-gradient-to-b from-slate-800 via-slate-900 to-stone-950 text-slate-300 border-slate-700 hover:border-amber-600/80 hover:text-amber-200 hover:bg-gradient-to-b hover:from-slate-700 hover:to-slate-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.1),inset_0_-2px_0_rgba(0,0,0,0.8),0_3px_6px_rgba(0,0,0,0.6)]' }}">
                        <span :class="collapsed ? 'w-full text-amber-400' : 'w-5 text-amber-400 group-hover:scale-110 transition-transform'" class="text-base shrink-0 flex items-center justify-center transition-all duration-300">
                            <i class="fa-solid fa-paw"></i>
                        </span>
                        <span x-show="!collapsed"
                              x-transition:enter="transition-opacity ease-out duration-200 delay-100"
                              x-transition:enter-start="opacity-0"
                              x-transition:enter-end="opacity-100"
                              x-transition:leave="transition-opacity ease-in duration-75"
                              x-transition:leave-start="opacity-100"
                              x-transition:leave-end="opacity-0"
                              class="truncate">PETY</span>
                    </a>

                    {{-- Czarnoksiężnik --}}
                    <a href="{{ route('city.warlock', $charId) }}" wire:navigate
                       @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                       @click="$dispatch('play-audio', { type: 'tab' }); $dispatch('location-leave', { text: 'Wizyta u Czarnoksiężnika...', icon: 'fa-solid fa-skull', url: $el.href })"
                       :title="collapsed ? 'Czarnoksiężnik' : ''"
                       :class="collapsed ? 'justify-center px-0' : 'px-3 gap-3'"
                       class="flex items-center h-11 rounded-lg text-xs tracking-widest font-extrabold uppercase transition-all duration-300 ease-out relative group border-2 {{ request()->routeIs('city.warlock') ? 'bg-gradient-to-b from-amber-800 via-amber-900 to-amber-950 text-yellow-200 border-amber-400 shadow-[0_0_15px_rgba(245,158,11,0.5),inset_0_1px_0_rgba(254,240,138,0.4),inset_0_-2px_0_rgba(0,0,0,0.9)] scale-[1.02]' : 'bg-gradient-to-b from-slate-800 via-slate-900 to-stone-950 text-slate-300 border-slate-700 hover:border-amber-600/80 hover:text-amber-200 hover:bg-gradient-to-b hover:from-slate-700 hover:to-slate-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.1),inset_0_-2px_0_rgba(0,0,0,0.8),0_3px_6px_rgba(0,0,0,0.6)]' }}">
                        <span :class="collapsed ? 'w-full text-amber-400' : 'w-5 text-amber-400 group-hover:scale-110 transition-transform'" class="text-base shrink-0 flex items-center justify-center transition-all duration-300">
                            <i class="fa-solid fa-skull"></i>
                        </span>
                        <span x-show="!collapsed"
                              x-transition:enter="transition-opacity ease-out duration-200 delay-100"
                              x-transition:enter-start="opacity-0"
                              x-transition:enter-end="opacity-100"
                              x-transition:leave="transition-opacity ease-in duration-75"
                              x-transition:leave-start="opacity-100"
                              x-transition:leave-end="opacity-0"
                              class="truncate">CZARNOKSIĘŻNIK</span>
                        @if($skillPointsCount > 0)
                            <span :class="collapsed ? 'absolute top-1 right-1' : 'absolute right-3 top-1/2 -translate-y-1/2'" class="w-4 h-4 bg-amber-500 rounded-full flex items-center justify-center text-[10px] text-slate-950 font-black animate-bounce z-10 transition-all duration-300">!</span>
                        @endif
                    </a>

                    {{-- Aukcje & Rynek --}}
                    <a href="{{ route('city.market', $charId) }}" wire:navigate
                       @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                       @click="$dispatch('play-audio', { type: 'tab' }); $dispatch('location-leave', { text: 'Podróż na Rynek...', icon: 'fa-solid fa-scale-balanced', url: $el.href })"
                       :title="collapsed ? 'Aukcje & Rynek' : ''"
                       :class="collapsed ? 'justify-center px-0' : 'px-3 gap-3'"
                       class="flex items-center h-11 rounded-lg text-xs tracking-widest font-extrabold uppercase transition-all duration-300 ease-out relative group border-2 {{ request()->routeIs('city.market') ? 'bg-gradient-to-b from-amber-800 via-amber-900 to-amber-950 text-yellow-200 border-amber-400 shadow-[0_0_15px_rgba(245,158,11,0.5),inset_0_1px_0_rgba(254,240,138,0.4),inset_0_-2px_0_rgba(0,0,0,0.9)] scale-[1.02]' : 'bg-gradient-to-b from-slate-800 via-slate-900 to-stone-950 text-slate-300 border-slate-700 hover:border-amber-600/80 hover:text-amber-200 hover:bg-gradient-to-b hover:from-slate-700 hover:to-slate-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.1),inset_0_-2px_0_rgba(0,0,0,0.8),0_3px_6px_rgba(0,0,0,0.6)]' }}">
                        <span :class="collapsed ? 'w-full text-amber-400' : 'w-5 text-amber-400 group-hover:scale-110 transition-transform'" class="text-base shrink-0 flex items-center justify-center transition-all duration-300">
                            <i class="fa-solid fa-scale-balanced"></i>
                        </span>
                        <span x-show="!collapsed"
                              x-transition:enter="transition-opacity ease-out duration-200 delay-100"
                              x-transition:enter-start="opacity-0"
                              x-transition:enter-end="opacity-100"
                              x-transition:leave="transition-opacity ease-in duration-75"
                              x-transition:leave-start="opacity-100"
                              x-transition:leave-end="opacity-0"
                              class="truncate">AUKCJE & RYNEK</span>
                    </a>
                </div>
            </div>

            {{-- Sekcja: Rywalizacja & Społeczność --}}
            <div>
                <div class="flex items-center gap-2 mb-2 px-1" x-show="!collapsed"
                     x-transition:enter="transition-opacity ease-out duration-200 delay-100"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition-opacity ease-in duration-75"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0">
                    <div class="h-0.5 bg-gradient-to-r from-amber-700/60 to-transparent flex-1"></div>
                    <span class="text-[10px] font-extrabold uppercase tracking-widest text-amber-500/80 whitespace-nowrap">Społeczność & Walka</span>
                    <div class="h-0.5 bg-gradient-to-l from-amber-700/60 to-transparent flex-1"></div>
                </div>

                <div class="space-y-1.5">
                    {{-- Arena --}}
                    <a href="{{ route('city.arena', $charId) }}" wire:navigate
                       @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                       @click="$dispatch('play-audio', { type: 'tab' }); $dispatch('location-leave', { text: 'Wejście na Arenę...', icon: 'fa-solid fa-dungeon', url: $el.href })"
                       :title="collapsed ? 'Arena Walk' : ''"
                       :class="collapsed ? 'justify-center px-0' : 'px-3 gap-3'"
                       class="flex items-center h-11 rounded-lg text-xs tracking-widest font-extrabold uppercase transition-all duration-300 ease-out relative group border-2 {{ $isArenaLocked ? 'opacity-60 grayscale' : '' }} {{ request()->routeIs('city.arena*') ? 'bg-gradient-to-b from-amber-800 via-amber-900 to-amber-950 text-yellow-200 border-amber-400 shadow-[0_0_15px_rgba(245,158,11,0.5),inset_0_1px_0_rgba(254,240,138,0.4),inset_0_-2px_0_rgba(0,0,0,0.9)] scale-[1.02]' : 'bg-gradient-to-b from-slate-800 via-slate-900 to-stone-950 text-slate-300 border-slate-700 hover:border-amber-600/80 hover:text-amber-200 hover:bg-gradient-to-b hover:from-slate-700 hover:to-slate-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.1),inset_0_-2px_0_rgba(0,0,0,0.8),0_3px_6px_rgba(0,0,0,0.6)]' }} {{ $navTutorialPulse['arena'] ? 'ring-4 ring-amber-500 animate-pulse' : '' }}">
                        <span :class="collapsed ? 'w-full text-amber-400' : 'w-5 text-amber-400 group-hover:scale-110 transition-transform'" class="text-base shrink-0 flex items-center justify-center transition-all duration-300">
                            @if($isArenaLocked)
                                <i class="fa-solid fa-lock text-amber-500"></i>
                            @else
                                <i class="fa-solid fa-dungeon"></i>
                            @endif
                        </span>
                        <span x-show="!collapsed"
                              x-transition:enter="transition-opacity ease-out duration-200 delay-100"
                              x-transition:enter-start="opacity-0"
                              x-transition:enter-end="opacity-100"
                              x-transition:leave="transition-opacity ease-in duration-75"
                              x-transition:leave-start="opacity-100"
                              x-transition:leave-end="opacity-0"
                              class="truncate">ARENA WALK</span>
                    </a>

                    {{-- Gladiator --}}
                    <a href="{{ route('city.gladiator', $charId) }}" wire:navigate
                       @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                       @click="$dispatch('play-audio', { type: 'tab' }); $dispatch('location-leave', { text: 'Wizyta u Gladiatora...', icon: 'fa-solid fa-coins', url: $el.href })"
                       :title="collapsed ? 'Gladiator Shop' : ''"
                       :class="collapsed ? 'justify-center px-0' : 'px-3 gap-3'"
                       class="flex items-center h-11 rounded-lg text-xs tracking-widest font-extrabold uppercase transition-all duration-300 ease-out relative group border-2 {{ request()->routeIs('city.gladiator') ? 'bg-gradient-to-b from-amber-800 via-amber-900 to-amber-950 text-yellow-200 border-amber-400 shadow-[0_0_15px_rgba(245,158,11,0.5),inset_0_1px_0_rgba(254,240,138,0.4),inset_0_-2px_0_rgba(0,0,0,0.9)] scale-[1.02]' : 'bg-gradient-to-b from-slate-800 via-slate-900 to-stone-950 text-slate-300 border-slate-700 hover:border-amber-600/80 hover:text-amber-200 hover:bg-gradient-to-b hover:from-slate-700 hover:to-slate-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.1),inset_0_-2px_0_rgba(0,0,0,0.8),0_3px_6px_rgba(0,0,0,0.6)]' }}">
                        <span :class="collapsed ? 'w-full text-amber-400' : 'w-5 text-amber-400 group-hover:scale-110 transition-transform'" class="text-base shrink-0 flex items-center justify-center transition-all duration-300">
                            <i class="fa-solid fa-coins"></i>
                        </span>
                        <span x-show="!collapsed"
                              x-transition:enter="transition-opacity ease-out duration-200 delay-100"
                              x-transition:enter-start="opacity-0"
                              x-transition:enter-end="opacity-100"
                              x-transition:leave="transition-opacity ease-in duration-75"
                              x-transition:leave-start="opacity-100"
                              x-transition:leave-end="opacity-0"
                              class="truncate">GLADIATOR SHOP</span>
                    </a>

                    {{-- Gildia --}}
                    @if($isGuildLocked)
                        <a href="javascript:void(0)"
                           @click.prevent="$dispatch('notify', { type: 'error', message: 'Gildia jest zablokowana! Wbij 10 poziom postaci i wyczekuj rozkazów Kapitana.' })"
                           :title="collapsed ? 'Gildia (Zablokowane)' : ''"
                           :class="collapsed ? 'justify-center px-0' : 'px-3 gap-3'"
                           class="flex items-center h-11 rounded-lg text-xs tracking-widest font-extrabold uppercase transition-all duration-300 ease-out relative group border-2 bg-stone-900/90 text-stone-500 border-stone-800 opacity-60 grayscale cursor-not-allowed">
                            <span :class="collapsed ? 'w-full text-amber-500/40' : 'w-5 text-amber-500/40'" class="text-base shrink-0 flex items-center justify-center transition-all duration-300">
                                <i class="fa-solid fa-flag"></i>
                            </span>
                            <span x-show="!collapsed" class="truncate">GILDIA</span>
                            <span :class="collapsed ? 'absolute top-1 right-1' : 'absolute right-2 top-1/2 -translate-y-1/2'" class="text-amber-500 text-xs">
                                <i class="fa-solid fa-lock"></i>
                            </span>
                        </a>
                    @else
                        <a href="{{ route('city.guild', $charId) }}" wire:navigate
                           @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                           @click="$dispatch('play-audio', { type: 'tab' }); $dispatch('location-leave', { text: 'Podróż do Gildii...', icon: 'fa-solid fa-flag', url: $el.href })"
                           :title="collapsed ? 'Gildia' : ''"
                           :class="collapsed ? 'justify-center px-0' : 'px-3 gap-3'"
                           class="flex items-center h-11 rounded-lg text-xs tracking-widest font-extrabold uppercase transition-all duration-300 ease-out relative group border-2 {{ request()->routeIs('city.guild') ? 'bg-gradient-to-b from-amber-800 via-amber-900 to-amber-950 text-yellow-200 border-amber-400 shadow-[0_0_15px_rgba(245,158,11,0.5),inset_0_1px_0_rgba(254,240,138,0.4),inset_0_-2px_0_rgba(0,0,0,0.9)] scale-[1.02]' : 'bg-gradient-to-b from-slate-800 via-slate-900 to-stone-950 text-slate-300 border-slate-700 hover:border-amber-600/80 hover:text-amber-200 hover:bg-gradient-to-b hover:from-slate-700 hover:to-slate-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.1),inset_0_-2px_0_rgba(0,0,0,0.8),0_3px_6px_rgba(0,0,0,0.6)]' }} {{ $navTutorialPulse['guild'] ? 'ring-4 ring-amber-500 animate-pulse' : '' }}">
                            <span :class="collapsed ? 'w-full text-amber-400' : 'w-5 text-amber-400 group-hover:scale-110 transition-transform'" class="text-base shrink-0 flex items-center justify-center transition-all duration-300">
                                <i class="fa-solid fa-flag"></i>
                            </span>
                            <span x-show="!collapsed"
                                  x-transition:enter="transition-opacity ease-out duration-200 delay-100"
                                  x-transition:enter-start="opacity-0"
                                  x-transition:enter-end="opacity-100"
                                  x-transition:leave="transition-opacity ease-in duration-75"
                                  x-transition:leave-start="opacity-100"
                                  x-transition:leave-end="opacity-0"
                                  class="truncate">GILDIA</span>
                        </a>
                    @endif


                    {{-- Poczta --}}
                    <a href="{{ route('city.mailbox', $charId) }}" wire:navigate
                       @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                       @click="$dispatch('play-audio', { type: 'tab' }); $dispatch('location-leave', { text: 'Sprawdzanie Poczty...', icon: 'fa-solid fa-envelope', url: $el.href })"
                       :title="collapsed ? 'Skrzynka Pocztowa' : ''"
                       :class="collapsed ? 'justify-center px-0' : 'px-3 gap-3'"
                       class="flex items-center h-11 rounded-lg text-xs tracking-widest font-extrabold uppercase transition-all duration-300 ease-out relative group border-2 {{ request()->routeIs('city.mailbox') ? 'bg-gradient-to-b from-amber-800 via-amber-900 to-amber-950 text-yellow-200 border-amber-400 shadow-[0_0_15px_rgba(245,158,11,0.5),inset_0_1px_0_rgba(254,240,138,0.4),inset_0_-2px_0_rgba(0,0,0,0.9)] scale-[1.02]' : 'bg-gradient-to-b from-slate-800 via-slate-900 to-stone-950 text-slate-300 border-slate-700 hover:border-amber-600/80 hover:text-amber-200 hover:bg-gradient-to-b hover:from-slate-700 hover:to-slate-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.1),inset_0_-2px_0_rgba(0,0,0,0.8),0_3px_6px_rgba(0,0,0,0.6)]' }}">
                        <span :class="collapsed ? 'w-full text-amber-400' : 'w-5 text-amber-400 group-hover:scale-110 transition-transform'" class="text-base shrink-0 flex items-center justify-center transition-all duration-300">
                            <i class="fa-solid fa-envelope"></i>
                        </span>
                        <span x-show="!collapsed"
                              x-transition:enter="transition-opacity ease-out duration-200 delay-100"
                              x-transition:enter-start="opacity-0"
                              x-transition:enter-end="opacity-100"
                              x-transition:leave="transition-opacity ease-in duration-75"
                              x-transition:leave-start="opacity-100"
                              x-transition:leave-end="opacity-0"
                              class="truncate">SKRZYNKA POCZTOWA</span>
                        @if($unreadMailCount > 0)
                            <span :class="collapsed ? 'absolute top-1 right-1' : 'absolute right-3 top-1/2 -translate-y-1/2'" class="w-4 h-4 bg-red-600 rounded-full flex items-center justify-center text-white text-[10px] font-black font-sans leading-none shadow-[0_0_10px_rgba(239,68,68,0.9)] animate-bounce z-10 select-none transition-all duration-300">{{ $unreadMailCount }}</span>
                        @endif
                    </a>
                </div>
            </div>

            {{-- Sekcja: Premium & Feedback --}}
            <div class="pt-2 space-y-1.5">
                <a href="{{ route('itemshop') }}" wire:navigate
                   @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                   @click="$dispatch('play-audio', { type: 'tab' }); $dispatch('location-leave', { text: 'Otwieranie Sklepu Gemów...', icon: 'fa-solid fa-gem', url: $el.href })"
                   :title="collapsed ? 'Sklep Gemów' : ''"
                   :class="collapsed ? 'justify-center px-0' : 'px-3 gap-3'"
                   class="flex items-center h-11 rounded-lg text-xs tracking-widest font-extrabold uppercase transition-all duration-300 ease-out relative group border-2 {{ request()->routeIs('itemshop') ? 'bg-gradient-to-b from-amber-600 via-yellow-600 to-amber-800 text-stone-950 border-yellow-300 shadow-[0_0_20px_rgba(245,158,11,0.7),inset_0_1px_0_rgba(255,255,255,0.5)] scale-[1.02]' : 'bg-gradient-to-b from-amber-950 via-stone-900 to-amber-950 border-amber-600/60 text-amber-300 hover:border-amber-400 hover:shadow-[0_0_15px_rgba(245,158,11,0.4)] shadow-[inset_0_1px_0_rgba(255,255,255,0.1)]' }}">
                    <span :class="collapsed ? 'w-full text-yellow-400' : 'w-5 text-yellow-400 group-hover:scale-110 transition-transform'" class="text-base shrink-0 flex items-center justify-center transition-all duration-300">
                        <i class="fa-solid fa-gem"></i>
                    </span>
                    <span x-show="!collapsed"
                          x-transition:enter="transition-opacity ease-out duration-200 delay-100"
                          x-transition:enter-start="opacity-0"
                          x-transition:enter-end="opacity-100"
                          x-transition:leave="transition-opacity ease-in duration-75"
                          x-transition:leave-start="opacity-100"
                          x-transition:leave-end="opacity-0"
                          class="truncate">SKLEP GEMÓW</span>
                </a>

                <a href="{{ route('city.settings', $charId) }}" wire:navigate
                   @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                   @click="$dispatch('play-audio', { type: 'tab' }); $dispatch('location-leave', { text: 'Otwieranie Ustawień...', icon: 'fa-solid fa-gear', url: $el.href })"
                   :title="collapsed ? 'Ustawienia gry' : ''"
                   :class="collapsed ? 'justify-center px-0' : 'px-3 gap-3'"
                   class="flex items-center h-11 rounded-lg text-xs tracking-widest font-extrabold uppercase transition-all duration-300 ease-out relative group border-2 {{ request()->routeIs('city.settings') ? 'bg-gradient-to-b from-amber-600 via-yellow-600 to-amber-800 text-stone-950 border-yellow-300 shadow-[0_0_20px_rgba(245,158,11,0.7),inset_0_1px_0_rgba(255,255,255,0.5)] scale-[1.02]' : 'bg-gradient-to-b from-amber-950 via-stone-900 to-amber-950 border-amber-600/60 text-amber-300 hover:border-amber-400 hover:shadow-[0_0_15px_rgba(245,158,11,0.4)] shadow-[inset_0_1px_0_rgba(255,255,255,0.1)]' }}">
                    <span :class="collapsed ? 'w-full text-yellow-400' : 'w-5 text-yellow-400 group-hover:scale-110 transition-transform'" class="text-base shrink-0 flex items-center justify-center transition-all duration-300">
                        <i class="fa-solid fa-gear"></i>
                    </span>
                    <span x-show="!collapsed"
                          x-transition:enter="transition-opacity ease-out duration-200 delay-100"
                          x-transition:enter-start="opacity-0"
                          x-transition:enter-end="opacity-100"
                          x-transition:leave="transition-opacity ease-in duration-75"
                          x-transition:leave-start="opacity-100"
                          x-transition:leave-end="opacity-0"
                          class="truncate">USTAWIENIA GRY</span>
                </a>

                <a href="https://discord.gg/YJa68KK9hC" target="_blank" rel="noopener noreferrer"
                   @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                   :title="collapsed ? 'Discord' : ''"
                   :class="collapsed ? 'justify-center px-0' : 'px-3 gap-3'"
                   class="flex items-center h-11 rounded-lg text-xs tracking-widest font-extrabold uppercase transition-all duration-300 ease-out relative group border-2 bg-gradient-to-b from-indigo-950 via-slate-900 to-indigo-950 border-indigo-600/60 text-indigo-300 hover:border-indigo-400 hover:text-indigo-100 hover:shadow-[0_0_15px_rgba(99,102,241,0.4)] shadow-[inset_0_1px_0_rgba(255,255,255,0.1),inset_0_-2px_0_rgba(0,0,0,0.8)] cursor-pointer">
                    <span :class="collapsed ? 'w-full text-indigo-400' : 'w-5 text-indigo-400 group-hover:scale-110 transition-transform'" class="text-base shrink-0 flex items-center justify-center transition-all duration-300">
                        <i class="fa-brands fa-discord"></i>
                    </span>
                    <span x-show="!collapsed"
                          x-transition:enter="transition-opacity ease-out duration-200 delay-100"
                          x-transition:enter-start="opacity-0"
                          x-transition:enter-end="opacity-100"
                          x-transition:leave="transition-opacity ease-in duration-75"
                          x-transition:leave-start="opacity-100"
                          x-transition:leave-end="opacity-0"
                          class="truncate">DISCORD</span>
                </a>

                <button @click="$dispatch('open-suggestion-modal'); $dispatch('play-audio', { type: 'hover' })"
                        :title="collapsed ? 'Zgłoś sugestię' : ''"
                        :class="collapsed ? 'justify-center px-0' : 'px-3 gap-3'"
                        class="w-full flex items-center h-11 rounded-lg text-xs tracking-widest font-extrabold uppercase transition-all duration-300 ease-out relative group border-2 bg-gradient-to-b from-indigo-950 via-stone-900 to-indigo-950 border-indigo-600/60 text-indigo-300 hover:border-indigo-400 hover:text-indigo-100 hover:shadow-[0_0_15px_rgba(99,102,241,0.4)] shadow-[inset_0_1px_0_rgba(255,255,255,0.1),inset_0_-2px_0_rgba(0,0,0,0.8)] cursor-pointer">
                    <span :class="collapsed ? 'w-full text-indigo-400' : 'w-5 text-indigo-400 group-hover:scale-110 transition-transform'" class="text-base shrink-0 flex items-center justify-center transition-all duration-300">
                        <i class="fa-solid fa-comment-dots"></i>
                    </span>
                    <span x-show="!collapsed"
                          x-transition:enter="transition-opacity ease-out duration-200 delay-100"
                          x-transition:enter-start="opacity-0"
                          x-transition:enter-end="opacity-100"
                          x-transition:leave="transition-opacity ease-in duration-75"
                          x-transition:leave-start="opacity-100"
                          x-transition:leave-end="opacity-0"
                          class="truncate">ZGŁOŚ SUGESTIĘ</span>
                </button>
            </div>

            {{-- Sekcja: Powrót do Lobby --}}
            <div class="pt-2 border-t border-amber-900/40">
                <a href="{{ route('characters.leave') }}" wire:navigate
                   @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                   @click="$dispatch('play-audio', { type: 'tab' }); $dispatch('location-leave', { text: 'Powrót do Lobby...', icon: 'fa-solid fa-right-from-bracket', url: $el.href })"
                   :title="collapsed ? 'Lobby' : ''"
                   :class="collapsed ? 'justify-center px-0' : 'px-3 gap-3'"
                   class="flex items-center h-11 rounded-lg text-xs tracking-widest font-extrabold uppercase transition-all duration-300 ease-out relative group border-2 bg-gradient-to-b from-stone-800 via-stone-900 to-black text-amber-200 border-amber-800/80 hover:border-red-500 hover:text-red-200 hover:bg-gradient-to-b hover:from-red-950 hover:to-black shadow-[inset_0_1px_0_rgba(255,255,255,0.1),inset_0_-2px_0_rgba(0,0,0,0.8),0_3px_6px_rgba(0,0,0,0.6)]">
                    <span :class="collapsed ? 'w-full text-amber-400' : 'w-5 text-amber-400 group-hover:scale-110 transition-transform'" class="text-base shrink-0 flex items-center justify-center transition-all duration-300">
                        <i class="fa-solid fa-right-from-bracket"></i>
                    </span>
                    <span x-show="!collapsed"
                          x-transition:enter="transition-opacity ease-out duration-200 delay-100"
                          x-transition:enter-start="opacity-0"
                          x-transition:enter-end="opacity-100"
                          x-transition:leave="transition-opacity ease-in duration-75"
                          x-transition:leave-start="opacity-100"
                          x-transition:leave-end="opacity-0"
                          class="truncate">LOBBY</span>
                </a>
            </div>

        </div>

        {{-- Footer --}}
        <div class="p-3 border-t border-amber-900/50 text-center text-[10px] text-amber-500/60 font-semibold tracking-wider"
             x-show="!collapsed"
             x-transition:enter="transition-opacity ease-out duration-200 delay-100"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-in duration-75"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            BERSERK RUSH &copy; {{ date('Y') }}
        </div>
    </aside>
@endif
