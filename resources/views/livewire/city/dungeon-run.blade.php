<div class="min-h-screen bg-gradient-to-b from-gray-900 via-gray-800 to-gray-900 text-gray-100 relative overflow-hidden">

    {{-- Background --}}
    <div class="absolute inset-0 bg-gradient-to-b from-red-950/30 via-transparent to-gray-950/50"></div>

    <div class="relative container mx-auto px-4 py-8 max-w-5xl">

        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center space-x-4">
                <div class="text-4xl">⚔️</div>
                <div>
                    <h1 class="text-3xl font-bold bg-gradient-to-r from-amber-300 via-yellow-400 to-amber-500 bg-clip-text text-transparent" style="font-family: 'Cinzel', serif;">
                        {{ $dungeon->name }}
                    </h1>
                    <p class="text-gray-400 text-sm">Etap {{ $currentStage }} / {{ $totalStages }}</p>
                </div>
            </div>
            <button wire:click="backToDungeonList"
                class="bg-gradient-to-r from-gray-700 to-gray-800 hover:from-gray-600 hover:to-gray-700 text-amber-200 font-bold py-2 px-4 rounded-lg transition-all duration-200 transform hover:scale-105 shadow-lg" style="font-family: 'Cinzel', serif;">
                ↩️ Lista lochów
            </button>
        </div>

        {{-- Error message --}}
        @if($errorMessage)
            <div class="bg-red-900/50 border border-red-700 rounded-lg p-4 mb-6">
                <p class="text-red-300 font-semibold">⚠️ {{ $errorMessage }}</p>
            </div>
        @endif

        {{-- NO ACTIVE RUN - Start screen --}}
        @if(!$run)
            <div class="bg-gray-800/80 border border-gray-700 rounded-xl p-8 text-center backdrop-blur-sm">
                <div class="text-6xl mb-4">🏚️</div>
                <h2 class="text-2xl font-bold text-amber-300 mb-3" style="font-family: 'Cinzel', serif;">{{ $dungeon->name }}</h2>
                <div class="text-gray-400 mb-6 space-y-1">
                    <p>Etapy: <strong class="text-amber-300">{{ $totalStages }}</strong></p>
                    <p>Wymagany poziom: <strong class="text-amber-300">{{ $dungeon->min_level }}</strong></p>
                </div>
                <button wire:click="startRun"
                    class="bg-gradient-to-r from-green-600 to-green-700 hover:from-green-500 hover:to-green-600 text-white font-bold py-3 px-8 rounded-lg transition-all duration-200 transform hover:scale-105 shadow-lg shadow-green-900/30 text-lg"
                    style="font-family: 'Cinzel', serif;"
                    wire:loading.attr="disabled" wire:loading.class="opacity-50 cursor-not-allowed">
                    <span wire:loading.remove wire:target="startRun">⚔️ Rozpocznij ekspedycję</span>
                    <span wire:loading wire:target="startRun">
                        <svg class="animate-spin h-5 w-5 inline mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        Rozpoczynanie...
                    </span>
                </button>
            </div>

        {{-- DUNGEON COMPLETE --}}
        @elseif($battleResult && ($battleResult['result'] ?? '') === 'dungeon_complete' && !$showBattle)
            <div class="bg-gray-800/80 border border-amber-500 rounded-xl p-8 text-center backdrop-blur-sm">
                <div class="text-7xl mb-4">🏆</div>
                <h2 class="text-3xl font-bold text-amber-300 mb-3" style="font-family: 'Cinzel', serif;">Gratulacje!</h2>
                <p class="text-gray-300 text-lg mb-2">Ukończyłeś loch <strong class="text-amber-400">{{ $dungeon->name }}</strong>!</p>
                <p class="text-gray-400 mb-6">Przetrwałeś wszystkie {{ $totalStages }} etapów.</p>
                <button wire:click="backToDungeonList"
                    class="bg-gradient-to-r from-amber-600 to-amber-700 hover:from-amber-500 hover:to-amber-600 text-white font-bold py-3 px-8 rounded-lg transition-all duration-200 transform hover:scale-105 shadow-lg text-lg"
                    style="font-family: 'Cinzel', serif;">
                    🏰 Powrót do listy lochów
                </button>
            </div>

        {{-- LOSS --}}
        @elseif($battleResult && ($battleResult['result'] ?? '') === 'loss' && !$showBattle)
            <div class="bg-gray-800/80 border border-red-700 rounded-xl p-8 text-center backdrop-blur-sm">
                <div class="text-7xl mb-4">💀</div>
                <h2 class="text-3xl font-bold text-red-400 mb-3" style="font-family: 'Cinzel', serif;">Poległeś!</h2>
                <p class="text-gray-300 text-lg mb-2">Zostałeś pokonany na etapie <strong class="text-red-300">{{ $battleResult['stage'] ?? $currentStage }}</strong>.</p>
                <p class="text-gray-500 mb-6">Twoja ekspedycja dobiegła końca...</p>
                <button wire:click="backToDungeonList"
                    class="bg-gradient-to-r from-gray-600 to-gray-700 hover:from-gray-500 hover:to-gray-600 text-white font-bold py-3 px-8 rounded-lg transition-all duration-200 transform hover:scale-105 shadow-lg text-lg"
                    style="font-family: 'Cinzel', serif;">
                    🏰 Powrót do listy lochów
                </button>
            </div>

        {{-- ACTIVE RUN --}}
        @else
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- Left: Character status + Potions --}}
                <div class="space-y-4 order-1 lg:order-1">
                    {{-- Character HP --}}
                    <div class="bg-gray-800/80 border border-gray-700 rounded-xl p-5 backdrop-blur-sm">
                        <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-3">Twoja postać</h3>
                        <div class="text-center mb-3">
                            <span class="text-3xl">🛡️</span>
                            <p class="font-bold text-amber-300 mt-1" style="font-family: 'Cinzel', serif;">{{ $character->name }}</p>
                            <p class="text-gray-400 text-sm">Poziom {{ $character->level }}</p>
                        </div>

                        {{-- HP Bar --}}
                        <div class="mb-2">
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-400">HP</span>
                                <span class="text-{{ $currentHp > $maxHp * 0.5 ? 'green' : ($currentHp > $maxHp * 0.25 ? 'yellow' : 'red') }}-400 font-bold">
                                    {{ $currentHp }} / {{ $maxHp }}
                                </span>
                            </div>
                            <div class="w-full bg-gray-900 rounded-full h-4 border border-gray-600">
                                @php $hpPercent = $maxHp > 0 ? ($currentHp / $maxHp) * 100 : 0; @endphp
                                <div class="h-full rounded-full transition-all duration-500 {{ $hpPercent > 50 ? 'bg-gradient-to-r from-green-600 to-green-500' : ($hpPercent > 25 ? 'bg-gradient-to-r from-yellow-600 to-yellow-500' : 'bg-gradient-to-r from-red-600 to-red-500') }}"
                                     style="width: {{ $hpPercent }}%"></div>
                            </div>
                        </div>

                        {{-- Stage progress --}}
                        <div class="mt-4">
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-400">Postęp</span>
                                <span class="text-amber-300 font-bold">Etap {{ $currentStage }} / {{ $totalStages }}</span>
                            </div>
                            <div class="w-full bg-gray-900 rounded-full h-2.5 border border-gray-600">
                                @php $stagePercent = $totalStages > 0 ? (($currentStage - 1) / $totalStages) * 100 : 0; @endphp
                                <div class="h-full rounded-full bg-gradient-to-r from-amber-600 to-amber-400 transition-all duration-500"
                                     style="width: {{ $stagePercent }}%"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Potions --}}
                    <div class="bg-gray-800/80 border border-gray-700 rounded-xl p-5 backdrop-blur-sm">
                        <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-3">🧪 Mikstury</h3>
                        @if($potions->count() > 0)
                            <div class="space-y-2">
                                @foreach($potions as $potion)
                                    <div class="flex items-center justify-between bg-gray-900/50 rounded-lg p-3 border border-gray-700/50">
                                        <div class="flex items-center space-x-2">
                                            <span class="text-lg">🧪</span>
                                            <div>
                                                <p class="text-sm font-semibold text-gray-200">{{ $potion->template->name }}</p>
                                                <p class="text-xs text-gray-500">
                                                    Leczy: {{ $potion->template->base_stats['heal'] ?? 50 }} HP
                                                    @if($potion->stack_size > 1)
                                                        • x{{ $potion->stack_size }}
                                                    @endif
                                                </p>
                                            </div>
                                        </div>
                                        <button wire:click="usePotion('{{ $potion->id }}')"
                                            class="bg-green-700 hover:bg-green-600 text-white text-xs font-bold py-1.5 px-3 rounded transition-colors"
                                            wire:loading.attr="disabled" wire:target="usePotion('{{ $potion->id }}')">
                                            <span wire:loading.remove wire:target="usePotion('{{ $potion->id }}')">Użyj</span>
                                            <span wire:loading wire:target="usePotion('{{ $potion->id }}')">...</span>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-gray-500 text-sm text-center py-3">Brak mikstur w ekwipunku.</p>
                        @endif
                    </div>
                </div>

                {{-- Center: Fight Action / Battle Log --}}
                <div class="space-y-4 order-2 lg:order-2 flex flex-col justify-center min-h-[400px]">
                    @if($showBattle && $battleResult)
                        {{-- Battle result overlay --}}
                        <div class="bg-gray-800/90 border {{ ($battleResult['result'] ?? '') === 'loss' ? 'border-red-700' : 'border-amber-600' }} rounded-xl p-6 backdrop-blur-sm h-full flex flex-col">
                            <h3 class="text-lg font-bold text-center mb-4 {{ ($battleResult['result'] ?? '') === 'loss' ? 'text-red-400' : 'text-amber-300' }}" style="font-family: 'Cinzel', serif;">
                                @if(($battleResult['result'] ?? '') === 'loss')
                                    💀 Porażka!
                                @elseif(($battleResult['result'] ?? '') === 'dungeon_complete')
                                    🏆 Loch ukończony!
                                @else
                                    ⚔️ Etap zaliczony!
                                @endif
                            </h3>

                            {{-- Battle log --}}
                            <div class="flex-1 overflow-y-auto bg-gray-900/50 rounded-lg p-3 mb-4 border border-gray-700/50 space-y-1">
                                @foreach($turns as $turn)
                                    <div class="text-sm {{ $turn['actor'] === 'player' ? 'text-blue-300' : 'text-red-300' }}">
                                        @if($turn['type'] === 'miss')
                                            <span class="text-gray-500">{{ $turn['actor'] === 'player' ? '🛡️ Ty' : '👹 Potwór' }}: Pudło!</span>
                                        @else
                                            <span>{{ $turn['actor'] === 'player' ? '🛡️ Ty' : '👹 Potwór' }}: </span>
                                            <span class="{{ $turn['crit'] ? 'text-yellow-400 font-bold' : '' }}">
                                                {{ $turn['value'] }} obrażeń{{ $turn['crit'] ? ' ⚡KRYTYCZNE!' : '' }}
                                            </span>
                                            <span class="text-gray-600 text-xs ml-2">
                                                [HP: {{ $turn['playerHp'] }} | Potwór: {{ $turn['enemyHp'] }}]
                                            </span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>

                            {{-- Summary --}}
                            <div class="text-center mt-auto">
                                <p class="text-gray-400 text-sm mb-4">
                                    Twoje HP po walce: <strong class="text-{{ ($battleResult['player_hp'] ?? 0) > 0 ? 'green' : 'red' }}-400">{{ $battleResult['player_hp'] ?? 0 }}</strong>
                                </p>
                                <button wire:click="dismissBattle"
                                    class="bg-gradient-to-r from-gray-600 to-gray-700 hover:from-gray-500 hover:to-gray-600 text-white font-bold py-2 px-6 rounded-lg transition-all duration-200 w-full"
                                    style="font-family: 'Cinzel', serif;">
                                    @if(($battleResult['result'] ?? '') === 'stage_clear')
                                        ➡️ Następny etap
                                    @elseif(($battleResult['result'] ?? '') === 'dungeon_complete')
                                        🏆 Zobacz Podsumowanie
                                    @else
                                        💀 Podsumowanie Porażki
                                    @endif
                                </button>
                            </div>
                        </div>
                    @elseif($monster && !$showBattle)
                        {{-- Intermediate view in the center --}}
                        <div class="bg-gray-800/90 border border-amber-900/50 rounded-xl p-8 text-center shadow-2xl backdrop-blur-sm flex flex-col justify-center h-full">
                            <div class="text-5xl mb-4">🚪</div>
                            <h3 class="text-lg text-gray-400 uppercase tracking-widest mb-2 font-bold">Wyzwanie Etapu</h3>
                            <h2 class="text-3xl font-bold text-amber-400 mb-6" style="font-family: 'Cinzel', serif;">Etap {{ $currentStage }} z {{ $totalStages }}</h2>
                            <p class="text-gray-300 italic mb-8">Z mroku wyłania się kolejny przeciwnik. Przygotuj się do walki, wypij mikstury i dobądź broni!</p>
                            <button wire:click="fight"
                                class="w-full bg-gradient-to-r from-red-600 to-red-700 hover:from-red-500 hover:to-red-600 text-white font-bold py-4 px-10 rounded-lg transition-all duration-200 transform hover:scale-105 shadow-[0_0_20px_rgba(220,38,38,0.4)] text-xl uppercase tracking-wider"
                                style="font-family: 'Cinzel', serif;"
                                wire:loading.attr="disabled" wire:loading.class="opacity-50 cursor-not-allowed">
                                <span wire:loading.remove wire:target="fight">⚔️ Rozpocznij Walkę</span>
                                <span wire:loading wire:target="fight">
                                    <svg class="animate-spin h-5 w-5 inline mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                    Walka trwa...
                                </span>
                            </button>
                        </div>
                    @endif
                </div>

                {{-- Right: Monster --}}
                <div class="space-y-4 order-3 lg:order-3">
                    @if($monster)
                        {{-- Monster info always visible on the right --}}
                        <div class="bg-gray-800/80 border border-red-900/50 rounded-xl p-5 backdrop-blur-sm">
                            <h3 class="text-sm font-bold text-red-400 uppercase tracking-wider mb-3">Przeciwnik</h3>
                            <div class="text-center mb-5">
                                <span class="text-5xl drop-shadow-[0_0_15px_rgba(220,38,38,0.6)]">👹</span>
                                <p class="font-bold text-red-300 mt-3 text-xl" style="font-family: 'Cinzel', serif;">{{ $monster->name }}</p>
                                <p class="text-gray-400 text-sm">Poziom {{ $monster->level }} • {{ ucfirst($monster->rank ?? 'normal') }}</p>
                            </div>

                            <div class="grid grid-cols-2 gap-3 mb-2">
                                <div class="bg-gray-900/60 rounded-lg p-3 border border-gray-700/50 text-center">
                                    <p class="text-xs text-gray-500 uppercase tracking-widest mb-1">HP</p>
                                    <p class="font-bold text-red-400 text-lg">{{ $monster->stats['hp'] ?? $monster->level * 20 }}</p>
                                </div>
                                <div class="bg-gray-900/60 rounded-lg p-3 border border-gray-700/50 text-center">
                                    <p class="text-xs text-gray-500 uppercase tracking-widest mb-1">ATK</p>
                                    <p class="font-bold text-orange-400 text-lg">{{ $monster->stats['atk'] ?? $monster->level * 2 }}</p>
                                </div>
                                <div class="bg-gray-900/60 rounded-lg p-3 border border-gray-700/50 text-center">
                                    <p class="text-xs text-gray-500 uppercase tracking-widest mb-1">DEF</p>
                                    <p class="font-bold text-blue-400 text-lg">{{ $monster->stats['def'] ?? $monster->level }}</p>
                                </div>
                                <div class="bg-gray-900/60 rounded-lg p-3 border border-gray-700/50 text-center">
                                    <p class="text-xs text-gray-500 uppercase tracking-widest mb-1">AGI</p>
                                    <p class="font-bold text-green-400 text-lg">{{ $monster->stats['agi'] ?? $monster->level }}</p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

            </div>
        @endif
    </div>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&display=swap');
    </style>
</div>
