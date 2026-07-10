<div class="min-h-screen bg-gradient-to-b from-purple-900/90 via-indigo-800/90 to-purple-900/90 text-amber-100 relative overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-b from-slate-900/40 via-slate-800/50 to-slate-900/60"></div>

    <div class="relative container mx-auto px-4 py-8 min-h-screen">
        {{-- Header --}}
        <div class="flex items-center justify-between mb-8">
            <div class="bg-gradient-to-r from-purple-50/95 to-purple-100/95 border-4 border-purple-700 rounded-lg p-4 shadow-2xl backdrop-blur-sm">
                <h2 class="text-xl font-bold text-purple-900 medieval-font">{{ $character->name }} u Wiedźmy</h2>
            </div>
            
            <div class="flex gap-4 items-center">
                <div class="bg-amber-100 border-2 border-amber-400 text-amber-900 font-bold py-2 px-6 rounded-lg shadow-lg medieval-font flex items-center gap-2">
                    <span class="text-xl">💰</span>
                    <span>{{ number_format($character->gold, 0, ',', ' ') }} Złota</span>
                </div>

                <button wire:click="backToHub" @click="$dispatch('location-leave')" class="bg-gradient-to-r from-slate-600 to-slate-700 hover:from-slate-700 hover:to-slate-800 text-amber-200 font-bold py-2 px-4 rounded-lg transition-all duration-200 transform hover:scale-105 shadow-lg medieval-font">
                    🏰 Powrót do miasta
                </button>
            </div>
        </div>



        <div class="max-w-4xl mx-auto flex gap-4 mb-6">
            <button wire:click="switchTab('special')" class="{{ $activeTab === 'special' ? 'bg-purple-700' : 'bg-purple-900/50' }} hover:bg-purple-600 px-6 py-3 rounded-lg font-bold border-2 border-purple-500 transition-all">✨ Wywary Specjalne</button>
            <button wire:click="switchTab('shop')" class="{{ $activeTab === 'shop' ? 'bg-purple-700' : 'bg-purple-900/50' }} hover:bg-purple-600 px-6 py-3 rounded-lg font-bold border-2 border-purple-500 transition-all">🛒 Sklep Alchemiczny</button>
            <button wire:click="switchTab('crafting')" class="{{ $activeTab === 'crafting' ? 'bg-purple-700' : 'bg-purple-900/50' }} hover:bg-purple-600 px-6 py-3 rounded-lg font-bold border-2 border-purple-500 transition-all">⚗️ Warzenie Mikstur</button>
        </div>

        {{-- Witch content --}}
        <div class="text-center">
            <div class="bg-gradient-to-br from-purple-50/95 to-purple-100/95 border-4 border-purple-700 rounded-lg p-8 shadow-2xl backdrop-blur-sm max-w-4xl mx-auto flex flex-col md:flex-row gap-8 items-start text-left">
                
                <div class="w-full md:w-1/3 flex flex-col items-center justify-center sticky top-8">
                    <div class="text-9xl mb-4">🧙‍♀️</div>
                    <div class="bg-purple-900 text-purple-100 px-4 py-1 rounded-full text-sm font-bold shadow-lg border border-purple-700">Czarownica</div>
                </div>

                <div class="w-full md:w-2/3">
                    @if($activeTab === 'special')
                        <h1 class="text-4xl font-bold text-purple-900 medieval-font mb-2">"Witaj wędrowcze..."</h1>
                        <p class="text-lg text-purple-800 font-semibold mb-6 italic">
                            "Szukasz drogi na skróty? Podzielę się z tobą najcenniejszą miksturą, ale twój organizm zniesie ją tylko raz na dobę!"
                        </p>
                        <div class="bg-purple-900/10 border-2 border-purple-700 p-6 rounded-lg shadow-inner flex flex-col md:flex-row items-center justify-between gap-4 transition-all duration-300 hover:bg-purple-900/20">
                            <div>
                                <h3 class="text-xl font-bold text-purple-900 medieval-font mb-1 flex items-center gap-2">
                                    <span>🧪</span> Specjalna Mikstura Doświadczenia
                                </h3>
                                <p class="text-sm text-purple-800 mb-2 font-medium">+20% zdobywanego doświadczenia z potworów przez 10 minut.</p>
                                <div class="inline-flex items-center gap-1 bg-amber-100 text-amber-800 px-3 py-1 rounded-full text-sm font-bold border border-amber-300 shadow-sm">
                                    <span>💰</span> Cena: 1500 Złota
                                </div>
                            </div>

                            <div class="flex-shrink-0 text-center md:text-right mt-4 md:mt-0">
                                @if($canBuySpecial)
                                    <button wire:click="buySpecialExpPotion" wire:loading.attr="disabled" class="bg-gradient-to-r from-purple-700 to-indigo-700 hover:from-purple-600 hover:to-indigo-600 text-purple-50 font-bold py-3 px-8 rounded-lg shadow-lg border-2 border-purple-500 transition-all duration-200 transform hover:scale-105 medieval-font text-lg">
                                        <span wire:loading.remove>Kup Miksturę</span>
                                        <span wire:loading>Warzenie... ⚗️</span>
                                    </button>
                                @else
                                    <div class="bg-slate-800 border-2 border-slate-600 p-3 rounded-lg shadow-inner">
                                        <div class="text-xs text-amber-400 font-bold mb-1 uppercase tracking-wider text-center">Gotowa za:</div>
                                        <div class="text-slate-200 font-mono text-lg font-bold text-center w-32"
                                             x-data="{ 
                                                end: new Date('{{ $specialCooldown->toIso8601String() }}').getTime(),
                                                timeLeft: '',
                                                update() {
                                                    let diff = this.end - new Date().getTime();
                                                    if(diff <= 0) { this.timeLeft = 'Gotowe!'; return; }
                                                    let h = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                                                    let m = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                                                    let s = Math.floor((diff % (1000 * 60)) / 1000);
                                                    this.timeLeft = h + 'h ' + m + 'm ' + s + 's';
                                                }
                                             }"
                                             x-init="update(); setInterval(() => update(), 1000)"
                                             x-text="timeLeft"
                                        >
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    
                    @elseif($activeTab === 'shop')
                        <h1 class="text-4xl font-bold text-purple-900 medieval-font mb-2">"Oto moje towary..."</h1>
                        <p class="text-lg text-purple-800 font-semibold mb-6 italic">
                            "Złoto nie śmierdzi, a ja mam tu podstawowe mikstury. Wybierz, co cię interesuje."
                        </p>
                        <div class="space-y-4 max-h-[500px] overflow-y-auto pr-2 custom-scrollbar">
                            @foreach($regularPotions as $potion)
                                @php $price = str_ends_with($potion->id, '-m') ? 800 : 300; @endphp
                                <div class="bg-purple-900/10 border-2 border-purple-700 p-4 rounded-lg shadow-inner flex flex-col md:flex-row items-center justify-between gap-4 transition-all duration-300 hover:bg-purple-900/20">
                                    <div>
                                        <h3 class="text-lg font-bold text-purple-900 medieval-font mb-1 flex items-center gap-2">
                                            <span>🍾</span> {{ $potion->name }}
                                        </h3>
                                        <p class="text-xs text-purple-800 mb-2 font-medium">{{ $potion->description }}</p>
                                        <div class="inline-flex items-center gap-1 bg-amber-100 text-amber-800 px-2 py-1 rounded-full text-xs font-bold border border-amber-300 shadow-sm">
                                            <span>💰</span> Cena: {{ $price }} Złota
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <button wire:click="buyRegularPotion('{{ $potion->id }}')" wire:loading.attr="disabled" wire:loading.class="opacity-50 cursor-not-allowed" wire:target="buyRegularPotion('{{ $potion->id }}')" class="bg-purple-700 hover:bg-purple-600 text-purple-50 font-bold py-2 px-4 rounded-lg shadow-lg border border-purple-500 transition-all duration-200 transform hover:scale-105 medieval-font">
                                            <span wire:loading.remove wire:target="buyRegularPotion('{{ $potion->id }}')">Kup</span>
                                            <svg wire:loading wire:target="buyRegularPotion('{{ $potion->id }}')" class="animate-spin inline-block h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                    @elseif($activeTab === 'crafting')
                        <h1 class="text-4xl font-bold text-purple-900 medieval-font mb-2">"Warzenie Mikstur..."</h1>
                        <p class="text-lg text-purple-800 font-semibold mb-6 italic">
                            "Masz materiały? Rzuć je do kociołka. Trochę złota za moją pracę i uwarzymy coś specjalnego."
                        </p>
                        <div class="space-y-4 max-h-[500px] overflow-y-auto pr-2 custom-scrollbar">
                            @foreach($recipes as $recipe)
                                <div class="bg-indigo-900/10 border-2 border-indigo-700 p-4 rounded-lg shadow-inner flex flex-col md:flex-row items-center justify-between gap-4 transition-all duration-300 hover:bg-indigo-900/20">
                                    <div class="flex-1">
                                        <h3 class="text-lg font-bold text-indigo-900 medieval-font mb-2 flex items-center gap-2">
                                            <span>⚗️</span> {{ $recipe['result_name'] }}
                                        </h3>
                                        <div class="text-sm text-indigo-800 flex flex-wrap gap-2 mb-2">
                                            @foreach($recipe['ingredients'] as $ing)
                                                <span class="inline-block px-2 py-1 rounded text-xs font-bold {{ $ing['ok'] ? 'bg-green-100 text-green-800 border-green-300' : 'bg-red-100 text-red-800 border-red-300' }} border">
                                                    {{ $ing['name'] }} ({{ $ing['owned'] }}/{{ $ing['required'] }})
                                                </span>
                                            @endforeach
                                            <span class="inline-block px-2 py-1 rounded text-xs font-bold {{ $character->gold >= $recipe['gold_cost'] ? 'bg-amber-100 text-amber-800 border-amber-300' : 'bg-red-100 text-red-800 border-red-300' }} border">
                                                💰 {{ $recipe['gold_cost'] }} Złota
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <button wire:click="craftPotion('{{ $recipe['id'] }}')" 
                                            wire:loading.attr="disabled" wire:loading.class="opacity-50 cursor-not-allowed" wire:target="craftPotion('{{ $recipe['id'] }}')" 
                                            @if(!$recipe['can_craft']) disabled @endif
                                            class="font-bold py-2 px-4 rounded-lg shadow-lg border transition-all duration-200 medieval-font 
                                            {{ $recipe['can_craft'] ? 'bg-indigo-700 hover:bg-indigo-600 text-indigo-50 border-indigo-500 transform hover:scale-105' : 'bg-gray-400 text-gray-200 border-gray-500 cursor-not-allowed opacity-50' }}">
                                            <span wire:loading.remove wire:target="craftPotion('{{ $recipe['id'] }}')">Uwarz</span>
                                            <svg wire:loading wire:target="craftPotion('{{ $recipe['id'] }}')" class="animate-spin inline-block h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
            
            <div class="mt-8 bg-purple-900/50 backdrop-blur-md rounded-lg p-6 max-w-4xl mx-auto border-2 border-purple-800 shadow-xl inline-block text-left">
                <h3 class="text-lg font-bold text-purple-200 mb-3 medieval-font">📜 Wskazówka Czarownicy</h3>
                <p class="text-purple-300 text-sm">Pamiętaj, że możesz mieć aktywną tylko jedną miksturę danego typu naraz. Jeśli wypijesz nową, nadpisze ona starą, ale słabsze wywary nie zastąpią tych silniejszych!</p>
            </div>
        </div>
    </div>
</div>
