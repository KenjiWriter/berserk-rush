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
            <div class="text-center mb-4">
                <h2 class="text-2xl font-bold text-yellow-500">{{ $character->name }}</h2>
                <p class="text-gray-400">Level {{ $character->level }}</p>
            </div>

            <!-- Equipment Slots & Portrait -->
            <div class="flex justify-center items-center gap-4 mb-8">
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
                                <!-- Tooltip placeholder -->
                                <div class="hidden group-hover:block absolute left-full ml-2 z-50 bg-gray-900 border border-gray-600 p-2 rounded text-xs w-48 shadow-lg">
                                    <p class="font-bold text-yellow-400">{{ $equipped[$slot]->template->name }}</p>
                                    <p class="text-gray-300">Slot: {{ $slot }}</p>
                                    <p class="text-red-400 mt-2">Click to unequip</p>
                                </div>
                            @else
                                <span class="text-gray-500 text-xs">{{ ucfirst($slot) }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>

                <!-- Portrait -->
                <div class="w-48 h-64 bg-gray-800 border-4 border-yellow-700 rounded-lg overflow-hidden flex items-center justify-center">
                    @if($character->avatar && file_exists(public_path('img/avatars/' . $character->avatar . '.png')))
                        <img src="{{ asset('img/avatars/' . $character->avatar . '.png') }}" alt="Avatar" class="object-cover w-full h-full">
                    @else
                        <div class="text-gray-500 flex flex-col items-center">
                            <svg class="w-16 h-16 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            <span>No Avatar</span>
                        </div>
                    @endif
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
                                <div class="hidden group-hover:block absolute right-full mr-2 z-50 bg-gray-900 border border-gray-600 p-2 rounded text-xs w-48 shadow-lg">
                                    <p class="font-bold text-yellow-400">{{ $equipped[$slot]->template->name }}</p>
                                    <p class="text-gray-300">Slot: {{ $slot }}</p>
                                    <p class="text-red-400 mt-2">Click to unequip</p>
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
                <h3 class="text-yellow-500 font-bold mb-2 border-b border-gray-700 pb-1">Attributes</h3>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-400">Strength (STR):</span>
                        <span class="text-white font-bold">{{ $totalAttributes['str'] ?? 0 }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Intelligence (INT):</span>
                        <span class="text-white font-bold">{{ $totalAttributes['int'] ?? 0 }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Vitality (VIT):</span>
                        <span class="text-white font-bold">{{ $totalAttributes['vit'] ?? 0 }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Agility (AGI):</span>
                        <span class="text-white font-bold">{{ $totalAttributes['agi'] ?? 0 }}</span>
                    </div>
                </div>
                
                <div class="mt-4 pt-4 border-t border-gray-700 text-sm">
                    <div class="flex justify-between text-green-400 font-bold">
                        <span>Max HP:</span>
                        <span>{{ 100 + (($totalAttributes['vit'] ?? 1) * 10) + ($character->level * 5) }}</span>
                    </div>
                    <div class="flex justify-between text-red-400 font-bold mt-1">
                        <span>Base Damage:</span>
                        <span>{{ 10 + (($totalAttributes['str'] ?? 1) * 2) + ($character->level * 1) }}</span>
                    </div>
                </div>
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
                        </div>

                        <!-- Tooltip -->
                        <div class="hidden group-hover:block absolute bottom-full mb-2 left-1/2 -translate-x-1/2 z-50 bg-gray-900 border border-gray-500 p-2 rounded text-xs w-48 shadow-xl">
                            <p class="font-bold text-blue-300">{{ $item->template->name }}</p>
                            <p class="text-gray-400">Slot: {{ $item->template->slot ?? 'None' }}</p>
                            <p class="text-gray-400">Level Req: {{ $item->template->level_requirement }}</p>
                            
                            @if(count($item->template->base_stats ?? []) > 0)
                                <div class="mt-1 text-green-400">
                                    @foreach($item->template->base_stats as $stat => $val)
                                        +{{ $val }} {{ strtoupper($stat) }}
                                    @endforeach
                                </div>
                            @endif
                            
                            @if($character->level < $item->template->level_requirement)
                                <p class="text-red-500 mt-2 font-bold">Level too low!</p>
                            @else
                                <p class="text-green-500 mt-2">Click to equip</p>
                            @endif
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
