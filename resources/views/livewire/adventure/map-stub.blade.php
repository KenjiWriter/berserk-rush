<div class="min-h-screen relative overflow-hidden">
    {{-- Dynamic background per map --}}
    <div class="absolute inset-0 bg-cover bg-center bg-no-repeat" style="background-image: url('{{ $background }}');">
    </div>

    {{-- Dark overlay for depth --}}
    <div class="absolute inset-0 bg-black/60"></div>

    {{-- Warning message --}}
    @if (session('warning'))
        <div class="absolute top-4 left-1/2 transform -translate-x-1/2 z-50">
            <div class="bg-amber-100 border-2 border-amber-600 rounded-lg px-4 py-2 shadow-lg ring-1 ring-amber-800/50">
                <p class="text-amber-800 font-semibold text-sm">{{ session('warning') }}</p>
            </div>
        </div>
    @endif

    <div class="relative z-10 container mx-auto px-4 py-6 min-h-screen">
        {{-- Header with navigation --}}
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-3xl font-bold text-amber-100 medieval-font drop-shadow-2xl">
                🗺️ {{ $map->name }}
            </h1>

            <div class="flex space-x-3">
                <button wire:click="backToAdventure"
                    class="relative rounded-lg px-4 py-2 shadow-lg overflow-hidden group">
                    <img src="{{ asset('img/avatars/plate.png') }}" alt=""
                        class="absolute inset-0 w-full h-full object-cover rounded-lg">
                    <div class="absolute inset-0 bg-amber-900/20 rounded-lg"></div>
                    <span
                        class="relative text-amber-100 font-bold medieval-font drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)]">
                        🗺️ Mapy
                    </span>
                </button>
                <button wire:click="backToHub" class="relative rounded-lg px-4 py-2 shadow-lg overflow-hidden group">
                    <img src="{{ asset('img/avatars/plate.png') }}" alt=""
                        class="absolute inset-0 w-full h-full object-cover rounded-lg">
                    <div class="absolute inset-0 bg-amber-900/20 rounded-lg"></div>
                    <span
                        class="relative text-amber-100 font-bold medieval-font drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)]">
                        🏰 Miasto
                    </span>
                </button>
            </div>
        </div>

        {{-- Classic RPG Battle Layout --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 max-w-7xl mx-auto">

            {{-- Left Side - Player Panel --}}
            <div class="order-1 lg:order-1">
                <div
                    class="relative rounded-xl shadow-2xl overflow-hidden {{ $this->isPlayerTurn() ? 'ring-4 ring-amber-300 shadow-[0_0_30px_rgba(255,200,60,.4)]' : '' }}">
                    {{-- Wooden background --}}
                    <img src="{{ asset('img/avatars/plate.png') }}" alt=""
                        class="absolute inset-0 w-full h-full object-cover">

                    {{-- Wooden overlay for better contrast --}}
                    <div class="absolute inset-0 bg-amber-900/25"></div>

                    <div class="relative p-6 space-y-4">
                        {{-- Player Portrait --}}
                        <div class="text-center">
                            <div
                                class="w-28 h-28 mx-auto rounded-xl overflow-hidden ring-4 ring-amber-800/80 shadow-xl">
                                @if ($player['avatar'])
                                    <img src="{{ $player['avatar'] }}" alt="{{ $player['name'] }}"
                                        class="w-full h-full object-cover">
                                @else
                                    <div
                                        class="w-full h-full bg-gradient-to-b from-amber-200 to-amber-300 flex items-center justify-center text-4xl text-amber-800">
                                        ⚔️
                                    </div>
                                @endif
                            </div>

                            {{-- Name & Level --}}
                            <h3
                                class="mt-3 text-xl font-bold text-amber-100 medieval-font drop-shadow-[0_2px_4px_rgba(0,0,0,0.8)]">
                                {{ $player['name'] }}
                            </h3>
                            <p class="text-sm text-amber-200 tracking-wide drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)]">
                                Poziom {{ $player['level'] }}
                            </p>
                        </div>

                        {{-- Player HP Bar --}}
                        <div class="space-y-2">
                            <div
                                class="flex justify-between text-sm font-semibold text-amber-100 drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)]">
                                <span>❤️ Życie</span>
                                <span>{{ $this->getCurrentPlayerHp() }}/{{ $player['maxHp'] }}</span>
                            </div>
                            <div class="h-4 w-full rounded-full bg-black/40 ring-2 ring-amber-800/60 shadow-inner">
                                <div class="h-full rounded-full bg-gradient-to-r from-red-500 via-red-600 to-red-500 shadow-sm transition-all duration-500"
                                    style="width: {{ $this->getPlayerHpPercent() }}%"></div>
                            </div>
                        </div>

                        {{-- Player Stats --}}
                        <div>
                            <h4
                                class="text-sm font-semibold text-amber-100 mb-3 medieval-font drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)]">
                                📊 Atrybuty
                            </h4>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="relative rounded-lg overflow-hidden shadow-lg">
                                    <img src="{{ asset('img/avatars/plate.png') }}" alt=""
                                        class="absolute inset-0 w-full h-full object-cover">
                                    <div class="absolute inset-0 bg-red-900/30"></div>
                                    <div class="relative px-3 py-3 text-center">
                                        <div class="text-xs font-medium text-amber-200 tracking-wider">💪 STR</div>
                                        <div
                                            class="text-xl font-bold text-amber-100 drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)]">
                                            {{ $player['stats']['str'] }}
                                        </div>
                                    </div>
                                </div>
                                <div class="relative rounded-lg overflow-hidden shadow-lg">
                                    <img src="{{ asset('img/avatars/plate.png') }}" alt=""
                                        class="absolute inset-0 w-full h-full object-cover">
                                    <div class="absolute inset-0 bg-blue-900/30"></div>
                                    <div class="relative px-3 py-3 text-center">
                                        <div class="text-xs font-medium text-amber-200 tracking-wider">🧠 INT</div>
                                        <div
                                            class="text-xl font-bold text-amber-100 drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)]">
                                            {{ $player['stats']['int'] }}
                                        </div>
                                    </div>
                                </div>
                                <div class="relative rounded-lg overflow-hidden shadow-lg">
                                    <img src="{{ asset('img/avatars/plate.png') }}" alt=""
                                        class="absolute inset-0 w-full h-full object-cover">
                                    <div class="absolute inset-0 bg-green-900/30"></div>
                                    <div class="relative px-3 py-3 text-center">
                                        <div class="text-xs font-medium text-amber-200 tracking-wider">❤️ VIT</div>
                                        <div
                                            class="text-xl font-bold text-amber-100 drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)]">
                                            {{ $player['stats']['vit'] }}
                                        </div>
                                    </div>
                                </div>
                                <div class="relative rounded-lg overflow-hidden shadow-lg">
                                    <img src="{{ asset('img/avatars/plate.png') }}" alt=""
                                        class="absolute inset-0 w-full h-full object-cover">
                                    <div class="absolute inset-0 bg-yellow-900/30"></div>
                                    <div class="relative px-3 py-3 text-center">
                                        <div class="text-xs font-medium text-amber-200 tracking-wider">💨 AGI</div>
                                        <div
                                            class="text-xl font-bold text-amber-100 drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)]">
                                            {{ $player['stats']['agi'] }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Center - Parchment Battle Log --}}
            <div class="order-3 lg:order-2">
                <section class="relative rounded-2xl shadow-2xl overflow-hidden h-[500px] flex flex-col">
                    {{-- Parchment background --}}
                    <img src="{{ asset('img/avatars/plate.png') }}" alt=""
                        class="absolute inset-0 w-full h-full object-cover">

                    {{-- Parchment overlay --}}
                    <div class="absolute inset-0 bg-amber-100/80"></div>

                    {{-- Header --}}
                    <header class="relative p-4 text-center border-b-2 border-amber-800/30">
                        <h3 class="font-serif text-2xl text-amber-900 tracking-wider medieval-font drop-shadow-sm">
                            ⚔️ Kronika Bitwy
                        </h3>
                    </header>

                    {{-- Battle Log Scroll Area --}}
                    <div class="relative flex-1 overflow-y-auto p-4">
                        <ul class="space-y-3 text-amber-900">
                            @if ($currentTurn == 0)
                                <li class="text-center py-8">
                                    <div class="text-4xl mb-3">⚔️</div>
                                    <div class="text-amber-800 font-serif italic text-lg">
                                        Naciśnij "Start" aby rozpocząć bitewną potyczkę...
                                    </div>
                                </li>
                            @else
                                @for ($i = 0; $i < min($currentTurn, count($turns)); $i++)
                                    @php $turn = $turns[$i]; @endphp
                                    <li class="leading-relaxed bg-white/30 rounded-lg px-3 py-2 shadow-sm">
                                        <span
                                            class="inline-block w-8 text-center text-xs font-bold bg-amber-800 text-amber-100 rounded px-1 mr-2">
                                            T{{ $i + 1 }}
                                        </span>
                                        @if ($turn['type'] == 'miss')
                                            <span class="text-slate-700 italic font-semibold">
                                                <strong>{{ $turn['actor'] == 'player' ? $player['name'] : $enemy['name'] }}</strong>
                                                pudłuje atak!
                                            </span>
                                        @else
                                            <span
                                                class="{{ $turn['actor'] == 'player' ? 'text-emerald-800' : 'text-red-800' }} font-semibold">
                                                <strong>{{ $turn['actor'] == 'player' ? $player['name'] : $enemy['name'] }}</strong>
                                                zadaje <strong class="text-amber-900">{{ $turn['value'] }}</strong>
                                                obrażeń
                                                @if ($turn['crit'])
                                                    <span class="font-bold text-red-900">✨ KRYTYK!</span>
                                                @endif
                                            </span>
                                        @endif
                                    </li>
                                @endfor

                                {{-- Battle Result --}}
                                @if ($currentTurn >= count($turns))
                                    <li
                                        class="text-center mt-6 p-4 rounded-xl {{ $result == 'win' ? 'bg-green-200/90 border-2 border-green-600 text-green-800' : 'bg-red-200/90 border-2 border-red-600 text-red-800' }} shadow-lg">
                                        <div class="text-4xl mb-2">{{ $result == 'win' ? '🏆' : '💀' }}</div>
                                        <div class="text-2xl font-bold medieval-font">
                                            {{ $result == 'win' ? 'TRIUMF!' : 'KLĘSKA!' }}
                                        </div>
                                        <div class="text-sm mt-1 font-semibold">
                                            {{ $result == 'win' ? 'Nieprzyjaciel został pokonany!' : 'Zostałeś pokonany w walce...' }}
                                        </div>
                                    </li>
                                @endif
                            @endif
                        </ul>
                    </div>

                    {{-- Battle Controls --}}
                    <footer class="relative p-4 border-t-2 border-amber-800/30">
                        <div class="flex items-center justify-center gap-3">
                            @if ($currentTurn < count($turns))
                                <button wire:click="togglePlayback"
                                    class="relative rounded-lg px-6 py-3 shadow-lg overflow-hidden">
                                    <img src="{{ asset('img/avatars/plate.png') }}" alt=""
                                        class="absolute inset-0 w-full h-full object-cover">
                                    <div class="absolute inset-0 bg-emerald-800/40"></div>
                                    <span
                                        class="relative text-white font-bold medieval-font drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)]">
                                        {{ $isPlaying ? '⏸️ Pauza' : '▶️ Rozpocznij' }}
                                    </span>
                                </button>
                            @endif

                            <div class="flex gap-2">
                                <button wire:click="setPlaybackSpeed(1)"
                                    class="relative rounded-lg px-4 py-2 shadow-lg overflow-hidden">
                                    <img src="{{ asset('img/avatars/plate.png') }}" alt=""
                                        class="absolute inset-0 w-full h-full object-cover">
                                    <div
                                        class="absolute inset-0 {{ $playbackSpeed == 1 ? 'bg-amber-700/60' : 'bg-amber-900/40' }}">
                                    </div>
                                    <span
                                        class="relative {{ $playbackSpeed == 1 ? 'text-amber-100' : 'text-amber-200' }} font-bold medieval-font drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)]">
                                        ⏯️ x1
                                    </span>
                                </button>
                                <button wire:click="setPlaybackSpeed(2)"
                                    class="relative rounded-lg px-4 py-2 shadow-lg overflow-hidden">
                                    <img src="{{ asset('img/avatars/plate.png') }}" alt=""
                                        class="absolute inset-0 w-full h-full object-cover">
                                    <div
                                        class="absolute inset-0 {{ $playbackSpeed == 2 ? 'bg-amber-700/60' : 'bg-amber-900/40' }}">
                                    </div>
                                    <span
                                        class="relative {{ $playbackSpeed == 2 ? 'text-amber-100' : 'text-amber-200' }} font-bold medieval-font drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)]">
                                        ⏩ x2
                                    </span>
                                </button>
                            </div>

                            @if ($currentTurn >= count($turns))
                                <button wire:click="resetEncounter"
                                    class="relative rounded-lg px-6 py-3 shadow-lg overflow-hidden">
                                    <img src="{{ asset('img/avatars/plate.png') }}" alt=""
                                        class="absolute inset-0 w-full h-full object-cover">
                                    <div class="absolute inset-0 bg-slate-700/60"></div>
                                    <span
                                        class="relative text-slate-100 font-bold medieval-font drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)]">
                                        🔄 Nowa Walka
                                    </span>
                                </button>
                            @endif
                        </div>
                    </footer>
                </section>
            </div>

            {{-- Right Side - Enemy Panel --}}
            <div class="order-2 lg:order-3">
                <div
                    class="relative rounded-xl shadow-2xl overflow-hidden {{ $this->isEnemyTurn() ? 'ring-4 ring-red-300 shadow-[0_0_30px_rgba(255,100,100,.4)]' : '' }}">
                    {{-- Wooden background --}}
                    <img src="{{ asset('img/avatars/plate.png') }}" alt=""
                        class="absolute inset-0 w-full h-full object-cover">

                    {{-- Wooden overlay for better contrast --}}
                    <div class="absolute inset-0 bg-red-900/20"></div>

                    <div class="relative p-6 space-y-4">
                        {{-- Enemy Portrait --}}
                        <div class="text-center">
                            <div class="w-28 h-28 mx-auto rounded-xl overflow-hidden ring-4 ring-red-800/80 shadow-xl">
                                <img src="{{ asset('img/monsters/placeholder.png') }}" alt="{{ $enemy['name'] }}"
                                    class="w-full h-full object-cover">
                            </div>

                            {{-- Name & Level --}}
                            <h3
                                class="mt-3 text-xl font-bold text-amber-100 medieval-font drop-shadow-[0_2px_4px_rgba(0,0,0,0.8)]">
                                {{ $enemy['name'] }}
                            </h3>
                            <p class="text-sm text-amber-200 tracking-wide drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)]">
                                Poziom {{ $enemy['level'] }}
                            </p>
                        </div>

                        {{-- Enemy HP Bar --}}
                        <div class="space-y-2">
                            <div
                                class="flex justify-between text-sm font-semibold text-amber-100 drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)]">
                                <span>❤️ Życie</span>
                                <span>{{ $this->getCurrentEnemyHp() }}/{{ $enemy['maxHp'] }}</span>
                            </div>
                            <div class="h-4 w-full rounded-full bg-black/40 ring-2 ring-red-800/60 shadow-inner">
                                <div class="h-full rounded-full bg-gradient-to-r from-red-500 via-red-600 to-red-500 shadow-sm transition-all duration-500"
                                    style="width: {{ $this->getEnemyHpPercent() }}%"></div>
                            </div>
                        </div>

                        {{-- Enemy Stats --}}
                        <div>
                            <h4
                                class="text-sm font-semibold text-amber-100 mb-3 medieval-font drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)]">
                                ⚡ Statystyki
                            </h4>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="relative rounded-lg overflow-hidden shadow-lg">
                                    <img src="{{ asset('img/avatars/plate.png') }}" alt=""
                                        class="absolute inset-0 w-full h-full object-cover">
                                    <div class="absolute inset-0 bg-red-900/40"></div>
                                    <div class="relative px-3 py-3 text-center">
                                        <div class="text-xs font-medium text-red-200 tracking-wider">⚔️ ATK</div>
                                        <div
                                            class="text-xl font-bold text-amber-100 drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)]">
                                            {{ $enemy['stats']['atk'] }}
                                        </div>
                                    </div>
                                </div>
                                <div class="relative rounded-lg overflow-hidden shadow-lg">
                                    <img src="{{ asset('img/avatars/plate.png') }}" alt=""
                                        class="absolute inset-0 w-full h-full object-cover">
                                    <div class="absolute inset-0 bg-slate-900/40"></div>
                                    <div class="relative px-3 py-3 text-center">
                                        <div class="text-xs font-medium text-slate-200 tracking-wider">🛡️ DEF</div>
                                        <div
                                            class="text-xl font-bold text-amber-100 drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)]">
                                            {{ $enemy['stats']['def'] }}
                                        </div>
                                    </div>
                                </div>
                                <div class="relative rounded-lg overflow-hidden shadow-lg">
                                    <img src="{{ asset('img/avatars/plate.png') }}" alt=""
                                        class="absolute inset-0 w-full h-full object-cover">
                                    <div class="absolute inset-0 bg-yellow-900/40"></div>
                                    <div class="relative px-3 py-3 text-center">
                                        <div class="text-xs font-medium text-yellow-200 tracking-wider">💨 AGI</div>
                                        <div
                                            class="text-xl font-bold text-amber-100 drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)]">
                                            {{ $enemy['stats']['agi'] }}
                                        </div>
                                    </div>
                                </div>
                                <div class="relative rounded-lg overflow-hidden shadow-lg">
                                    <img src="{{ asset('img/avatars/plate.png') }}" alt=""
                                        class="absolute inset-0 w-full h-full object-cover">
                                    <div class="absolute inset-0 bg-blue-900/40"></div>
                                    <div class="relative px-3 py-3 text-center">
                                        <div class="text-xs font-medium text-blue-200 tracking-wider">🧠 INT</div>
                                        <div
                                            class="text-xl font-bold text-amber-100 drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)]">
                                            {{ $enemy['stats']['int'] }}
                                        </div>
                                    </div>
                                </div>
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
            });
        });

        window.addEventListener('beforeunload', () => {
            clearInterval(playbackInterval);
        });
    </script>
</div>
