<div>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-4">

        {{-- Left: Incubator --}}
        <div class="lg:col-span-1 space-y-4">

            {{-- Incubator section --}}
            <div class="bg-gray-800/80 border border-gray-700 rounded-xl p-5">
                <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4 flex items-center">
                    <span class="text-lg mr-2">🥚</span> Inkubator
                </h3>

                @if($incubator && !$incubator->is_hatched && $incubator->egg_item_instance_id)
                    {{-- Egg is incubating --}}
                    <div class="text-center">
                        <div class="text-5xl mb-3 animate-pulse">🥚</div>
                        <p class="text-amber-300 font-bold mb-1" style="font-family: 'Cinzel', serif;">Inkubacja w toku</p>
                        <p class="text-sm text-gray-400 mb-3">
                            Rzadkość:
                            <span class="font-bold
                                {{ $incubator->egg_rarity === 'legendary' ? 'text-yellow-400' :
                                   ($incubator->egg_rarity === 'epic' ? 'text-purple-400' :
                                   ($incubator->egg_rarity === 'rare' ? 'text-blue-400' :
                                   ($incubator->egg_rarity === 'uncommon' ? 'text-green-400' : 'text-gray-300'))) }}">
                                {{ match($incubator->egg_rarity) {
                                    'common' => 'Zwykłe',
                                    'uncommon' => 'Nietypowe',
                                    'rare' => 'Rzadkie',
                                    'epic' => 'Epickie',
                                    'legendary' => 'Legendarne',
                                    default => ucfirst($incubator->egg_rarity),
                                } }}
                            </span>
                        </p>

                        {{-- Progress bar --}}
                        @php
                            $progress = $incubator->getProgress();
                            $isReady = $incubator->isReady();
                            $timeRemaining = $isReady ? null : $incubator->hatches_at->diffForHumans();
                        @endphp

                        <div class="w-full bg-gray-900 rounded-full h-4 border border-gray-600 mb-2">
                            <div class="h-full rounded-full transition-all duration-1000 {{ $isReady ? 'bg-gradient-to-r from-green-500 to-green-400' : 'bg-gradient-to-r from-amber-600 to-amber-400' }}"
                                 style="width: {{ min(100, $progress) }}%"></div>
                        </div>

                        <p class="text-xs text-gray-500 mb-4">
                            @if($isReady)
                                ✅ Gotowe do wyklucia!
                            @else
                                ⏳ {{ $timeRemaining }}
                            @endif
                        </p>

                        @if($isReady)
                            <button wire:click="hatchEgg"
                                class="w-full bg-gradient-to-r from-green-600 to-green-700 hover:from-green-500 hover:to-green-600 text-white font-bold py-3 px-6 rounded-lg transition-all duration-200 transform hover:scale-105 shadow-lg shadow-green-900/30"
                                style="font-family: 'Cinzel', serif;"
                                wire:loading.attr="disabled" wire:loading.class="opacity-50 cursor-not-allowed">
                                <span wire:loading.remove wire:target="hatchEgg">🐣 Wykluj!</span>
                                <span wire:loading wire:target="hatchEgg">
                                    Wyklucie...
                                </span>
                            </button>
                        @endif
                    </div>
                @else
                    {{-- Incubator is empty --}}
                    <div class="text-center">
                        <div class="text-5xl mb-3 opacity-30">🥚</div>
                        <p class="text-gray-500 mb-4">Inkubator jest pusty</p>

                        @if($eggs->count() > 0)
                            <p class="text-sm text-gray-400 mb-3">Wybierz jajko do inkubacji:</p>
                            <div class="space-y-2">
                                @foreach($eggs as $egg)
                                    <div class="flex items-center justify-between bg-gray-900/50 rounded-lg p-3 border border-gray-700/50">
                                        <div class="flex items-center space-x-2">
                                            @if($egg->template->icon)
                                                <img src="{{ route('assets.items', ['filename' => $egg->template->icon]) }}" class="w-8 h-8 object-contain drop-shadow-md" alt="{{ $egg->template->name }}">
                                            @else
                                                <span class="text-lg">🥚</span>
                                            @endif
                                            <div class="text-left">
                                                <p class="text-sm font-semibold text-gray-200">{{ $egg->template->name }}</p>
                                                <p class="text-xs font-bold
                                                    {{ $egg->rarity === 'legendary' ? 'text-yellow-400' :
                                                       ($egg->rarity === 'epic' ? 'text-purple-400' :
                                                       ($egg->rarity === 'rare' ? 'text-blue-400' :
                                                       ($egg->rarity === 'uncommon' ? 'text-green-400' : 'text-gray-400'))) }}">
                                                    {{ match($egg->rarity) {
                                                        'common' => 'Zwykłe',
                                                        'uncommon' => 'Nietypowe',
                                                        'rare' => 'Rzadkie',
                                                        'epic' => 'Epickie',
                                                        'legendary' => 'Legendarne',
                                                        default => ucfirst($egg->rarity ?? 'common'),
                                                    } }}
                                                    @if($egg->stack_size > 1)
                                                        • x{{ $egg->stack_size }}
                                                    @endif
                                                </p>
                                            </div>
                                        </div>
                                        <button wire:click="placeEgg('{{ $egg->id }}')"
                                            class="bg-amber-700 hover:bg-amber-600 text-white text-xs font-bold py-1.5 px-3 rounded transition-colors"
                                            wire:loading.attr="disabled" wire:target="placeEgg('{{ $egg->id }}')">
                                            <span wire:loading.remove wire:target="placeEgg('{{ $egg->id }}')">Umieść</span>
                                            <span wire:loading wire:target="placeEgg('{{ $egg->id }}')">...</span>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-600">Nie posiadasz żadnych jajek w plecaku.</p>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        {{-- Right: Pets list --}}
        <div class="lg:col-span-2">
            <div class="bg-gray-800/80 border border-gray-700 rounded-xl p-5">
                <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4 flex items-center">
                    <span class="text-lg mr-2">🐾</span> Twoje zwierzaki
                    <span class="ml-2 bg-gray-700 text-gray-300 text-xs font-bold px-2 py-0.5 rounded-full">{{ $pets->count() }}</span>
                </h3>

                @if($pets->count() > 0)
                    <div class="space-y-3">
                        @foreach($pets as $pet)
                            @php
                                $rarityColor = match($pet->rarity) {
                                    'legendary' => 'yellow',
                                    'epic' => 'purple',
                                    'rare' => 'blue',
                                    'uncommon' => 'green',
                                    default => 'gray',
                                };
                                $rarityLabel = match($pet->rarity) {
                                    'common' => 'Zwykły',
                                    'uncommon' => 'Nietypowy',
                                    'rare' => 'Rzadki',
                                    'epic' => 'Epicki',
                                    'legendary' => 'Legendarny',
                                    default => ucfirst($pet->rarity),
                                };
                            @endphp
                            <div class="flex items-center justify-between bg-gray-900/50 rounded-lg p-4 border {{ $pet->is_equipped ? 'border-amber-500 bg-amber-950/20' : 'border-gray-700/50' }} transition-all duration-200">
                                <div class="flex items-center space-x-4">
                                    {{-- Pet icon --}}
                                    <div class="w-12 h-12 rounded-full border-2 border-{{ $rarityColor }}-500/50 bg-gray-800 flex items-center justify-center text-2xl {{ $pet->is_equipped ? 'ring-2 ring-amber-400 ring-offset-2 ring-offset-gray-900' : '' }}">
                                        🐾
                                    </div>

                                    <div>
                                        <div class="flex items-center space-x-2">
                                            <h4 class="font-bold text-gray-100" style="font-family: 'Cinzel', serif;">{{ $pet->name }}</h4>
                                            @if($pet->is_equipped)
                                                <span class="bg-amber-600/30 text-amber-300 text-xs font-semibold px-2 py-0.5 rounded-full border border-amber-600/50">
                                                    AKTYWNY
                                                </span>
                                            @endif
                                        </div>
                                        <div class="flex items-center space-x-2 mt-0.5">
                                            <span class="text-xs font-bold text-{{ $rarityColor }}-400">{{ $rarityLabel }}</span>
                                            <span class="text-gray-600 text-xs">•</span>
                                            <span class="text-xs text-gray-400">Poz. {{ $pet->level }}</span>
                                            <span class="text-gray-600 text-xs">•</span>
                                            <span class="text-xs text-gray-500">CP: {{ $pet->getCombatPower() }}</span>
                                        </div>

                                        {{-- Pet stats --}}
                                        <div class="flex space-x-3 mt-1.5">
                                            @foreach($pet->stats ?? [] as $stat => $value)
                                                <span class="text-xs">
                                                    <span class="text-gray-500 uppercase">{{ $stat }}:</span>
                                                    <span class="text-amber-300 font-bold">{{ $value }}</span>
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                {{-- Equip button --}}
                                <button wire:click="toggleEquipPet({{ $pet->id }})"
                                    class="{{ $pet->is_equipped
                                        ? 'bg-red-800/80 hover:bg-red-700 text-red-200 border border-red-700/50'
                                        : 'bg-green-800/80 hover:bg-green-700 text-green-200 border border-green-700/50' }}
                                        font-bold py-2 px-4 rounded-lg transition-colors text-sm"
                                    wire:loading.attr="disabled" wire:target="toggleEquipPet({{ $pet->id }})">
                                    <span wire:loading.remove wire:target="toggleEquipPet({{ $pet->id }})">
                                        {{ $pet->is_equipped ? '❌ Zdejmij' : '✅ Załóż' }}
                                    </span>
                                    <span wire:loading wire:target="toggleEquipPet({{ $pet->id }})">
                                        ...
                                    </span>
                                </button>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <div class="text-5xl mb-3 opacity-30">🐾</div>
                        <p class="text-gray-500 text-lg mb-1">Nie posiadasz żadnych zwierzaków</p>
                        <p class="text-gray-600 text-sm">Umieść jajko w inkubatorze, aby wykluć swojego pierwszego peta!</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
