<div class="min-h-screen bg-gradient-to-b from-gray-900 via-gray-800 to-gray-900 text-gray-100 relative overflow-hidden">

    {{-- Background decorative element --}}
    <div class="absolute inset-0 bg-gradient-to-b from-red-950/20 via-transparent to-amber-950/20"></div>

    <div class="relative container mx-auto px-4 py-8 max-w-4xl">

        {{-- Header --}}
        <div class="flex items-center justify-between mb-8">
            <div class="flex items-center space-x-4">
                <div class="text-4xl">🏰</div>
                <div>
                    <h1 class="text-3xl font-bold bg-gradient-to-r from-amber-300 via-yellow-400 to-amber-500 bg-clip-text text-transparent" style="font-family: 'Cinzel', serif;">
                        Lochy
                    </h1>
                    <p class="text-gray-400 text-sm">Wybierz loch i pokonaj wszystkie etapy</p>
                </div>
            </div>
            <button wire:click="backToHub"
                class="bg-gradient-to-r from-gray-700 to-gray-800 hover:from-gray-600 hover:to-gray-700 text-amber-200 font-bold py-2 px-4 rounded-lg transition-all duration-200 transform hover:scale-105 shadow-lg" style="font-family: 'Cinzel', serif;">
                🏠 Powrót
            </button>
        </div>

        {{-- Character info bar --}}
        <div class="bg-gray-800/80 border border-gray-700 rounded-lg p-4 mb-6 flex items-center justify-between backdrop-blur-sm">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 border-2 border-amber-600 rounded-full overflow-hidden bg-gradient-to-b from-gray-700 to-gray-800 flex items-center justify-center text-lg">
                    ⚔️
                </div>
                <div>
                    <span class="font-bold text-amber-300" style="font-family: 'Cinzel', serif;">{{ $character->name }}</span>
                    <span class="text-gray-400 text-sm ml-2">Poziom {{ $character->level }}</span>
                </div>
            </div>
            @if($activeRun)
                <div class="bg-amber-900/50 border border-amber-600/50 rounded-lg px-3 py-1.5">
                    <span class="text-amber-300 text-sm font-semibold">⚡ Aktywna ekspedycja w toku</span>
                </div>
            @endif
        </div>

        {{-- Dungeon list --}}
        <div class="space-y-4">
            @forelse($dungeons as $dungeon)
                @php
                    $isLevelTooLow = $character->level < $dungeon->min_level;
                    $hasActiveRunHere = $activeRun && $activeRun->dungeon_id === $dungeon->id;
                    $hasActiveRunElsewhere = $activeRun && $activeRun->dungeon_id !== $dungeon->id;
                    $stageCount = $dungeon->stages->count();
                @endphp
                <div class="bg-gray-800/80 border {{ $hasActiveRunHere ? 'border-amber-500' : ($isLevelTooLow ? 'border-gray-700/50' : 'border-gray-700') }} rounded-xl shadow-lg backdrop-blur-sm transition-all duration-300 {{ $isLevelTooLow ? 'opacity-60' : 'hover:border-amber-600/50 hover:shadow-amber-900/20 hover:shadow-xl' }}">
                    <div class="p-5">
                        <div class="flex items-start justify-between">
                            {{-- Dungeon info --}}
                            <div class="flex-1">
                                <div class="flex items-center space-x-3 mb-2">
                                    <span class="text-2xl">🏚️</span>
                                    <h2 class="text-xl font-bold text-amber-200" style="font-family: 'Cinzel', serif;">
                                        {{ $dungeon->name }}
                                    </h2>
                                    @if($hasActiveRunHere)
                                        <span class="bg-amber-600/30 text-amber-300 text-xs font-semibold px-2 py-0.5 rounded-full border border-amber-600/50">
                                            W TOKU
                                        </span>
                                    @endif
                                </div>

                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mt-3">
                                    {{-- Min level --}}
                                    <div class="flex items-center space-x-2">
                                        <span class="text-sm {{ $isLevelTooLow ? 'text-red-400' : 'text-green-400' }}">📊</span>
                                        <span class="text-sm {{ $isLevelTooLow ? 'text-red-400' : 'text-gray-300' }}">
                                            Wymagany poz. <strong class="{{ $isLevelTooLow ? 'text-red-300' : 'text-amber-300' }}">{{ $dungeon->min_level }}</strong>
                                        </span>
                                    </div>

                                    {{-- Stage count --}}
                                    <div class="flex items-center space-x-2">
                                        <span class="text-sm text-gray-400">🗡️</span>
                                        <span class="text-sm text-gray-300">
                                            Etapy: <strong class="text-amber-300">{{ $stageCount }}</strong>
                                        </span>
                                    </div>

                                    {{-- Key item --}}
                                    @if($dungeon->entryItemTemplate)
                                        <div class="flex items-center space-x-2">
                                            <span class="text-sm text-yellow-400">🔑</span>
                                            <span class="text-sm text-gray-300">
                                                {{ $dungeon->entryItemTemplate->name }}
                                            </span>
                                        </div>
                                    @else
                                        <div class="flex items-center space-x-2">
                                            <span class="text-sm text-green-400">🔓</span>
                                            <span class="text-sm text-green-400/80">Wejście wolne</span>
                                        </div>
                                    @endif
                                </div>

                                @if($hasActiveRunHere)
                                    <div class="mt-3 bg-gray-900/50 rounded-lg p-2">
                                        <div class="flex items-center space-x-2 text-sm text-gray-300">
                                            <span>⚔️</span>
                                            <span>Etap <strong class="text-amber-300">{{ $activeRun->current_stage }}</strong> / {{ $stageCount }}</span>
                                            <span class="text-gray-600">•</span>
                                            <span>HP: <strong class="text-green-400">{{ $activeRun->current_hp }}</strong> / {{ $character->getMaxHp() }}</span>
                                        </div>
                                    </div>
                                @endif

                                @if($isLevelTooLow)
                                    <p class="mt-2 text-sm text-red-400/80">
                                        ⚠️ Twój poziom ({{ $character->level }}) jest za niski. Wymagany: {{ $dungeon->min_level }}.
                                    </p>
                                @endif
                            </div>

                            {{-- Enter button --}}
                            <div class="ml-4 flex-shrink-0">
                                @if($hasActiveRunHere)
                                    <button wire:click="enterDungeon({{ $dungeon->id }})"
                                        class="bg-gradient-to-r from-amber-600 to-amber-700 hover:from-amber-500 hover:to-amber-600 text-white font-bold py-3 px-6 rounded-lg transition-all duration-200 transform hover:scale-105 shadow-lg shadow-amber-900/30"
                                        style="font-family: 'Cinzel', serif;">
                                        <span wire:loading.remove wire:target="enterDungeon({{ $dungeon->id }})">⚔️ Kontynuuj</span>
                                        <span wire:loading wire:target="enterDungeon({{ $dungeon->id }})">
                                            <svg class="animate-spin h-5 w-5 inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                        </span>
                                    </button>
                                @elseif($isLevelTooLow || $hasActiveRunElsewhere)
                                    <button disabled
                                        class="bg-gray-700 text-gray-500 font-bold py-3 px-6 rounded-lg cursor-not-allowed opacity-50"
                                        style="font-family: 'Cinzel', serif;">
                                        🚫 Wejdź
                                    </button>
                                @else
                                    <button wire:click="enterDungeon({{ $dungeon->id }})"
                                        class="bg-gradient-to-r from-green-600 to-green-700 hover:from-green-500 hover:to-green-600 text-white font-bold py-3 px-6 rounded-lg transition-all duration-200 transform hover:scale-105 shadow-lg shadow-green-900/30"
                                        style="font-family: 'Cinzel', serif;">
                                        <span wire:loading.remove wire:target="enterDungeon({{ $dungeon->id }})">⚔️ Wejdź</span>
                                        <span wire:loading wire:target="enterDungeon({{ $dungeon->id }})">
                                            <svg class="animate-spin h-5 w-5 inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                        </span>
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-gray-800/80 border border-gray-700 rounded-xl p-12 text-center">
                    <div class="text-5xl mb-4">🏚️</div>
                    <h3 class="text-xl font-bold text-gray-400 mb-2" style="font-family: 'Cinzel', serif;">Brak dostępnych lochów</h3>
                    <p class="text-gray-500">Obecnie nie ma żadnych lochów do eksploracji.</p>
                </div>
            @endforelse
        </div>
    </div>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&display=swap');
    </style>
</div>
