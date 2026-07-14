<div x-data="{ travelingTo: null }"
    class="min-h-screen bg-gradient-to-b from-green-900/90 via-emerald-800/90 to-green-900/90 text-amber-100 relative overflow-hidden">
    {{-- Background adventure image --}}
    <div class="absolute inset-0 bg-cover bg-center bg-no-repeat opacity-60"
        style="background-image: url('{{ asset('img/adventure-background.png') }}');">
    </div>

    {{-- Dark overlay for readability --}}
    <div class="absolute inset-0 bg-gradient-to-b from-slate-900/40 via-slate-800/50 to-slate-900/60"></div>

    {{-- Floating adventure elements --}}
    <div class="absolute inset-0 pointer-events-none">
        <div class="adventure-element adventure-element-1">⚔️</div>
        <div class="adventure-element adventure-element-2">🗡️</div>
        <div class="adventure-element adventure-element-3">🛡️</div>
        <div class="adventure-element adventure-element-4">💎</div>
        <div class="adventure-element adventure-element-5">🏹</div>
    </div>

    {{-- Transition Overlay --}}
    <div x-show="travelingTo" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         class="fixed inset-0 z-[100] flex items-center justify-center bg-black/80 backdrop-blur-sm"
         style="display: none;">
         
         <div class="relative w-full max-w-lg mx-auto p-8 text-center">
            <img src="{{ asset('img/avatars/plate.png') }}" class="absolute inset-0 w-full h-full object-cover rounded-2xl shadow-2xl border-4 border-emerald-700">
            <div class="absolute inset-0 bg-green-900/40 rounded-2xl"></div>
            
            <div class="relative z-10 flex flex-col items-center">
                <div class="text-6xl mb-4 animate-bounce">🗺️</div>
                <h2 class="text-3xl font-bold text-amber-100 medieval-font mb-4 drop-shadow-lg">
                    Przenoszenie do...
                </h2>
                <h3 class="text-2xl text-emerald-300 font-bold drop-shadow-md mb-6" x-text="travelingTo"></h3>
                
                <div class="w-12 h-12 border-4 border-amber-500 border-t-transparent rounded-full animate-spin"></div>
            </div>
         </div>
    </div>

    <div class="relative container mx-auto px-4 py-8 min-h-screen">
        @php
            $gameStage = auth()->user()->game_stage;
        @endphp

        @if($gameStage == 9)
            <livewire:global.tutorial-overlay :step="10" />
        @endif

        {{-- Header with character info --}}
        <div class="flex items-center justify-between mb-8">
            <div
                class="bg-gradient-to-r from-green-50/95 to-green-100/95 border-4 border-green-700 rounded-lg p-4 shadow-2xl backdrop-blur-sm">
                <div class="flex items-center space-x-3">
                    {{-- Character avatar --}}
                    <div
                        class="w-12 h-12 border-2 border-green-700 rounded-full overflow-hidden bg-gradient-to-b from-green-200 to-green-300">
                        @if ($character->avatar && file_exists(public_path('img/avatars/' . $character->avatar . '.png')))
                            <img src="{{ asset('img/avatars/' . $character->avatar . '.png') }}"
                                alt="Avatar {{ $character->avatar }}" class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-lg text-green-700">
                                ⚔️
                            </div>
                        @endif
                    </div>

                    {{-- Character info --}}
                    <div>
                        <h2 class="text-xl font-bold text-green-900 medieval-font">{{ $character->name }}</h2>
                        <div class="text-sm text-green-700">
                            Poziom {{ $character->level }} • {{ $character->xp }} XP •
                            {{ number_format($character->gold) }} złota
                        </div>
                    </div>
                </div>
            </div>

            {{-- Back button --}}
            <button wire:click="backToHub" @click="$dispatch('location-leave')"
                class="bg-gradient-to-r from-slate-600 to-slate-700 hover:from-slate-700 hover:to-slate-800 text-amber-200 font-bold py-2 px-4 rounded-lg transition-all duration-200 transform hover:scale-105 shadow-lg medieval-font">
                🏰 Powrót do miasta
            </button>
        </div>

        {{-- Title --}}
        <div class="text-center mb-8">
            <h1
                class="text-5xl font-bold bg-gradient-to-r from-green-300 via-emerald-400 to-green-500 bg-clip-text text-transparent medieval-font drop-shadow-2xl mb-2">
                🗺️ Wybierz Przygodę
            </h1>
            <p class="text-xl text-green-200 font-semibold drop-shadow-lg mb-6">
                Twój poziom: {{ $character->level }} • Wybierz mapę odpowiednią dla Ciebie
            </p>

            {{-- Tabs --}}
            <div class="inline-flex bg-green-900/50 rounded-lg p-1 border border-green-700/50 mb-4">
                <button wire:click="setTab('maps')" 
                    class="px-8 py-3 rounded-md font-bold text-lg transition-all duration-200 {{ $tab === 'maps' ? 'bg-gradient-to-r from-green-600 to-emerald-600 text-white shadow-lg' : 'text-green-300 hover:text-green-100 hover:bg-green-800/50' }}">
                    🌲 Mapy
                </button>
                <button wire:click="setTab('dungeons')" 
                    class="px-8 py-3 rounded-md font-bold text-lg transition-all duration-200 {{ $tab === 'dungeons' ? 'bg-gradient-to-r from-green-600 to-emerald-600 text-white shadow-lg' : 'text-green-300 hover:text-green-100 hover:bg-green-800/50' }}">
                    🏰 Lochy
                </button>
            </div>
        </div>

        {{-- Map access error --}}
        @error('map_access')
            <div class="mb-6 p-4 bg-red-100/90 border-2 border-red-600 rounded-lg backdrop-blur-sm max-w-2xl mx-auto">
                <p class="text-red-800 font-semibold text-center">{{ $message }}</p>
            </div>
        @enderror

        {{-- MAPS TAB --}}
        @if($tab === 'maps')
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 max-w-6xl mx-auto">
            @foreach ($maps as $map)
                @php
                    $isAccessible = $map->isAccessibleBy($character);
                    $isCurrentLevel = $character->level >= $map->level_min && $character->level <= $map->level_max;

                    // Najpierw sprawdź czy obraz z bazy istnieje (dodaj img/ prefix jeśli potrzeba)
                    $imagePath = null;
                    if ($map->image_path) {
                        if (str_starts_with($map->image_path, 'img/')) {
                            $imagePath = $map->image_path;
                        } else {
                            $imagePath = 'img/' . $map->image_path;
                        }
                    }

                    $imageExists = $imagePath && file_exists(public_path($imagePath));

                    // Jeśli nie istnieje, użyj hardcoded mapping
                    if (!$imageExists) {
                        $hardcodedImages = [
                            'Mroczny Las' => 'img/maps/dark-forest.png',
                            'Stare Ruiny' => 'img/maps/old-ruins.png',
                            'Jaskinia Trolli' => 'img/maps/troll-cave.png',
                            'Pustkowia Orków' => 'img/maps/orc-wasteland.png',
                            'Bagna Grozy' => 'img/maps/horror-swamps.png',
                            'Góry Cienia' => 'img/maps/shadow-mountains.png',
                            'Wieża Magów' => 'img/maps/shadow-mountains.png',
                            'Skażone Miasto' => 'img/maps/corrupted-city.png',
                        ];

                        $fallbackPath = $hardcodedImages[$map->name] ?? null;
                        if ($fallbackPath && file_exists(public_path($fallbackPath))) {
                            $imagePath = $fallbackPath;
                            $imageExists = true;
                        }
                    }
                @endphp

                <div class="relative group" x-data="{ showBestiaryModal: false, showBossModal: false, selectedMonster: '{{ $map->monsters->first()->id ?? '' }}' }">
                    @php
                        $isFirstMapTutorial = $isAccessible && $gameStage == 10 && $map->level_min == 0;
                    @endphp
                    <div
                        class="bg-gradient-to-br from-green-50/90 to-green-100/90 border-4 {{ $isAccessible ? 'border-green-700' : 'border-gray-500' }} rounded-lg shadow-2xl backdrop-blur-sm {{ $isAccessible ? 'hover:from-green-100/95 hover:to-green-200/95 hover:shadow-3xl' : 'opacity-50' }} transition-all duration-300 {{ $isAccessible && empty($map->monsters) ? 'transform hover:scale-105 cursor-pointer' : '' }} {{ $isFirstMapTutorial ? 'animate-[pulse_1.5s_ease-in-out_infinite] ring-4 ring-amber-500 scale-105 shadow-[0_0_20px_rgba(245,158,11,0.6)] relative z-10' : '' }}">

                        {{-- Decorative corners --}}
                        @if ($isAccessible)
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
                        @endif

                        {{-- Current level indicator --}}
                        @if ($isCurrentLevel)
                            <div
                                class="absolute -top-2 -right-2 bg-yellow-500 text-yellow-900 px-3 py-1 rounded-full text-sm font-bold shadow-lg border-2 border-yellow-600 z-10">
                                🌟 TWÓJ POZIOM
                            </div>
                        @endif

                        <div class="relative p-6">
                            {{-- Map image --}}
                            <div
                                class="w-full h-40 rounded-lg mb-4 overflow-hidden border-2 {{ $isAccessible ? 'border-green-600' : 'border-gray-600' }} relative">
                                @if ($imageExists)
                                    {{-- Rzeczywisty obraz mapy --}}
                                    <img src="{{ asset($imagePath) }}" alt="{{ $map->name }}"
                                        class="w-full h-full object-cover {{ $isAccessible ? '' : 'grayscale' }} transition-all duration-300"
                                        loading="lazy">

                                    {{-- Overlay z kłódką dla niedostępnych map --}}
                                    @if (!$isAccessible)
                                        <div class="absolute inset-0 bg-black/50 flex items-center justify-center">
                                            <div class="text-4xl text-gray-300">🔒</div>
                                        </div>
                                    @endif
                                @else
                                    {{-- Emoji placeholder jako ostatnia opcja --}}
                                    <div
                                        class="w-full h-full {{ $isAccessible ? 'bg-gradient-to-b from-green-200 to-green-400' : 'bg-gradient-to-b from-gray-300 to-gray-500' }} flex items-center justify-center">
                                        <div class="text-4xl {{ $isAccessible ? 'text-green-800' : 'text-gray-700' }}">
                                            @switch($map->name)
                                                @case('Mroczny Las')
                                                    🌲
                                                @break

                                                @case('Stare Ruiny')
                                                    🏛️
                                                @break

                                                @case('Jaskinia Trolli')
                                                    🕳️
                                                @break

                                                @case('Pustkowia Orków')
                                                    🏜️
                                                @break

                                                @case('Bagna Grozy')
                                                    🌿
                                                @break

                                                @case('Góry Cienia')
                                                    ⛰️
                                                @break

                                                @case('Wieża Magów')
                                                    🗼
                                                @break

                                                @case('Skażone Miasto')
                                                    🏙️
                                                @break

                                                @default
                                                    🗺️
                                            @endswitch
                                        </div>
                                    </div>
                                @endif
                            </div>

                            {{-- Map info --}}
                            <div class="text-center">
                                <h3
                                    class="text-2xl font-bold {{ $isAccessible ? 'text-green-900' : 'text-gray-700' }} medieval-font mb-2">
                                    {{ $map->name }}
                                </h3>

                                <div
                                    class="text-lg font-semibold {{ $isAccessible ? 'text-green-800' : 'text-gray-600' }} mb-3">
                                    Poziom {{ $map->level_range }}
                                    @if (isset($map->tier))
                                        • Tier {{ $map->tier }}
                                    @endif
                                </div>

                                {{-- Action button --}}
                                @if ($isAccessible)
                                    <button @click="travelingTo = '{{ addslashes($map->name) }}'; $dispatch('play-audio', { type: 'combat' }); setTimeout(() => $wire.enterMap('{{ $map->id }}'), 500)"
                                        class="w-full bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-700 hover:to-emerald-800 text-white font-bold py-3 px-4 rounded-lg transition-all duration-200 transform hover:scale-105 shadow-lg medieval-font mb-4">
                                        ⚔️ Wejdź na mapę
                                    </button>
                                    
                                    {{-- World Boss --}}
                                    @if(isset($activeWorldBosses[$map->id]))
                                        <button @click="showBossModal = true" class="w-full mb-4 bg-purple-900/80 hover:bg-purple-800 text-purple-200 font-bold py-2 rounded-lg border-2 border-purple-600 transition-colors shadow-lg shadow-purple-900/50">
                                            👑 World Boss: {{ $activeWorldBosses[$map->id]->monster->name }}
                                        </button>
                                    @elseif(isset($defeatedWorldBosses[$map->id]))
                                        <div class="w-full mb-4 bg-gray-800/80 border border-gray-600 rounded-lg p-3 text-center">
                                            <p class="text-gray-400 font-bold text-sm">👑 World Boss powróci o: {{ now()->addHour()->startOfHour()->format('H:i') }}</p>
                                        </div>
                                    @endif

                                    <button @click="showBestiaryModal = true" class="w-full mt-2 bg-gradient-to-r from-amber-700 to-amber-900 hover:from-amber-600 hover:to-amber-800 text-amber-100 font-bold py-2 px-4 rounded-lg transition-colors text-sm border-2 border-amber-500 shadow-[0_0_15px_rgba(217,119,6,0.3)] medieval-font">
                                        📖 Otwórz Bestiariusz
                                    </button>
                                @else
                                    <div
                                        class="w-full bg-gray-400 text-gray-700 font-bold py-3 px-4 rounded-lg cursor-not-allowed medieval-font">
                                        🔒 Niedostępne
                                    </div>
                                @endif

                                {{-- Level requirement info --}}
                                @if (!$isAccessible)
                                    <div class="mt-2 text-xs text-gray-600">
                                        @if ($character->level < $map->level_min)
                                            Wymagany poziom: {{ $map->level_min }}
                                        @else
                                            Za wysoki poziom (max: {{ $map->level_max }})
                                        @endif
                                    </div>
                                @endif
                                
                                {{-- Bestiary Modal --}}
                                <template x-teleport="body">
                                    <div x-show="showBestiaryModal" style="display: none;" 
                                         class="fixed inset-0 z-[200] flex items-center justify-center p-2 sm:p-4 bg-black/80 backdrop-blur-sm">
                                        
                                        <div @click.outside="showBestiaryModal = false" class="bg-[url('{{ asset('img/avatars/plate.png') }}')] bg-cover bg-center border-4 border-amber-900 rounded-xl max-w-5xl w-full h-[90vh] sm:h-[80vh] shadow-2xl relative flex flex-col overflow-hidden">
                                            {{-- Dark overlay for text readability --}}
                                            <div class="absolute inset-0 bg-amber-100/90"></div>

                                            <button @click="showBestiaryModal = false" class="absolute top-2 right-4 z-20 text-amber-900 hover:text-red-700 text-3xl font-bold drop-shadow-md">&times;</button>
                                            
                                            <div class="relative z-10 flex flex-col h-full p-2 sm:p-4">
                                                <h2 class="text-2xl sm:text-3xl font-bold text-center text-amber-900 medieval-font mb-4 border-b-2 border-amber-800/30 pb-2 drop-shadow-sm">
                                                    📖 Bestiariusz: {{ $map->name }}
                                                </h2>

                                                @if($map->monsters->isEmpty())
                                                    <div class="flex items-center justify-center h-full">
                                                        <p class="text-amber-800 italic text-xl font-bold">Brak danych o przeciwnikach na tej mapie.</p>
                                                    </div>
                                                @else
                                                    {{-- Tabs --}}
                                                    <div class="flex overflow-x-auto gap-2 mb-4 pb-2 custom-scrollbar">
                                                        @foreach($map->monsters as $monster)
                                                            <button @click="selectedMonster = '{{ $monster->id }}'" 
                                                                :class="selectedMonster == '{{ $monster->id }}' ? 'bg-amber-800 text-amber-100 shadow-inner -translate-y-1' : 'bg-amber-200/80 text-amber-900 hover:bg-amber-300 hover:-translate-y-0.5'"
                                                                class="px-4 py-2 rounded-t-lg font-bold border-t-2 border-x-2 border-amber-800/50 whitespace-nowrap transition-all duration-200 flex items-center gap-2">
                                                                @if($monster->type && $monster->type->value === 'undead') 💀
                                                                @elseif($monster->type && $monster->type->value === 'demon') 👹
                                                                @elseif($monster->type && $monster->type->value === 'beast') 🐺
                                                                @elseif($monster->type && $monster->type->value === 'orc') 🧌
                                                                @else 👹 @endif
                                                                {{ $monster->name }}
                                                            </button>
                                                        @endforeach
                                                    </div>

                                                    {{-- Book Pages --}}
                                                    <div class="flex flex-col md:flex-row flex-1 gap-4 overflow-y-auto custom-scrollbar bg-amber-50/50 rounded-lg p-2 sm:p-4 border-2 border-amber-800/20 shadow-inner">
                                                        @foreach($map->monsters as $monster)
                                                            <div x-show="selectedMonster == '{{ $monster->id }}'" class="flex flex-col md:flex-row w-full gap-6">
                                                                {{-- Left Page: Stats & Avatar --}}
                                                                <div class="w-full md:w-1/2 flex flex-col items-center">
                                                                    <div class="w-32 h-32 sm:w-48 sm:h-48 rounded-xl overflow-hidden ring-4 ring-amber-800/80 shadow-2xl mb-4 relative">
                                                                        @if(!empty($monster->avatar))
                                                                            <img src="{{ route('assets.monsters.avatars', ['filename' => $monster->avatar]) }}"
                                                                                alt="{{ $monster->name }}"
                                                                                class="w-full h-full object-cover">
                                                                        @else
                                                                            <img src="{{ asset('img/monsters/placeholder.png') }}"
                                                                                alt="{{ $monster->name }}"
                                                                                class="w-full h-full object-cover">
                                                                        @endif
                                                                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                                                                        <div class="absolute bottom-2 left-0 w-full text-center text-amber-100 font-bold medieval-font text-xl drop-shadow-[0_2px_2px_rgba(0,0,0,1)]">
                                                                            Lvl {{ $monster->level }}
                                                                        </div>
                                                                    </div>
                                                                    
                                                                    <h3 class="text-2xl font-bold text-amber-900 medieval-font mb-2 text-center">{{ $monster->name }}</h3>
                                                                    
                                                                    @if($monster->type)
                                                                        <div class="bg-amber-800 text-amber-100 px-3 py-1 rounded-full text-sm font-bold shadow-md mb-4">
                                                                            Typ: {{ $monster->type->label() }}
                                                                        </div>
                                                                    @endif

                                                                    <div class="w-full bg-white/40 rounded-lg p-4 border border-amber-800/30">
                                                                        <h4 class="font-bold text-amber-900 mb-2 border-b border-amber-800/20 pb-1">⚡ Podstawowe parametry</h4>
                                                                        <div class="grid grid-cols-2 gap-2 text-sm">
                                                                            <div class="flex justify-between items-center bg-amber-100/50 p-2 rounded">
                                                                                <span class="text-amber-800 font-bold">HP</span>
                                                                                <span class="text-red-700 font-bold">{{ $monster->stats['hp'] ?? $monster->level * 20 }} ❤️</span>
                                                                            </div>
                                                                            <div class="flex justify-between items-center bg-amber-100/50 p-2 rounded">
                                                                                <span class="text-amber-800 font-bold">ATK</span>
                                                                                <span class="text-amber-900 font-bold">{{ $monster->stats['atk'] ?? '?' }} 🗡️</span>
                                                                            </div>
                                                                            <div class="flex justify-between items-center bg-amber-100/50 p-2 rounded">
                                                                                <span class="text-amber-800 font-bold">DEF</span>
                                                                                <span class="text-slate-700 font-bold">{{ $monster->stats['def'] ?? '?' }} 🛡️</span>
                                                                            </div>
                                                                            <div class="flex justify-between items-center bg-amber-100/50 p-2 rounded">
                                                                                <span class="text-amber-800 font-bold">AGI</span>
                                                                                <span class="text-green-700 font-bold">{{ $monster->stats['agi'] ?? '?' }} 🍃</span>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                {{-- Right Page: Loot --}}
                                                                <div class="w-full md:w-1/2">
                                                                    <h4 class="text-xl font-bold text-amber-900 medieval-font mb-4 border-b border-amber-800/30 pb-2">🎁 Możliwe Zdobycze</h4>
                                                                    
                                                                    <div class="space-y-3">
                                                                        @if($monster->lootTable && $monster->lootTable->entries->isNotEmpty())
                                                                            @php
                                                                                $totalWeight = max(1, $monster->lootTable->entries->sum('weight'));
                                                                            @endphp
                                                                            @foreach($monster->lootTable->entries->sortByDesc('weight') as $entry)
                                                                                @php
                                                                                    $chance = round(($entry->weight / $totalWeight) * 100, 1);
                                                                                    $isQuestItem = false;
                                                                                    if (in_array($entry->reward_type, ['item', 'material']) && $entry->itemTemplate) {
                                                                                        if ($entry->itemTemplate->type === 'quest_item') {
                                                                                            if (!$entry->itemTemplate->quest_id || !in_array($entry->itemTemplate->quest_id, $activeQuestIds)) {
                                                                                                continue;
                                                                                            }
                                                                                            $isQuestItem = true;
                                                                                        }
                                                                                    }
                                                                                @endphp
                                                                                <div class="bg-white/60 rounded-lg p-3 border border-amber-800/20 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
                                                                                    {{-- Background chance indicator --}}
                                                                                    <div class="absolute inset-y-0 left-0 bg-amber-200/40 transition-all" style="width: {{ $chance }}%"></div>
                                                                                    
                                                                                    <div class="relative z-10 flex items-center justify-between">
                                                                                        <div class="flex items-center gap-3">
                                                                                            <div class="w-10 h-10 rounded bg-amber-100 border border-amber-300 flex items-center justify-center text-xl shadow-inner">
                                                                                                @if($entry->reward_type === 'gold') 💰
                                                                                                @elseif($entry->reward_type === 'xp') ✨
                                                                                                @elseif(in_array($entry->reward_type, ['item', 'material']) && $entry->itemTemplate)
                                                                                                    <img src="{{ route('assets.items', ['filename' => $entry->itemTemplate->icon]) }}" 
                                                                                                         onerror="this.src='{{ route('assets.items', ['filename' => 'default.png']) }}'" 
                                                                                                         class="w-8 h-8 object-contain">
                                                                                                @endif
                                                                                            </div>
                                                                                            <div>
                                                                                                <div class="font-bold text-amber-900">
                                                                                                    @if($entry->reward_type === 'gold') Złoto
                                                                                                    @elseif($entry->reward_type === 'xp') Punkty Doświadczenia
                                                                                                    @elseif(in_array($entry->reward_type, ['item', 'material']) && $entry->itemTemplate)
                                                                                                        <span class="{{ $entry->itemTemplate->rarity === 'legendary' ? 'text-orange-600' : ($entry->itemTemplate->rarity === 'epic' ? 'text-purple-700' : ($entry->itemTemplate->rarity === 'rare' ? 'text-blue-700' : 'text-slate-800')) }}">
                                                                                                            {{ $entry->itemTemplate->name }}
                                                                                                        </span>
                                                                                                        @if($isQuestItem) <span class="text-xs bg-yellow-400 text-yellow-900 px-1 rounded ml-1">Quest</span> @endif
                                                                                                    @endif
                                                                                                </div>
                                                                                                <div class="text-xs text-amber-700 font-semibold">
                                                                                                    Ilość: {{ $entry->min_qty }}{{ $entry->min_qty != $entry->max_qty ? ' - ' . $entry->max_qty : '' }}
                                                                                                </div>
                                                                                            </div>
                                                                                        </div>
                                                                                        
                                                                                        <div class="text-right">
                                                                                            <div class="text-lg font-bold text-amber-800">{{ $chance }}%</div>
                                                                                            <div class="text-[10px] text-amber-600 font-bold uppercase tracking-wider">Szansa</div>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            @endforeach
                                                                        @else
                                                                            <div class="text-center py-8">
                                                                                <div class="text-4xl mb-2 opacity-50">🕸️</div>
                                                                                <p class="text-amber-800 italic font-bold">Bestia nie posiada znanych łupów...</p>
                                                                            </div>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                        
                        {{-- World Boss Modal --}}
                        @if(isset($activeWorldBosses[$map->id]))
                            @php
                                $boss = $activeWorldBosses[$map->id];
                                $hpPercent = max(0, min(100, ($boss->current_hp / max(1, $boss->total_hp)) * 100));
                                $hasParticipated = in_array($boss->id, $participatedBosses);
                                $deadlineTimestamp = now()->endOfHour()->timestamp * 1000;
                                $topDmg = $topDamageDealers[$boss->id] ?? collect();
                            @endphp
                            <template x-teleport="body">
                                <div x-show="showBossModal" style="display: none;" 
                                     x-data="{ 
                                         timeLeftStr: 'Obliczanie...', 
                                         deadline: {{ $deadlineTimestamp }},
                                         init() {
                                             this.updateTimer();
                                             setInterval(() => this.updateTimer(), 1000);
                                         },
                                         updateTimer() {
                                             let now = new Date().getTime();
                                             let diff = this.deadline - now;
                                             if (diff <= 0) {
                                                 this.timeLeftStr = '00:00';
                                                 return;
                                             }
                                             let minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                                             let seconds = Math.floor((diff % (1000 * 60)) / 1000);
                                             this.timeLeftStr = (minutes < 10 ? '0' : '') + minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
                                         }
                                     }"
                                     class="fixed inset-0 z-[200] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
                                    <div @click.outside="showBossModal = false" class="bg-gradient-to-br from-gray-900 to-purple-950 border-2 border-purple-500 rounded-xl max-w-4xl w-full p-6 shadow-2xl relative text-left">
                                        <button @click="showBossModal = false" class="absolute top-4 right-4 text-gray-400 hover:text-white text-2xl font-bold">&times;</button>
                                        
                                        <h2 class="text-3xl font-bold text-center text-purple-300 medieval-font mb-6 border-b border-purple-700/50 pb-4">
                                            👑 Najeźdźca: {{ $boss->monster->name }}
                                        </h2>
                                        
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                            {{-- Left: Boss Info --}}
                                            <div class="space-y-4 bg-black/30 p-5 rounded-lg border border-purple-900/50">
                                                <h3 class="text-xl font-bold text-purple-200">Informacje</h3>
                                                <p class="text-red-300 font-bold">Pozostały czas: <span x-text="timeLeftStr"></span></p>
                                            
                                            <div class="mt-4">
                                                <div class="flex justify-between text-sm mb-1">
                                                    <span class="text-gray-300 font-bold">Punkty Życia (HP)</span>
                                                    <span class="text-red-400 font-bold">{{ number_format($boss->current_hp) }} / {{ number_format($boss->total_hp) }}</span>
                                                </div>
                                                <div class="w-full bg-gray-900 rounded-full h-4 border border-gray-600 overflow-hidden">
                                                    <div class="h-full bg-gradient-to-r from-red-600 to-red-500 transition-all duration-1000 rounded-full" style="width: {{ $hpPercent }}%"></div>
                                                </div>
                                                <div class="text-right text-xs text-red-500 font-bold mt-1">{{ round($hpPercent, 1) }}%</div>
                                            </div>
                                            
                                            <div class="mt-4 pt-4 border-t border-purple-900/50 text-sm text-gray-400 leading-relaxed">
                                                Każde zadane obrażenia zostają na stałe! Dołącz do walki by pomóc innym pokonać bossa. 
                                                Nagrody są przyznawane po pokonaniu lub po upływie czasu na podstawie zadanego DMG (Klucze do lochów dla TOP 10).
                                            </div>
                                        </div>
                                        
                                        {{-- Right: Top DMG --}}
                                        <div class="space-y-4 bg-black/30 p-5 rounded-lg border border-purple-900/50">
                                            <h3 class="text-xl font-bold text-purple-200 flex items-center justify-between">
                                                <span>Top 10 Wojowników</span>
                                                <span class="text-xs text-gray-500 bg-black/50 px-2 py-1 rounded">DMG</span>
                                            </h3>
                                            <div class="space-y-2 max-h-48 overflow-y-auto pr-2 custom-scrollbar">
                                                @if($topDmg->isEmpty())
                                                    <p class="text-gray-500 italic text-center py-4">Brak uczestników. Bądź pierwszy!</p>
                                                @else
                                                    @foreach($topDmg as $index => $log)
                                                        <div class="flex justify-between items-center text-sm {{ $log->character_id === $character->id ? 'bg-purple-900/40 border border-purple-500/50' : 'bg-gray-800/50' }} p-2 rounded">
                                                            <div class="flex items-center gap-2">
                                                                <span class="font-bold {{ $index === 0 ? 'text-yellow-400' : ($index === 1 ? 'text-gray-300' : ($index === 2 ? 'text-amber-600' : 'text-gray-500')) }}">
                                                                    #{{ $index + 1 }}
                                                                </span>
                                                                <span class="{{ $log->character_id === $character->id ? 'text-purple-300 font-bold' : 'text-gray-300' }}">
                                                                    {{ $log->character->name }}
                                                                </span>
                                                            </div>
                                                            <span class="text-red-400 font-bold">{{ number_format($log->damage) }}</span>
                                                        </div>
                                                    @endforeach
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    
                                    {{-- Bottom Action --}}
                                    <div class="mt-8 pt-6 border-t border-purple-700/50">
                                        @if($hasParticipated)
                                            <button disabled class="w-full bg-gray-700 text-gray-400 font-bold py-4 rounded-lg cursor-not-allowed border-2 border-gray-600 text-xl medieval-font shadow-inner">
                                                Już brałeś udział w tej walce
                                            </button>
                                        @else
                                            <a href="{{ route('adventure.map', ['character' => $character, 'map' => $map, 'world_boss' => $boss->monster_id]) }}" wire:navigate class="block w-full text-center bg-gradient-to-r from-red-700 via-purple-600 to-red-700 hover:from-red-600 hover:via-purple-500 hover:to-red-600 text-white font-bold py-4 rounded-lg shadow-lg shadow-red-900/50 border-2 border-red-500 transition-all transform hover:scale-[1.02] text-2xl medieval-font">
                                                ⚔️ DOŁĄCZ DO WALKI! ⚔️
                                            </a>
                                        @endif
                                    </div>
                                    </div>
                                </div>
                            </template>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
        @endif

        {{-- No maps available message --}}
        @if ($maps->isEmpty())
            @if($tab === 'maps')
            <div class="text-center">
                <div
                    class="bg-gradient-to-br from-amber-50/95 to-amber-100/95 border-4 border-amber-700 rounded-lg p-12 shadow-2xl backdrop-blur-sm max-w-2xl mx-auto">
                    <div class="text-8xl mb-6">🗺️</div>
                    <h2 class="text-3xl font-bold text-amber-900 medieval-font mb-4">Brak Dostępnych Map</h2>
                    <p class="text-xl text-amber-800 font-semibold">
                        Aktualnie nie ma żadnych map do eksploracji. Wróć później!
                    </p>
                </div>
            </div>
            @endif
        @endif

        {{-- DUNGEONS TAB --}}
        @if($tab === 'dungeons')
        <div style="display: block;">
            @if($activeRun)
                <div class="bg-amber-900/80 border border-amber-500 rounded-xl p-6 mb-8 flex items-center justify-between shadow-2xl max-w-4xl mx-auto">
                    <div>
                        <h3 class="text-2xl font-bold text-amber-300 medieval-font mb-2">Trwająca Ekspedycja</h3>
                        <p class="text-amber-100">Jesteś w trakcie przemierzania lochu. Kontynuuj swoją przygodę!</p>
                        <p class="text-sm text-amber-400 mt-1 font-bold">Etap: {{ $activeRun->current_stage }}</p>
                    </div>
                    <button wire:click="enterDungeon({{ $activeRun->dungeon_id }})" 
                        class="bg-gradient-to-r from-amber-600 to-amber-700 hover:from-amber-500 hover:to-amber-600 text-white font-bold py-3 px-8 rounded-lg transition-all duration-200 transform hover:scale-105 shadow-lg border border-amber-500">
                        ➡️ Kontynuuj
                    </button>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 max-w-6xl mx-auto">
                @foreach($dungeons as $dungeon)
                    @php
                        $canEnter = $dungeon->canCharacterEnter($character);
                        $hasKey = $dungeon->entry_item_template_id ? $character->inventoryItems()->where('template_id', $dungeon->entry_item_template_id)->exists() : true;
                        $isInProgress = $activeRun && $activeRun->dungeon_id === $dungeon->id;
                    @endphp
                    <div x-data="{ showMonsters: false }" class="bg-slate-900/80 border-2 {{ $isInProgress ? 'border-amber-500' : ($canEnter && $hasKey ? 'border-slate-600 hover:border-slate-400' : 'border-red-900/50 opacity-75') }} rounded-xl p-6 transition-all duration-300 relative group flex flex-col h-full">
                        
                        <div class="flex justify-between items-start mb-4">
                            <h3 class="text-2xl font-bold text-slate-200 medieval-font">{{ $dungeon->name }}</h3>
                            <div class="bg-slate-800 text-slate-300 text-xs font-bold px-2 py-1 rounded border border-slate-700">
                                Wym. Poz: {{ $dungeon->min_level }}
                            </div>
                        </div>

                        <p class="text-slate-400 text-sm mb-6 flex-grow">{{ $dungeon->description }}</p>

                        <div class="space-y-3 mb-6 bg-slate-950/50 p-3 rounded-lg border border-slate-800">
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-400">Liczba etapów:</span>
                                <span class="text-slate-200 font-bold">{{ $dungeon->stages->count() }}</span>
                            </div>
                            
                            @if($dungeon->entryItemTemplate)
                                <div class="flex justify-between items-center text-sm border-t border-slate-800 pt-2 mt-2">
                                    <span class="text-slate-400">Klucz:</span>
                                    <span class="font-bold {{ $hasKey ? 'text-green-400' : 'text-red-400' }} flex items-center gap-1">
                                        @if($hasKey) ✓ @else ✗ @endif 
                                        {{ $dungeon->entryItemTemplate->name }}
                                    </span>
                                </div>
                            @endif
                        </div>

                        <button @click="showMonsters = !showMonsters" class="w-full mb-4 bg-slate-800/80 hover:bg-slate-700 text-slate-200 font-semibold py-2 px-4 rounded-lg transition-colors text-sm border border-slate-600">
                            <span x-text="showMonsters ? 'Ukryj przeciwników' : '👁️ Lista przeciwników'"></span>
                        </button>
                        
                        {{-- Expanded Monster List --}}
                        <div x-show="showMonsters" x-transition class="mb-4 text-left border-t border-slate-700/50 pt-4" style="display: none;">
                            <h4 class="text-slate-300 font-bold mb-2">Przeciwnicy (wg etapów):</h4>
                            <div class="space-y-2 max-h-64 overflow-y-auto pr-2 custom-scrollbar">
                                @foreach($dungeon->stages as $stage)
                                    @php $monster = $stage->monster; @endphp
                                    @if($monster)
                                    <div x-data="{ showLoot: false }" class="bg-slate-800/50 rounded p-2 border border-slate-700/50">
                                        <div class="flex justify-between items-center cursor-pointer hover:bg-slate-700/50 p-1 rounded transition" @click="showLoot = !showLoot">
                                            <div>
                                                <span class="text-xs text-amber-500 font-bold mr-1">Etap {{ $stage->stage_order }}:</span>
                                                <span class="font-bold text-red-400">{{ $monster->name }}</span>
                                                @if($monster->type)
                                                    <span class="text-xs text-yellow-600 ml-1 font-bold">[{{ $monster->type->label() }}]</span>
                                                @endif
                                                <span class="text-xs text-slate-400 ml-1 font-bold">(Lvl {{ $monster->level }})</span>
                                            </div>
                                            <span class="text-xs text-slate-500" x-text="showLoot ? '▼' : '▶'"></span>
                                        </div>
                                        
                                        {{-- Loot --}}
                                        <div x-show="showLoot" class="mt-2 pl-2 border-l-2 border-slate-600/50 space-y-1 text-xs" style="display: none;">
                                            @if($monster->lootTable && $monster->lootTable->entries->isNotEmpty())
                                                @php
                                                    $totalWeight = max(1, $monster->lootTable->entries->sum('weight'));
                                                @endphp
                                                @foreach($monster->lootTable->entries as $entry)
                                                    <div class="flex items-center text-slate-300">
                                                        @if($entry->reward_type === 'gold')
                                                            <span class="text-yellow-500 font-bold mr-1">💰</span> Złoto ({{ $entry->min_qty }} - {{ $entry->max_qty }})
                                                        @elseif($entry->reward_type === 'xp')
                                                            <span class="text-blue-400 font-bold mr-1">✨</span> XP ({{ $entry->min_qty }} - {{ $entry->max_qty }})
                                                        @elseif($entry->reward_type === 'item' && $entry->itemTemplate)
                                                            <span class="{{ $entry->itemTemplate->rarity === 'legendary' ? 'text-orange-500 font-bold' : ($entry->itemTemplate->rarity === 'epic' ? 'text-purple-400 font-bold' : ($entry->itemTemplate->rarity === 'rare' ? 'text-blue-400 font-bold' : 'text-slate-300')) }}">
                                                                {{ $entry->itemTemplate->name }}
                                                            </span>
                                                        @else
                                                            {{ $entry->reward_type }}
                                                        @endif
                                                        <span class="text-slate-500 font-bold ml-2">({{ round(($entry->weight / $totalWeight) * 100) }}%)</span>
                                                    </div>
                                                @endforeach
                                            @else
                                                <div class="text-slate-500 italic">Brak dropu</div>
                                            @endif
                                        </div>
                                    </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>

                        @if($isInProgress)
                            <button wire:click="enterDungeon({{ $dungeon->id }})" class="w-full bg-amber-600 hover:bg-amber-500 text-white font-bold py-3 px-4 rounded-lg transition-colors border border-amber-500">
                                Kontynuuj Ekspedycję
                            </button>
                        @elseif($canEnter && $hasKey && !$activeRun)
                            <button wire:click="enterDungeon({{ $dungeon->id }})" class="w-full bg-slate-700 hover:bg-slate-600 text-white font-bold py-3 px-4 rounded-lg transition-colors border border-slate-500">
                                Rozpocznij Ekspedycję
                            </button>
                        @else
                            <button disabled class="w-full bg-slate-800 text-slate-500 font-bold py-3 px-4 rounded-lg cursor-not-allowed border border-slate-700">
                                @if($activeRun)
                                    Inna ekspedycja trwa
                                @elseif(!$canEnter)
                                    Za niski poziom
                                @else
                                    Brak klucza
                                @endif
                            </button>
                        @endif
                    </div>
                @endforeach
            </div>
            
            @if($dungeons && $dungeons->isEmpty())
                <div class="text-center py-12">
                    <div class="text-6xl mb-4 opacity-50">🏰</div>
                    <h3 class="text-2xl font-bold text-slate-400 medieval-font mb-2">Brak dostępnych lochów</h3>
                    <p class="text-slate-500">Wróć później, gdy pojawią się nowe wyzwania.</p>
                </div>
            @endif
        </div>
        @endif
    </div>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&display=swap');

        .medieval-font {
            font-family: 'Cinzel', serif;
        }

        /* Adventure floating elements */
        .adventure-element {
            position: absolute;
            font-size: 1.5rem;
            opacity: 0.7;
            pointer-events: none;
            animation: float-adventure 15s infinite linear;
        }

        .adventure-element-1 {
            left: 10%;
            animation-delay: 0s;
            animation-duration: 20s;
        }

        .adventure-element-2 {
            left: 30%;
            animation-delay: 4s;
            animation-duration: 18s;
        }

        .adventure-element-3 {
            left: 50%;
            animation-delay: 8s;
            animation-duration: 22s;
        }

        .adventure-element-4 {
            left: 70%;
            animation-delay: 12s;
            animation-duration: 16s;
        }

        .adventure-element-5 {
            left: 85%;
            animation-delay: 16s;
            animation-duration: 19s;
        }

        @keyframes float-adventure {
            0% {
                transform: translateY(100vh) translateX(0px) rotate(0deg);
                opacity: 0;
            }

            10% {
                opacity: 0.7;
            }

            90% {
                opacity: 0.7;
            }

            100% {
                transform: translateY(-100px) translateX(30px) rotate(180deg);
                opacity: 0;
            }
        }

        /* Image hover effects */
        .group:hover img {
            transform: scale(1.05);
        }

        img {
            transition: transform 0.3s ease;
        }
    </style>
</div>
