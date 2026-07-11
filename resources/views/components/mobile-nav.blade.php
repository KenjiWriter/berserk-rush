@if (session('active_character'))
    @php
        $charId = session('active_character');
    @endphp
    <div class="lg:hidden fixed bottom-0 w-full z-[9900] bg-gray-900 border-t-2 border-amber-700 shadow-[0_-5px_15px_rgba(0,0,0,0.5)]">
        <div class="flex justify-around items-center h-16">
            <a href="{{ route('city.hub', $charId) }}" wire:navigate class="flex flex-col items-center justify-center w-full h-full text-amber-200 hover:bg-gray-800 transition-colors {{ request()->routeIs('city.hub') ? 'bg-gray-800 text-amber-500 border-t-2 border-amber-500' : '' }}">
                <span class="text-2xl mb-1">🏰</span>
                <span class="text-[10px] font-bold uppercase tracking-wider" style="font-family: 'Cinzel', serif;">Miasto</span>
            </a>
            <a href="{{ route('city.profile', $charId) }}" wire:navigate class="flex flex-col items-center justify-center w-full h-full text-amber-200 hover:bg-gray-800 transition-colors {{ request()->routeIs('city.profile') ? 'bg-gray-800 text-amber-500 border-t-2 border-amber-500' : '' }}">
                <span class="text-2xl mb-1">👤</span>
                <span class="text-[10px] font-bold uppercase tracking-wider" style="font-family: 'Cinzel', serif;">Profil</span>
            </a>
            <a href="{{ route('city.adventure', $charId) }}" wire:navigate class="flex flex-col items-center justify-center w-full h-full text-amber-200 hover:bg-gray-800 transition-colors {{ request()->routeIs('city.adventure') ? 'bg-gray-800 text-amber-500 border-t-2 border-amber-500' : '' }}">
                <span class="text-2xl mb-1">🗺️</span>
                <span class="text-[10px] font-bold uppercase tracking-wider" style="font-family: 'Cinzel', serif;">Wyprawy</span>
            </a>
            <a href="{{ route('city.arena', $charId) }}" wire:navigate class="flex flex-col items-center justify-center w-full h-full text-amber-200 hover:bg-gray-800 transition-colors {{ request()->routeIs('city.arena*') ? 'bg-gray-800 text-amber-500 border-t-2 border-amber-500' : '' }}">
                <span class="text-2xl mb-1">🏟️</span>
                <span class="text-[10px] font-bold uppercase tracking-wider" style="font-family: 'Cinzel', serif;">Arena</span>
            </a>
        </div>
    </div>
@endif
