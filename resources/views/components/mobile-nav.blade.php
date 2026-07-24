@if (session('active_character'))
    @php
        $charId = session('active_character');
        $character = \App\Infrastructure\Persistence\Character::find($charId);
        
        $claimableQuests = $character ? $character->characterQuests()->where('status', 'completed')->count() : 0;
        $availableQuests = $character ? app(\App\Application\Quests\QuestService::class)->getAvailableQuests($character)->count() : 0;
        $questBadgeCount = $claimableQuests + $availableQuests;

        $unclaimedAchievements = $character ? \App\Infrastructure\Persistence\CharacterAchievement::where('character_id', $character->id)->whereNotNull('completed_at')->where('rewarded', false)->count() : 0;
        $unassignedStatPoints = $character ? ($character->character_points ?? 0) : 0;
        $profileBadgeCount = $unclaimedAchievements + $unassignedStatPoints;
    @endphp
    <div class="lg:hidden fixed bottom-0 w-full z-[9900] bg-stone-950/95 backdrop-blur-lg border-t-2 border-amber-700/80 shadow-[0_-5px_20px_rgba(0,0,0,0.8)]">
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
        </div>
    </div>
@endif
