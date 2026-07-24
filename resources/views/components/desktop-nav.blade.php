@if (session('active_character'))
    @php
        $charId = session('active_character');
        $character = \App\Infrastructure\Persistence\Character::with('activeTitle')->find($charId);

        $claimableQuests = $character ? $character->characterQuests()->where('status', 'completed')->count() : 0;
        $availableQuests = $character ? app(\App\Application\Quests\QuestService::class)->getAvailableQuests($character)->count() : 0;
        $questBadgeCount = $claimableQuests + $availableQuests;

        $unclaimedAchievements = $character ? \App\Infrastructure\Persistence\CharacterAchievement::where('character_id', $character->id)->whereNotNull('completed_at')->where('rewarded', false)->count() : 0;
        $unassignedStatPoints = $character ? ($character->character_points ?? 0) : 0;
        $profileBadgeCount = $unclaimedAchievements + $unassignedStatPoints;

        $skillPointsCount = $character ? ($character->skill_points ?? 0) : 0;

        $unreadMailCount = $character ? \App\Infrastructure\Persistence\Mail::where('to_character_id', $character->id)->where('claimed', false)->count() : 0;
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
                <div @click="$dispatch('toggle-reward-infobox'); $dispatch('play-audio', { type: 'hover' })"
                     :class="collapsed ? 'p-1.5 justify-center' : 'p-2 pr-6'"
                     class="flex items-center gap-3 rounded-xl bg-stone-950 border-2 border-amber-700/60 hover:border-amber-400 hover:bg-amber-950/40 transition-all duration-300 shadow-[inset_0_2px_4px_rgba(0,0,0,0.8),0_4px_10px_rgba(0,0,0,0.5)] group cursor-pointer"
                     title="Kliknij, aby zobaczyć profil i skarbiec">
                    <div :class="collapsed ? 'w-10 h-10' : 'w-11 h-11'" class="rounded-lg bg-stone-900 border-2 border-amber-500/80 overflow-hidden shrink-0 shadow-[0_0_12px_rgba(245,158,11,0.3)] group-hover:scale-105 transition-all duration-300 relative">
                        @if($character->avatar && file_exists(public_path('img/avatars/' . $character->avatar . '.png')))
                            <img src="{{ asset('img/avatars/' . $character->avatar . '.png') }}" alt="Avatar" class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-amber-400 text-xs font-bold">HERO</div>
                        @endif
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
                            <span>Lvl {{ $character->level }}</span>
                            <span class="text-yellow-400 font-bold">⚡ {{ number_format($character->getTotalCombatPower()) }}</span>
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
                       @click="$dispatch('play-audio', { type: 'tab' }); $dispatch('location-leave', { text: 'Podróż do Miasta...', icon: 'fa-solid fa-archway' })"
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
                    </a>

                    {{-- Postać & Ekwipunek --}}
                    <a href="{{ route('city.profile', $charId) }}" wire:navigate
                       @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                       @click="$dispatch('play-audio', { type: 'tab' }); $dispatch('location-leave', { text: 'Otwieranie Ekwipunku...', icon: 'fa-solid fa-user-shield' })"
                       :title="collapsed ? 'Postać & Ekwipunek' : ''"
                       :class="collapsed ? 'justify-center px-0' : 'px-3 gap-3'"
                       class="flex items-center h-11 rounded-lg text-xs tracking-widest font-extrabold uppercase transition-all duration-300 ease-out relative group border-2 {{ request()->routeIs('city.profile') ? 'bg-gradient-to-b from-amber-800 via-amber-900 to-amber-950 text-yellow-200 border-amber-400 shadow-[0_0_15px_rgba(245,158,11,0.5),inset_0_1px_0_rgba(254,240,138,0.4),inset_0_-2px_0_rgba(0,0,0,0.9)] scale-[1.02]' : 'bg-gradient-to-b from-slate-800 via-slate-900 to-stone-950 text-slate-300 border-slate-700 hover:border-amber-600/80 hover:text-amber-200 hover:bg-gradient-to-b hover:from-slate-700 hover:to-slate-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.1),inset_0_-2px_0_rgba(0,0,0,0.8),0_3px_6px_rgba(0,0,0,0.6)]' }}">
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
                    <a href="{{ route('city.adventure', $charId) }}" wire:navigate
                       @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                       @click="$dispatch('play-audio', { type: 'tab' }); $dispatch('location-leave', { text: 'Wyruszanie na Wyprawę...', icon: 'fa-solid fa-map-location-dot' })"
                       :title="collapsed ? 'Wyprawy' : ''"
                       :class="collapsed ? 'justify-center px-0' : 'px-3 gap-3'"
                       class="flex items-center h-11 rounded-lg text-xs tracking-widest font-extrabold uppercase transition-all duration-300 ease-out relative group border-2 {{ request()->routeIs('city.adventure*') || request()->routeIs('adventure.*') ? 'bg-gradient-to-b from-amber-800 via-amber-900 to-amber-950 text-yellow-200 border-amber-400 shadow-[0_0_15px_rgba(245,158,11,0.5),inset_0_1px_0_rgba(254,240,138,0.4),inset_0_-2px_0_rgba(0,0,0,0.9)] scale-[1.02]' : 'bg-gradient-to-b from-slate-800 via-slate-900 to-stone-950 text-slate-300 border-slate-700 hover:border-amber-600/80 hover:text-amber-200 hover:bg-gradient-to-b hover:from-slate-700 hover:to-slate-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.1),inset_0_-2px_0_rgba(0,0,0,0.8),0_3px_6px_rgba(0,0,0,0.6)]' }}">
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
                    </a>

                    {{-- Zadania & Karczma --}}
                    <a href="{{ route('city.quests', $charId) }}" wire:navigate
                       @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                       @click="$dispatch('play-audio', { type: 'tab' }); $dispatch('location-leave', { text: 'Podróż do Karczmy...', icon: 'fa-solid fa-beer-mug-empty' })"
                       :title="collapsed ? 'Zadania & Karczma' : ''"
                       :class="collapsed ? 'justify-center px-0' : 'px-3 gap-3'"
                       class="flex items-center h-11 rounded-lg text-xs tracking-widest font-extrabold uppercase transition-all duration-300 ease-out relative group border-2 {{ request()->routeIs('city.quests') ? 'bg-gradient-to-b from-amber-800 via-amber-900 to-amber-950 text-yellow-200 border-amber-400 shadow-[0_0_15px_rgba(245,158,11,0.5),inset_0_1px_0_rgba(254,240,138,0.4),inset_0_-2px_0_rgba(0,0,0,0.9)] scale-[1.02]' : 'bg-gradient-to-b from-slate-800 via-slate-900 to-stone-950 text-slate-300 border-slate-700 hover:border-amber-600/80 hover:text-amber-200 hover:bg-gradient-to-b hover:from-slate-700 hover:to-slate-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.1),inset_0_-2px_0_rgba(0,0,0,0.8),0_3px_6px_rgba(0,0,0,0.6)]' }}">
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
                    {{-- Brońmistrz --}}
                    <a href="{{ route('city.weaponsmith', $charId) }}" wire:navigate
                       @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                       @click="$dispatch('play-audio', { type: 'tab' }); $dispatch('location-leave', { text: 'Wizyta u Brońmistrza...', icon: 'fa-solid fa-khanda' })"
                       :title="collapsed ? 'Handlarz Bronią' : ''"
                       :class="collapsed ? 'justify-center px-0' : 'px-3 gap-3'"
                       class="flex items-center h-11 rounded-lg text-xs tracking-widest font-extrabold uppercase transition-all duration-300 ease-out relative group border-2 {{ request()->routeIs('city.weaponsmith') ? 'bg-gradient-to-b from-amber-800 via-amber-900 to-amber-950 text-yellow-200 border-amber-400 shadow-[0_0_15px_rgba(245,158,11,0.5),inset_0_1px_0_rgba(254,240,138,0.4),inset_0_-2px_0_rgba(0,0,0,0.9)] scale-[1.02]' : 'bg-gradient-to-b from-slate-800 via-slate-900 to-stone-950 text-slate-300 border-slate-700 hover:border-amber-600/80 hover:text-amber-200 hover:bg-gradient-to-b hover:from-slate-700 hover:to-slate-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.1),inset_0_-2px_0_rgba(0,0,0,0.8),0_3px_6px_rgba(0,0,0,0.6)]' }}">
                        <span :class="collapsed ? 'w-full text-amber-400' : 'w-5 text-amber-400 group-hover:scale-110 transition-transform'" class="text-base shrink-0 flex items-center justify-center transition-all duration-300">
                            <i class="fa-solid fa-khanda"></i>
                        </span>
                        <span x-show="!collapsed"
                              x-transition:enter="transition-opacity ease-out duration-200 delay-100"
                              x-transition:enter-start="opacity-0"
                              x-transition:enter-end="opacity-100"
                              x-transition:leave="transition-opacity ease-in duration-75"
                              x-transition:leave-start="opacity-100"
                              x-transition:leave-end="opacity-0"
                              class="truncate">HANDLARZ BRONIĄ</span>
                    </a>

                    {{-- Zbrojownia --}}
                    <a href="{{ route('city.armorsmith', $charId) }}" wire:navigate
                       @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                       @click="$dispatch('play-audio', { type: 'tab' }); $dispatch('location-leave', { text: 'Wizyta w Zbrojowni...', icon: 'fa-solid fa-shield-halved' })"
                       :title="collapsed ? 'Zbrojownia' : ''"
                       :class="collapsed ? 'justify-center px-0' : 'px-3 gap-3'"
                       class="flex items-center h-11 rounded-lg text-xs tracking-widest font-extrabold uppercase transition-all duration-300 ease-out relative group border-2 {{ request()->routeIs('city.armorsmith') ? 'bg-gradient-to-b from-amber-800 via-amber-900 to-amber-950 text-yellow-200 border-amber-400 shadow-[0_0_15px_rgba(245,158,11,0.5),inset_0_1px_0_rgba(254,240,138,0.4),inset_0_-2px_0_rgba(0,0,0,0.9)] scale-[1.02]' : 'bg-gradient-to-b from-slate-800 via-slate-900 to-stone-950 text-slate-300 border-slate-700 hover:border-amber-600/80 hover:text-amber-200 hover:bg-gradient-to-b hover:from-slate-700 hover:to-slate-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.1),inset_0_-2px_0_rgba(0,0,0,0.8),0_3px_6px_rgba(0,0,0,0.6)]' }}">
                        <span :class="collapsed ? 'w-full text-amber-400' : 'w-5 text-amber-400 group-hover:scale-110 transition-transform'" class="text-base shrink-0 flex items-center justify-center transition-all duration-300">
                            <i class="fa-solid fa-shield-halved"></i>
                        </span>
                        <span x-show="!collapsed"
                              x-transition:enter="transition-opacity ease-out duration-200 delay-100"
                              x-transition:enter-start="opacity-0"
                              x-transition:enter-end="opacity-100"
                              x-transition:leave="transition-opacity ease-in duration-75"
                              x-transition:leave-start="opacity-100"
                              x-transition:leave-end="opacity-0"
                              class="truncate">ZBROJOWNIA</span>
                    </a>

                    {{-- Wiedźma --}}
                    <a href="{{ route('city.witch', $charId) }}" wire:navigate
                       @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                       @click="$dispatch('play-audio', { type: 'tab' }); $dispatch('location-leave', { text: 'Wizyta u Wiedźmy...', icon: 'fa-solid fa-wand-magic-sparkles' })"
                       :title="collapsed ? 'Wiedźma' : ''"
                       :class="collapsed ? 'justify-center px-0' : 'px-3 gap-3'"
                       class="flex items-center h-11 rounded-lg text-xs tracking-widest font-extrabold uppercase transition-all duration-300 ease-out relative group border-2 {{ request()->routeIs('city.witch') ? 'bg-gradient-to-b from-amber-800 via-amber-900 to-amber-950 text-yellow-200 border-amber-400 shadow-[0_0_15px_rgba(245,158,11,0.5),inset_0_1px_0_rgba(254,240,138,0.4),inset_0_-2px_0_rgba(0,0,0,0.9)] scale-[1.02]' : 'bg-gradient-to-b from-slate-800 via-slate-900 to-stone-950 text-slate-300 border-slate-700 hover:border-amber-600/80 hover:text-amber-200 hover:bg-gradient-to-b hover:from-slate-700 hover:to-slate-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.1),inset_0_-2px_0_rgba(0,0,0,0.8),0_3px_6px_rgba(0,0,0,0.6)]' }}">
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

                    {{-- Sklep Magiczny --}}
                    <a href="{{ route('city.wizard', $charId) }}" wire:navigate
                       @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                       @click="$dispatch('play-audio', { type: 'tab' }); $dispatch('location-leave', { text: 'Wizyta w Sklepie Magicznym...', icon: 'fa-solid fa-hat-wizard' })"
                       :title="collapsed ? 'Sklep Magiczny' : ''"
                       :class="collapsed ? 'justify-center px-0' : 'px-3 gap-3'"
                       class="flex items-center h-11 rounded-lg text-xs tracking-widest font-extrabold uppercase transition-all duration-300 ease-out relative group border-2 {{ request()->routeIs('city.wizard') ? 'bg-gradient-to-b from-amber-800 via-amber-900 to-amber-950 text-yellow-200 border-amber-400 shadow-[0_0_15px_rgba(245,158,11,0.5),inset_0_1px_0_rgba(254,240,138,0.4),inset_0_-2px_0_rgba(0,0,0,0.9)] scale-[1.02]' : 'bg-gradient-to-b from-slate-800 via-slate-900 to-stone-950 text-slate-300 border-slate-700 hover:border-amber-600/80 hover:text-amber-200 hover:bg-gradient-to-b hover:from-slate-700 hover:to-slate-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.1),inset_0_-2px_0_rgba(0,0,0,0.8),0_3px_6px_rgba(0,0,0,0.6)]' }}">
                        <span :class="collapsed ? 'w-full text-amber-400' : 'w-5 text-amber-400 group-hover:scale-110 transition-transform'" class="text-base shrink-0 flex items-center justify-center transition-all duration-300">
                            <i class="fa-solid fa-hat-wizard"></i>
                        </span>
                        <span x-show="!collapsed"
                              x-transition:enter="transition-opacity ease-out duration-200 delay-100"
                              x-transition:enter-start="opacity-0"
                              x-transition:enter-end="opacity-100"
                              x-transition:leave="transition-opacity ease-in duration-75"
                              x-transition:leave-start="opacity-100"
                              x-transition:leave-end="opacity-0"
                              class="truncate">SKLEP MAGICZNY</span>
                    </a>

                    {{-- Czarnoksiężnik --}}
                    <a href="{{ route('city.warlock', $charId) }}" wire:navigate
                       @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                       @click="$dispatch('play-audio', { type: 'tab' }); $dispatch('location-leave', { text: 'Wizyta u Czarnoksiężnika...', icon: 'fa-solid fa-skull' })"
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
                       @click="$dispatch('play-audio', { type: 'tab' }); $dispatch('location-leave', { text: 'Podróż na Rynek...', icon: 'fa-solid fa-scale-balanced' })"
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
                       @click="$dispatch('play-audio', { type: 'tab' }); $dispatch('location-leave', { text: 'Wejście na Arenę...', icon: 'fa-solid fa-dungeon' })"
                       :title="collapsed ? 'Arena Walk' : ''"
                       :class="collapsed ? 'justify-center px-0' : 'px-3 gap-3'"
                       class="flex items-center h-11 rounded-lg text-xs tracking-widest font-extrabold uppercase transition-all duration-300 ease-out relative group border-2 {{ request()->routeIs('city.arena*') ? 'bg-gradient-to-b from-amber-800 via-amber-900 to-amber-950 text-yellow-200 border-amber-400 shadow-[0_0_15px_rgba(245,158,11,0.5),inset_0_1px_0_rgba(254,240,138,0.4),inset_0_-2px_0_rgba(0,0,0,0.9)] scale-[1.02]' : 'bg-gradient-to-b from-slate-800 via-slate-900 to-stone-950 text-slate-300 border-slate-700 hover:border-amber-600/80 hover:text-amber-200 hover:bg-gradient-to-b hover:from-slate-700 hover:to-slate-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.1),inset_0_-2px_0_rgba(0,0,0,0.8),0_3px_6px_rgba(0,0,0,0.6)]' }}">
                        <span :class="collapsed ? 'w-full text-amber-400' : 'w-5 text-amber-400 group-hover:scale-110 transition-transform'" class="text-base shrink-0 flex items-center justify-center transition-all duration-300">
                            <i class="fa-solid fa-dungeon"></i>
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
                       @click="$dispatch('play-audio', { type: 'tab' }); $dispatch('location-leave', { text: 'Wizyta u Gladiatora...', icon: 'fa-solid fa-coins' })"
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
                    <a href="{{ route('city.guild', $charId) }}" wire:navigate
                       @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                       @click="$dispatch('play-audio', { type: 'tab' }); $dispatch('location-leave', { text: 'Podróż do Gildii...', icon: 'fa-solid fa-flag' })"
                       :title="collapsed ? 'Gildia' : ''"
                       :class="collapsed ? 'justify-center px-0' : 'px-3 gap-3'"
                       class="flex items-center h-11 rounded-lg text-xs tracking-widest font-extrabold uppercase transition-all duration-300 ease-out relative group border-2 {{ request()->routeIs('city.guild') ? 'bg-gradient-to-b from-amber-800 via-amber-900 to-amber-950 text-yellow-200 border-amber-400 shadow-[0_0_15px_rgba(245,158,11,0.5),inset_0_1px_0_rgba(254,240,138,0.4),inset_0_-2px_0_rgba(0,0,0,0.9)] scale-[1.02]' : 'bg-gradient-to-b from-slate-800 via-slate-900 to-stone-950 text-slate-300 border-slate-700 hover:border-amber-600/80 hover:text-amber-200 hover:bg-gradient-to-b hover:from-slate-700 hover:to-slate-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.1),inset_0_-2px_0_rgba(0,0,0,0.8),0_3px_6px_rgba(0,0,0,0.6)]' }}">
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

                    {{-- Wyzwania --}}
                    <a href="{{ route('city.quests', $charId) }}" wire:navigate
                       @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                       @click="$dispatch('play-audio', { type: 'tab' }); $dispatch('location-leave', { text: 'Otwieranie Wyzwań...', icon: 'fa-solid fa-award' })"
                       :title="collapsed ? 'Wyzwania' : ''"
                       :class="collapsed ? 'justify-center px-0' : 'px-3 gap-3'"
                       class="flex items-center h-11 rounded-lg text-xs tracking-widest font-extrabold uppercase transition-all duration-300 ease-out relative group border-2 {{ request()->routeIs('city.quests') ? 'bg-gradient-to-b from-amber-800 via-amber-900 to-amber-950 text-yellow-200 border-amber-400 shadow-[0_0_15px_rgba(245,158,11,0.5),inset_0_1px_0_rgba(254,240,138,0.4),inset_0_-2px_0_rgba(0,0,0,0.9)] scale-[1.02]' : 'bg-gradient-to-b from-slate-800 via-slate-900 to-stone-950 text-slate-300 border-slate-700 hover:border-amber-600/80 hover:text-amber-200 hover:bg-gradient-to-b hover:from-slate-700 hover:to-slate-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.1),inset_0_-2px_0_rgba(0,0,0,0.8),0_3px_6px_rgba(0,0,0,0.6)]' }}">
                        <span :class="collapsed ? 'w-full text-amber-400' : 'w-5 text-amber-400 group-hover:scale-110 transition-transform'" class="text-base shrink-0 flex items-center justify-center transition-all duration-300">
                            <i class="fa-solid fa-award"></i>
                        </span>
                        <span x-show="!collapsed"
                              x-transition:enter="transition-opacity ease-out duration-200 delay-100"
                              x-transition:enter-start="opacity-0"
                              x-transition:enter-end="opacity-100"
                              x-transition:leave="transition-opacity ease-in duration-75"
                              x-transition:leave-start="opacity-100"
                              x-transition:leave-end="opacity-0"
                              class="truncate">WYZWANIA</span>
                        @if($questBadgeCount > 0)
                            <span :class="collapsed ? 'absolute top-1 right-1' : 'absolute right-3 top-1/2 -translate-y-1/2'" class="w-4 h-4 bg-amber-500 rounded-full flex items-center justify-center text-[10px] text-slate-950 font-black animate-bounce z-10 transition-all duration-300">!</span>
                        @endif
                    </a>

                    {{-- Poczta --}}
                    <a href="{{ route('city.mailbox', $charId) }}" wire:navigate
                       @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                       @click="$dispatch('play-audio', { type: 'tab' }); $dispatch('location-leave', { text: 'Sprawdzanie Poczty...', icon: 'fa-solid fa-envelope' })"
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

            {{-- Sekcja: Premium --}}
            <div class="pt-2">
                <a href="{{ route('itemshop') }}" wire:navigate
                   @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                   @click="$dispatch('play-audio', { type: 'tab' }); $dispatch('location-leave', { text: 'Otwieranie Sklepu Gemów...', icon: 'fa-solid fa-gem' })"
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
            </div>

            {{-- Sekcja: Powrót do Lobby --}}
            <div class="pt-2 border-t border-amber-900/40">
                <a href="{{ route('characters.leave') }}" wire:navigate
                   @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                   @click="$dispatch('play-audio', { type: 'tab' }); $dispatch('location-leave', { text: 'Powrót do Lobby...', icon: 'fa-solid fa-right-from-bracket' })"
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
