<div class="min-h-screen bg-gradient-to-b from-purple-900/90 via-indigo-900/90 to-blue-900/90 text-amber-100 relative overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-b from-slate-900/40 via-slate-800/50 to-slate-900/60"></div>

    <div class="relative container mx-auto px-4 py-8 min-h-screen">
        {{-- Header --}}
        <div class="flex items-center justify-between mb-8">
            <div class="bg-gradient-to-r from-purple-50/95 to-purple-100/95 border-4 border-purple-700 rounded-lg p-4 shadow-2xl backdrop-blur-sm">
                <h2 class="text-xl font-bold text-purple-900 medieval-font flex items-center gap-2">
                    <span class="text-2xl">🧙‍♂️</span> Czarodziej
                </h2>
            </div>

            <div class="flex items-center gap-4">
                <div class="bg-gray-900 border-2 border-yellow-600 rounded px-4 py-2 font-bold text-yellow-400">
                    🪙 {{ $character->gold }} | 💎 {{ $character->user->premium_currency ?? 0 }}
                </div>
                <button wire:click="backToHub"
                    class="bg-gradient-to-r from-slate-600 to-slate-700 hover:from-slate-700 hover:to-slate-800 text-amber-200 font-bold py-2 px-4 rounded-lg transition-all duration-200 transform hover:scale-105 shadow-lg medieval-font">
                    🏰 Powrót do miasta
                </button>
            </div>
        </div>

        <div class="bg-gray-900 border-2 border-purple-700 rounded-lg shadow-xl p-4">
            
            <div class="flex flex-col gap-4">
                <div class="bg-gray-800 border border-purple-600 p-4 rounded text-sm text-gray-300 mb-4">
                    <h3 class="font-bold text-purple-400 mb-2 text-lg">Pradawna Wieża Magii</h3>
                    <ul class="list-disc pl-5 space-y-1">
                        <li>Możesz dodać do 5 magicznych bonusów do każdego przedmiotu (broń/zbroja/biżuteria).</li>
                        <li>Każdy kolejny bonus ma coraz mniejszą szansę na sukces (75%, 50%, 40%, 30%, 20%).</li>
                        <li>W każdej chwili możesz wyczyścić i przelosować wszystkie przypisane bonusy naraz!</li>
                    </ul>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($enchantableItems as $item)
                        @php
                            $enchants = $item->roll_stats['enchants'] ?? [];
                            $enchantCount = count($enchants);
                            $nextChance = [75, 50, 40, 30, 20][$enchantCount] ?? 0;
                            $rerollGoldCost = max(200, $enchantCount * 200);
                            $rerollGemCost = max(2, $enchantCount * 2);
                        @endphp
                        <div class="bg-gray-800 border border-gray-600 rounded p-4 flex flex-col items-center text-center relative">
                            @if($item->location === 'equipped')
                                <div class="absolute top-0 right-0 bg-blue-600/90 text-white text-[10px] font-bold px-2 py-1 rounded-bl-lg rounded-tr border-b border-l border-blue-500 shadow-sm">
                                    Założone
                                </div>
                            @endif
                            <h3 class="font-bold text-lg text-blue-300 mb-2 mt-2">
                                {{ $item->template->name }} 
                                @if($item->upgrade_level > 0)<span class="text-yellow-400">+{{ $item->upgrade_level }}</span>@endif
                            </h3>
                            
                            {{-- Aktywne bonusy --}}
                            <div class="w-full bg-gray-900 rounded p-2 mb-2 text-xs border border-gray-700 text-left min-h-[60px]">
                                <div class="text-purple-400 font-bold mb-1 border-b border-gray-700 pb-1 text-center">Magiczne Bonusy ({{ $enchantCount }}/5):</div>
                                @if($enchantCount > 0)
                                    @foreach($enchants as $bonusType => $bonusValue)
                                        <div class="flex justify-between text-yellow-300">
                                            <span class="capitalize">{{ str_replace('_', ' ', $bonusType) }}</span>
                                            <span class="font-bold">+{{ $bonusValue }}</span>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="text-gray-500 text-center italic mt-1">Brak magicznych mocy</div>
                                @endif
                            </div>

                            @if($enchantCount < 5)
                                <div class="mt-auto w-full mb-2">
                                    <button wire:click="openEnchantModal('{{ $item->id }}')" class="w-full bg-purple-700 hover:bg-purple-600 text-white font-bold py-2 px-4 rounded shadow flex flex-col justify-center items-center gap-1 transition-colors">
                                        <span>Zaklnij Przedmiot</span>
                                        <span class="text-[10px] text-purple-200">Szansa: {{ $nextChance }}%</span>
                                    </button>
                                </div>
                            @else
                                <div class="mt-auto w-full mb-2 bg-gray-700 text-gray-400 font-bold py-2 px-4 rounded text-center">
                                    Maksymalna liczba bonusów
                                </div>
                            @endif

                            @if($enchantCount > 0)
                                <div class="w-full">
                                    <button wire:click="openEnchantModal('{{ $item->id }}')" class="w-full bg-indigo-700 hover:bg-indigo-600 text-white font-bold py-2 px-4 rounded shadow flex justify-center items-center transition-colors">
                                        Przelosuj (Reroll)
                                    </button>
                                </div>
                            @endif
                        </div>
                    @endforeach

                    @if($enchantableItems->isEmpty())
                        <div class="col-span-full text-center text-gray-500 py-12">
                            Nie masz żadnych przedmiotów do zaklinania (wymagany typ: broń, zbroja lub biżuteria).
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>

    {{-- Modal akcji zaklinania --}}
    @if($showEnchantModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm transition-opacity">
            <div class="bg-gray-900 border-4 {{ $enchantModalType === 'success' ? 'border-green-600' : ($enchantModalType === 'error' ? 'border-red-600' : 'border-purple-600') }} rounded-lg p-8 max-w-md w-full text-center shadow-2xl transform transition-all scale-100">
                <div class="text-6xl mb-4">
                    @if($enchantModalType === 'success') ✨
                    @elseif($enchantModalType === 'error') 💥
                    @else 🧙‍♂️ @endif
                </div>
                <h3 class="text-3xl font-bold {{ $enchantModalType === 'success' ? 'text-green-500' : ($enchantModalType === 'error' ? 'text-red-500' : 'text-purple-500') }} medieval-font mb-4">
                    {{ $enchantModalTitle }}
                </h3>
                <p class="text-gray-300 text-lg mb-8">
                    {{ $enchantModalMessage }}
                </p>

                @if($enchantModalType === 'info')
                    @php
                        $item = $enchantableItems->firstWhere('id', $selectedItemId);
                        $enchantCount = $item ? count($item->roll_stats['enchants'] ?? []) : 0;
                        $rerollGoldCost = max(200, $enchantCount * 200);
                        $rerollGemCost = max(2, $enchantCount * 2);
                    @endphp
                    
                    <div class="flex flex-col gap-3">
                        @if($enchantCount < 5)
                            <div class="text-left text-sm text-gray-400 mb-1">Dodanie nowego bonusu:</div>
                            <div class="flex gap-2">
                                <button wire:click="enchant('gold')" class="w-1/2 py-2 rounded font-bold text-white shadow-lg bg-yellow-700 hover:bg-yellow-600 transition-colors">
                                    🪙 500 Złota
                                </button>
                                <button wire:click="enchant('gems')" class="w-1/2 py-2 rounded font-bold text-white shadow-lg bg-blue-700 hover:bg-blue-600 transition-colors">
                                    💎 5 Gemów
                                </button>
                            </div>
                        @endif

                        @if($enchantCount > 0)
                            <div class="text-left text-sm text-gray-400 mt-3 mb-1">Reroll obecnych bonusów:</div>
                            <div class="flex gap-2">
                                <button wire:click="reroll('gold')" class="w-1/2 py-2 rounded font-bold text-white shadow-lg bg-orange-700 hover:bg-orange-600 transition-colors">
                                    🪙 {{ $rerollGoldCost }} Złota
                                </button>
                                <button wire:click="reroll('gems')" class="w-1/2 py-2 rounded font-bold text-white shadow-lg bg-indigo-700 hover:bg-indigo-600 transition-colors">
                                    💎 {{ $rerollGemCost }} Gemów
                                </button>
                            </div>
                        @endif
                    </div>
                    <button wire:click="closeEnchantModal" class="mt-6 text-gray-500 hover:text-gray-300 underline text-sm">
                        Anuluj
                    </button>
                @else
                    <button wire:click="closeEnchantModal" class="w-full py-3 px-6 rounded-lg font-bold text-white shadow-lg transition-colors {{ $enchantModalType === 'success' ? 'bg-green-700 hover:bg-green-600' : 'bg-red-700 hover:bg-red-600' }}">
                        Zamknij
                    </button>
                @endif
            </div>
        </div>
    @endif

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&display=swap');
        .medieval-font { font-family: 'Cinzel', serif; }
    </style>
</div>
