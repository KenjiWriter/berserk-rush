<div class="min-h-screen relative overflow-hidden" x-data="{ travelingTo: null }">
    {{-- Dynamic background --}}
    <div class="absolute inset-0 bg-cover bg-center bg-no-repeat" style="background-image: url('{{ asset('img/maps/shadow-mountains.png') }}');">
    </div>

    {{-- Dark overlay for depth --}}
    <div class="absolute inset-0 bg-black/50"></div>

    {{-- Transition Overlay --}}
    <div x-show="travelingTo" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         class="fixed inset-0 z-[100] flex items-center justify-center bg-black/80 backdrop-blur-sm"
         style="display: none;">
         <div class="relative w-full max-w-lg mx-auto p-8 text-center">
            <img src="{{ asset('img/avatars/plate.png') }}" class="absolute inset-0 w-full h-full object-cover rounded-2xl shadow-2xl border-4 border-amber-700">
            <div class="absolute inset-0 bg-amber-900/60 rounded-2xl"></div>
            <div class="relative z-10 flex flex-col items-center">
                <div class="text-6xl mb-4 animate-bounce" x-text="travelingTo === 'Miasto' ? '🏰' : '🏟️'"></div>
                <h2 class="text-3xl font-bold text-amber-100 medieval-font mb-4 drop-shadow-lg">Podróż do...</h2>
                <h3 class="text-2xl text-amber-300 font-bold drop-shadow-md mb-6" x-text="travelingTo"></h3>
                <div class="w-12 h-12 border-4 border-amber-500 border-t-transparent rounded-full animate-spin"></div>
            </div>
         </div>
    </div>

    @if (session('error'))
        <div class="absolute top-16 left-1/2 transform -translate-x-1/2 z-50">
            <div class="bg-red-100 border-2 border-red-600 rounded-lg px-4 py-2 shadow-lg">
                <p class="text-red-800 font-semibold text-sm">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    <div class="relative z-10 container mx-auto px-4 py-6 min-h-screen flex flex-col">
        {{-- Header with navigation --}}
        <div class="flex flex-col md:flex-row items-center md:justify-between mb-8 gap-4 text-center md:text-left">
            <h1 class="text-2xl md:text-4xl font-bold text-amber-100 medieval-font drop-shadow-2xl flex items-center gap-3 justify-center md:justify-start">
                <span class="text-4xl md:text-5xl">🏟️</span> Arena Gladiatorów
            </h1>
            <div class="flex flex-col sm:flex-row items-center space-y-3 sm:space-y-0 sm:space-x-3">
                <div class="text-amber-100 text-sm medieval-font text-center sm:text-right">
                    <div class="font-bold text-lg">{{ $character->name }}</div>
                    <div class="text-amber-300">Ranking: {{ $character->elo }} ({{ ucfirst($currentLeague) }})</div>
                    <div class="text-yellow-400">Żetony: {{ $character->arena_tokens }}</div>
                </div>
                <button wire:click="goTo('gladiator')" @click="travelingTo = 'Gladiator'" 
                    class="relative rounded-lg px-6 py-2 shadow-lg overflow-hidden group">
                    <img src="{{ asset('img/avatars/plate.png') }}" class="absolute inset-0 w-full h-full object-cover rounded-lg">
                    <div class="absolute inset-0 bg-amber-900/40 group-hover:bg-amber-800/40 transition-colors rounded-lg"></div>
                    <span class="relative text-amber-100 font-bold medieval-font drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)]">🏛️ Sklep Gladiatora</span>
                </button>
                <button wire:click="backToHub" @click="travelingTo = 'Miasto'" 
                    class="relative rounded-lg px-6 py-2 shadow-lg overflow-hidden group">
                    <img src="{{ asset('img/avatars/plate.png') }}" class="absolute inset-0 w-full h-full object-cover rounded-lg">
                    <div class="absolute inset-0 bg-amber-900/40 group-hover:bg-amber-800/40 transition-colors rounded-lg"></div>
                    <span class="relative text-amber-100 font-bold medieval-font drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)]">🏰 Powrót</span>
                </button>
            </div>
        </div>

        <div class="max-w-5xl mx-auto w-full relative flex-1">
            <img src="{{ asset('img/avatars/plate.png') }}" class="absolute inset-0 w-full h-full object-cover rounded-2xl shadow-2xl border-2 border-amber-900/50">
            <div class="absolute inset-0 bg-amber-950/80 rounded-2xl backdrop-blur-sm"></div>

            <div class="relative p-4 md:p-8">
                <div class="flex flex-col md:flex-row justify-between items-center mb-8 border-b-2 border-amber-800/50 pb-4 gap-4">
                    <div class="text-center md:text-left">
                        <h2 class="text-2xl md:text-3xl font-bold text-amber-100 medieval-font">Dostępni przeciwnicy</h2>
                        <p class="text-amber-300/80 text-xs md:text-sm mt-1">Znajdź rywala w swojej lidze i walcz o chwałę oraz żetony areny!</p>
                    </div>
                    <div class="text-center md:text-right">
                        <button wire:click="refreshOpponents" class="relative rounded-lg px-6 py-2 shadow-lg overflow-hidden group">
                            <img src="{{ asset('img/avatars/plate.png') }}" class="absolute inset-0 w-full h-full object-cover rounded-lg">
                            <div class="absolute inset-0 bg-amber-800/60 group-hover:bg-amber-700/60 transition-colors rounded-lg"></div>
                            <span class="relative text-amber-100 font-bold medieval-font flex items-center gap-2">
                                🔄 Odśwież ({{ 3 - ($character->pvp_refreshes_used ?? 0) }}/3)
                            </span>
                        </button>
                        @if($character->pvp_refreshes_reset_at)
                            <div class="text-xs text-amber-400 mt-1">Reset: {{ \Carbon\Carbon::parse($character->pvp_refreshes_reset_at)->format('H:i') }}</div>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @forelse($opponents as $opponent)
                        <div class="relative rounded-xl overflow-hidden shadow-lg border border-amber-800/30 group hover:border-amber-500/50 transition-colors">
                            <img src="{{ asset('img/avatars/plate.png') }}" class="absolute inset-0 w-full h-full object-cover">
                            <div class="absolute inset-0 bg-gradient-to-b from-amber-900/80 to-slate-900/90"></div>
                            
                            <div class="relative p-6 text-center flex flex-col items-center">
                                <div class="w-24 h-24 rounded-full overflow-hidden border-4 border-amber-700 shadow-xl mb-4 bg-black">
                                    <img src="{{ $opponent['avatar'] ? asset('img/avatars/'.$opponent['avatar'].'.png') : asset('img/avatars/default.png') }}" 
                                         class="w-full h-full object-cover">
                                </div>
                                <h3 class="text-xl font-bold text-amber-100 medieval-font mb-1">{{ $opponent['name'] }}</h3>
                                <div class="flex gap-4 text-sm font-semibold text-amber-300 mb-4">
                                    <span>Lvl: {{ $opponent['level'] }}</span>
                                    <span>Elo: {{ $opponent['elo'] }}</span>
                                </div>
                                
                                <button wire:click="challengeOpponent('{{ $opponent['id'] }}')" 
                                    wire:loading.attr="disabled"
                                    class="w-full relative rounded-lg px-4 py-2 shadow-lg overflow-hidden group/btn mt-auto disabled:opacity-50 disabled:cursor-wait">
                                    <img src="{{ asset('img/avatars/plate.png') }}" class="absolute inset-0 w-full h-full object-cover rounded-lg">
                                    <div class="absolute inset-0 bg-red-900/60 group-hover/btn:bg-red-800/60 transition-colors rounded-lg"></div>
                                    <span class="relative text-red-100 font-bold medieval-font drop-shadow-md">
                                        <span wire:loading.remove wire:target="challengeOpponent('{{ $opponent['id'] }}')">⚔️ Wyzwij</span>
                                        <span wire:loading wire:target="challengeOpponent('{{ $opponent['id'] }}')">
                                            <svg class="animate-spin h-5 w-5 mx-auto text-red-200 inline-block mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                            Przygotowanie...
                                        </span>
                                    </span>
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full text-center py-12">
                            <div class="text-5xl mb-4 opacity-50">👻</div>
                            <h3 class="text-2xl font-bold text-amber-200/50 medieval-font">Brak przeciwników w zasięgu.</h3>
                            <p class="text-amber-300/40 mt-2">Odśwież listę, aby poszukać ponownie.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
