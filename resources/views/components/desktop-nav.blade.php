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

    <aside class="hidden lg:flex flex-col w-72 shrink-0 h-screen sticky top-0 bg-stone-950 border-r-4 border-amber-800/80 shadow-[10px_0_30px_rgba(0,0,0,0.9)] z-40 overflow-y-auto select-none custom-scrollbar relative" 
           style="background: radial-gradient(circle at 50% 0%, #1c1917 0%, #0c0a09 70%, #050505 100%); font-family: 'Cinzel', serif;">
        
        {{-- Gothic / Metallic Filigree Border Trim --}}
        <div class="absolute inset-y-0 right-0 w-1 bg-gradient-to-b from-amber-600 via-yellow-500 to-amber-900 shadow-[0_0_10px_rgba(245,158,11,0.5)]"></div>
        <div class="absolute top-0 inset-x-0 h-32 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-amber-600/20 via-transparent to-transparent pointer-events-none"></div>

        {{-- Character Mini Profile Header Widget --}}
        @if($character)
            <div class="p-3 border-b-2 border-amber-900/60 bg-gradient-to-b from-amber-950/50 via-stone-900/80 to-stone-950/90 relative">
                <a href="{{ route('city.profile', $charId) }}" wire:navigate 
                   @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                   class="flex items-center gap-3 p-2.5 rounded-xl bg-stone-950 border-2 border-amber-700/60 hover:border-amber-400 hover:bg-amber-950/40 transition-all duration-200 shadow-[inset_0_2px_4px_rgba(0,0,0,0.8),0_4px_10px_rgba(0,0,0,0.5)] group">
                    <div class="w-12 h-12 rounded-lg bg-stone-900 border-2 border-amber-500/80 overflow-hidden shrink-0 shadow-[0_0_12px_rgba(245,158,11,0.3)] group-hover:scale-105 transition-transform">
                        @if($character->avatar && file_exists(public_path('img/avatars/' . $character->avatar . '.png')))
                            <img src="{{ asset('img/avatars/' . $character->avatar . '.png') }}" alt="Avatar" class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-amber-400 text-sm font-bold">HERO</div>
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        @if($character->activeTitle)
                            <p class="text-[9px] text-amber-400 font-bold uppercase tracking-wider truncate leading-tight drop-shadow">
                                {{ $character->activeTitle->prefix }}
                            </p>
                        @endif
                        <h3 class="text-sm font-extrabold text-amber-100 group-hover:text-amber-300 truncate leading-snug tracking-wide">
                            {{ $character->name }}
                        </h3>
                        <div class="flex items-center justify-between text-[10px] text-amber-300/80 font-bold mt-0.5">
                            <span>POZIOM {{ $character->level }}</span>
                            <span class="text-yellow-400 font-bold">MOC {{ number_format($character->getTotalCombatPower()) }}</span>
                        </div>
                    </div>
                </a>
            </div>
        @endif

        {{-- Navigation Menu Links --}}
        <div class="p-3 space-y-4 flex-1">
            
            {{-- Sekcja: Eksploracja --}}
            <div>
                <div class="flex items-center gap-2 mb-2 px-1">
                    <div class="h-0.5 bg-gradient-to-r from-amber-700/60 to-transparent flex-1"></div>
                    <span class="text-[10px] font-extrabold uppercase tracking-widest text-amber-500/80">Eksploracja</span>
                    <div class="h-0.5 bg-gradient-to-l from-amber-700/60 to-transparent flex-1"></div>
                </div>

                <div class="space-y-1.5">
                    {{-- Miasto --}}
                    <a href="{{ route('city.hub', $charId) }}" wire:navigate
                       @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                       @click="$dispatch('play-audio', { type: 'tab' }); $dispatch('location-leave', { text: 'Podróż do Miasta...', icon: '🏰' })"
                       class="flex items-center justify-center h-11 px-4 rounded-lg text-xs tracking-widest font-extrabold uppercase transition-all duration-200 relative group border-2 text-center {{ request()->routeIs('city.hub') ? 'bg-gradient-to-b from-amber-800 via-amber-900 to-amber-950 text-yellow-200 border-amber-400 shadow-[0_0_15px_rgba(245,158,11,0.5),inset_0_1px_0_rgba(254,240,138,0.4),inset_0_-2px_0_rgba(0,0,0,0.9)] scale-[1.02]' : 'bg-gradient-to-b from-slate-800 via-slate-900 to-stone-950 text-slate-300 border-slate-700 hover:border-amber-600/80 hover:text-amber-200 hover:bg-gradient-to-b hover:from-slate-700 hover:to-slate-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.1),inset_0_-2px_0_rgba(0,0,0,0.8),0_3px_6px_rgba(0,0,0,0.6)]' }}">
                        <span>MIASTO</span>
                    </a>

                    {{-- Postać & Ekwipunek --}}
                    <a href="{{ route('city.profile', $charId) }}" wire:navigate
                       @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                       @click="$dispatch('play-audio', { type: 'tab' }); $dispatch('location-leave', { text: 'Otwieranie Ekwipunku...', icon: '👤' })"
                       class="flex items-center justify-center h-11 px-4 rounded-lg text-xs tracking-widest font-extrabold uppercase transition-all duration-200 relative group border-2 text-center {{ request()->routeIs('city.profile') ? 'bg-gradient-to-b from-amber-800 via-amber-900 to-amber-950 text-yellow-200 border-amber-400 shadow-[0_0_15px_rgba(245,158,11,0.5),inset_0_1px_0_rgba(254,240,138,0.4),inset_0_-2px_0_rgba(0,0,0,0.9)] scale-[1.02]' : 'bg-gradient-to-b from-slate-800 via-slate-900 to-stone-950 text-slate-300 border-slate-700 hover:border-amber-600/80 hover:text-amber-200 hover:bg-gradient-to-b hover:from-slate-700 hover:to-slate-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.1),inset_0_-2px_0_rgba(0,0,0,0.8),0_3px_6px_rgba(0,0,0,0.6)]' }}">
                        <span>POSTAĆ & EKWIPUNEK</span>
                        @if($profileBadgeCount > 0)
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 bg-amber-500 rounded-full flex items-center justify-center text-slate-950 text-[10px] font-black shadow-[0_0_10px_rgba(245,158,11,0.9)] animate-bounce z-10">!</span>
                        @endif
                    </a>

                    {{-- Wyprawy --}}
                    <a href="{{ route('city.adventure', $charId) }}" wire:navigate
                       @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                       @click="$dispatch('play-audio', { type: 'tab' }); $dispatch('location-leave', { text: 'Wyruszanie na Wyprawę...', icon: '🗺️' })"
                       class="flex items-center justify-center h-11 px-4 rounded-lg text-xs tracking-widest font-extrabold uppercase transition-all duration-200 relative group border-2 text-center {{ request()->routeIs('city.adventure*') || request()->routeIs('adventure.*') ? 'bg-gradient-to-b from-amber-800 via-amber-900 to-amber-950 text-yellow-200 border-amber-400 shadow-[0_0_15px_rgba(245,158,11,0.5),inset_0_1px_0_rgba(254,240,138,0.4),inset_0_-2px_0_rgba(0,0,0,0.9)] scale-[1.02]' : 'bg-gradient-to-b from-slate-800 via-slate-900 to-stone-950 text-slate-300 border-slate-700 hover:border-amber-600/80 hover:text-amber-200 hover:bg-gradient-to-b hover:from-slate-700 hover:to-slate-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.1),inset_0_-2px_0_rgba(0,0,0,0.8),0_3px_6px_rgba(0,0,0,0.6)]' }}">
                        <span>WYPRAWY</span>
                    </a>

                    {{-- Zadania & Karczma --}}
                    <a href="{{ route('city.quests', $charId) }}" wire:navigate
                       @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                       @click="$dispatch('play-audio', { type: 'tab' }); $dispatch('location-leave', { text: 'Podróż do Karczmy...', icon: '🍺' })"
                       class="flex items-center justify-center h-11 px-4 rounded-lg text-xs tracking-widest font-extrabold uppercase transition-all duration-200 relative group border-2 text-center {{ request()->routeIs('city.quests') ? 'bg-gradient-to-b from-amber-800 via-amber-900 to-amber-950 text-yellow-200 border-amber-400 shadow-[0_0_15px_rgba(245,158,11,0.5),inset_0_1px_0_rgba(254,240,138,0.4),inset_0_-2px_0_rgba(0,0,0,0.9)] scale-[1.02]' : 'bg-gradient-to-b from-slate-800 via-slate-900 to-stone-950 text-slate-300 border-slate-700 hover:border-amber-600/80 hover:text-amber-200 hover:bg-gradient-to-b hover:from-slate-700 hover:to-slate-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.1),inset_0_-2px_0_rgba(0,0,0,0.8),0_3px_6px_rgba(0,0,0,0.6)]' }}">
                        <span>ZADANIA & KARCZMA</span>
                        @if($questBadgeCount > 0)
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 bg-amber-500 rounded-full flex items-center justify-center text-slate-950 text-[10px] font-black shadow-[0_0_10px_rgba(245,158,11,0.9)] animate-bounce z-10">!</span>
                        @endif
                    </a>
                </div>
            </div>

            {{-- Sekcja: Sklepy & Rzemiosło --}}
            <div>
                <div class="flex items-center gap-2 mb-2 px-1">
                    <div class="h-0.5 bg-gradient-to-r from-amber-700/60 to-transparent flex-1"></div>
                    <span class="text-[10px] font-extrabold uppercase tracking-widest text-amber-500/80">Sklepy & Rzemiosło</span>
                    <div class="h-0.5 bg-gradient-to-l from-amber-700/60 to-transparent flex-1"></div>
                </div>

                <div class="space-y-1.5">
                    {{-- Brońmistrz --}}
                    <a href="{{ route('city.weaponsmith', $charId) }}" wire:navigate
                       @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                       @click="$dispatch('play-audio', { type: 'tab' }); $dispatch('location-leave', { text: 'Wizyta u Brońmistrza...', icon: '⚔️' })"
                       class="flex items-center justify-center h-11 px-4 rounded-lg text-xs tracking-widest font-extrabold uppercase transition-all duration-200 relative group border-2 text-center {{ request()->routeIs('city.weaponsmith') ? 'bg-gradient-to-b from-amber-800 via-amber-900 to-amber-950 text-yellow-200 border-amber-400 shadow-[0_0_15px_rgba(245,158,11,0.5),inset_0_1px_0_rgba(254,240,138,0.4),inset_0_-2px_0_rgba(0,0,0,0.9)] scale-[1.02]' : 'bg-gradient-to-b from-slate-800 via-slate-900 to-stone-950 text-slate-300 border-slate-700 hover:border-amber-600/80 hover:text-amber-200 hover:bg-gradient-to-b hover:from-slate-700 hover:to-slate-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.1),inset_0_-2px_0_rgba(0,0,0,0.8),0_3px_6px_rgba(0,0,0,0.6)]' }}">
                        <span>HANDLARZ BRONIĄ</span>
                    </a>

                    {{-- Zbrojownia --}}
                    <a href="{{ route('city.armorsmith', $charId) }}" wire:navigate
                       @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                       @click="$dispatch('play-audio', { type: 'tab' }); $dispatch('location-leave', { text: 'Wizyta w Zbrojowni...', icon: '🛡️' })"
                       class="flex items-center justify-center h-11 px-4 rounded-lg text-xs tracking-widest font-extrabold uppercase transition-all duration-200 relative group border-2 text-center {{ request()->routeIs('city.armorsmith') ? 'bg-gradient-to-b from-amber-800 via-amber-900 to-amber-950 text-yellow-200 border-amber-400 shadow-[0_0_15px_rgba(245,158,11,0.5),inset_0_1px_0_rgba(254,240,138,0.4),inset_0_-2px_0_rgba(0,0,0,0.9)] scale-[1.02]' : 'bg-gradient-to-b from-slate-800 via-slate-900 to-stone-950 text-slate-300 border-slate-700 hover:border-amber-600/80 hover:text-amber-200 hover:bg-gradient-to-b hover:from-slate-700 hover:to-slate-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.1),inset_0_-2px_0_rgba(0,0,0,0.8),0_3px_6px_rgba(0,0,0,0.6)]' }}">
                        <span>ZBROJOWNIA</span>
                    </a>

                    {{-- Wiedźma --}}
                    <a href="{{ route('city.witch', $charId) }}" wire:navigate
                       @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                       @click="$dispatch('play-audio', { type: 'tab' }); $dispatch('location-leave', { text: 'Wizyta u Wiedźmy...', icon: '🔮' })"
                       class="flex items-center justify-center h-11 px-4 rounded-lg text-xs tracking-widest font-extrabold uppercase transition-all duration-200 relative group border-2 text-center {{ request()->routeIs('city.witch') ? 'bg-gradient-to-b from-amber-800 via-amber-900 to-amber-950 text-yellow-200 border-amber-400 shadow-[0_0_15px_rgba(245,158,11,0.5),inset_0_1px_0_rgba(254,240,138,0.4),inset_0_-2px_0_rgba(0,0,0,0.9)] scale-[1.02]' : 'bg-gradient-to-b from-slate-800 via-slate-900 to-stone-950 text-slate-300 border-slate-700 hover:border-amber-600/80 hover:text-amber-200 hover:bg-gradient-to-b hover:from-slate-700 hover:to-slate-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.1),inset_0_-2px_0_rgba(0,0,0,0.8),0_3px_6px_rgba(0,0,0,0.6)]' }}">
                        <span>WIEDŹMA</span>
                    </a>

                    {{-- Sklep Magiczny --}}
                    <a href="{{ route('city.wizard', $charId) }}" wire:navigate
                       @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                       @click="$dispatch('play-audio', { type: 'tab' }); $dispatch('location-leave', { text: 'Wizyta w Sklepie Magicznym...', icon: '📜' })"
                       class="flex items-center justify-center h-11 px-4 rounded-lg text-xs tracking-widest font-extrabold uppercase transition-all duration-200 relative group border-2 text-center {{ request()->routeIs('city.wizard') ? 'bg-gradient-to-b from-amber-800 via-amber-900 to-amber-950 text-yellow-200 border-amber-400 shadow-[0_0_15px_rgba(245,158,11,0.5),inset_0_1px_0_rgba(254,240,138,0.4),inset_0_-2px_0_rgba(0,0,0,0.9)] scale-[1.02]' : 'bg-gradient-to-b from-slate-800 via-slate-900 to-stone-950 text-slate-300 border-slate-700 hover:border-amber-600/80 hover:text-amber-200 hover:bg-gradient-to-b hover:from-slate-700 hover:to-slate-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.1),inset_0_-2px_0_rgba(0,0,0,0.8),0_3px_6px_rgba(0,0,0,0.6)]' }}">
                        <span>SKLEP MAGICZNY</span>
                    </a>

                    {{-- Czarnoksiężnik --}}
                    <a href="{{ route('city.warlock', $charId) }}" wire:navigate
                       @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                       @click="$dispatch('play-audio', { type: 'tab' }); $dispatch('location-leave', { text: 'Wizyta u Czarnoksiężnika...', icon: '💀' })"
                       class="flex items-center justify-center h-11 px-4 rounded-lg text-xs tracking-widest font-extrabold uppercase transition-all duration-200 relative group border-2 text-center {{ request()->routeIs('city.warlock') ? 'bg-gradient-to-b from-amber-800 via-amber-900 to-amber-950 text-yellow-200 border-amber-400 shadow-[0_0_15px_rgba(245,158,11,0.5),inset_0_1px_0_rgba(254,240,138,0.4),inset_0_-2px_0_rgba(0,0,0,0.9)] scale-[1.02]' : 'bg-gradient-to-b from-slate-800 via-slate-900 to-stone-950 text-slate-300 border-slate-700 hover:border-amber-600/80 hover:text-amber-200 hover:bg-gradient-to-b hover:from-slate-700 hover:to-slate-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.1),inset_0_-2px_0_rgba(0,0,0,0.8),0_3px_6px_rgba(0,0,0,0.6)]' }}">
                        <span>CZARNOKSIĘŻNIK</span>
                        @if($skillPointsCount > 0)
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 bg-amber-500 rounded-full flex items-center justify-center text-slate-950 text-[10px] font-black shadow-[0_0_10px_rgba(245,158,11,0.9)] animate-bounce z-10">!</span>
                        @endif
                    </a>

                    {{-- Aukcje & Rynek --}}
                    <a href="{{ route('city.market', $charId) }}" wire:navigate
                       @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                       @click="$dispatch('play-audio', { type: 'tab' }); $dispatch('location-leave', { text: 'Podróż na Rynek...', icon: '⚖️' })"
                       class="flex items-center justify-center h-11 px-4 rounded-lg text-xs tracking-widest font-extrabold uppercase transition-all duration-200 relative group border-2 text-center {{ request()->routeIs('city.market') ? 'bg-gradient-to-b from-amber-800 via-amber-900 to-amber-950 text-yellow-200 border-amber-400 shadow-[0_0_15px_rgba(245,158,11,0.5),inset_0_1px_0_rgba(254,240,138,0.4),inset_0_-2px_0_rgba(0,0,0,0.9)] scale-[1.02]' : 'bg-gradient-to-b from-slate-800 via-slate-900 to-stone-950 text-slate-300 border-slate-700 hover:border-amber-600/80 hover:text-amber-200 hover:bg-gradient-to-b hover:from-slate-700 hover:to-slate-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.1),inset_0_-2px_0_rgba(0,0,0,0.8),0_3px_6px_rgba(0,0,0,0.6)]' }}">
                        <span>AUKCJE & RYNEK</span>
                    </a>
                </div>
            </div>

            {{-- Sekcja: Rywalizacja & Społeczność --}}
            <div>
                <div class="flex items-center gap-2 mb-2 px-1">
                    <div class="h-0.5 bg-gradient-to-r from-amber-700/60 to-transparent flex-1"></div>
                    <span class="text-[10px] font-extrabold uppercase tracking-widest text-amber-500/80">Społeczność & Walka</span>
                    <div class="h-0.5 bg-gradient-to-l from-amber-700/60 to-transparent flex-1"></div>
                </div>

                <div class="space-y-1.5">
                    {{-- Arena --}}
                    <a href="{{ route('city.arena', $charId) }}" wire:navigate
                       @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                       @click="$dispatch('play-audio', { type: 'tab' }); $dispatch('location-leave', { text: 'Wejście na Arenę...', icon: '🏟️' })"
                       class="flex items-center justify-center h-11 px-4 rounded-lg text-xs tracking-widest font-extrabold uppercase transition-all duration-200 relative group border-2 text-center {{ request()->routeIs('city.arena*') ? 'bg-gradient-to-b from-amber-800 via-amber-900 to-amber-950 text-yellow-200 border-amber-400 shadow-[0_0_15px_rgba(245,158,11,0.5),inset_0_1px_0_rgba(254,240,138,0.4),inset_0_-2px_0_rgba(0,0,0,0.9)] scale-[1.02]' : 'bg-gradient-to-b from-slate-800 via-slate-900 to-stone-950 text-slate-300 border-slate-700 hover:border-amber-600/80 hover:text-amber-200 hover:bg-gradient-to-b hover:from-slate-700 hover:to-slate-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.1),inset_0_-2px_0_rgba(0,0,0,0.8),0_3px_6px_rgba(0,0,0,0.6)]' }}">
                        <span>ARENA WALK</span>
                    </a>

                    {{-- Gladiator --}}
                    <a href="{{ route('city.gladiator', $charId) }}" wire:navigate
                       @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                       @click="$dispatch('play-audio', { type: 'tab' }); $dispatch('location-leave', { text: 'Wizyta u Gladiatora...', icon: '🩸' })"
                       class="flex items-center justify-center h-11 px-4 rounded-lg text-xs tracking-widest font-extrabold uppercase transition-all duration-200 relative group border-2 text-center {{ request()->routeIs('city.gladiator') ? 'bg-gradient-to-b from-amber-800 via-amber-900 to-amber-950 text-yellow-200 border-amber-400 shadow-[0_0_15px_rgba(245,158,11,0.5),inset_0_1px_0_rgba(254,240,138,0.4),inset_0_-2px_0_rgba(0,0,0,0.9)] scale-[1.02]' : 'bg-gradient-to-b from-slate-800 via-slate-900 to-stone-950 text-slate-300 border-slate-700 hover:border-amber-600/80 hover:text-amber-200 hover:bg-gradient-to-b hover:from-slate-700 hover:to-slate-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.1),inset_0_-2px_0_rgba(0,0,0,0.8),0_3px_6px_rgba(0,0,0,0.6)]' }}">
                        <span>GLADIATOR SHOP</span>
                    </a>

                    {{-- Gildia --}}
                    <a href="{{ route('city.guild', $charId) }}" wire:navigate
                       @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                       @click="$dispatch('play-audio', { type: 'tab' }); $dispatch('location-leave', { text: 'Podróż do Gildii...', icon: '🚩' })"
                       class="flex items-center justify-center h-11 px-4 rounded-lg text-xs tracking-widest font-extrabold uppercase transition-all duration-200 relative group border-2 text-center {{ request()->routeIs('city.guild') ? 'bg-gradient-to-b from-amber-800 via-amber-900 to-amber-950 text-yellow-200 border-amber-400 shadow-[0_0_15px_rgba(245,158,11,0.5),inset_0_1px_0_rgba(254,240,138,0.4),inset_0_-2px_0_rgba(0,0,0,0.9)] scale-[1.02]' : 'bg-gradient-to-b from-slate-800 via-slate-900 to-stone-950 text-slate-300 border-slate-700 hover:border-amber-600/80 hover:text-amber-200 hover:bg-gradient-to-b hover:from-slate-700 hover:to-slate-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.1),inset_0_-2px_0_rgba(0,0,0,0.8),0_3px_6px_rgba(0,0,0,0.6)]' }}">
                        <span>GILDIA</span>
                    </a>

                    {{-- Wyzwania --}}
                    <a href="{{ route('city.quests', $charId) }}" wire:navigate
                       @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                       @click="$dispatch('play-audio', { type: 'tab' }); $dispatch('location-leave', { text: 'Otwieranie Wyzwań...', icon: '📜' })"
                       class="flex items-center justify-center h-11 px-4 rounded-lg text-xs tracking-widest font-extrabold uppercase transition-all duration-200 relative group border-2 text-center {{ request()->routeIs('city.quests') ? 'bg-gradient-to-b from-amber-800 via-amber-900 to-amber-950 text-yellow-200 border-amber-400 shadow-[0_0_15px_rgba(245,158,11,0.5),inset_0_1px_0_rgba(254,240,138,0.4),inset_0_-2px_0_rgba(0,0,0,0.9)] scale-[1.02]' : 'bg-gradient-to-b from-slate-800 via-slate-900 to-stone-950 text-slate-300 border-slate-700 hover:border-amber-600/80 hover:text-amber-200 hover:bg-gradient-to-b hover:from-slate-700 hover:to-slate-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.1),inset_0_-2px_0_rgba(0,0,0,0.8),0_3px_6px_rgba(0,0,0,0.6)]' }}">
                        <span>WYZWANIA</span>
                        @if($questBadgeCount > 0)
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 bg-amber-500 rounded-full flex items-center justify-center text-slate-950 text-[10px] font-black shadow-[0_0_10px_rgba(245,158,11,0.9)] animate-bounce z-10">!</span>
                        @endif
                    </a>

                    {{-- Poczta --}}
                    <a href="{{ route('city.mailbox', $charId) }}" wire:navigate
                       @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                       @click="$dispatch('play-audio', { type: 'tab' }); $dispatch('location-leave', { text: 'Sprawdzanie Poczty...', icon: '✉️' })"
                       class="flex items-center justify-center h-11 px-4 rounded-lg text-xs tracking-widest font-extrabold uppercase transition-all duration-200 relative group border-2 text-center {{ request()->routeIs('city.mailbox') ? 'bg-gradient-to-b from-amber-800 via-amber-900 to-amber-950 text-yellow-200 border-amber-400 shadow-[0_0_15px_rgba(245,158,11,0.5),inset_0_1px_0_rgba(254,240,138,0.4),inset_0_-2px_0_rgba(0,0,0,0.9)] scale-[1.02]' : 'bg-gradient-to-b from-slate-800 via-slate-900 to-stone-950 text-slate-300 border-slate-700 hover:border-amber-600/80 hover:text-amber-200 hover:bg-gradient-to-b hover:from-slate-700 hover:to-slate-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.1),inset_0_-2px_0_rgba(0,0,0,0.8),0_3px_6px_rgba(0,0,0,0.6)]' }}">
                        <span>SKRZYNKA POCZTOWA</span>
                        @if($unreadMailCount > 0)
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 bg-red-600 rounded-full flex items-center justify-center text-white text-[10px] font-black shadow-[0_0_10px_rgba(239,68,68,0.9)] animate-bounce z-10">{{ $unreadMailCount }}</span>
                        @endif
                    </a>
                </div>
            </div>

            {{-- Sekcja: Premium --}}
            <div class="pt-2">
                <a href="{{ route('itemshop') }}" wire:navigate
                   @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                   @click="$dispatch('play-audio', { type: 'tab' }); $dispatch('location-leave', { text: 'Otwieranie Sklepu Gemów...', icon: '💎' })"
                   class="flex items-center justify-center h-11 px-4 rounded-lg text-xs tracking-widest font-extrabold uppercase transition-all duration-200 relative group border-2 text-center {{ request()->routeIs('itemshop') ? 'bg-gradient-to-b from-amber-600 via-yellow-600 to-amber-800 text-stone-950 border-yellow-300 shadow-[0_0_20px_rgba(245,158,11,0.7),inset_0_1px_0_rgba(255,255,255,0.5)] scale-[1.02]' : 'bg-gradient-to-b from-amber-950 via-stone-900 to-amber-950 border-amber-600/60 text-amber-300 hover:border-amber-400 hover:shadow-[0_0_15px_rgba(245,158,11,0.4)] shadow-[inset_0_1px_0_rgba(255,255,255,0.1)]' }}">
                    <span>SKLEP GEMÓW</span>
                </a>
            </div>

            {{-- Sekcja: Powrót do Lobby --}}
            <div class="pt-2 border-t border-amber-900/40">
                <a href="{{ route('characters.leave') }}" wire:navigate
                   @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                   @click="$dispatch('play-audio', { type: 'tab' }); $dispatch('location-leave', { text: 'Powrót do Lobby...', icon: '🚪' })"
                   class="flex items-center justify-center h-11 px-4 rounded-lg text-xs tracking-widest font-extrabold uppercase transition-all duration-200 relative group border-2 text-center bg-gradient-to-b from-stone-800 via-stone-900 to-black text-amber-200 border-amber-800/80 hover:border-red-500 hover:text-red-200 hover:bg-gradient-to-b hover:from-red-950 hover:to-black shadow-[inset_0_1px_0_rgba(255,255,255,0.1),inset_0_-2px_0_rgba(0,0,0,0.8),0_3px_6px_rgba(0,0,0,0.6)]">
                    <span>LOBBY</span>
                </a>
            </div>

        </div>

        {{-- Footer --}}
        <div class="p-3 border-t border-amber-900/50 text-center text-[10px] text-amber-500/60 font-semibold tracking-wider">
            BERSERK RUSH &copy; {{ date('Y') }}
        </div>
    </aside>
@endif
