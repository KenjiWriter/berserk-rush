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

        $totalBadgeCount = $questBadgeCount + $profileBadgeCount + $skillPointsCount + $unreadMailCount;
    @endphp

    <div x-data="{ mobileMenuOpen: false }" class="lg:hidden">
        {{-- Backdrop overlay --}}
        <div x-show="mobileMenuOpen"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="mobileMenuOpen = false"
             class="fixed inset-0 bg-stone-950/80 backdrop-blur-md z-[9890]"
             style="display: none;"></div>

        {{-- Expanded Bottom Sheet Menu --}}
        <div x-show="mobileMenuOpen"
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="translate-y-full opacity-0"
             x-transition:enter-end="translate-y-0 opacity-100"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="translate-y-0 opacity-100"
             x-transition:leave-end="translate-y-full opacity-0"
             class="fixed bottom-16 inset-x-0 max-h-[82vh] overflow-y-auto z-[9895] bg-gradient-to-b from-stone-900 via-stone-950 to-stone-950 border-t-2 border-amber-700/80 rounded-t-2xl p-4 shadow-[0_-10px_35px_rgba(0,0,0,0.95)] custom-scrollbar"
             style="font-family: 'Cinzel', serif; display: none;">

            {{-- Header with Character summary & Close button --}}
            <div class="flex items-center justify-between border-b border-amber-900/60 pb-3 mb-4">
                <div class="flex items-center gap-3">
                    @if($character)
                        <div class="w-10 h-10 rounded-lg bg-stone-900 border border-amber-500/80 overflow-hidden shrink-0 shadow">
                            @if($character->avatar && file_exists(public_path('img/avatars/' . $character->avatar . '.png')))
                                <img src="{{ asset('img/avatars/' . $character->avatar . '.png') }}" alt="Avatar" class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-amber-400 text-xs font-bold">HERO</div>
                            @endif
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-xs font-extrabold text-amber-100 truncate">{{ $character->name }}</h3>
                            <p class="text-[10px] text-amber-400">Lvl {{ $character->level }} | ⚡ {{ number_format($character->getTotalCombatPower()) }}</p>
                        </div>
                    @endif
                </div>

                <button @click="mobileMenuOpen = false" class="w-8 h-8 rounded-full bg-amber-950 border border-amber-700/60 text-amber-300 flex items-center justify-center text-sm hover:bg-amber-900 transition-colors">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            {{-- Navigation Sections Grid --}}
            <div class="space-y-4 text-xs font-bold uppercase tracking-wider">
                
                {{-- Sekcja: Eksploracja --}}
                <div>
                    <div class="text-[10px] text-amber-500/80 mb-2 font-extrabold tracking-widest border-b border-stone-800 pb-1">Eksploracja</div>
                    <div class="grid grid-cols-2 gap-2">
                        <a href="{{ route('city.hub', $charId) }}" wire:navigate @click="mobileMenuOpen = false; $dispatch('location-leave', { text: 'Podróż do Miasta...', icon: 'fa-solid fa-archway' })"
                           class="flex items-center gap-2 p-2 sm:p-2.5 rounded-lg border border-amber-900/60 bg-stone-900/90 text-amber-100 hover:border-amber-500 transition-all min-w-0">
                            <i class="fa-solid fa-archway text-amber-400 text-sm shrink-0"></i>
                            <span class="truncate text-[11px] sm:text-xs">Miasto</span>
                        </a>

                        <a href="{{ route('city.profile', $charId) }}" wire:navigate @click="mobileMenuOpen = false; $dispatch('location-leave', { text: 'Otwieranie Ekwipunku...', icon: 'fa-solid fa-user-shield' })"
                           class="flex items-center gap-2 p-2 sm:p-2.5 rounded-lg border border-amber-900/60 bg-stone-900/90 text-amber-100 hover:border-amber-500 transition-all relative min-w-0">
                            <i class="fa-solid fa-user-shield text-amber-400 text-sm shrink-0"></i>
                            <span class="truncate text-[11px] sm:text-xs">Postać</span>
                            @if($profileBadgeCount > 0)
                                <span class="ml-auto shrink-0 w-4 h-4 bg-amber-500 rounded-full flex items-center justify-center text-[9px] text-slate-950 font-black shadow">!</span>
                            @endif
                        </a>

                        <a href="{{ route('city.adventure', $charId) }}" wire:navigate @click="mobileMenuOpen = false; $dispatch('location-leave', { text: 'Wyruszanie na Wyprawę...', icon: 'fa-solid fa-map-location-dot' })"
                           class="flex items-center gap-2 p-2 sm:p-2.5 rounded-lg border border-amber-900/60 bg-stone-900/90 text-amber-100 hover:border-amber-500 transition-all min-w-0">
                            <i class="fa-solid fa-map-location-dot text-amber-400 text-sm shrink-0"></i>
                            <span class="truncate text-[11px] sm:text-xs">Wyprawy</span>
                        </a>

                        <a href="{{ route('city.quests', $charId) }}" wire:navigate @click="mobileMenuOpen = false; $dispatch('location-leave', { text: 'Otwieranie Wyzwań...', icon: 'fa-solid fa-beer-mug-empty' })"
                           class="flex items-center gap-2 p-2 sm:p-2.5 rounded-lg border border-amber-900/60 bg-stone-900/90 text-amber-100 hover:border-amber-500 transition-all relative min-w-0">
                            <i class="fa-solid fa-beer-mug-empty text-amber-400 text-sm shrink-0"></i>
                            <span class="truncate text-[11px] sm:text-xs">Wyzwania</span>
                            @if($questBadgeCount > 0)
                                <span class="ml-auto shrink-0 w-4 h-4 bg-amber-500 rounded-full flex items-center justify-center text-[9px] text-slate-950 font-black shadow">!</span>
                            @endif
                        </a>
                    </div>
                </div>

                {{-- Sekcja: Sklepy & Rzemiosło --}}
                <div>
                    <div class="text-[10px] text-amber-500/80 mb-2 font-extrabold tracking-widest border-b border-stone-800 pb-1">Sklepy & Rzemiosło</div>
                    <div class="grid grid-cols-2 gap-2">
                        <a href="{{ route('city.weaponsmith', $charId) }}" wire:navigate @click="mobileMenuOpen = false; $dispatch('location-leave', { text: 'Wizyta u Brońmistrza...', icon: 'fa-solid fa-khanda' })"
                           class="flex items-center gap-2 p-2 sm:p-2.5 rounded-lg border border-amber-900/60 bg-stone-900/90 text-amber-100 hover:border-amber-500 transition-all min-w-0">
                            <i class="fa-solid fa-khanda text-amber-400 text-sm shrink-0"></i>
                            <span class="truncate text-[11px] sm:text-xs">Brońmistrz</span>
                        </a>

                        <a href="{{ route('city.armorsmith', $charId) }}" wire:navigate @click="mobileMenuOpen = false; $dispatch('location-leave', { text: 'Wizyta w Zbrojowni...', icon: 'fa-solid fa-shield-halved' })"
                           class="flex items-center gap-2 p-2 sm:p-2.5 rounded-lg border border-amber-900/60 bg-stone-900/90 text-amber-100 hover:border-amber-500 transition-all min-w-0">
                            <i class="fa-solid fa-shield-halved text-amber-400 text-sm shrink-0"></i>
                            <span class="truncate text-[11px] sm:text-xs">Zbrojownia</span>
                        </a>

                        <a href="{{ route('city.witch', $charId) }}" wire:navigate @click="mobileMenuOpen = false; $dispatch('location-leave', { text: 'Wizyta u Wiedźmy...', icon: 'fa-solid fa-wand-magic-sparkles' })"
                           class="flex items-center gap-2 p-2 sm:p-2.5 rounded-lg border border-amber-900/60 bg-stone-900/90 text-amber-100 hover:border-amber-500 transition-all min-w-0">
                            <i class="fa-solid fa-wand-magic-sparkles text-amber-400 text-sm shrink-0"></i>
                            <span class="truncate text-[11px] sm:text-xs">Wiedźma</span>
                        </a>

                        <a href="{{ route('city.wizard', $charId) }}" wire:navigate @click="mobileMenuOpen = false; $dispatch('location-leave', { text: 'Sklep Magiczny...', icon: 'fa-solid fa-hat-wizard' })"
                           class="flex items-center gap-2 p-2 sm:p-2.5 rounded-lg border border-amber-900/60 bg-stone-900/90 text-amber-100 hover:border-amber-500 transition-all min-w-0">
                            <i class="fa-solid fa-hat-wizard text-amber-400 text-sm shrink-0"></i>
                            <span class="truncate text-[11px] sm:text-xs">Magia</span>
                        </a>

                        <a href="{{ route('city.warlock', $charId) }}" wire:navigate @click="mobileMenuOpen = false; $dispatch('location-leave', { text: 'Czarnoksiężnik...', icon: 'fa-solid fa-skull' })"
                           class="flex items-center gap-1.5 p-2 sm:p-2.5 rounded-lg border border-amber-900/60 bg-stone-900/90 text-amber-100 hover:border-amber-500 transition-all relative min-w-0">
                            <i class="fa-solid fa-skull text-amber-400 text-sm shrink-0"></i>
                            <span class="truncate text-[10px] xs:text-[11px] sm:text-xs tracking-tight xs:tracking-wider min-w-0">Czarnoksiężnik</span>
                            @if($skillPointsCount > 0)
                                <span class="ml-auto shrink-0 w-4 h-4 bg-amber-500 rounded-full flex items-center justify-center text-[9px] text-slate-950 font-black shadow">!</span>
                            @endif
                        </a>

                        <a href="{{ route('city.market', $charId) }}" wire:navigate @click="mobileMenuOpen = false; $dispatch('location-leave', { text: 'Podróż na Rynek...', icon: 'fa-solid fa-scale-balanced' })"
                           class="flex items-center gap-2 p-2 sm:p-2.5 rounded-lg border border-amber-900/60 bg-stone-900/90 text-amber-100 hover:border-amber-500 transition-all min-w-0">
                            <i class="fa-solid fa-scale-balanced text-amber-400 text-sm shrink-0"></i>
                            <span class="truncate text-[11px] sm:text-xs">Rynek</span>
                        </a>
                    </div>
                </div>

                {{-- Sekcja: Społeczność & Rywalizacja --}}
                <div>
                    <div class="text-[10px] text-amber-500/80 mb-2 font-extrabold tracking-widest border-b border-stone-800 pb-1">Społeczność & Walka</div>
                    <div class="grid grid-cols-2 gap-2">
                        <a href="{{ route('city.arena', $charId) }}" wire:navigate @click="mobileMenuOpen = false; $dispatch('location-leave', { text: 'Wejście na Arenę...', icon: 'fa-solid fa-dungeon' })"
                           class="flex items-center gap-2 p-2 sm:p-2.5 rounded-lg border border-amber-900/60 bg-stone-900/90 text-amber-100 hover:border-amber-500 transition-all min-w-0">
                            <i class="fa-solid fa-dungeon text-amber-400 text-sm shrink-0"></i>
                            <span class="truncate text-[11px] sm:text-xs">Arena Walk</span>
                        </a>

                        <a href="{{ route('city.gladiator', $charId) }}" wire:navigate @click="mobileMenuOpen = false; $dispatch('location-leave', { text: 'Gladiator Shop...', icon: 'fa-solid fa-coins' })"
                           class="flex items-center gap-2 p-2 sm:p-2.5 rounded-lg border border-amber-900/60 bg-stone-900/90 text-amber-100 hover:border-amber-500 transition-all min-w-0">
                            <i class="fa-solid fa-coins text-amber-400 text-sm shrink-0"></i>
                            <span class="truncate text-[11px] sm:text-xs">Gladiator</span>
                        </a>

                        <a href="{{ route('city.guild', $charId) }}" wire:navigate @click="mobileMenuOpen = false; $dispatch('location-leave', { text: 'Podróż do Gildii...', icon: 'fa-solid fa-flag' })"
                           class="flex items-center gap-2 p-2 sm:p-2.5 rounded-lg border border-amber-900/60 bg-stone-900/90 text-amber-100 hover:border-amber-500 transition-all min-w-0">
                            <i class="fa-solid fa-flag text-amber-400 text-sm shrink-0"></i>
                            <span class="truncate text-[11px] sm:text-xs">Gildia</span>
                        </a>

                        <a href="{{ route('city.mailbox', $charId) }}" wire:navigate @click="mobileMenuOpen = false; $dispatch('location-leave', { text: 'Skrzynka Pocztowa...', icon: 'fa-solid fa-envelope' })"
                           class="flex items-center gap-2 p-2 sm:p-2.5 rounded-lg border border-amber-900/60 bg-stone-900/90 text-amber-100 hover:border-amber-500 transition-all relative min-w-0">
                            <i class="fa-solid fa-envelope text-amber-400 text-sm shrink-0"></i>
                            <span class="truncate text-[11px] sm:text-xs">Poczta</span>
                            @if($unreadMailCount > 0)
                                <span class="ml-auto shrink-0 px-1.5 py-0.5 bg-red-600 rounded-full text-white text-[9px] font-black">{{ $unreadMailCount }}</span>
                            @endif
                        </a>
                    </div>
                </div>

                {{-- Premium & Lobby --}}
                <div class="pt-2 space-y-2 border-t border-amber-900/40">
                    <a href="{{ route('itemshop') }}" wire:navigate @click="mobileMenuOpen = false; $dispatch('location-leave', { text: 'Sklep Gemów...', icon: 'fa-solid fa-gem' })"
                       class="flex items-center justify-center gap-2 p-2.5 rounded-lg bg-gradient-to-r from-amber-600 via-yellow-600 to-amber-700 text-stone-950 font-black border border-yellow-300 shadow">
                        <i class="fa-solid fa-gem text-stone-950 text-sm"></i>
                        <span>SKLEP GEMÓW</span>
                    </a>

                    <a href="{{ route('characters.leave') }}" wire:navigate @click="mobileMenuOpen = false; $dispatch('location-leave', { text: 'Powrót do Lobby...', icon: 'fa-solid fa-right-from-bracket' })"
                       class="flex items-center justify-center gap-2 p-2.5 rounded-lg bg-gradient-to-r from-stone-900 via-red-950 to-stone-950 text-red-200 border border-red-700/60 hover:border-red-500 transition-all">
                        <i class="fa-solid fa-right-from-bracket text-red-400 text-sm"></i>
                        <span>POWRÓT DO LOBBY</span>
                    </a>
                </div>

            </div>
        </div>

        {{-- Bottom Fixed Navigation Bar --}}
        <div class="fixed bottom-0 w-full z-[9900] bg-stone-950/95 backdrop-blur-lg border-t-2 border-amber-700/80 shadow-[0_-5px_20px_rgba(0,0,0,0.8)]">
            <div class="flex justify-around items-center h-16 px-1">
                <a href="{{ route('city.hub', $charId) }}" wire:navigate 
                   class="flex flex-col items-center justify-center w-full h-full transition-all duration-200 group {{ request()->routeIs('city.hub') ? 'bg-amber-500/15 text-amber-300 border-t-2 border-amber-400 shadow-[0_-4px_12px_rgba(245,158,11,0.3)]' : 'text-stone-400 hover:text-amber-200 hover:bg-stone-900/60' }}" 
                   @click="$dispatch('location-leave', { text: 'Podróż do Miasta...', icon: 'fa-solid fa-archway' })">
                    <span class="text-xl mb-1 group-hover:scale-110 transition-transform">
                        <i class="fa-solid fa-archway text-amber-400 text-lg"></i>
                    </span>
                    <span class="text-[10px] font-bold uppercase tracking-wider" style="font-family: 'Cinzel', serif;">Miasto</span>
                </a>

                <a href="{{ route('city.profile', $charId) }}" wire:navigate 
                   class="flex flex-col items-center justify-center w-full h-full transition-all duration-200 relative group {{ request()->routeIs('city.profile') ? 'bg-amber-500/15 text-amber-300 border-t-2 border-amber-400 shadow-[0_-4px_12px_rgba(245,158,11,0.3)]' : 'text-stone-400 hover:text-amber-200 hover:bg-stone-900/60' }}" 
                   @click="$dispatch('location-leave', { text: 'Otwieranie Ekwipunku...', icon: 'fa-solid fa-user-shield' })">
                    <span class="text-xl mb-1 relative group-hover:scale-110 transition-transform">
                        <i class="fa-solid fa-user-shield text-amber-400 text-lg"></i>
                        @if($profileBadgeCount > 0)
                            <span class="absolute -top-1 -right-2 w-4 h-4 bg-amber-500 rounded-full flex items-center justify-center text-slate-950 text-[9px] font-black animate-bounce shadow">!</span>
                        @endif
                    </span>
                    <span class="text-[10px] font-bold uppercase tracking-wider" style="font-family: 'Cinzel', serif;">Profil</span>
                </a>

                <a href="{{ route('city.adventure', $charId) }}" wire:navigate 
                   class="flex flex-col items-center justify-center w-full h-full transition-all duration-200 relative group {{ request()->routeIs('city.adventure*') || request()->routeIs('adventure.*') ? 'bg-amber-500/15 text-amber-300 border-t-2 border-amber-400 shadow-[0_-4px_12px_rgba(245,158,11,0.3)]' : 'text-stone-400 hover:text-amber-200 hover:bg-stone-900/60' }}" 
                   @click="$dispatch('location-leave', { text: 'Wyruszanie na Przygodę...', icon: 'fa-solid fa-map-location-dot' })">
                    <span class="text-xl mb-1 relative group-hover:scale-110 transition-transform">
                        <i class="fa-solid fa-map-location-dot text-amber-400 text-lg"></i>
                    </span>
                    <span class="text-[10px] font-bold uppercase tracking-wider" style="font-family: 'Cinzel', serif;">Przygoda</span>
                </a>

                <a href="{{ route('city.arena', $charId) }}" wire:navigate 
                   class="flex flex-col items-center justify-center w-full h-full transition-all duration-200 group {{ request()->routeIs('city.arena*') ? 'bg-amber-500/15 text-amber-300 border-t-2 border-amber-400 shadow-[0_-4px_12px_rgba(245,158,11,0.3)]' : 'text-stone-400 hover:text-amber-200 hover:bg-stone-900/60' }}" 
                   @click="$dispatch('location-leave', { text: 'Wejście na Arenę...', icon: 'fa-solid fa-dungeon' })">
                    <span class="text-xl mb-1 group-hover:scale-110 transition-transform">
                        <i class="fa-solid fa-khanda text-amber-400 text-lg"></i>
                    </span>
                    <span class="text-[10px] font-bold uppercase tracking-wider" style="font-family: 'Cinzel', serif;">Arena</span>
                </a>

                <button @click="mobileMenuOpen = !mobileMenuOpen" 
                        class="flex flex-col items-center justify-center w-full h-full transition-all duration-200 relative group"
                        :class="mobileMenuOpen ? 'bg-amber-500/20 text-amber-300 border-t-2 border-amber-400 shadow-[0_-4px_12px_rgba(245,158,11,0.3)]' : 'text-stone-400 hover:text-amber-200 hover:bg-stone-900/60'">
                    <span class="text-xl mb-1 relative group-hover:scale-110 transition-transform">
                        <i class="fa-solid fa-bars text-amber-400 text-lg" :class="mobileMenuOpen ? 'rotate-90 transition-transform' : ''"></i>
                        @if($totalBadgeCount > 0)
                            <span class="absolute -top-1 -right-2 w-4 h-4 bg-amber-500 rounded-full flex items-center justify-center text-slate-950 text-[9px] font-black animate-bounce shadow">!</span>
                        @endif
                    </span>
                    <span class="text-[10px] font-bold uppercase tracking-wider" style="font-family: 'Cinzel', serif;">Więcej</span>
                </button>
            </div>
        </div>
    </div>
@endif
