<div class="min-h-screen bg-gradient-to-b from-blue-900/90 via-indigo-800/90 to-purple-900/90 text-amber-100 relative overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-b from-slate-900/40 via-slate-800/50 to-slate-900/60"></div>

    <div class="relative container mx-auto px-4 py-8 min-h-screen">
        {{-- Header --}}
        <div class="flex items-center justify-between mb-8">
            <div class="bg-gradient-to-r from-amber-50/95 to-amber-100/95 border-4 border-amber-700 rounded-lg p-4 shadow-2xl backdrop-blur-sm">
                <h2 class="text-xl font-bold text-amber-900 medieval-font flex items-center gap-2">
                    <span class="text-2xl">⚔️</span> Brońmistrz
                </h2>
            </div>

            <div class="flex items-center gap-4">
                <div class="bg-gray-900 border-2 border-yellow-600 rounded px-4 py-2 font-bold text-yellow-400">
                    🪙 {{ $character->gold }}
                </div>
                <button wire:click="backToHub" @click="$dispatch('location-leave')"
                    class="bg-gradient-to-r from-slate-600 to-slate-700 hover:from-slate-700 hover:to-slate-800 text-amber-200 font-bold py-2 px-4 rounded-lg transition-all duration-200 transform hover:scale-105 shadow-lg medieval-font">
                    🏰 Powrót do miasta
                </button>
            </div>
        </div>

        <div class="bg-gray-900 border-2 border-amber-700 rounded-lg shadow-xl p-4">
            
            {{-- Tabs --}}
            <div class="flex overflow-x-auto whitespace-nowrap border-b border-gray-700 mb-4 gap-2 pb-2 custom-scrollbar flex-nowrap">
                <button wire:click="setTab('buy')" class="px-6 py-2 font-bold transition-colors shrink-0 {{ $activeTab === 'buy' ? 'bg-amber-700 text-white rounded-t-lg' : 'text-gray-400 hover:text-amber-500' }}">
                    Kup
                </button>
                <button wire:click="setTab('sell')" class="px-6 py-2 font-bold transition-colors shrink-0 {{ $activeTab === 'sell' ? 'bg-amber-700 text-white rounded-t-lg' : 'text-gray-400 hover:text-amber-500' }}">
                    Sprzedaj
                </button>
                <button wire:click="setTab('upgrade')" class="px-6 py-2 font-bold transition-colors flex items-center gap-2 shrink-0 {{ $activeTab === 'upgrade' ? 'bg-amber-700 text-white rounded-t-lg' : 'text-gray-400 hover:text-amber-500' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    Kuźnia Ulepszeń
                </button>
                <button wire:click="setTab('crafting')" class="px-6 py-2 font-bold transition-colors flex items-center gap-2 shrink-0 {{ $activeTab === 'crafting' ? 'bg-amber-700 text-white rounded-t-lg' : 'text-gray-400 hover:text-amber-500' }}">
                    🛠️ Rzemiosło
                </button>
            </div>

            {{-- Content --}}
            <div class="min-h-[400px]">
                @if($activeTab === 'buy')
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($shopItems as $item)
                            <div class="bg-gray-800 border border-gray-600 rounded p-4 flex flex-col items-center text-center relative">
                                @if($item->is_limited)
                                    <div class="absolute -top-3 right-2 bg-red-600 text-white text-[10px] font-bold px-2 py-1 rounded shadow-lg animate-pulse">
                                        🔥 Zostało: {{ $item->max_quantity - $item->sold_quantity }}
                                    </div>
                                @endif
                                <h3 class="font-bold text-lg text-blue-300 mb-2">{{ $item->template->name }}</h3>
                                <p class="text-sm text-gray-400 mb-2">Poziom: {{ $item->template->level_requirement }}</p>
                                
                                @if(count($item->template->base_stats ?? []) > 0)
                                    <div class="mt-2 text-green-400 border-t border-gray-700 pt-2 space-y-1">
                                        @foreach($item->template->base_stats as $stat => $val)
                                            <div class="flex justify-between">
                                                <span class="capitalize">{{ str_replace('_', ' ', $stat) }}</span>
                                                <span class="font-bold">+{{ $val }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                                
                                <div class="mt-auto w-full">
                                    <button wire:click="buyItem('{{ $item->id }}')" wire:loading.attr="disabled" wire:loading.class="opacity-50 cursor-not-allowed" wire:target="buyItem('{{ $item->id }}')" class="w-full bg-amber-600 hover:bg-amber-500 text-white font-bold py-2 px-4 rounded shadow flex justify-center items-center gap-2 transition-colors">
                                        <span wire:loading.remove wire:target="buyItem('{{ $item->id }}')">Kup za</span>
                                        <span wire:loading.remove wire:target="buyItem('{{ $item->id }}')" class="text-yellow-200">🪙 {{ $shopPrices[$item->id] }}</span>
                                        <span wire:loading wire:target="buyItem('{{ $item->id }}')"><svg class="animate-spin inline-block h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> Przetwarzanie...</span>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @elseif($activeTab === 'sell')
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2">
                        @foreach($inventoryItems as $item)
                            <div wire:click="sellItem('{{ $item->id }}')" class="aspect-square bg-gray-800 border border-gray-600 rounded flex flex-col items-center justify-center cursor-pointer hover:border-red-500 relative group transition-colors {{ count($item->roll_stats['enchants'] ?? []) > 0 ? 'enchanted-border' : '' }}">
                                <div class="text-center text-xs text-white my-4">
                                    <span class="block truncate w-20">{{ $item->template->name }}</span>
                                    @if($item->upgrade_level > 0)
                                        <span class="text-yellow-400">+{{ $item->upgrade_level }}</span>
                                    @endif
                                    @if($item->stack_size > 1)
                                        <span class="text-blue-300 font-bold block">{{ $item->stack_size }}x</span>
                                    @endif
                                </div>
                                @if($item->location === 'equipped')
                                    <div class="absolute top-0 right-0 bg-blue-600/90 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-bl-lg rounded-tr border-b border-l border-blue-500 shadow-sm">
                                        Założone
                                    </div>
                                @endif
                                <div class="mt-auto font-bold text-yellow-500 text-sm">
                                    🪙 {{ $sellPrices[$item->id] }}
                                </div>

                                {{-- Tooltip --}}
                                <div class="hidden group-hover:block absolute bottom-full mb-2 z-50 bg-gray-900 border border-red-500 p-2 rounded text-xs w-48 shadow-xl">
                                    @if($item->location === 'equipped')
                                        <p class="font-bold text-red-400 text-center">Musisz zdjąć przedmiot przed sprzedażą!</p>
                                    @else
                                        <p class="font-bold text-red-400 text-center">Kliknij, aby sprzedać!</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                        
                        @if($inventoryItems->isEmpty())
                            <div class="col-span-full text-center text-gray-500 py-12">
                                Twój plecak jest pusty.
                            </div>
                        @endif
                    </div>
                @elseif($activeTab === 'upgrade')
                    <div class="flex flex-col gap-4">
                        <div class="bg-gray-800 border border-amber-600 p-4 rounded text-sm text-gray-300">
                            <h3 class="font-bold text-amber-500 mb-2 text-lg">Zasady Kuźni</h3>
                            <ul class="list-disc pl-5 space-y-1">
                                <li>Możesz ulepszać ekwipunek od poziomu <span class="text-white font-bold">+0</span> do <span class="text-white font-bold">+9</span>.</li>
                                <li>Im wyższy poziom, tym niższa szansa na pomyślne ulepszenie.</li>
                                <li><span class="text-red-400 font-bold">Uwaga:</span> W przypadku niepowodzenia tracisz jedynie złoto i materiały. Przedmiot nie niszczy się i nie traci swojego obecnego poziomu ulepszenia!</li>
                            </ul>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($upgradableItems as $item)
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
                                    
                                    @if($item->upgrade_level < 9)
                                        @php
                                            $currentBonus = $item->getUpgradeBonusStats();
                                            $nextBonus = $item->getUpgradeBonusStats($item->upgrade_level + 1);
                                        @endphp
                                        @if(count($nextBonus) > 0)
                                            <div class="w-full bg-gray-900 rounded p-2 mb-2 text-xs border border-gray-700 text-left">
                                                <div class="text-gray-400 font-bold mb-1 border-b border-gray-700 pb-1 text-center">Dodatkowe bonusy ulepszenia:</div>
                                                @foreach($nextBonus as $stat => $val)
                                                    <div class="flex justify-between">
                                                        <span class="capitalize text-gray-300">{{ str_replace('_', ' ', $stat) }}</span>
                                                        <span class="text-green-400 font-bold">+{{ $currentBonus[$stat] ?? 0 }} ➔ <span class="text-yellow-400">+{{ $val }}</span></span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif

                                        <div class="w-full bg-gray-900 rounded p-2 mb-4 text-xs">
                                            <div class="flex justify-between mb-1">
                                                <span class="text-gray-400">Szansa na sukces:</span>
                                                <span class="{{ $upgradeCosts[$item->id]['chance'] > 50 ? 'text-green-400' : 'text-orange-400' }} font-bold">
                                                    {{ $upgradeCosts[$item->id]['chance'] }}%
                                                </span>
                                            </div>
                                            <div class="flex justify-between mb-1">
                                                <span class="text-gray-400">Koszt złota:</span>
                                                <span class="text-yellow-400 font-bold">🪙 {{ $upgradeCosts[$item->id]['gold'] }}</span>
                                            </div>
                                            @foreach($upgradeCosts[$item->id]['materials'] as $reqMat)
                                                @php
                                                    $owned = $inventoryMaterials->where('template_id', $reqMat['template_id'])->sum('stack_size');
                                                    $hasEnough = $owned >= $reqMat['quantity'];
                                                @endphp
                                                <div class="flex justify-between">
                                                    <span class="text-gray-400">{{ $reqMat['name'] }}:</span>
                                                    <span class="{{ $hasEnough ? 'text-purple-400' : 'text-red-400' }} font-bold">
                                                        {{ $owned }} / {{ $reqMat['quantity'] }}
                                                    </span>
                                                </div>
                                            @endforeach
                                        </div>
                                        
                                        <div class="mt-auto w-full">
                                            <button wire:click="upgradeItem('{{ $item->id }}')" wire:loading.attr="disabled" wire:loading.class="opacity-50 cursor-not-allowed" wire:target="upgradeItem('{{ $item->id }}')" class="w-full bg-green-700 hover:bg-green-600 text-white font-bold py-2 px-4 rounded shadow flex justify-center items-center gap-2 transition-colors">
                                                <span wire:loading.remove wire:target="upgradeItem('{{ $item->id }}')">Ulepsz na +{{ $item->upgrade_level + 1 }}</span>
                                                <span wire:loading wire:target="upgradeItem('{{ $item->id }}')"><svg class="animate-spin inline-block h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> Przetwarzanie...</span>
                                            </button>
                                        </div>
                                    @else
                                        <div class="mt-auto w-full bg-gray-700 text-gray-400 font-bold py-2 px-4 rounded text-center">
                                            Osiągnięto maksymalny poziom
                                        </div>
                                    @endif
                                </div>
                            @endforeach

                            @if($upgradableItems->isEmpty())
                                <div class="col-span-full text-center text-gray-500 py-12">
                                    Nie masz żadnych przedmiotów tego typu do ulepszenia.
                                </div>
                            @endif
                        </div>
                    </div>
                @elseif($activeTab === 'crafting')
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($recipes as $recipe)
                            <div class="bg-gray-800 border border-gray-600 rounded-lg p-6 flex flex-col relative shadow-xl">
                                <div class="text-center mb-4 pb-4 border-b border-gray-700">
                                    <h3 class="font-bold text-xl text-blue-300">{{ $recipe['result_name'] }}</h3>
                                    <p class="text-sm text-gray-400 mt-1">Koszt: <span class="text-yellow-400 font-bold">🪙 {{ $recipe['gold_cost'] }}</span></p>
                                </div>
                                
                                <div class="flex-grow">
                                    <h4 class="text-amber-500 font-bold text-sm mb-2">Potrzebne materiały:</h4>
                                    <div class="space-y-2">
                                        @foreach($recipe['ingredients'] as $ing)
                                            <div class="flex justify-between items-center text-sm bg-gray-900 p-2 rounded border border-gray-700">
                                                <span class="text-gray-300">{{ $ing['name'] }}</span>
                                                <span class="{{ $ing['ok'] ? 'text-green-400' : 'text-red-400' }} font-bold">
                                                    {{ $ing['owned'] }} / {{ $ing['required'] }}
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="mt-6 pt-4 border-t border-gray-700">
                                    @if($recipe['can_craft'])
                                        <button wire:click="craftItem('{{ $recipe['id'] }}')" wire:loading.attr="disabled" wire:loading.class="opacity-50 cursor-not-allowed" wire:target="craftItem('{{ $recipe['id'] }}')" class="w-full bg-gradient-to-r from-amber-600 to-yellow-600 hover:from-amber-500 hover:to-yellow-500 text-white font-bold py-3 px-4 rounded shadow flex justify-center items-center gap-2 transition-all transform hover:scale-105">
                                            <span wire:loading.remove wire:target="craftItem('{{ $recipe['id'] }}')">🛠️ Wytwórz</span>
                                            <span wire:loading wire:target="craftItem('{{ $recipe['id'] }}')"><svg class="animate-spin inline-block h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> Przetwarzanie...</span>
                                        </button>
                                    @else
                                        <button disabled class="w-full bg-gray-700 text-gray-500 font-bold py-3 px-4 rounded cursor-not-allowed flex justify-center items-center gap-2">
                                            <span>❌ Brak surowców</span>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                        
                        @if(empty($recipes))
                            <div class="col-span-full text-center text-gray-500 py-12">
                                Brak dostępnych przepisów.
                            </div>
                        @endif
                    </div>
                @endif
            </div>

        </div>
    </div>

    {{-- Upgrade Modal --}}
    @if($showUpgradeModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm transition-opacity">
            <div class="bg-gray-900 border-4 {{ $upgradeModalType === 'success' ? 'border-green-600' : 'border-red-600' }} rounded-lg p-8 max-w-md w-full text-center shadow-2xl transform transition-all scale-100">
                <div class="text-6xl mb-4">
                    @if($upgradeModalType === 'success')
                        ✨
                    @else
                        💥
                    @endif
                </div>
                <h3 class="text-3xl font-bold {{ $upgradeModalType === 'success' ? 'text-green-500' : 'text-red-500' }} medieval-font mb-4">
                    {{ $upgradeModalTitle }}
                </h3>
                <p class="text-gray-300 text-lg mb-8">
                    {{ $upgradeModalMessage }}
                </p>
                <button wire:click="closeUpgradeModal" class="w-full py-3 px-6 rounded-lg font-bold text-white shadow-lg transition-colors {{ $upgradeModalType === 'success' ? 'bg-green-700 hover:bg-green-600' : 'bg-red-700 hover:bg-red-600' }}">
                    Kontynuuj
                </button>
            </div>
        </div>
    @endif

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&display=swap');
        .medieval-font { font-family: 'Cinzel', serif; }

        @keyframes borderGlow {
            0% { box-shadow: 0 0 5px #a855f7, inset 0 0 5px #a855f7; border-color: #a855f7; }
            50% { box-shadow: 0 0 15px #d946ef, inset 0 0 10px #d946ef; border-color: #d946ef; }
            100% { box-shadow: 0 0 5px #a855f7, inset 0 0 5px #a855f7; border-color: #a855f7; }
        }
        .enchanted-border {
            animation: borderGlow 2s infinite alternate;
            border-width: 2px;
            z-index: 10;
        }
    </style>
</div>
