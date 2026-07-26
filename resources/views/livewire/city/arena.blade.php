<div class="min-h-screen relative overflow-hidden" x-data="{ travelingTo: null }">
    {{-- Dynamic background --}}
    <div class="absolute inset-0 bg-cover bg-center bg-no-repeat" style="background-image: url('{{ asset('img/maps/shadow-mountains.png') }}');">
    </div>

    {{-- Dark overlay for depth --}}
    <div class="absolute inset-0 bg-black/50"></div>

    {{-- Transition Overlay --}}
    <div x-show="$data.travelingTo" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         class="fixed inset-0 z-[100] flex items-center justify-center bg-black/80 backdrop-blur-sm"
         style="display: none;">
         <div class="relative w-full max-w-lg mx-auto p-8 text-center">
            <img src="{{ asset('img/avatars/plate.png') }}" class="absolute inset-0 w-full h-full object-cover rounded-2xl shadow-2xl border-4 border-amber-700">
            <div class="absolute inset-0 bg-amber-900/60 rounded-2xl"></div>
            <div class="relative z-10 flex flex-col items-center">
                <div class="text-5xl mb-4 text-amber-300 animate-bounce">
                    <template x-if="$data.travelingTo === 'Miasto'">
                        <i class="fa-solid fa-castle"></i>
                    </template>
                    <template x-if="$data.travelingTo !== 'Miasto'">
                        <i class="fa-solid fa-chess-board"></i>
                    </template>
                </div>
                <h2 class="text-3xl font-bold text-amber-100 medieval-font mb-4 drop-shadow-lg">Podróż do...</h2>
                <h3 class="text-2xl text-amber-300 font-bold drop-shadow-md mb-6" x-text="$data.travelingTo"></h3>
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

    <div class="relative z-10 w-full px-6 md:px-10 lg:px-12 py-6 min-h-screen flex flex-col">
        {{-- Header with navigation --}}
        <div class="flex flex-col md:flex-row items-center md:justify-between mb-8 gap-4 text-center md:text-left">
            <h1 class="text-2xl md:text-4xl font-bold text-amber-100 medieval-font drop-shadow-2xl flex items-center gap-3 justify-center md:justify-start">
                <i class="fa-solid fa-chess-board text-amber-400 text-3xl md:text-4xl"></i> Arena Gladiatorów
            </h1>
            <div class="flex flex-col sm:flex-row items-center space-y-3 sm:space-y-0 sm:space-x-3 w-full sm:w-auto">
                <div class="text-amber-100 text-sm medieval-font text-center sm:text-right">
                    <div class="font-bold text-lg">{{ $character->name }}</div>
                    <div class="text-amber-300">Ranking: {{ $character->elo }} ({{ ucfirst($currentLeague) }})</div>
                    <div class="text-yellow-400">Żetony: {{ $character->arena_tokens }}</div>
                </div>
                <button wire:click="goTo('gladiator')" @click="travelingTo = 'Gladiator'" 
                    class="w-full sm:w-auto min-h-[44px] relative rounded-lg px-6 py-2.5 shadow-lg overflow-hidden group flex items-center justify-center">
                    <img src="{{ asset('img/avatars/plate.png') }}" class="absolute inset-0 w-full h-full object-cover rounded-lg">
                    <div class="absolute inset-0 bg-amber-900/40 group-hover:bg-amber-800/40 transition-colors rounded-lg"></div>
                    <span class="relative text-amber-100 font-bold medieval-font drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)] flex items-center justify-center gap-2">
                        <i class="fa-solid fa-store text-amber-300"></i> Sklep Gladiatora
                    </span>
                </button>
                <button wire:click="backToHub" @click="travelingTo = 'Miasto'; $dispatch('play-audio', { type: 'tab' })" 
                    class="w-full sm:w-auto min-h-[44px] relative rounded-lg px-6 py-2.5 shadow-lg overflow-hidden group flex items-center justify-center">
                    <img src="{{ asset('img/avatars/plate.png') }}" class="absolute inset-0 w-full h-full object-cover rounded-lg">
                    <div class="absolute inset-0 bg-amber-900/40 group-hover:bg-amber-800/40 transition-colors rounded-lg"></div>
                    <span class="relative text-amber-100 font-bold medieval-font drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)] flex items-center justify-center gap-2">
                        <i class="fa-solid fa-castle text-amber-300"></i> Powrót
                    </span>
                </button>
            </div>
        </div>

        <div class="w-full relative flex-1">
            <img src="{{ asset('img/avatars/plate.png') }}" class="absolute inset-0 w-full h-full object-cover rounded-2xl shadow-2xl border-2 border-amber-900/50">
            <div class="absolute inset-0 bg-amber-950/80 rounded-2xl backdrop-blur-sm"></div>

            <div class="relative p-4 md:p-8">
                {{-- Tabs navigation --}}
                <div class="flex border-b border-amber-800/60 mb-6 gap-2">
                    <button wire:click="switchTab('opponents')" 
                        class="px-3 sm:px-5 py-3 min-h-[44px] flex-1 sm:flex-initial font-bold medieval-font text-xs sm:text-base transition-all rounded-t-xl flex items-center justify-center gap-2 {{ $activeTab === 'opponents' ? 'bg-amber-900/90 text-amber-100 border-t-2 border-x border-amber-500/80 shadow-lg' : 'bg-stone-900/50 text-amber-300/70 hover:bg-stone-900/80 hover:text-amber-200 border-t border-x border-transparent' }}">
                        <i class="fa-solid fa-shield-halved text-amber-400"></i> Dostępni Przeciwnicy
                    </button>
                    <button wire:click="switchTab('ranking')" 
                        class="px-3 sm:px-5 py-3 min-h-[44px] flex-1 sm:flex-initial font-bold medieval-font text-xs sm:text-base transition-all rounded-t-xl flex items-center justify-center gap-2 {{ $activeTab === 'ranking' ? 'bg-amber-900/90 text-amber-100 border-t-2 border-x border-amber-500/80 shadow-lg' : 'bg-stone-900/50 text-amber-300/70 hover:bg-stone-900/80 hover:text-amber-200 border-t border-x border-transparent' }}">
                        <i class="fa-solid fa-trophy text-amber-400"></i> Ranking Chwały
                    </button>
                </div>

                @if($activeTab === 'opponents')
                    {{-- Section: Opponents --}}
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
                                    <i class="fa-solid fa-rotate-right text-amber-200"></i> Odśwież ({{ 3 - ($character->pvp_refreshes_used ?? 0) }}/3)
                                </span>
                            </button>
                            @if($character->pvp_refreshes_reset_at)
                                <div class="text-xs text-amber-400 mt-1">Reset: {{ \Carbon\Carbon::parse($character->pvp_refreshes_reset_at)->format('H:i') }}</div>
                            @endif
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @forelse($opponents as $opponent)
                            @php
                                $oppId = is_array($opponent) ? $opponent['id'] : $opponent->id;
                                $oppName = is_array($opponent) ? $opponent['name'] : $opponent->name;
                                $oppLvl = is_array($opponent) ? $opponent['level'] : $opponent->level;
                                $oppElo = is_array($opponent) ? $opponent['elo'] : $opponent->elo;
                                $oppAvatar = is_array($opponent) ? ($opponent['avatar'] ?? 'default.png') : ($opponent->avatar ?? 'default.png');
                                $avatarSrc = str_contains($oppAvatar, '/') || str_contains($oppAvatar, '.') ? asset('img/avatars/' . ltrim($oppAvatar, '/')) : asset('img/avatars/' . $oppAvatar . '.png');
                            @endphp
                            <div class="relative rounded-xl overflow-hidden shadow-lg border border-amber-800/30 group hover:border-amber-500/50 transition-colors">
                                <img src="{{ asset('img/avatars/plate.png') }}" class="absolute inset-0 w-full h-full object-cover">
                                <div class="absolute inset-0 bg-gradient-to-b from-amber-900/80 to-slate-900/90"></div>
                                
                                <div class="relative p-6 text-center flex flex-col items-center">
                                    <div class="w-24 h-24 rounded-full overflow-hidden border-4 border-amber-700 shadow-xl mb-4 bg-black">
                                        <img src="{{ $avatarSrc }}" class="w-full h-full object-cover" alt="{{ $oppName }}">
                                    </div>
                                    <h3 class="text-xl font-bold text-amber-100 medieval-font mb-1">{{ $oppName }}</h3>
                                    <div class="flex gap-4 text-sm font-semibold text-amber-300 mb-4">
                                        <span>Lvl: {{ $oppLvl }}</span>
                                        <span>Elo: {{ $oppElo }}</span>
                                    </div>
                                    
                                    <button wire:click="challengeOpponent('{{ $oppId }}')" 
                                        wire:loading.attr="disabled"
                                        class="w-full relative rounded-lg px-4 py-2 shadow-lg overflow-hidden group/btn mt-auto disabled:opacity-50 disabled:cursor-wait">
                                        <img src="{{ asset('img/avatars/plate.png') }}" class="absolute inset-0 w-full h-full object-cover rounded-lg">
                                        <div class="absolute inset-0 bg-red-900/60 group-hover/btn:bg-red-800/60 transition-colors rounded-lg"></div>
                                        <span class="relative text-red-100 font-bold medieval-font drop-shadow-md">
                                            <span wire:loading.remove wire:target="challengeOpponent('{{ $oppId }}')" class="flex items-center justify-center gap-1.5">
                                                <i class="fa-solid fa-hand-fist text-red-200"></i> Wyzwij
                                            </span>
                                            <span wire:loading wire:target="challengeOpponent('{{ $oppId }}')">
                                                <svg class="animate-spin h-5 w-5 mx-auto text-red-200 inline-block mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                                Przygotowanie...
                                            </span>
                                        </span>
                                    </button>
                                </div>
                            </div>

                        @empty
                            <div class="col-span-full text-center py-12">
                                <div class="text-5xl mb-4 text-amber-400/40"><i class="fa-solid fa-ghost"></i></div>
                                <h3 class="text-2xl font-bold text-amber-200/50 medieval-font">Brak przeciwników w zasięgu.</h3>
                                <p class="text-amber-300/40 mt-2">Odśwież listę, aby poszukać ponownie.</p>
                            </div>
                        @endforelse
                    </div>

                @elseif($activeTab === 'ranking')
                    {{-- Section: Glory Ranking --}}
                    <div class="mb-6 border-b-2 border-amber-800/50 pb-4 text-center md:text-left">
                        <h2 class="text-2xl md:text-3xl font-bold text-amber-100 medieval-font flex items-center justify-center md:justify-start gap-2">
                            <i class="fa-solid fa-crown text-yellow-400"></i> Ranking Chwały
                        </h2>
                        <p class="text-amber-300/80 text-xs md:text-sm mt-1">Zestawienie najpotężniejszych gladiatorów królestwa uszeregowanych według oceny ELO.</p>
                    </div>

                    @if($ranking && $ranking->count() > 0)
                        <div class="overflow-x-auto rounded-xl border border-amber-900/60 shadow-2xl bg-black/40 backdrop-blur-md">
                            <table class="w-full text-left text-amber-100">
                                <thead class="bg-amber-950/90 text-amber-300 uppercase text-xs medieval-font border-b border-amber-800/60">
                                    <tr>
                                        <th scope="col" class="px-6 py-4">Miejsce</th>
                                        <th scope="col" class="px-6 py-4">Gracz</th>
                                        <th scope="col" class="px-6 py-4">Poziom</th>
                                        <th scope="col" class="px-6 py-4">ELO</th>
                                        <th scope="col" class="px-6 py-4">Liga</th>
                                        <th scope="col" class="px-6 py-4 text-right">Akcja</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-amber-900/40 font-medium">
                                    @foreach($ranking as $index => $rowChar)
                                        @php
                                            $rankNumber = ($ranking->currentPage() - 1) * $ranking->perPage() + $loop->iteration;
                                            $isCurrentCharacter = $rowChar->id === $character->id;
                                            $avatar = $rowChar->avatar ?? 'default.png';
                                            $avatarSrc = str_contains($avatar, '/') || str_contains($avatar, '.') ? asset('img/avatars/' . ltrim($avatar, '/')) : asset('img/avatars/' . $avatar . '.png');
                                        @endphp
                                        <tr class="transition-colors hover:bg-amber-900/30 {{ $isCurrentCharacter ? 'bg-amber-900/40 border-l-4 border-l-amber-400' : '' }}">
                                            {{-- Miejsce --}}
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if($rankNumber === 1)
                                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-extrabold bg-yellow-500/20 text-yellow-300 border border-yellow-500/50 shadow-[0_0_10px_rgba(234,179,8,0.3)]">
                                                        <i class="fa-solid fa-crown text-yellow-400 mr-1.5 text-sm"></i> #1 Top Gladiator
                                                    </span>
                                                @elseif($rankNumber === 2)
                                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-slate-300/20 text-slate-200 border border-slate-400/50">
                                                        <i class="fa-solid fa-medal text-slate-300 mr-1.5 text-sm"></i> #2
                                                    </span>
                                                @elseif($rankNumber === 3)
                                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-amber-700/20 text-amber-400 border border-amber-600/50">
                                                        <i class="fa-solid fa-award text-amber-500 mr-1.5 text-sm"></i> #3
                                                    </span>
                                                @else
                                                    <span class="text-amber-300/70 font-mono text-sm font-semibold pl-2">#{{ $rankNumber }}</span>
                                                @endif
                                            </td>

                                            {{-- Gracz --}}
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-10 h-10 rounded-full overflow-hidden border-2 border-amber-700/80 bg-black shrink-0">
                                                        <img src="{{ $avatarSrc }}" class="w-full h-full object-cover" alt="{{ $rowChar->name }}">
                                                    </div>
                                                    <div>
                                                        <div class="font-bold text-amber-100 flex items-center gap-2">
                                                            {{ $rowChar->name }}
                                                            @if($isCurrentCharacter)
                                                                <span class="text-[10px] bg-amber-500 text-stone-950 font-black px-1.5 py-0.5 rounded uppercase">Ty</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>

                                            {{-- Poziom --}}
                                            <td class="px-6 py-4 whitespace-nowrap text-amber-200">
                                                Lvl {{ $rowChar->level }}
                                            </td>

                                            {{-- ELO --}}
                                            <td class="px-6 py-4 whitespace-nowrap font-bold text-amber-300">
                                                <i class="fa-solid fa-bolt text-amber-400 mr-1 text-xs"></i> {{ $rowChar->elo }}
                                            </td>

                                            {{-- Liga --}}
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @php
                                                    $league = strtolower($rowChar->league ?? 'bronze');
                                                @endphp
                                                @if($league === 'platinum' || $league === 'platyna')
                                                    <span class="px-2.5 py-1 rounded text-xs font-bold bg-cyan-900/60 text-cyan-200 border border-cyan-500/40">Platyna</span>
                                                @elseif($league === 'gold' || $league === 'zloto' || $league === 'złoto')
                                                    <span class="px-2.5 py-1 rounded text-xs font-bold bg-yellow-900/60 text-yellow-200 border border-yellow-500/40">Złoto</span>
                                                @elseif($league === 'silver' || $league === 'srebro')
                                                    <span class="px-2.5 py-1 rounded text-xs font-bold bg-slate-800/60 text-slate-200 border border-slate-500/40">Srebro</span>
                                                @else
                                                    <span class="px-2.5 py-1 rounded text-xs font-bold bg-amber-950/60 text-amber-400 border border-amber-800/40">Brąz</span>
                                                @endif
                                            </td>

                                            {{-- Akcja --}}
                                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                                @if(!$isCurrentCharacter)
                                                    <button wire:click="challengeOpponent('{{ $rowChar->id }}')"
                                                        wire:loading.attr="disabled"
                                                        class="relative rounded-lg px-4 py-1.5 shadow overflow-hidden group/btn disabled:opacity-50 disabled:cursor-wait">
                                                        <img src="{{ asset('img/avatars/plate.png') }}" class="absolute inset-0 w-full h-full object-cover rounded-lg">
                                                        <div class="absolute inset-0 bg-red-900/70 group-hover/btn:bg-red-800/70 transition-colors rounded-lg"></div>
                                                        <span class="relative text-red-100 text-xs font-bold medieval-font flex items-center gap-1">
                                                            <i class="fa-solid fa-hand-fist text-red-200"></i> Wyzwij
                                                        </span>
                                                    </button>
                                                @else
                                                    <span class="text-xs text-amber-400/50 italic">Twoja postać</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Pagination links --}}
                        <div class="mt-6">
                            {{ $ranking->links() }}
                        </div>
                    @else
                        <div class="text-center py-12 bg-black/30 rounded-xl border border-amber-900/40">
                            <i class="fa-solid fa-trophy text-5xl mb-4 text-amber-500/30"></i>
                            <h3 class="text-xl font-bold text-amber-200/60 medieval-font">Brak wpisów w rankingu.</h3>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>
