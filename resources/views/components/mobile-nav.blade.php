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
        $isQuestsLocked = auth()->check() && (!$character || $character->level < 5);
        $isGuildLocked = auth()->check() && (!$character || $character->level < 10);
        $isArenaLocked = auth()->check() && (!$character || $character->level < 15);

        $hubTutorialStages = [3, 4, 8, 15, 16, 20, 22, 30, 35, 38, 41];
        $currentGameStage = auth()->check() ? auth()->user()->game_stage : null;
        $hubTutorialPending = $character && $currentGameStage !== null && (
            in_array($currentGameStage, $hubTutorialStages, true)
            || ($currentGameStage == 13 && $character->character_points > 0)
        );

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

        $totalBadgeCount = $questBadgeCount + $profileBadgeCount + $skillPointsCount + $unreadMailCount + ($hubTutorialPending ? 1 : 0);
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
             class="fixed inset-0 bg-stone-950/80 backdrop-blur-md z-[9940]"
             style="display: none;"></div>

        {{-- Expanded Bottom Sheet Menu --}}
        <div x-show="mobileMenuOpen"
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="translate-y-full opacity-0"
             x-transition:enter-end="translate-y-0 opacity-100"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="translate-y-0 opacity-100"
             x-transition:leave-end="translate-y-full opacity-0"
             class="fixed bottom-16 inset-x-0 max-h-[82vh] overflow-y-auto z-[9945] bg-gradient-to-b from-stone-900 via-stone-950 to-stone-950 border-t-2 border-amber-700/80 rounded-t-2xl p-4 shadow-[0_-10px_35px_rgba(0,0,0,0.95)] custom-scrollbar"
             style="font-family: 'Cinzel', serif; display: none;">

            {{-- Header with Character summary & Close button --}}
            <div class="border-b border-amber-900/60 pb-3 mb-4 space-y-2.5"
                 @if($character)
                    @php
                        $mobileXpReq = app(\App\Application\Characters\LevelUpService::class)->xpToNext($character->level);
                        $mobileXpPct = $mobileXpReq > 0 ? number_format(min(100, max(0, ($character->xp / $mobileXpReq) * 100)), 1) : '0.0';
                    @endphp
                    x-data="{
                        navLevel: {{ $character->level }},
                        navXp: {{ $character->xp }},
                        navXpReq: {{ $mobileXpReq }},
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
                                this.navXpReq = Math.round(req);
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
                            navXpReq = Math.round(req);
                        }
                    "
                 @endif
            >
                <div class="flex items-center justify-between">
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
                                <p class="text-[10px] text-amber-400 font-bold">Lvl <span x-text="navLevel">{{ $character->level }}</span> | ⚡ {{ number_format($character->getTotalCombatPower()) }}</p>
                            </div>
                        @endif
                    </div>

                    <button @click="mobileMenuOpen = false" class="w-8 h-8 rounded-full bg-amber-950 border border-amber-700/60 text-amber-300 flex items-center justify-center text-sm hover:bg-amber-900 transition-colors">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                @if($character)
                    {{-- EXP Progress Bar --}}
                    <div>
                        <div class="flex justify-between text-[9px] font-bold text-stone-400 mb-1 font-sans">
                            <span class="text-amber-500/90 tracking-wider">EXP <span class="text-amber-300/90 font-semibold ml-0.5">(<span x-text="navXpPct + '%'">{{ $mobileXpPct }}%</span>)</span></span>
                            <span><span x-text="Number(navXp).toLocaleString()">{{ number_format($character->xp) }}</span> / <span x-text="Number(navXpReq).toLocaleString()">{{ number_format($mobileXpReq) }}</span></span>
                        </div>
                        <div class="w-full h-2 bg-stone-900 rounded-full overflow-hidden border border-stone-800 shadow-inner">
                            <div class="h-full bg-gradient-to-r from-blue-600 via-indigo-500 to-cyan-400 transition-all duration-700 relative rounded-full"
                                 :style="`width: ${Math.min(100, Math.max(0, (navXp / Math.max(1, navXpReq)) * 100))}%`">
                                <div class="absolute inset-0 bg-white/20 animate-pulse"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Currencies: Gold & Diamonds --}}
                    <div class="grid grid-cols-2 gap-1.5 text-xs font-sans pt-0.5">
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
                @endif
            </div>

            {{-- Navigation Sections Grid --}}
            <div class="space-y-4 text-xs font-bold uppercase tracking-wider">
                
                {{-- Sekcja: Eksploracja --}}
                <div>
                    <div class="text-[10px] text-amber-500/80 mb-2 font-extrabold tracking-widest border-b border-stone-800 pb-1">Eksploracja</div>
                    <div class="grid grid-cols-2 gap-2">
                        <a href="{{ route('city.hub', $charId) }}" wire:navigate @click="mobileMenuOpen = false; $dispatch('location-leave', { text: 'Podróż do Miasta...', icon: 'fa-solid fa-archway', url: $el.href })"
                           class="flex items-center gap-2 p-2.5 sm:p-3 min-h-[44px] rounded-lg border border-amber-900/60 bg-stone-900/90 text-amber-100 hover:border-amber-500 transition-all relative min-w-0">
                            <i class="fa-solid fa-archway text-amber-400 text-sm shrink-0"></i>
                            <span class="truncate text-[11px] sm:text-xs">Miasto</span>
                            @if($hubTutorialPending)
                                <span class="ml-auto shrink-0 w-4 h-4 bg-amber-500 rounded-full flex items-center justify-center text-[9px] text-slate-950 font-black shadow">!</span>
                            @endif
                        </a>

                        <a href="{{ route('city.profile', $charId) }}" wire:navigate @click="mobileMenuOpen = false; $dispatch('location-leave', { text: 'Otwieranie Ekwipunku...', icon: 'fa-solid fa-user-shield', url: $el.href })"
                           class="flex items-center gap-2 p-2.5 sm:p-3 min-h-[44px] rounded-lg border border-amber-900/60 bg-stone-900/90 text-amber-100 hover:border-amber-500 transition-all relative min-w-0 {{ $navTutorialPulse['profile'] ? 'ring-2 ring-amber-500 animate-pulse' : '' }}">
                            <i class="fa-solid fa-user-shield text-amber-400 text-sm shrink-0"></i>
                            <span class="truncate text-[11px] sm:text-xs">Postać</span>
                            @if($profileBadgeCount > 0)
                                <span class="ml-auto shrink-0 w-4 h-4 bg-amber-500 rounded-full flex items-center justify-center text-[9px] text-slate-950 font-black shadow">!</span>
                            @endif
                        </a>

                        <a href="{{ route('city.adventure', $charId) }}" wire:navigate @click="mobileMenuOpen = false; $dispatch('location-leave', { text: 'Wyruszanie na Wyprawę...', icon: 'fa-solid fa-map-location-dot', url: $el.href })"
                           class="flex items-center gap-2 p-2.5 sm:p-3 min-h-[44px] rounded-lg border border-amber-900/60 bg-stone-900/90 text-amber-100 hover:border-amber-500 transition-all min-w-0 {{ $navTutorialPulse['adventure'] ? 'ring-2 ring-amber-500 animate-pulse' : '' }}">
                            <i class="fa-solid fa-map-location-dot text-amber-400 text-sm shrink-0"></i>
                            <span class="truncate text-[11px] sm:text-xs">Wyprawy</span>
                        </a>

                        @if($isQuestsLocked)
                            <a href="javascript:void(0)" @click="$dispatch('notify', { type: 'error', message: 'Tablica Wyzwań jest zablokowana! Wbij 5 poziom postaci i wyczekuj rozkazów Kapitana.' })"
                               class="flex items-center gap-2 p-2.5 sm:p-3 min-h-[44px] rounded-lg border border-stone-800 bg-stone-950/80 text-stone-500 opacity-60 grayscale cursor-not-allowed relative min-w-0">
                                <i class="fa-solid fa-beer-mug-empty text-amber-500/40 text-sm shrink-0"></i>
                                <span class="truncate text-[11px] sm:text-xs">Wyzwania</span>
                                <i class="fa-solid fa-lock text-amber-500 text-xs ml-auto shrink-0"></i>
                            </a>
                        @else
                            <a href="{{ route('city.quests', $charId) }}" wire:navigate @click="mobileMenuOpen = false; $dispatch('location-leave', { text: 'Otwieranie Wyzwań...', icon: 'fa-solid fa-beer-mug-empty', url: $el.href })"
                               class="flex items-center gap-2 p-2.5 sm:p-3 min-h-[44px] rounded-lg border border-amber-900/60 bg-stone-900/90 text-amber-100 hover:border-amber-500 transition-all relative min-w-0 {{ $navTutorialPulse['quests'] ? 'ring-2 ring-amber-500 animate-pulse' : '' }}">
                                <i class="fa-solid fa-beer-mug-empty text-amber-400 text-sm shrink-0"></i>
                                <span class="truncate text-[11px] sm:text-xs">Wyzwania</span>
                                @if($questBadgeCount > 0)
                                    <span class="ml-auto shrink-0 w-4 h-4 bg-amber-500 rounded-full flex items-center justify-center text-[9px] text-slate-950 font-black shadow">!</span>
                                @endif
                            </a>
                        @endif
                    </div>
                </div>

                {{-- Sekcja: Sklepy & Rzemiosło --}}
                <div>
                    <div class="text-[10px] text-amber-500/80 mb-2 font-extrabold tracking-widest border-b border-stone-800 pb-1">Sklepy & Rzemiosło</div>
                    <div class="grid grid-cols-2 gap-2">
                        <a href="{{ route('city.merchant', $charId) }}" wire:navigate @click="mobileMenuOpen = false; $dispatch('location-leave', { text: 'Wizyta u Handlarza...', icon: 'fa-solid fa-shop', url: $el.href })"
                           class="flex items-center gap-2 p-2.5 sm:p-3 min-h-[44px] rounded-lg border border-amber-900/60 bg-stone-900/90 text-amber-100 hover:border-amber-500 transition-all min-w-0 {{ $navTutorialPulse['merchant'] ? 'ring-2 ring-amber-500 animate-pulse' : '' }}">
                            <i class="fa-solid fa-shop text-amber-400 text-sm shrink-0"></i>
                            <span class="truncate text-[11px] sm:text-xs">Handlarz</span>
                        </a>

                        <a href="{{ route('city.blacksmith', $charId) }}" wire:navigate @click="mobileMenuOpen = false; $dispatch('location-leave', { text: 'Podróż do Kowala...', icon: 'fa-solid fa-hammer', url: $el.href })"
                           class="flex items-center gap-2 p-2.5 sm:p-3 min-h-[44px] rounded-lg border border-amber-900/60 bg-stone-900/90 text-amber-100 hover:border-amber-500 transition-all min-w-0">
                            <i class="fa-solid fa-hammer text-amber-400 text-sm shrink-0"></i>
                            <span class="truncate text-[11px] sm:text-xs">Kowal</span>
                        </a>

                        <a href="{{ route('city.witch', $charId) }}" wire:navigate @click="mobileMenuOpen = false; $dispatch('location-leave', { text: 'Wizyta u Wiedźmy...', icon: 'fa-solid fa-wand-magic-sparkles', url: $el.href })"
                           class="flex items-center gap-2 p-2.5 sm:p-3 min-h-[44px] rounded-lg border border-amber-900/60 bg-stone-900/90 text-amber-100 hover:border-amber-500 transition-all min-w-0 {{ $navTutorialPulse['witch'] ? 'ring-2 ring-amber-500 animate-pulse' : '' }}">
                            <i class="fa-solid fa-wand-magic-sparkles text-amber-400 text-sm shrink-0"></i>
                            <span class="truncate text-[11px] sm:text-xs">Wiedźma</span>
                        </a>

                        <a href="{{ route('city.warlock', $charId) }}" wire:navigate @click="mobileMenuOpen = false; $dispatch('location-leave', { text: 'Czarnoksiężnik...', icon: 'fa-solid fa-skull', url: $el.href })"
                           class="flex items-center gap-1.5 p-2.5 sm:p-3 min-h-[44px] rounded-lg border border-amber-900/60 bg-stone-900/90 text-amber-100 hover:border-amber-500 transition-all relative min-w-0">
                            <i class="fa-solid fa-skull text-amber-400 text-sm shrink-0"></i>
                            <span class="truncate text-[10px] xs:text-[11px] sm:text-xs tracking-tight xs:tracking-wider min-w-0">Czarnoksiężnik</span>
                            @if($skillPointsCount > 0)
                                <span class="ml-auto shrink-0 w-4 h-4 bg-amber-500 rounded-full flex items-center justify-center text-[9px] text-slate-950 font-black shadow">!</span>
                            @endif
                        </a>

                        <a href="{{ route('city.market', $charId) }}" wire:navigate @click="mobileMenuOpen = false; $dispatch('location-leave', { text: 'Podróż na Rynek...', icon: 'fa-solid fa-scale-balanced', url: $el.href })"
                           class="flex items-center gap-2 p-2.5 sm:p-3 min-h-[44px] rounded-lg border border-amber-900/60 bg-stone-900/90 text-amber-100 hover:border-amber-500 transition-all min-w-0">
                            <i class="fa-solid fa-scale-balanced text-amber-400 text-sm shrink-0"></i>
                            <span class="truncate text-[11px] sm:text-xs">Rynek</span>
                        </a>
                    </div>
                </div>

                {{-- Sekcja: Społeczność & Rywalizacja --}}
                <div>
                    <div class="text-[10px] text-amber-500/80 mb-2 font-extrabold tracking-widest border-b border-stone-800 pb-1">Społeczność & Walka</div>
                    <div class="grid grid-cols-2 gap-2">
                        <a href="{{ route('city.arena', $charId) }}" wire:navigate @click="mobileMenuOpen = false; $dispatch('location-leave', { text: 'Wejście na Arenę...', icon: 'fa-solid fa-dungeon', url: $el.href })"
                           class="flex items-center gap-2 p-2.5 sm:p-3 min-h-[44px] rounded-lg border border-amber-900/60 bg-stone-900/90 text-amber-100 hover:border-amber-500 transition-all min-w-0 {{ $isArenaLocked ? 'opacity-60 grayscale' : '' }} {{ $navTutorialPulse['arena'] ? 'ring-2 ring-amber-500 animate-pulse' : '' }}">
                            <i class="fa-solid {{ $isArenaLocked ? 'fa-lock text-amber-500' : 'fa-dungeon text-amber-400' }} text-sm shrink-0"></i>
                            <span class="truncate text-[11px] sm:text-xs">Arena Walk</span>
                        </a>

                        <a href="{{ route('city.gladiator', $charId) }}" wire:navigate @click="mobileMenuOpen = false; $dispatch('location-leave', { text: 'Gladiator Shop...', icon: 'fa-solid fa-coins', url: $el.href })"
                           class="flex items-center gap-2 p-2.5 sm:p-3 min-h-[44px] rounded-lg border border-amber-900/60 bg-stone-900/90 text-amber-100 hover:border-amber-500 transition-all min-w-0">
                            <i class="fa-solid fa-coins text-amber-400 text-sm shrink-0"></i>
                            <span class="truncate text-[11px] sm:text-xs">Gladiator</span>
                        </a>

                        @if($isGuildLocked)
                            <a href="javascript:void(0)" @click="$dispatch('notify', { type: 'error', message: 'Gildia jest zablokowana! Wbij 10 poziom postaci i wyczekuj rozkazów Kapitana.' })"
                               class="flex items-center gap-2 p-2.5 sm:p-3 min-h-[44px] rounded-lg border border-stone-800 bg-stone-950/80 text-stone-500 opacity-60 grayscale cursor-not-allowed relative min-w-0">
                                <i class="fa-solid fa-flag text-amber-500/40 text-sm shrink-0"></i>
                                <span class="truncate text-[11px] sm:text-xs">Gildia</span>
                                <i class="fa-solid fa-lock text-amber-500 text-xs ml-auto shrink-0"></i>
                            </a>
                        @else
                            <a href="{{ route('city.guild', $charId) }}" wire:navigate @click="mobileMenuOpen = false; $dispatch('location-leave', { text: 'Podróż do Gildii...', icon: 'fa-solid fa-flag', url: $el.href })"
                               class="flex items-center gap-2 p-2.5 sm:p-3 min-h-[44px] rounded-lg border border-amber-900/60 bg-stone-900/90 text-amber-100 hover:border-amber-500 transition-all min-w-0 {{ $navTutorialPulse['guild'] ? 'ring-2 ring-amber-500 animate-pulse' : '' }}">
                                <i class="fa-solid fa-flag text-amber-400 text-sm shrink-0"></i>
                                <span class="truncate text-[11px] sm:text-xs">Gildia</span>
                            </a>
                        @endif

                        <a href="{{ route('city.mailbox', $charId) }}" wire:navigate @click="mobileMenuOpen = false; $dispatch('location-leave', { text: 'Skrzynka Pocztowa...', icon: 'fa-solid fa-envelope', url: $el.href })"
                           class="flex items-center gap-2 p-2.5 sm:p-3 min-h-[44px] rounded-lg border border-amber-900/60 bg-stone-900/90 text-amber-100 hover:border-amber-500 transition-all relative min-w-0">
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
                    <a href="{{ route('itemshop') }}" wire:navigate @click="mobileMenuOpen = false; $dispatch('location-leave', { text: 'Sklep Gemów...', icon: 'fa-solid fa-gem', url: $el.href })"
                       class="flex items-center justify-center gap-2 p-2.5 min-h-[44px] rounded-lg bg-gradient-to-r from-amber-600 via-yellow-600 to-amber-700 text-stone-950 font-black border border-yellow-300 shadow">
                        <i class="fa-solid fa-gem text-stone-950 text-sm"></i>
                        <span>SKLEP GEMÓW</span>
                    </a>

                    <a href="{{ route('city.settings', $charId) }}" wire:navigate @click="mobileMenuOpen = false; $dispatch('location-leave', { text: 'Otwieranie Ustawień...', icon: 'fa-solid fa-gear', url: $el.href })"
                       class="flex items-center justify-center gap-2 p-2.5 min-h-[44px] rounded-lg {{ request()->routeIs('city.settings') ? 'bg-gradient-to-r from-amber-600 via-yellow-600 to-amber-700 text-stone-950 font-black border border-yellow-300 shadow' : 'bg-gradient-to-b from-amber-950 via-stone-900 to-amber-950 text-amber-300 font-bold border border-amber-600/60 hover:border-amber-400' }} transition-all">
                        <i class="fa-solid fa-gear text-sm {{ request()->routeIs('city.settings') ? 'text-stone-950' : 'text-yellow-400' }}"></i>
                        <span>USTAWIENIA GRY</span>
                    </a>

                    <a href="https://discord.gg/YJa68KK9hC" target="_blank" rel="noopener noreferrer" @click="mobileMenuOpen = false"
                       class="w-full flex items-center justify-center gap-2 p-2.5 min-h-[44px] rounded-lg bg-gradient-to-r from-indigo-950 via-slate-900 to-indigo-950 text-indigo-200 font-bold border border-indigo-600/60 hover:border-indigo-400 transition-all cursor-pointer">
                        <i class="fa-brands fa-discord text-indigo-400 text-sm"></i>
                        <span>DISCORD</span>
                    </a>

                    <button @click="mobileMenuOpen = false; $dispatch('open-suggestion-modal')"
                            class="w-full flex items-center justify-center gap-2 p-2.5 min-h-[44px] rounded-lg bg-gradient-to-r from-indigo-950 via-slate-900 to-indigo-950 text-indigo-200 font-bold border border-indigo-600/60 hover:border-indigo-400 transition-all cursor-pointer">
                        <i class="fa-solid fa-comment-dots text-indigo-400 text-sm"></i>
                        <span>ZGŁOŚ SUGESTIĘ</span>
                    </button>

                    <a href="{{ route('characters.leave') }}" wire:navigate @click="mobileMenuOpen = false; $dispatch('location-leave', { text: 'Powrót do Lobby...', icon: 'fa-solid fa-right-from-bracket', url: $el.href })"
                       class="flex items-center justify-center gap-2 p-2.5 min-h-[44px] rounded-lg bg-gradient-to-r from-stone-900 via-red-950 to-stone-950 text-red-200 border border-red-700/60 hover:border-red-500 transition-all">
                        <i class="fa-solid fa-right-from-bracket text-red-400 text-sm"></i>
                        <span>POWRÓT DO LOBBY</span>
                    </a>
                </div>

            </div>
        </div>

        {{-- Bottom Fixed Navigation Bar --}}
        <div class="fixed bottom-0 w-full z-[9950] bg-stone-950/95 backdrop-blur-lg border-t-2 border-amber-700/80 shadow-[0_-5px_20px_rgba(0,0,0,0.8)]">
            <div class="flex justify-around items-center h-16 px-1">
                {{-- 1. Arena (najdalej od kciuka) --}}
                <a href="{{ route('city.arena', $charId) }}" wire:navigate
                   class="flex flex-col items-center justify-center w-full h-full transition-all duration-200 group {{ $isArenaLocked ? 'opacity-60 grayscale' : '' }} {{ request()->routeIs('city.arena*') ? 'bg-amber-500/15 text-amber-300 border-t-2 border-amber-400 shadow-[0_-4px_12px_rgba(245,158,11,0.3)]' : 'text-stone-400 hover:text-amber-200 hover:bg-stone-900/60' }} {{ $navTutorialPulse['arena'] ? 'ring-2 ring-amber-500 ring-inset animate-pulse' : '' }}"
                   @click="$dispatch('location-leave', { text: 'Wejście na Arenę...', icon: 'fa-solid fa-dungeon', url: $el.href })">
                    <span class="text-xl mb-1 group-hover:scale-110 transition-transform">
                        <i class="fa-solid {{ $isArenaLocked ? 'fa-lock text-amber-500' : 'fa-khanda text-amber-400' }} text-lg"></i>
                    </span>
                    <span class="text-[10px] font-bold uppercase tracking-wider" style="font-family: 'Cinzel', serif;">Arena</span>
                </a>

                {{-- 2. Profil (po lewej stronie Miasta) --}}
                <a href="{{ route('city.profile', $charId) }}" wire:navigate
                   class="flex flex-col items-center justify-center w-full h-full transition-all duration-200 relative group {{ request()->routeIs('city.profile') ? 'bg-amber-500/15 text-amber-300 border-t-2 border-amber-400 shadow-[0_-4px_12px_rgba(245,158,11,0.3)]' : 'text-stone-400 hover:text-amber-200 hover:bg-stone-900/60' }} {{ $navTutorialPulse['profile'] ? 'ring-2 ring-amber-500 ring-inset animate-pulse' : '' }}"
                   @click="$dispatch('location-leave', { text: 'Otwieranie Ekwipunku...', icon: 'fa-solid fa-user-shield', url: $el.href })">
                    <span class="text-xl mb-1 relative group-hover:scale-110 transition-transform">
                        <i class="fa-solid fa-user-shield text-amber-400 text-lg"></i>
                        @if($profileBadgeCount > 0)
                            <span class="absolute -top-1 -right-2 w-4 h-4 bg-amber-500 rounded-full flex items-center justify-center text-slate-950 text-[9px] font-black animate-bounce shadow">!</span>
                        @endif
                    </span>
                    <span class="text-[10px] font-bold uppercase tracking-wider" style="font-family: 'Cinzel', serif;">Profil</span>
                </a>

                {{-- 3. Miasto (Centrum) --}}
                <a href="{{ route('city.hub', $charId) }}" wire:navigate
                   class="flex flex-col items-center justify-center w-full h-full transition-all duration-200 relative group {{ request()->routeIs('city.hub') ? 'bg-amber-500/15 text-amber-300 border-t-2 border-amber-400 shadow-[0_-4px_12px_rgba(245,158,11,0.3)]' : 'text-stone-400 hover:text-amber-200 hover:bg-stone-900/60' }}"
                   @click="$dispatch('location-leave', { text: 'Podróż do Miasta...', icon: 'fa-solid fa-archway', url: $el.href })">
                    <span class="text-xl mb-1 relative group-hover:scale-110 transition-transform">
                        <i class="fa-solid fa-archway text-amber-400 text-lg"></i>
                        @if($hubTutorialPending)
                            <span class="absolute -top-1 -right-2 w-4 h-4 bg-amber-500 rounded-full flex items-center justify-center text-slate-950 text-[9px] font-black animate-bounce shadow">!</span>
                        @endif
                    </span>
                    <span class="text-[10px] font-bold uppercase tracking-wider" style="font-family: 'Cinzel', serif;">Miasto</span>
                </a>

                {{-- 4. Przygoda (po prawej stronie Miasta) --}}
                <a href="{{ route('city.adventure', $charId) }}" wire:navigate
                   class="flex flex-col items-center justify-center w-full h-full transition-all duration-200 relative group {{ request()->routeIs('city.adventure*') || request()->routeIs('adventure.*') ? 'bg-amber-500/15 text-amber-300 border-t-2 border-amber-400 shadow-[0_-4px_12px_rgba(245,158,11,0.3)]' : 'text-stone-400 hover:text-amber-200 hover:bg-stone-900/60' }} {{ $navTutorialPulse['adventure'] ? 'ring-2 ring-amber-500 ring-inset animate-pulse' : '' }}"
                   @click="$dispatch('location-leave', { text: 'Wyruszanie na Przygodę...', icon: 'fa-solid fa-map-location-dot', url: $el.href })">
                    <span class="text-xl mb-1 relative group-hover:scale-110 transition-transform">
                        <i class="fa-solid fa-map-location-dot text-amber-400 text-lg"></i>
                    </span>
                    <span class="text-[10px] font-bold uppercase tracking-wider" style="font-family: 'Cinzel', serif;">Przygoda</span>
                </a>

                {{-- 5. Więcej (Menu) --}}
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
