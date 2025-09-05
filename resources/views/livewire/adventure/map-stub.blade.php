<div class="min-h-screen relative overflow-hidden">
    {{-- Dynamic background per map --}}
    <div class="absolute inset-0 bg-cover bg-center bg-no-repeat" style="background-image: url('{{ $background }}');">
    </div>

    {{-- Dark overlay for readability --}}
    <div class="absolute inset-0 bg-black/30"></div>

    {{-- Warning message --}}
    @if (session('warning'))
        <div class="absolute top-4 left-1/2 transform -translate-x-1/2 z-50">
            <div class="bg-amber-100/95 border-2 border-amber-600 rounded-lg px-4 py-2 shadow-lg backdrop-blur-sm">
                <p class="text-amber-800 font-semibold text-sm">{{ session('warning') }}</p>
            </div>
        </div>
    @endif

    <div class="relative z-10 container mx-auto px-4 py-8 min-h-screen">
        {{-- Header with navigation --}}
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-3xl font-bold text-amber-100 medieval-font drop-shadow-2xl">
                🗺️ {{ $map->name }}
            </h1>

            <div class="flex space-x-3">
                <button wire:click="backToAdventure"
                    class="bg-slate-600/90 hover:bg-slate-700 text-amber-200 font-bold py-2 px-4 rounded-lg transition-all duration-200 backdrop-blur-sm medieval-font">
                    🗺️ Mapy
                </button>
                <button wire:click="backToHub"
                    class="bg-slate-600/90 hover:bg-slate-700 text-amber-200 font-bold py-2 px-4 rounded-lg transition-all duration-200 backdrop-blur-sm medieval-font">
                    🏰 Miasto
                </button>
            </div>
        </div>

        {{-- Main combat layout --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 h-[calc(100vh-12rem)]">
            {{-- Left column - Player --}}
            <div class="lg:col-span-3 flex flex-col">
                <div class="bg-green-50/95 border-4 border-green-700 rounded-lg p-4 shadow-2xl backdrop-blur-sm">
                    {{-- Decorative corners --}}
                    <div
                        class="absolute top-0 left-0 w-6 h-6 bg-green-800 transform rotate-45 -translate-x-3 -translate-y-3">
                    </div>
                    <div
                        class="absolute top-0 right-0 w-6 h-6 bg-green-800 transform rotate-45 translate-x-3 -translate-y-3">
                    </div>
                    <div
                        class="absolute bottom-0 left-0 w-6 h-6 bg-green-800 transform rotate-45 -translate-x-3 translate-y-3">
                    </div>
                    <div
                        class="absolute bottom-0 right-0 w-6 h-6 bg-green-800 transform rotate-45 translate-x-3 translate-y-3">
                    </div>

                    <div class="relative">
                        {{-- Player avatar --}}
                        <div class="text-center mb-4">
                            <div
                                class="w-20 h-20 mx-auto border-4 border-amber-600 rounded-full overflow-hidden bg-gradient-to-b from-amber-200 to-amber-300 mb-2">
                                @if ($player['avatar'])
                                    <img src="{{ $player['avatar'] }}" alt="{{ $player['name'] }}"
                                        class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-2xl text-amber-700">
                                        ⚔️</div>
                                @endif
                            </div>
                            <h3 class="text-lg font-bold text-green-900 medieval-font">{{ $player['name'] }}</h3>
                            <p class="text-sm text-green-700">Poziom {{ $player['level'] }}</p>
                        </div>

                        {{-- Player HP bar --}}
                        <div class="mb-4">
                            <div class="flex justify-between text-sm font-semibold text-green-800 mb-1">
                                <span>Życie</span>
                                <span>{{ $player['hp'] }}/{{ $player['maxHp'] }}</span>
                            </div>
                            <div class="w-full bg-red-200 rounded-full h-3 border border-red-300">
                                <div class="bg-gradient-to-r from-red-500 to-red-600 h-full rounded-full transition-all duration-300"
                                    style="width: {{ ($player['hp'] / $player['maxHp']) * 100 }}%"></div>
                            </div>
                        </div>

                        {{-- Player stats --}}
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <div class="bg-red-100 rounded p-2 text-center">
                                <div class="font-bold text-red-800">STR</div>
                                <div class="text-red-700">{{ $player['stats']['str'] }}</div>
                            </div>
                            <div class="bg-blue-100 rounded p-2 text-center">
                                <div class="font-bold text-blue-800">INT</div>
                                <div class="text-blue-700">{{ $player['stats']['int'] }}</div>
                            </div>
                            <div class="bg-green-100 rounded p-2 text-center">
                                <div class="font-bold text-green-800">VIT</div>
                                <div class="text-green-700">{{ $player['stats']['vit'] }}</div>
                            </div>
                            <div class="bg-yellow-100 rounded p-2 text-center">
                                <div class="font-bold text-yellow-800">AGI</div>
                                <div class="text-yellow-700">{{ $player['stats']['agi'] }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Center column - Combat log --}}
            <div class="lg:col-span-6 flex flex-col">
                <div
                    class="bg-amber-50/95 border-4 border-amber-700 rounded-lg shadow-2xl backdrop-blur-sm flex-1 flex flex-col">
                    {{-- Pergamin header --}}
                    <div
                        class="bg-gradient-to-r from-amber-200 to-amber-300 border-b-4 border-amber-700 rounded-t-lg p-3">
                        <h3 class="text-xl font-bold text-amber-900 text-center medieval-font">⚔️ Przebieg Walki</h3>
                    </div>

                    {{-- Combat log scroll area --}}
                    <div class="flex-1 p-4 overflow-y-auto max-h-80">
                        <div class="space-y-2 text-sm font-medium">
                            @if ($currentTurn == 0)
                                <div class="text-center text-amber-700 italic">
                                    Naciśnij "Start" aby rozpocząć walkę...
                                </div>
                            @else
                                @for ($i = 0; $i < min($currentTurn, count($turns)); $i++)
                                    @php $turn = $turns[$i]; @endphp
                                    <div
                                        class="flex items-center space-x-2 p-2 rounded-lg {{ $turn['actor'] == 'player' ? 'bg-green-100' : 'bg-red-100' }}">
                                        <span
                                            class="font-bold {{ $turn['actor'] == 'player' ? 'text-green-800' : 'text-red-800' }}">
                                            T{{ $i + 1 }}:
                                        </span>
                                        <span
                                            class="{{ $turn['actor'] == 'player' ? 'text-green-700' : 'text-red-700' }}">
                                            @if ($turn['type'] == 'miss')
                                                <strong>{{ $turn['actor'] == 'player' ? $player['name'] : $enemy['name'] }}</strong>
                                                chybił!
                                            @else
                                                <strong>{{ $turn['actor'] == 'player' ? $player['name'] : $enemy['name'] }}</strong>
                                                zadał {{ $turn['value'] }} obrażeń
                                                @if ($turn['crit'])
                                                    <span class="text-yellow-600 font-bold">(KRYTYK!)</span>
                                                @endif
                                            @endif
                                        </span>
                                    </div>
                                @endfor

                                {{-- Result message --}}
                                @if ($currentTurn >= count($turns))
                                    <div
                                        class="text-center mt-4 p-3 rounded-lg {{ $result == 'win' ? 'bg-green-200 text-green-800' : 'bg-red-200 text-red-800' }}">
                                        <span class="text-2xl">{{ $result == 'win' ? '🎉' : '💀' }}</span>
                                        <div class="font-bold text-lg">
                                            {{ $result == 'win' ? 'ZWYCIĘSTWO!' : 'PORAŻKA!' }}
                                        </div>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>

                    {{-- Controls --}}
                    <div
                        class="border-t-4 border-amber-700 bg-gradient-to-r from-amber-200 to-amber-300 rounded-b-lg p-4">
                        <div class="flex items-center justify-center space-x-4">
                            @if ($currentTurn < count($turns))
                                <button wire:click="togglePlayback"
                                    class="bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-700 hover:to-emerald-800 text-white font-bold py-2 px-4 rounded-lg transition-all duration-200 medieval-font">
                                    {{ $isPlaying ? '⏸️ Pauza' : '▶️ Start' }}
                                </button>
                            @endif

                            <div class="flex space-x-2">
                                <button wire:click="setPlaybackSpeed(1)"
                                    class="py-2 px-3 rounded-lg font-bold transition-all duration-200 medieval-font {{ $playbackSpeed == 1 ? 'bg-amber-600 text-white' : 'bg-amber-100 text-amber-800 hover:bg-amber-200' }}">
                                    x1
                                </button>
                                <button wire:click="setPlaybackSpeed(2)"
                                    class="py-2 px-3 rounded-lg font-bold transition-all duration-200 medieval-font {{ $playbackSpeed == 2 ? 'bg-amber-600 text-white' : 'bg-amber-100 text-amber-800 hover:bg-amber-200' }}">
                                    x2
                                </button>
                            </div>

                            @if ($currentTurn >= count($turns))
                                <button wire:click="resetEncounter"
                                    class="bg-slate-600 hover:bg-slate-700 text-amber-200 font-bold py-2 px-4 rounded-lg transition-all duration-200 medieval-font">
                                    🔄 Reset
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right column - Enemy --}}
            <div class="lg:col-span-3 flex flex-col">
                <div class="bg-red-50/95 border-4 border-red-700 rounded-lg p-4 shadow-2xl backdrop-blur-sm">
                    {{-- Decorative corners --}}
                    <div
                        class="absolute top-0 left-0 w-6 h-6 bg-red-800 transform rotate-45 -translate-x-3 -translate-y-3">
                    </div>
                    <div
                        class="absolute top-0 right-0 w-6 h-6 bg-red-800 transform rotate-45 translate-x-3 -translate-y-3">
                    </div>
                    <div
                        class="absolute bottom-0 left-0 w-6 h-6 bg-red-800 transform rotate-45 -translate-x-3 translate-y-3">
                    </div>
                    <div
                        class="absolute bottom-0 right-0 w-6 h-6 bg-red-800 transform rotate-45 translate-x-3 translate-y-3">
                    </div>

                    <div class="relative">
                        {{-- Enemy portrait --}}
                        <div class="text-center mb-4">
                            <div
                                class="w-20 h-20 mx-auto border-4 border-red-600 rounded-full overflow-hidden bg-gradient-to-b from-red-200 to-red-300 mb-2">
                                <div class="w-full h-full flex items-center justify-center text-2xl text-red-700">👹
                                </div>
                            </div>
                            <h3 class="text-lg font-bold text-red-900 medieval-font">{{ $enemy['name'] }}</h3>
                            <p class="text-sm text-red-700">Poziom {{ $enemy['level'] }}</p>
                        </div>

                        {{-- Enemy HP bar --}}
                        <div class="mb-4">
                            <div class="flex justify-between text-sm font-semibold text-red-800 mb-1">
                                <span>Życie</span>
                                <span>{{ $enemy['hp'] }}/{{ $enemy['maxHp'] }}</span>
                            </div>
                            <div class="w-full bg-red-200 rounded-full h-3 border border-red-300">
                                <div class="bg-gradient-to-r from-red-500 to-red-600 h-full rounded-full transition-all duration-300"
                                    style="width: {{ ($enemy['hp'] / $enemy['maxHp']) * 100 }}%"></div>
                            </div>
                        </div>

                        {{-- Enemy stats --}}
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <div class="bg-red-100 rounded p-2 text-center">
                                <div class="font-bold text-red-800">ATK</div>
                                <div class="text-red-700">{{ $enemy['stats']['atk'] }}</div>
                            </div>
                            <div class="bg-gray-100 rounded p-2 text-center">
                                <div class="font-bold text-gray-800">DEF</div>
                                <div class="text-gray-700">{{ $enemy['stats']['def'] }}</div>
                            </div>
                            <div class="bg-yellow-100 rounded p-2 text-center">
                                <div class="font-bold text-yellow-800">AGI</div>
                                <div class="text-yellow-700">{{ $enemy['stats']['agi'] }}</div>
                            </div>
                            <div class="bg-blue-100 rounded p-2 text-center">
                                <div class="font-bold text-blue-800">INT</div>
                                <div class="text-blue-700">{{ $enemy['stats']['int'] }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&display=swap');

        .medieval-font {
            font-family: 'Cinzel', serif;
        }
    </style>

    <script>
        let playbackInterval;

        document.addEventListener('livewire:init', () => {
            Livewire.on('start-playback', (event) => {
                clearInterval(playbackInterval);
                const speed = event.speed || 1;
                const delay = Math.floor(800 / speed);

                playbackInterval = setInterval(() => {
                    @this.nextTurn();
                }, delay);
            });

            Livewire.on('stop-playback', () => {
                clearInterval(playbackInterval);
            });

            Livewire.on('update-playback-speed', (event) => {
                if (playbackInterval) {
                    clearInterval(playbackInterval);
                    const speed = event.speed || 1;
                    const delay = Math.floor(800 / speed);

                    playbackInterval = setInterval(() => {
                        @this.nextTurn();
                    }, delay);
                }
            });

            Livewire.on('encounter-finished', (event) => {
                clearInterval(playbackInterval);
                // Optional: Show victory/defeat animation
            });
        });

        // Cleanup on page unload
        window.addEventListener('beforeunload', () => {
            clearInterval(playbackInterval);
        });
    </script>
</div>
