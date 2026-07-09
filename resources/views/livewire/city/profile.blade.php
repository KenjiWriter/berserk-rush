<div class="min-h-screen bg-gradient-to-b from-blue-900/90 via-indigo-800/90 to-purple-900/90 text-amber-100 relative overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-b from-slate-900/40 via-slate-800/50 to-slate-900/60"></div>

    <div class="relative container mx-auto px-4 py-8 min-h-screen">
        {{-- Header --}}
        <div class="flex items-center justify-between mb-8">
            <div class="bg-gradient-to-r from-amber-50/95 to-amber-100/95 border-4 border-amber-700 rounded-lg p-4 shadow-2xl backdrop-blur-sm">
                <h2 class="text-xl font-bold text-amber-900 medieval-font">Profil: {{ $character->name }}</h2>
            </div>

            <button wire:click="backToHub"
                class="bg-gradient-to-r from-slate-600 to-slate-700 hover:from-slate-700 hover:to-slate-800 text-amber-200 font-bold py-2 px-4 rounded-lg transition-all duration-200 transform hover:scale-105 shadow-lg medieval-font">
                🏰 Powrót do miasta
            </button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        <!-- Left Side: Character Profile -->
        <div class="bg-gray-900 border-2 border-yellow-600 rounded-lg shadow-xl p-4 flex flex-col h-full">
            <!-- Equipment Slots & Portrait -->
            <div class="flex justify-center items-start gap-4 md:gap-8 mb-8 mt-4">
                <!-- Left Slots -->
                <div class="flex flex-col gap-4">
                    @foreach(['head', 'chest', 'main_hand'] as $slot)
                        <div class="w-16 h-16 bg-gray-800 border-2 {{ isset($equipped[$slot]) ? 'border-blue-500 cursor-pointer hover:border-red-500' : 'border-gray-600' }} rounded flex items-center justify-center relative group"
                             @if(isset($equipped[$slot])) wire:click="unequipItem('{{ $equipped[$slot]->id }}')" @endif>
                            @if(isset($equipped[$slot]))
                                <div class="text-center text-xs text-white">
                                    <span class="block truncate w-14">{{ $equipped[$slot]->template->name }}</span>
                                    <span class="text-yellow-400">+{{ $equipped[$slot]->upgrade_level }}</span>
                                </div>
                                <!-- Tooltip -->
                                <div class="hidden group-hover:block absolute left-full ml-2 z-50 bg-gray-900 border border-gray-600 p-2 rounded text-xs w-48 shadow-lg">
                                    <div class="flex justify-between items-center">
                                        <p class="font-bold text-yellow-400">{{ $equipped[$slot]->template->name }}</p>
                                        <span class="text-indigo-300 font-bold text-xs">⚡ {{ $equipped[$slot]->getCombatPower() }}</span>
                                    </div>
                                    <p class="text-gray-300">Slot: {{ $slot }}</p>
                                    
                                    @if(count($equipped[$slot]->template->base_stats ?? []) > 0)
                                        <div class="mt-2 text-green-400 border-t border-gray-700 pt-2 space-y-1">
                                            @foreach($equipped[$slot]->template->base_stats as $stat => $val)
                                                <div class="flex justify-between">
                                                    <span class="capitalize">{{ str_replace('_', ' ', $stat) }}</span>
                                                    <span class="font-bold">+{{ $val }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif

                                    <p class="text-red-400 mt-2 border-t border-gray-700 pt-2">Click to unequip</p>
                                </div>
                            @else
                                <span class="text-gray-500 text-xs">{{ ucfirst($slot) }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>

                <!-- Portrait & Info -->
                <div class="flex flex-col items-center w-48">
                    <div class="w-full h-64 bg-gray-800 border-4 border-yellow-700 rounded-lg overflow-hidden flex items-center justify-center mb-4 shadow-lg">
                        @if($character->avatar && file_exists(public_path('img/avatars/' . $character->avatar . '.png')))
                            <img src="{{ asset('img/avatars/' . $character->avatar . '.png') }}" alt="Avatar" class="object-cover w-full h-full">
                        @else
                            <div class="text-gray-500 flex flex-col items-center">
                                <svg class="w-16 h-16 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                <span>No Avatar</span>
                            </div>
                        @endif
                    </div>

                    <h2 class="text-2xl font-bold text-yellow-500 text-center drop-shadow-md">{{ $character->name }}</h2>
                    <p class="text-gray-400 font-semibold text-sm">Poziom {{ $character->level }}</p>
                    <div class="bg-indigo-900/80 border border-indigo-500 text-indigo-200 px-3 py-1 rounded-full text-xs font-bold shadow-lg my-2">
                        ⚡ Moc Bojowa: {{ number_format($character->getTotalCombatPower()) }}
                    </div>
                    
                    <!-- XP Bar -->
                    @php 
                        $xpRequired = $character->level * 100;
                        $xpPercent = min(100, ($character->xp / max(1, $xpRequired)) * 100);
                    @endphp
                    <div class="w-full bg-gray-900 rounded-full h-5 relative border-2 border-gray-700 shadow-inner overflow-hidden cursor-help" title="Doświadczenie: {{ $character->xp }} / {{ $xpRequired }}">
                        <div class="bg-gradient-to-r from-blue-700 to-blue-400 h-full transition-all duration-300" style="width: {{ $xpPercent }}%"></div>
                        <span class="absolute inset-0 flex items-center justify-center text-[10px] text-white font-bold drop-shadow-[0_1px_1px_rgba(0,0,0,0.8)]">
                            XP: {{ number_format($character->xp) }} / {{ number_format($xpRequired) }}
                        </span>
                    </div>
                </div>

                <!-- Right Slots -->
                <div class="flex flex-col gap-4">
                    @foreach(['neck', 'ring', 'feet'] as $slot)
                        <div class="w-16 h-16 bg-gray-800 border-2 {{ isset($equipped[$slot]) ? 'border-blue-500 cursor-pointer hover:border-red-500' : 'border-gray-600' }} rounded flex items-center justify-center relative group"
                             @if(isset($equipped[$slot])) wire:click="unequipItem('{{ $equipped[$slot]->id }}')" @endif>
                            @if(isset($equipped[$slot]))
                                <div class="text-center text-xs text-white">
                                    <span class="block truncate w-14">{{ $equipped[$slot]->template->name }}</span>
                                    <span class="text-yellow-400">+{{ $equipped[$slot]->upgrade_level }}</span>
                                </div>
                                <!-- Tooltip -->
                                <div class="hidden group-hover:block absolute right-full mr-2 z-50 bg-gray-900 border border-gray-600 p-2 rounded text-xs w-48 shadow-lg">
                                    <div class="flex justify-between items-center">
                                        <p class="font-bold text-yellow-400">{{ $equipped[$slot]->template->name }}</p>
                                        <span class="text-indigo-300 font-bold text-xs">⚡ {{ $equipped[$slot]->getCombatPower() }}</span>
                                    </div>
                                    <p class="text-gray-300">Slot: {{ $slot }}</p>
                                    
                                    @if(count($equipped[$slot]->template->base_stats ?? []) > 0)
                                        <div class="mt-2 text-green-400 border-t border-gray-700 pt-2 space-y-1">
                                            @foreach($equipped[$slot]->template->base_stats as $stat => $val)
                                                <div class="flex justify-between">
                                                    <span class="capitalize">{{ str_replace('_', ' ', $stat) }}</span>
                                                    <span class="font-bold">+{{ $val }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif

                                    <p class="text-red-400 mt-2 border-t border-gray-700 pt-2">Click to unequip</p>
                                </div>
                            @else
                                <span class="text-gray-500 text-xs">{{ ucfirst($slot) }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Stats -->
            <div class="bg-gray-800 border border-gray-700 rounded p-4 mt-auto">
                <div class="flex justify-between items-end border-b border-gray-700 pb-2 mb-3">
                    <div class="flex gap-6">
                        <button wire:click="setTab('attributes')" class="font-bold text-lg transition-colors {{ $activeTab === 'attributes' ? 'text-yellow-500 border-b-2 border-yellow-500' : 'text-gray-400 hover:text-yellow-400' }}">
                            Atrybuty
                        </button>
                        <button wire:click="setTab('stats')" class="font-bold text-lg transition-colors {{ $activeTab === 'stats' ? 'text-yellow-500 border-b-2 border-yellow-500' : 'text-gray-400 hover:text-yellow-400' }}">
                            Statystyki
                        </button>
                    </div>
                    @if($activeTab === 'attributes' && $character->character_points > 0)
                        <span class="text-green-400 font-bold text-sm animate-pulse bg-green-900/40 px-2 py-1 rounded border border-green-700">Punkty: {{ $character->character_points }}</span>
                    @endif
                </div>
                
                @if($activeTab === 'attributes')
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 text-sm mt-4">
                        <!-- STR -->
                        <div class="flex justify-between items-center group">
                            <span class="text-gray-400 cursor-help border-b border-dashed border-gray-600" title="Siła: Zwiększa obrażenia bazowe broni.">Strength (STR):</span>
                            <div class="flex items-center gap-2">
                                <span class="text-white font-bold text-base w-8 text-right">{{ $totalAttributes['str'] ?? 0 }}</span>
                                @if($character->character_points > 0)
                                    <div class="flex gap-1">
                                        <button wire:click="addAttribute('str', 1)" class="w-6 h-6 bg-green-700 hover:bg-green-500 text-white rounded text-xs flex items-center justify-center font-bold transition shadow" title="Dodaj 1">+1</button>
                                        @if($character->character_points >= 5)
                                            <button wire:click="addAttribute('str', 5)" class="w-6 h-6 bg-green-800 hover:bg-green-600 text-white rounded text-xs flex items-center justify-center font-bold transition shadow" title="Dodaj 5">+5</button>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                        
                        <!-- INT -->
                        <div class="flex justify-between items-center group">
                            <span class="text-gray-400 cursor-help border-b border-dashed border-gray-600" title="Inteligencja: Wpływa na obronę magiczną (w przyszłości na moc magii).">Intelligence (INT):</span>
                            <div class="flex items-center gap-2">
                                <span class="text-white font-bold text-base w-8 text-right">{{ $totalAttributes['int'] ?? 0 }}</span>
                                @if($character->character_points > 0)
                                    <div class="flex gap-1">
                                        <button wire:click="addAttribute('int', 1)" class="w-6 h-6 bg-green-700 hover:bg-green-500 text-white rounded text-xs flex items-center justify-center font-bold transition shadow" title="Dodaj 1">+1</button>
                                        @if($character->character_points >= 5)
                                            <button wire:click="addAttribute('int', 5)" class="w-6 h-6 bg-green-800 hover:bg-green-600 text-white rounded text-xs flex items-center justify-center font-bold transition shadow" title="Dodaj 5">+5</button>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                        
                        <!-- VIT -->
                        <div class="flex justify-between items-center group">
                            <span class="text-gray-400 cursor-help border-b border-dashed border-gray-600" title="Witalność: Zwiększa maksymalną ilość Punktów Życia (HP).">Vitality (VIT):</span>
                            <div class="flex items-center gap-2">
                                <span class="text-white font-bold text-base w-8 text-right">{{ $totalAttributes['vit'] ?? 0 }}</span>
                                @if($character->character_points > 0)
                                    <div class="flex gap-1">
                                        <button wire:click="addAttribute('vit', 1)" class="w-6 h-6 bg-green-700 hover:bg-green-500 text-white rounded text-xs flex items-center justify-center font-bold transition shadow" title="Dodaj 1">+1</button>
                                        @if($character->character_points >= 5)
                                            <button wire:click="addAttribute('vit', 5)" class="w-6 h-6 bg-green-800 hover:bg-green-600 text-white rounded text-xs flex items-center justify-center font-bold transition shadow" title="Dodaj 5">+5</button>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                        
                        <!-- AGI -->
                        <div class="flex justify-between items-center group">
                            <span class="text-gray-400 cursor-help border-b border-dashed border-gray-600" title="Zręczność: Decyduje o kolejności ataku w walce oraz o szansie na uniki.">Agility (AGI):</span>
                            <div class="flex items-center gap-2">
                                <span class="text-white font-bold text-base w-8 text-right">{{ $totalAttributes['agi'] ?? 0 }}</span>
                                @if($character->character_points > 0)
                                    <div class="flex gap-1">
                                        <button wire:click="addAttribute('agi', 1)" class="w-6 h-6 bg-green-700 hover:bg-green-500 text-white rounded text-xs flex items-center justify-center font-bold transition shadow" title="Dodaj 1">+1</button>
                                        @if($character->character_points >= 5)
                                            <button wire:click="addAttribute('agi', 5)" class="w-6 h-6 bg-green-800 hover:bg-green-600 text-white rounded text-xs flex items-center justify-center font-bold transition shadow" title="Dodaj 5">+5</button>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @elseif($activeTab === 'stats')
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4 text-sm mt-4 text-gray-300">
                        <div class="flex justify-between items-center pb-2 border-b border-gray-700/50">
                            <span class="text-gray-400 font-semibold cursor-help border-b border-dashed border-gray-600" title="Maksymalna liczba punktów życia w walce.">Max HP:</span>
                            <span class="text-green-400 font-bold text-base">{{ $derivedStats['max_hp'] }}</span>
                        </div>
                        <div class="flex justify-between items-center pb-2 border-b border-gray-700/50">
                            <span class="text-gray-400 font-semibold cursor-help border-b border-dashed border-gray-600" title="Podstawowe obrażenia ataków fizycznych.">Base Damage:</span>
                            <span class="text-red-400 font-bold text-base">{{ $derivedStats['base_damage_min'] }} - {{ $derivedStats['base_damage_max'] }}</span>
                        </div>
                        <div class="flex justify-between items-center pb-2 border-b border-gray-700/50">
                            <span class="text-gray-400 font-semibold cursor-help border-b border-dashed border-gray-600" title="Podstawowe obrażenia z umiejętności magicznych.">Magic Damage:</span>
                            <span class="text-purple-400 font-bold text-base">{{ $derivedStats['magic_damage_min'] }} - {{ $derivedStats['magic_damage_max'] }}</span>
                        </div>
                        <div class="flex justify-between items-center pb-2 border-b border-gray-700/50">
                            <span class="text-gray-400 font-semibold cursor-help border-b border-dashed border-gray-600" title="Redukuje nadchodzące obrażenia w walce.">Damage Reduction:</span>
                            <span class="text-blue-400 font-bold text-base">{{ $derivedStats['damage_reduction'] }}</span>
                        </div>
                        <div class="flex justify-between items-center pb-2 border-b border-gray-700/50">
                            <span class="text-gray-400 font-semibold cursor-help border-b border-dashed border-gray-600" title="Szansa na zadanie ataku krytycznego (1.5x obrażeń).">Crit Chance:</span>
                            <span class="text-yellow-500 font-bold text-base">{{ $derivedStats['crit_chance'] }}%</span>
                        </div>
                        <div class="flex justify-between items-center pb-2 border-b border-gray-700/50">
                            <span class="text-gray-400 font-semibold cursor-help border-b border-dashed border-gray-600" title="Szansa na całkowite uniknięcie ciosu przeciwnika.">Dodge Chance:</span>
                            <span class="text-emerald-400 font-bold text-base">{{ $derivedStats['dodge_chance'] }}%</span>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Right Side: Inventory -->
        <div class="bg-gray-900 border-2 border-blue-900 rounded-lg shadow-xl p-4 flex flex-col h-full">
            <div class="flex justify-between items-center mb-4 border-b border-gray-700 pb-2">
                <h2 class="text-2xl font-bold text-blue-400">Backpack</h2>
                <div class="text-yellow-400 font-bold flex gap-4">
                    <span>🪙 {{ $character->gold }}</span>
                    <span>💎 {{ $character->gems }}</span>
                </div>
            </div>

            <!-- Inventory Filters & Actions -->
            <div class="flex flex-wrap gap-2 mb-3">
                <button wire:click="setInventoryFilter('all')" class="px-2 py-1 text-xs rounded transition-colors {{ $inventoryFilter === 'all' ? 'bg-blue-600 text-white' : 'bg-gray-800 text-gray-400 hover:bg-gray-700' }}">All</button>
                <button wire:click="setInventoryFilter('weapon')" class="px-2 py-1 text-xs rounded transition-colors {{ $inventoryFilter === 'weapon' ? 'bg-blue-600 text-white' : 'bg-gray-800 text-gray-400 hover:bg-gray-700' }}">Weapons</button>
                <button wire:click="setInventoryFilter('armor')" class="px-2 py-1 text-xs rounded transition-colors {{ $inventoryFilter === 'armor' ? 'bg-blue-600 text-white' : 'bg-gray-800 text-gray-400 hover:bg-gray-700' }}">Armor</button>
                <button wire:click="setInventoryFilter('accessory')" class="px-2 py-1 text-xs rounded transition-colors {{ $inventoryFilter === 'accessory' ? 'bg-blue-600 text-white' : 'bg-gray-800 text-gray-400 hover:bg-gray-700' }}">Accessories</button>
                <button wire:click="setInventoryFilter('material')" class="px-2 py-1 text-xs rounded transition-colors {{ $inventoryFilter === 'material' ? 'bg-blue-600 text-white' : 'bg-gray-800 text-gray-400 hover:bg-gray-700' }}">Materials</button>
                <button wire:click="setInventoryFilter('consumable')" class="px-2 py-1 text-xs rounded transition-colors {{ $inventoryFilter === 'consumable' ? 'bg-blue-600 text-white' : 'bg-gray-800 text-gray-400 hover:bg-gray-700' }}">Consumables</button>
                
                <div class="flex-grow"></div>
                
                <button wire:click="stackItems" class="px-2 py-1 text-xs rounded bg-green-700 hover:bg-green-600 text-white flex items-center gap-1 shadow transition-colors" title="Połącz powtarzające się materiały">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path></svg>
                    Stackuj
                </button>
            </div>

            <!-- Inventory Grid -->
            <div class="grid grid-cols-5 gap-2 bg-gray-800 p-2 rounded flex-grow content-start">
                @foreach($inventory as $item)
                    <div wire:click="equipItem('{{ $item->id }}')" 
                         class="aspect-square bg-gray-700 border border-gray-600 rounded flex items-center justify-center cursor-pointer hover:border-green-400 relative group transition-colors">
                        
                        <div class="text-center text-xs text-white">
                            <span class="block truncate w-14">{{ $item->template->name }}</span>
                            @if($item->upgrade_level > 0)
                                <span class="text-yellow-400">+{{ $item->upgrade_level }}</span>
                            @endif
                            @if($item->stack_size > 1)
                                <span class="text-blue-300 font-bold block">{{ $item->stack_size }}x</span>
                            @endif
                        </div>

                        <!-- Tooltip -->
                        <div class="hidden group-hover:block absolute bottom-full mb-2 left-1/2 -translate-x-1/2 z-50 bg-gray-900 border border-gray-500 p-2 rounded text-xs w-48 shadow-xl">
                            <div class="flex justify-between items-center">
                                <p class="font-bold text-blue-300">{{ $item->template->name }}</p>
                                <span class="text-indigo-300 font-bold text-xs">⚡ {{ $item->getCombatPower() }}</span>
                            </div>
                            <p class="text-gray-400">Slot: {{ $item->template->slot ?? 'None' }}</p>
                            <p class="text-gray-400">Level Req: {{ $item->template->level_requirement }}</p>
                            
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
                            
                            <div class="mt-2 border-t border-gray-700 pt-2">
                                @if($character->level < $item->template->level_requirement)
                                    <p class="text-red-500 font-bold">Level too low!</p>
                                @else
                                    <p class="text-green-500">Click to equip</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach

                <!-- Empty Slots Filler -->
                @php $emptySlots = max(0, 25 - count($inventory)) @endphp
                @for($i = 0; $i < $emptySlots; $i++)
                    <div class="aspect-square bg-gray-800 border border-gray-700 rounded"></div>
                @endfor
            </div>
        </div>

    </div>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&display=swap');
        .medieval-font { font-family: 'Cinzel', serif; }
    </style>
</div>
