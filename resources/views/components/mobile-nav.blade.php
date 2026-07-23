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
    <div class="lg:hidden fixed bottom-0 w-full z-[9900] bg-gray-900 border-t-2 border-amber-700 shadow-[0_-5px_15px_rgba(0,0,0,0.5)]">
        <div class="flex justify-around items-center h-16">
            <a href="{{ route('city.hub', $charId) }}" wire:navigate class="flex flex-col items-center justify-center w-full h-full text-amber-200 hover:bg-gray-800 transition-colors {{ request()->routeIs('city.hub') ? 'bg-gray-800 text-amber-500 border-t-2 border-amber-500' : '' }}" @click="$dispatch('location-leave', { text: 'Podróż do Miasta...', icon: '🏰' })">
                <span class="text-2xl mb-1">🏰</span>
                <span class="text-[10px] font-bold uppercase tracking-wider" style="font-family: 'Cinzel', serif;">Miasto</span>
            </a>
            <a href="{{ route('city.profile', $charId) }}" wire:navigate class="flex flex-col items-center justify-center w-full h-full text-amber-200 hover:bg-gray-800 transition-colors relative {{ request()->routeIs('city.profile') ? 'bg-gray-800 text-amber-500 border-t-2 border-amber-500' : '' }}" @click="$dispatch('location-leave', { text: 'Otwieranie Ekwipunku...', icon: '👤' })">
                <span class="text-2xl mb-1 relative">
                    👤
                    @if($profileBadgeCount > 0)
                        <span class="absolute -top-1 -right-2 w-4 h-4 bg-amber-500 rounded-full flex items-center justify-center text-slate-950 text-[9px] font-black animate-bounce">!</span>
                    @endif
                </span>
                <span class="text-[10px] font-bold uppercase tracking-wider" style="font-family: 'Cinzel', serif;">Profil</span>
            </a>
            <a href="{{ route('city.quests', $charId) }}" wire:navigate class="flex flex-col items-center justify-center w-full h-full text-amber-200 hover:bg-gray-800 transition-colors relative {{ request()->routeIs('city.quests') ? 'bg-gray-800 text-amber-500 border-t-2 border-amber-500' : '' }}" @click="$dispatch('location-leave', { text: 'Otwieranie Wyzwań...', icon: '📜' })">
                <span class="text-2xl mb-1 relative">
                    📜
                    @if($questBadgeCount > 0)
                        <span class="absolute -top-1 -right-2 w-4 h-4 bg-amber-500 rounded-full flex items-center justify-center text-slate-950 text-[9px] font-black animate-bounce">!</span>
                    @endif
                </span>
                <span class="text-[10px] font-bold uppercase tracking-wider" style="font-family: 'Cinzel', serif;">Wyzwania</span>
            </a>
            <a href="{{ route('city.arena', $charId) }}" wire:navigate class="flex flex-col items-center justify-center w-full h-full text-amber-200 hover:bg-gray-800 transition-colors {{ request()->routeIs('city.arena*') ? 'bg-gray-800 text-amber-500 border-t-2 border-amber-500' : '' }}" @click="$dispatch('location-leave', { text: 'Wejście na Arenę...', icon: '🏟️' })">
                <span class="text-2xl mb-1">🏟️</span>
                <span class="text-[10px] font-bold uppercase tracking-wider" style="font-family: 'Cinzel', serif;">Arena</span>
            </a>
        </div>
    </div>
@endif
