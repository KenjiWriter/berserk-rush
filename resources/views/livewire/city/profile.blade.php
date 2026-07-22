<div class="min-h-screen bg-slate-950 text-amber-100 relative overflow-hidden">
    {{-- Bright Background Parallax Image & Ambient Gold Overlay --}}
    <div class="fixed inset-0 bg-cover bg-center opacity-75 mix-blend-normal scale-105 pointer-events-none" style="background-image: url('{{ asset('img/profile-bg.png') }}');"></div>
    <div class="fixed inset-0 bg-gradient-to-b from-slate-950/60 via-slate-900/40 to-slate-950/70 pointer-events-none"></div>
    <div class="fixed inset-0 bg-[radial-gradient(ellipse_at_center,_var(--tw-gradient-stops))] from-amber-500/10 via-black/20 to-black/70 pointer-events-none"></div>

    <div class="relative w-full px-6 md:px-10 lg:px-12 py-8 min-h-screen z-10">
        @php
            $gameStage = auth()->user()->game_stage;
        @endphp

        @if($gameStage == 5)
            <livewire:global.tutorial-overlay :step="6" />
        @elseif($gameStage == 7)
            <livewire:global.tutorial-overlay :step="8" :rewardXp="50" />
        @endif

        {{-- Header --}}
        <div class="bg-gradient-to-r from-amber-950/90 via-stone-900/90 to-amber-950/90 border-2 border-amber-500/80 rounded-2xl p-4 shadow-[0_0_30px_rgba(245,158,11,0.25)] backdrop-blur-md flex items-center justify-between mb-8 relative overflow-hidden">
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-amber-400/20 via-transparent to-transparent pointer-events-none"></div>
            <div class="relative flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-amber-500/20 border border-amber-400/60 flex items-center justify-center text-amber-300 font-bold text-2xl shadow-[0_0_15px_rgba(245,158,11,0.3)]">👤</div>
                <div>
                    <h2 class="text-2xl md:text-3xl font-bold text-amber-300 medieval-font drop-shadow-[0_2px_4px_rgba(0,0,0,0.8)] tracking-wide">Profil: {{ $character->name }}</h2>
                    <p class="text-amber-200/80 text-xs md:text-sm font-medium">Zarządzaj ekwipunkiem, atrybutami, statystykami i umiejętnościami bohatera</p>
                </div>
            </div>

            <button wire:click="backToHub" @click="$dispatch('location-leave')"
                class="bg-gradient-to-r from-amber-600 via-amber-500 to-yellow-500 hover:from-amber-500 hover:to-yellow-400 text-stone-950 font-extrabold py-2.5 px-6 rounded-xl transition-all duration-300 transform hover:scale-105 shadow-[0_0_20px_rgba(245,158,11,0.5)] medieval-font border border-amber-200/60 {{ $gameStage == 8 ? 'animate-[pulse_1.5s_ease-in-out_infinite] ring-4 ring-amber-500 scale-105 shadow-[0_0_25px_rgba(245,158,11,0.9)] relative z-10' : '' }}">
                🏰 Powrót do miasta
            </button>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-8 items-start">
        
        <!-- Left Side: Character Profile -->
        <div class="bg-gradient-to-b from-slate-900/90 via-stone-900/85 to-slate-950/90 border-2 border-amber-500/70 rounded-3xl shadow-[0_0_30px_rgba(245,158,11,0.15)] backdrop-blur-md p-6 flex flex-col h-full relative overflow-hidden">
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-amber-400/10 via-transparent to-transparent pointer-events-none"></div>


            <!-- Equipment Slots & Portrait -->
            <div class="flex justify-center items-start gap-4 md:gap-8 mb-8 mt-4">
                <!-- Left Slots -->
                <div class="flex flex-col gap-4">
                    <!-- Pet Slot -->
                    @php $activePet = $pets->firstWhere('is_equipped', true); @endphp
                    <div id="equip-slot-pet" x-data="{ open: false, hoverTimeout: null, isDragOver: false }" @click.outside="open = false" 
                         @if($activePet) 
                             wire:loading.class="opacity-50 scale-95 pointer-events-none" 
                             wire:target="toggleEquipPet({{ $activePet->id }})" 
                             draggable="true"
                             @dragstart="open = false; clearTimeout(hoverTimeout); window.currentDragItem = { id: {{ $activePet->id }}, type: 'pet', source: 'equipped_pet', domId: 'equip-slot-pet' }"
                             @dragend="window.currentDragItem = null"
                             @dblclick="open = false; clearTimeout(hoverTimeout); flyItem('equip-slot-pet', 'inventory-grid', () => $wire.toggleEquipPet({{ $activePet->id }}))"
                         @endif
                         @dragover.prevent="if (window.currentDragItem && window.currentDragItem.type === 'egg') isDragOver = true"
                         @dragleave="isDragOver = false"
                         @drop.prevent="
                             if (window.currentDragItem && window.currentDragItem.type === 'egg') {
                                 let dragItem = window.currentDragItem;
                                 isDragOver = false;
                                 flyItem(dragItem.domId, 'equip-slot-pet', () => $wire.placeEgg(dragItem.id));
                             } else {
                                 isDragOver = false;
                             }
                         "
                         class="w-16 h-16 bg-gray-800 border-2 {{ $activePet ? 'border-amber-500 cursor-grab active:cursor-grabbing hover:border-red-500 enchanted-border' : 'border-gray-600 border-dashed' }} rounded flex items-center justify-center relative transition-all duration-200"
                         :class="{ 'ring-4 ring-green-400 border-green-400 bg-green-900/40 scale-105 shadow-[0_0_15px_rgba(74,222,128,0.6)]': isDragOver }"
                         @if($activePet) @click="open = true" @endif>
                        @if($activePet)
                            <div class="text-center text-xs text-white">
                                <span class="text-xl">🐾</span>
                                <span class="block truncate w-14 text-[10px] text-amber-400 mt-1">{{ $activePet->name }}</span>
                            </div>
                            <!-- Modal -->
                            <div x-show="open" style="display: none;" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/80 backdrop-blur-sm p-4 text-left cursor-default">
                                <div class="bg-gray-900 border border-amber-500 p-4 rounded w-full max-w-xs shadow-2xl relative" @click.stop>
                                    <button @click="open = false" class="absolute top-2 right-2 text-gray-400 hover:text-white text-lg font-bold">✕</button>
                                    <div class="flex justify-between items-center mb-2">
                                        <p class="font-bold text-amber-400 text-lg">{{ $activePet->name }}</p>
                                        <span class="text-indigo-300 font-bold">⚡ {{ $activePet->getCombatPower() }}</span>
                                    </div>
                                    <p class="text-gray-300 mb-2">Poziom: {{ $activePet->level }}</p>
                                    @if(count($activePet->stats ?? []) > 0)
                                        <div class="mt-2 text-green-400 border-t border-gray-700 pt-2 space-y-1">
                                            @foreach($activePet->stats ?? [] as $stat => $val)
                                                <div class="flex justify-between text-amber-200">
                                                    <span class="capitalize">{{ $stat }}</span>
                                                    <span class="font-bold">+{{ $val }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                    <button @click="open = false; flyItem('equip-slot-pet', 'inventory-grid', () => $wire.toggleEquipPet({{ $activePet->id }}))" class="mt-4 w-full bg-red-600 hover:bg-red-500 text-white font-bold py-2 rounded">
                                        Odwołaj Peta
                                    </button>
                                </div>
                            </div>
                        @else
                            <div class="text-gray-500 flex flex-col items-center">
                                <span class="text-xl opacity-50 mb-1">🐾</span>
                                <span class="text-[10px]">Pet</span>
                            </div>
                        @endif
                    </div>

                    @foreach(['head', 'chest', 'main_hand'] as $slot)
                        <div id="equip-slot-{{ $slot }}" x-data="{ open: false, hoverTimeout: null, isDragOver: false, isDragInvalid: false }" @click.outside="open = false" 
                             @if(isset($equipped[$slot])) 
                                 wire:loading.class="opacity-50 scale-95 pointer-events-none" 
                                 wire:target="unequipItem('{{ $equipped[$slot]->id }}')" 
                                 draggable="true"
                                 @dragstart="open = false; clearTimeout(hoverTimeout); window.currentDragItem = { id: '{{ $equipped[$slot]->id }}', slot: '{{ $slot }}', source: 'equipped', domId: 'equip-slot-{{ $slot }}' }"
                                 @dragend="window.currentDragItem = null"
                                 @dblclick="open = false; clearTimeout(hoverTimeout); flyItem('equip-slot-{{ $slot }}', 'inventory-grid', () => $wire.unequipItem('{{ $equipped[$slot]->id }}'))"
                             @endif
                             @dragover.prevent="
                                 if (window.currentDragItem && window.currentDragItem.source === 'backpack') {
                                     if (window.currentDragItem.slot === '{{ $slot }}' && window.currentDragItem.charLevel >= window.currentDragItem.levelReq) {
                                         isDragOver = true; isDragInvalid = false;
                                     } else if (window.currentDragItem.slot === '{{ $slot }}') {
                                         isDragOver = false; isDragInvalid = true;
                                     }
                                 }
                             "
                             @dragleave="isDragOver = false; isDragInvalid = false"
                             @drop.prevent="
                                 if (window.currentDragItem && window.currentDragItem.source === 'backpack' && window.currentDragItem.slot === '{{ $slot }}' && window.currentDragItem.charLevel >= window.currentDragItem.levelReq) {
                                     let dragItem = window.currentDragItem;
                                     isDragOver = false; isDragInvalid = false;
                                     flyItem(dragItem.domId, 'equip-slot-{{ $slot }}', () => $wire.equipItem(dragItem.id));
                                 } else {
                                     isDragOver = false; isDragInvalid = false;
                                 }
                             "
                             class="w-16 h-16 bg-gray-800 border-2 {{ isset($equipped[$slot]) ? 'border-blue-500 cursor-grab active:cursor-grabbing hover:border-red-500' : 'border-gray-600' }} rounded flex items-center justify-center relative transition-all duration-200 {{ isset($equipped[$slot]) && count($equipped[$slot]->roll_stats['enchants'] ?? []) > 0 ? 'enchanted-border' : '' }}"
                             :class="{ 
                                 'ring-4 ring-green-400 border-green-400 bg-green-900/40 scale-105 shadow-[0_0_15px_rgba(74,222,128,0.6)]': isDragOver,
                                 'ring-4 ring-red-500 border-red-500 bg-red-900/40': isDragInvalid 
                             }"
                             @if(isset($equipped[$slot])) @mouseenter="clearTimeout(hoverTimeout); open = true" @mouseleave="hoverTimeout = setTimeout(() => { open = false }, 250)" @click="clearTimeout(hoverTimeout); open = true" @endif>
                            @if(isset($equipped[$slot]))
                                @if($equipped[$slot]->template->icon)
                                    <div class="text-center text-xs text-white flex flex-col items-center w-full h-full justify-center">
                                        <img src="{{ route('assets.items', ['filename' => $equipped[$slot]->template->icon]) }}" class="w-full h-full object-contain drop-shadow-lg p-1" alt="{{ $equipped[$slot]->template->name }}">
                                        @if(in_array($equipped[$slot]->template->type ?? '', ['weapon', 'armor', 'accessory']))
                                            <span class="absolute bottom-0 left-0 text-amber-500 font-bold text-[10px] bg-black/80 px-1 rounded-tr border-t border-r border-gray-700/50">+{{ $equipped[$slot]->upgrade_level ?? 0 }}</span>
                                        @endif
                                    </div>
                                @else
                                    <div class="text-center text-xs text-white">
                                        <span class="block truncate w-14">{{ $equipped[$slot]->template->name }}</span>
                                        @if($equipped[$slot]->upgrade_level > 0)
                                            <span class="text-yellow-400">+{{ $equipped[$slot]->upgrade_level }}</span>
                                        @endif
                                    </div>
                                @endif
                                <!-- Tooltip / Modal -->
                                <!-- Tooltip / Modal -->
                                <div x-show="open" x-transition.opacity style="display: none;" class="fixed inset-0 sm:absolute sm:inset-auto sm:top-full sm:left-1/2 sm:-translate-x-1/2 sm:mt-2 sm:w-auto z-[200] sm:z-50 flex items-center justify-center sm:block bg-black/80 sm:bg-transparent backdrop-blur-sm sm:backdrop-blur-none p-4 sm:p-0 cursor-default" @click.stop="open = false">
                                    <div class="relative w-full max-w-xs sm:w-auto sm:max-w-none">
                                        <button @click="open = false" class="absolute top-2 right-2 text-gray-400 hover:text-white text-lg font-bold sm:hidden z-10">✕</button>
                                        <x-item-tooltip :item="$equipped[$slot]">
                                            <x-slot:actions>
                                                <button @click="open = false; flyItem('equip-slot-{{ $slot }}', 'inventory-grid', () => $wire.unequipItem('{{ $equipped[$slot]->id }}'))" class="w-full bg-red-600 hover:bg-red-500 text-white font-bold py-2 rounded">
                                                    Zdejmij przedmiot
                                                </button>
                                            </x-slot:actions>
                                        </x-item-tooltip>
                                        <!-- Arrow (Desktop only) -->
                                        <div class="hidden sm:block absolute -top-2 left-1/2 -translate-x-1/2 w-4 h-4 bg-gray-900 border-t border-l border-slate-600 transform rotate-45 z-[-1]"></div>
                                    </div>
                                </div>
                            @else
                                <span class="text-gray-500 text-xs">{{ ucfirst($slot) }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>

                <!-- Portrait & Info -->
                <div class="flex flex-col items-center w-48" x-data="{ avatarModalOpen: false }">
                    <div class="w-full h-64 bg-stone-950/90 border-4 border-amber-600/80 rounded-2xl overflow-hidden flex items-center justify-center mb-4 shadow-[0_0_20px_rgba(245,158,11,0.25)] relative group">
                        @if($character->avatar && file_exists(public_path('img/avatars/' . $character->avatar . '.png')))
                            <img src="{{ asset('img/avatars/' . $character->avatar . '.png') }}" alt="Avatar" class="object-cover w-full h-full">
                        @else
                            <div class="text-gray-500 flex flex-col items-center">
                                <svg class="w-16 h-16 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                <span>No Avatar</span>
                            </div>
                        @endif

                        <button @click="avatarModalOpen = true" class="absolute top-2 right-2 w-8 h-8 bg-amber-600/80 hover:bg-amber-500 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity shadow-lg backdrop-blur-sm border border-amber-400">
                            ✏️
                        </button>
                    </div>

                    <!-- Avatar Modal -->
                    <div x-show="avatarModalOpen" style="display: none;" class="fixed inset-0 z-[150] flex items-center justify-center bg-black/80 backdrop-blur-sm p-4 text-left cursor-default">
                        <div class="bg-gray-900 border border-amber-500 p-6 rounded-lg w-full max-w-2xl shadow-2xl relative" @click.outside="avatarModalOpen = false">
                            <button @click="avatarModalOpen = false" class="absolute top-4 right-4 text-gray-400 hover:text-white text-xl font-bold">✕</button>
                            <h3 class="text-2xl font-bold text-amber-400 mb-6 border-b border-amber-900 pb-2 medieval-font">Wybierz Avatar</h3>
                            
                            <h4 class="text-lg font-bold text-gray-300 mb-4">Podstawowe Avatary</h4>
                            <div class="grid grid-cols-4 sm:grid-cols-6 gap-4 mb-8">
                                @foreach($baseAvatars as $avatar)
                                    <button @click="$wire.changeAvatar('{{ $avatar }}', false); avatarModalOpen = false" class="aspect-square border-2 border-gray-600 hover:border-amber-500 rounded overflow-hidden {{ $character->avatar === $avatar ? 'ring-4 ring-amber-500 border-amber-500' : '' }}">
                                        <img src="{{ asset('img/avatars/' . $avatar . '.png') }}" class="w-full h-full object-cover">
                                    </button>
                                @endforeach
                            </div>

                            @if(!empty(auth()->user()->unlocked_avatars))
                                <h4 class="text-lg font-bold text-yellow-400 mb-4 flex items-center gap-2"><span>💎</span> Avatary Premium</h4>
                                <div class="grid grid-cols-4 sm:grid-cols-6 gap-4">
                                    @foreach(auth()->user()->unlocked_avatars as $premiumAvatar)
                                        <button @click="$wire.changeAvatar('{{ $premiumAvatar }}', true); avatarModalOpen = false" class="aspect-square border-2 border-yellow-600 hover:border-yellow-400 rounded overflow-hidden {{ $character->avatar === 'premium/' . $premiumAvatar ? 'ring-4 ring-yellow-400 border-yellow-400 shadow-[0_0_15px_rgba(250,204,21,0.6)]' : '' }}">
                                            <img src="{{ asset('img/avatars/premium/' . $premiumAvatar . '.png') }}" class="w-full h-full object-cover">
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                    @if($character->activeTitle)
                        <div class="text-[11px] text-amber-400 font-bold uppercase tracking-widest mt-2 -mb-1 drop-shadow-md text-center">
                            {{ $character->activeTitle->prefix }}
                        </div>
                    @endif
                    <h2 class="text-2xl font-bold text-amber-300 text-center medieval-font drop-shadow-md">{{ $character->name }}</h2>
                    <p class="text-amber-200/70 font-semibold text-sm">Poziom {{ $character->level }}</p>
                    <div class="bg-amber-950/80 border border-amber-500/60 text-amber-300 px-4 py-1 rounded-full text-xs font-bold shadow-[0_0_10px_rgba(245,158,11,0.2)] my-2 medieval-font">
                        ⚡ Moc Bojowa: {{ number_format($character->getTotalCombatPower()) }}
                    </div>
                    
                    @php 
                        $xpRequired = app(\App\Application\Characters\LevelUpService::class)->xpToNext($character->level);
                        $xpPercent = min(100, ($character->xp / max(1, $xpRequired)) * 100);
                    @endphp
                    <div class="w-full bg-gray-900 rounded-full h-5 relative border-2 border-gray-700 shadow-inner overflow-hidden cursor-help" title="Doświadczenie: {{ $character->xp }} / {{ $xpRequired }}">
                        <div class="bg-gradient-to-r from-blue-700 to-blue-400 h-full transition-all duration-300" style="width: {{ $xpPercent }}%"></div>
                        <span class="absolute inset-0 flex items-center justify-center text-[10px] text-white font-bold drop-shadow-[0_1px_1px_rgba(0,0,0,0.8)]">
                            XP: {{ number_format($character->xp) }} / {{ number_format($xpRequired) }}
                        </span>
                    </div>
                    <div class="w-full mt-4 border-t border-gray-700/50 pt-3">
                        <h3 class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-2 text-center">Wyposażone Skille</h3>
                        <div class="flex justify-center gap-3">
                            @for($i = 0; $i < 3; $i++)
                                @php
                                    $equippedSkill = $character->equippedSkills[$i] ?? null;
                                @endphp
                                <div x-data="{ open: false, hoverTimeout: null }" @click.outside="open = false"
                                     class="w-10 h-10 rounded border border-gray-700 bg-gray-800 flex flex-col items-center justify-center relative shadow-inner {{ $equippedSkill ? 'border-amber-600/50 hover:border-amber-400 cursor-pointer' : '' }}"
                                     @if($equippedSkill) 
                                        @mouseenter="clearTimeout(hoverTimeout); open = true" 
                                        @mouseleave="hoverTimeout = setTimeout(() => { open = false }, 250)" 
                                        @click="clearTimeout(hoverTimeout); open = true" 
                                     @endif>
                                    @if($equippedSkill)
                                        @if($equippedSkill->skill->icon)
                                            <img src="{{ route('assets.skills.icons', ['filename' => $equippedSkill->skill->icon]) }}" class="w-6 h-6 object-contain drop-shadow" alt="{{ $equippedSkill->skill->name }}">
                                        @else
                                            <div class="text-sm">✨</div>
                                        @endif
                                        <div class="absolute -bottom-1 -right-1 bg-gray-900 border border-gray-600 text-yellow-500 text-[8px] px-0.5 rounded shadow-md font-bold leading-none">
                                            L{{ $equippedSkill->level }}
                                        </div>

                                        <!-- Infobox -->
                                        <div x-show="open" x-transition.opacity style="display: none;" class="fixed inset-0 sm:absolute sm:inset-auto sm:bottom-full sm:left-1/2 sm:-translate-x-1/2 sm:mb-2 sm:w-auto z-[200] sm:z-50 flex items-center justify-center sm:block bg-black/80 sm:bg-transparent backdrop-blur-sm sm:backdrop-blur-none p-4 sm:p-0 cursor-default" @click.stop="open = false">
                                            <div class="relative w-[280px] sm:w-[320px] bg-gray-900 border-2 border-amber-600 rounded-lg shadow-[0_0_15px_rgba(217,119,6,0.5)] p-4 text-left">
                                                <button @click="open = false" class="absolute top-2 right-2 text-gray-400 hover:text-white text-lg font-bold sm:hidden z-10">✕</button>
                                                
                                                <div class="flex items-center gap-3 mb-3 border-b border-gray-700 pb-3">
                                                    @if($equippedSkill->skill->icon)
                                                        <img src="{{ route('assets.skills.icons', ['filename' => $equippedSkill->skill->icon]) }}" class="w-10 h-10 object-contain bg-gray-800 rounded p-1 border border-gray-700">
                                                    @endif
                                                    <div>
                                                        <h4 class="text-amber-500 font-bold text-sm">{{ $equippedSkill->skill->name }}</h4>
                                                        <p class="text-gray-400 text-xs">Poziom {{ $equippedSkill->level }}</p>
                                                    </div>
                                                </div>
                                                
                                                <div class="text-gray-300 text-xs mb-3 space-y-2">
                                                    <p>{{ $equippedSkill->skill->description }}</p>
                                                    <div class="flex flex-wrap gap-1.5 text-[11px] pt-1">
                                                        <span class="bg-indigo-900/60 text-indigo-300 px-2 py-0.5 rounded border border-indigo-700/50">Odnowienie: {{ $equippedSkill->skill->base_cooldown }} tur</span>
                                                        @if($equippedSkill->skill->base_duration > 0)
                                                            <span class="bg-purple-900/60 text-purple-300 px-2 py-0.5 rounded border border-purple-700/50">Czas trwania: {{ $equippedSkill->skill->base_duration }} tur</span>
                                                        @endif
                                                        @if($equippedSkill->skill->type === 'dot_poison')
                                                            <span class="bg-green-900/60 text-green-300 px-2 py-0.5 rounded border border-green-700/50">Trucizna: {{ $equippedSkill->skill->base_value + ($equippedSkill->level * $equippedSkill->skill->scaling_value) }}% aktualnego HP</span>
                                                        @elseif($equippedSkill->skill->type === 'dot_fire')
                                                            <span class="bg-red-900/60 text-red-300 px-2 py-0.5 rounded border border-red-700/50">Ogień: {{ $equippedSkill->skill->base_value + ($equippedSkill->level * $equippedSkill->skill->scaling_value) }}% max HP</span>
                                                        @elseif($equippedSkill->skill->type === 'buff_damage')
                                                            <span class="bg-blue-900/60 text-blue-300 px-2 py-0.5 rounded border border-blue-700/50">Wzmocnienie: +{{ $equippedSkill->skill->base_value + ($equippedSkill->level * $equippedSkill->skill->scaling_value) }}% obrażeń</span>
                                                        @endif
                                                    </div>
                                                </div>

                                                <button @click="open = false; $wire.unequipSkill('{{ $equippedSkill->id }}')" class="w-full bg-red-900/80 hover:bg-red-800 text-red-200 border border-red-700 font-bold py-1.5 px-3 rounded text-xs transition-colors">
                                                    Zdejmij skill
                                                </button>
                                            </div>
                                            <!-- Arrow (Desktop only) -->
                                            <div class="hidden sm:block absolute left-1/2 -bottom-2 -translate-x-1/2 w-4 h-4 bg-gray-900 border-b-2 border-r-2 border-amber-600 transform rotate-45 z-10"></div>
                                        </div>
                                    @else
                                        <div class="text-gray-600 text-[10px]">-</div>
                                    @endif
                                </div>
                            @endfor
                        </div>
                    </div>
                </div>

                <!-- Right Slots -->
                <div class="flex flex-col gap-4">
                    @foreach(['neck', 'ring', 'feet'] as $slot)
                        <div id="equip-slot-{{ $slot }}" x-data="{ open: false, hoverTimeout: null, isDragOver: false, isDragInvalid: false }" @click.outside="open = false" 
                             @if(isset($equipped[$slot])) 
                                 wire:loading.class="opacity-50 scale-95 pointer-events-none" 
                                 wire:target="unequipItem('{{ $equipped[$slot]->id }}')" 
                                 draggable="true"
                                 @dragstart="open = false; clearTimeout(hoverTimeout); window.currentDragItem = { id: '{{ $equipped[$slot]->id }}', slot: '{{ $slot }}', source: 'equipped', domId: 'equip-slot-{{ $slot }}' }"
                                 @dragend="window.currentDragItem = null"
                                 @dblclick="open = false; clearTimeout(hoverTimeout); flyItem('equip-slot-{{ $slot }}', 'inventory-grid', () => $wire.unequipItem('{{ $equipped[$slot]->id }}'))"
                             @endif
                             @dragover.prevent="
                                 if (window.currentDragItem && window.currentDragItem.source === 'backpack') {
                                     if (window.currentDragItem.slot === '{{ $slot }}' && window.currentDragItem.charLevel >= window.currentDragItem.levelReq) {
                                         isDragOver = true; isDragInvalid = false;
                                     } else if (window.currentDragItem.slot === '{{ $slot }}') {
                                         isDragOver = false; isDragInvalid = true;
                                     }
                                 }
                             "
                             @dragleave="isDragOver = false; isDragInvalid = false"
                             @drop.prevent="
                                 if (window.currentDragItem && window.currentDragItem.source === 'backpack' && window.currentDragItem.slot === '{{ $slot }}' && window.currentDragItem.charLevel >= window.currentDragItem.levelReq) {
                                     let dragItem = window.currentDragItem;
                                     isDragOver = false; isDragInvalid = false;
                                     flyItem(dragItem.domId, 'equip-slot-{{ $slot }}', () => $wire.equipItem(dragItem.id));
                                 } else {
                                     isDragOver = false; isDragInvalid = false;
                                 }
                             "
                             class="w-16 h-16 bg-gray-800 border-2 {{ isset($equipped[$slot]) ? 'border-blue-500 cursor-grab active:cursor-grabbing hover:border-red-500' : 'border-gray-600' }} rounded flex items-center justify-center relative transition-all duration-200 {{ isset($equipped[$slot]) && count($equipped[$slot]->roll_stats['enchants'] ?? []) > 0 ? 'enchanted-border' : '' }}"
                             :class="{ 
                                 'ring-4 ring-green-400 border-green-400 bg-green-900/40 scale-105 shadow-[0_0_15px_rgba(74,222,128,0.6)]': isDragOver,
                                 'ring-4 ring-red-500 border-red-500 bg-red-900/40': isDragInvalid 
                             }"
                             @if(isset($equipped[$slot])) @mouseenter="clearTimeout(hoverTimeout); open = true" @mouseleave="hoverTimeout = setTimeout(() => { open = false }, 250)" @click="clearTimeout(hoverTimeout); open = true" @endif>
                            @if(isset($equipped[$slot]))
                                @if($equipped[$slot]->template->icon)
                                    <div class="text-center text-xs text-white flex flex-col items-center w-full h-full justify-center">
                                        <img src="{{ route('assets.items', ['filename' => $equipped[$slot]->template->icon]) }}" class="w-full h-full object-contain drop-shadow-lg p-1" alt="{{ $equipped[$slot]->template->name }}">
                                        @if(in_array($equipped[$slot]->template->type ?? '', ['weapon', 'armor', 'accessory']))
                                            <span class="absolute bottom-0 left-0 text-amber-500 font-bold text-[10px] bg-black/80 px-1 rounded-tr border-t border-r border-gray-700/50">+{{ $equipped[$slot]->upgrade_level ?? 0 }}</span>
                                        @endif
                                    </div>
                                @else
                                    <div class="text-center text-xs text-white">
                                        <span class="block truncate w-14">{{ $equipped[$slot]->template->name }}</span>
                                        @if($equipped[$slot]->upgrade_level > 0)
                                            <span class="text-yellow-400">+{{ $equipped[$slot]->upgrade_level }}</span>
                                        @endif
                                    </div>
                                @endif
                                <!-- Tooltip / Modal -->
                                <!-- Tooltip / Modal -->
                                <div x-show="open" x-transition.opacity style="display: none;" class="fixed inset-0 sm:absolute sm:inset-auto sm:top-full sm:left-1/2 sm:-translate-x-1/2 sm:mt-2 sm:w-auto z-[200] sm:z-50 flex items-center justify-center sm:block bg-black/80 sm:bg-transparent backdrop-blur-sm sm:backdrop-blur-none p-4 sm:p-0 cursor-default" @click.stop="open = false">
                                    <div class="relative w-full max-w-xs sm:w-auto sm:max-w-none">
                                        <button @click="open = false" class="absolute top-2 right-2 text-gray-400 hover:text-white text-lg font-bold sm:hidden z-10">✕</button>
                                        <x-item-tooltip :item="$equipped[$slot]">
                                            <x-slot:actions>
                                                <button @click="open = false; flyItem('equip-slot-{{ $slot }}', 'inventory-grid', () => $wire.unequipItem('{{ $equipped[$slot]->id }}'))" class="w-full bg-red-600 hover:bg-red-500 text-white font-bold py-2 rounded">
                                                    Zdejmij przedmiot
                                                </button>
                                            </x-slot:actions>
                                        </x-item-tooltip>
                                        <!-- Arrow (Desktop only) -->
                                        <div class="hidden sm:block absolute -top-2 left-1/2 -translate-x-1/2 w-4 h-4 bg-gray-900 border-t border-l border-slate-600 transform rotate-45 z-[-1]"></div>
                                    </div>
                                </div>
                            @else
                                <span class="text-gray-500 text-xs">{{ ucfirst($slot) }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Stats -->
            <div class="bg-stone-950/80 border border-amber-900/60 rounded-2xl p-4 mt-auto shadow-inner relative z-10">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-amber-900/60 pb-2 mb-3">
                    <div class="grid grid-cols-2 sm:grid-cols-5 gap-1.5 flex-grow">
                        <button wire:click="setTab('attributes')" class="w-full text-center py-1.5 px-1 font-bold text-xs sm:text-sm rounded-t-lg transition-all duration-200 medieval-font flex items-center justify-center {{ $activeTab === 'attributes' ? 'text-amber-300 border-b-2 border-amber-400 bg-amber-500/15 shadow-[0_0_10px_rgba(245,158,11,0.2)]' : 'text-stone-400 hover:text-amber-200 hover:bg-stone-800/40' }}">
                            Atrybuty
                        </button>
                        <button wire:click="setTab('stats')" class="w-full text-center py-1.5 px-1 font-bold text-xs sm:text-sm rounded-t-lg transition-all duration-200 medieval-font flex items-center justify-center {{ $activeTab === 'stats' ? 'text-amber-300 border-b-2 border-amber-400 bg-amber-500/15 shadow-[0_0_10px_rgba(245,158,11,0.2)]' : 'text-stone-400 hover:text-amber-200 hover:bg-stone-800/40' }}">
                            Statystyki
                        </button>
                        <button wire:click="setTab('pets')" class="w-full text-center py-1.5 px-1 font-bold text-xs sm:text-sm rounded-t-lg transition-all duration-200 medieval-font flex items-center justify-center {{ $activeTab === 'pets' ? 'text-amber-300 border-b-2 border-amber-400 bg-amber-500/15 shadow-[0_0_10px_rgba(245,158,11,0.2)]' : 'text-stone-400 hover:text-amber-200 hover:bg-stone-800/40' }}">
                            Pety & Inkubator
                        </button>
                        <button wire:click="setTab('collections')" class="w-full text-center py-1.5 px-1 font-bold text-xs sm:text-sm rounded-t-lg transition-all duration-200 medieval-font flex items-center justify-center {{ $activeTab === 'collections' ? 'text-amber-300 border-b-2 border-amber-400 bg-amber-500/15 shadow-[0_0_10px_rgba(245,158,11,0.2)]' : 'text-stone-400 hover:text-amber-200 hover:bg-stone-800/40' }}">
                            Kolekcje & Tytuły
                        </button>
                        <button wire:click="setTab('skills')" class="w-full text-center py-1.5 px-1 font-bold text-xs sm:text-sm rounded-t-lg transition-all duration-200 medieval-font flex items-center justify-center {{ $activeTab === 'skills' ? 'text-amber-300 border-b-2 border-amber-400 bg-amber-500/15 shadow-[0_0_10px_rgba(245,158,11,0.2)]' : 'text-stone-400 hover:text-amber-200 hover:bg-stone-800/40' }}">
                            Umiejętności
                        </button>
                    </div>
                    @if($activeTab === 'attributes')
                        <span x-data="{ points: {{ $character->character_points }} }" @stats-saved.window="points = $event.detail.points" x-show="points > 0" class="text-green-400 font-bold text-xs sm:text-sm animate-pulse bg-green-900/40 px-2.5 py-1 rounded-xl border border-green-700 whitespace-nowrap self-end sm:self-center">Punkty: <span x-text="points"></span></span>
                    @endif
                </div>
                @if($activeTab === 'attributes')
                    <div x-data="{
                        points: {{ $character->character_points }},
                        added: { str: 0, int: 0, vit: 0, agi: 0 },
                        saveTimeout: null,
                        
                        add(stat, amount) {
                            let actual = Math.min(amount, this.points);
                            if (actual > 0) {
                                this.added[stat] += actual;
                                this.points -= actual;
                                
                                // Błyskawiczne odtworzenie dźwięku oraz mikroszybka animacja
                                window.dispatchEvent(new CustomEvent('play-audio', { detail: { type: 'hover' } }));

                                let el = document.getElementById('stat-flash-' + stat);
                                if (el) {
                                    el.style.animation = 'none';
                                    el.offsetHeight; // trigger reflow
                                    el.style.animation = 'flashText 0.15s ease-out forwards';
                                }
                                
                                // Krótki, płynny auto-zapis (350ms)
                                clearTimeout(this.saveTimeout);
                                this.saveTimeout = setTimeout(() => {
                                    let toSave = { ...this.added };
                                    this.added = { str: 0, int: 0, vit: 0, agi: 0 };
                                    $wire.saveAttributes(toSave);
                                }, 350);
                            }
                        }
                    }" class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 text-sm mt-4 relative" @stats-saved.window="points = $event.detail.points">
                    
                        <style>
                            @keyframes flashText {
                                0% { color: #f59e0b; transform: scale(1.15); text-shadow: 0 0 8px rgba(245,158,11,0.8); }
                                100% { color: #ffffff; transform: scale(1); text-shadow: none; }
                            }
                        </style>

                        <!-- STR -->
                        <div class="flex justify-between items-center group">
                            <span class="text-stone-300 font-medium cursor-help border-b border-dashed border-stone-600" title="Siła: Zwiększa obrażenia bazowe broni.">Strength (STR):</span>
                            <div class="flex items-center gap-2">
                                <span id="stat-flash-str" class="text-amber-300 font-bold text-base w-8 text-right inline-block transition-transform" x-text="{{ $totalAttributes['str'] ?? 0 }} + added.str"></span>
                                <div class="flex gap-1" x-show="points > 0">
                                    <button @click="add('str', 1)" class="w-6 h-6 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white rounded-lg text-xs flex items-center justify-center font-extrabold shadow active:scale-90 transition-transform duration-75" title="Dodaj 1">+1</button>
                                    <button x-show="points >= 5" @click="add('str', 5)" class="w-6 h-6 bg-gradient-to-r from-amber-600 to-yellow-600 hover:from-amber-500 hover:to-yellow-500 text-stone-950 rounded-lg text-xs flex items-center justify-center font-extrabold shadow active:scale-90 transition-transform duration-75" title="Dodaj 5">+5</button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- INT -->
                        <div class="flex justify-between items-center group">
                            <span class="text-stone-300 font-medium cursor-help border-b border-dashed border-stone-600" title="Inteligencja: Wpływa na obronę magiczną.">Intelligence (INT):</span>
                            <div class="flex items-center gap-2">
                                <span id="stat-flash-int" class="text-amber-300 font-bold text-base w-8 text-right inline-block transition-transform" x-text="{{ $totalAttributes['int'] ?? 0 }} + added.int"></span>
                                <div class="flex gap-1" x-show="points > 0">
                                    <button @click="add('int', 1)" class="w-6 h-6 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white rounded-lg text-xs flex items-center justify-center font-extrabold shadow active:scale-90 transition-transform duration-75" title="Dodaj 1">+1</button>
                                    <button x-show="points >= 5" @click="add('int', 5)" class="w-6 h-6 bg-gradient-to-r from-amber-600 to-yellow-600 hover:from-amber-500 hover:to-yellow-500 text-stone-950 rounded-lg text-xs flex items-center justify-center font-extrabold shadow active:scale-90 transition-transform duration-75" title="Dodaj 5">+5</button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- VIT -->
                        <div class="flex justify-between items-center group">
                            <span class="text-stone-300 font-medium cursor-help border-b border-dashed border-stone-600" title="Witalność: Zwiększa maksymalną ilość Punktów Życia (HP).">Vitality (VIT):</span>
                            <div class="flex items-center gap-2">
                                <span id="stat-flash-vit" class="text-amber-300 font-bold text-base w-8 text-right inline-block transition-transform" x-text="{{ $totalAttributes['vit'] ?? 0 }} + added.vit"></span>
                                <div class="flex gap-1" x-show="points > 0">
                                    <button @click="add('vit', 1)" class="w-6 h-6 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white rounded-lg text-xs flex items-center justify-center font-extrabold shadow active:scale-90 transition-transform duration-75" title="Dodaj 1">+1</button>
                                    <button x-show="points >= 5" @click="add('vit', 5)" class="w-6 h-6 bg-gradient-to-r from-amber-600 to-yellow-600 hover:from-amber-500 hover:to-yellow-500 text-stone-950 rounded-lg text-xs flex items-center justify-center font-extrabold shadow active:scale-90 transition-transform duration-75" title="Dodaj 5">+5</button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- AGI -->
                        <div class="flex justify-between items-center group">
                            <span class="text-stone-300 font-medium cursor-help border-b border-dashed border-stone-600" title="Zręczność: Decyduje o kolejności ataku w walce oraz o szansie na uniki.">Agility (AGI):</span>
                            <div class="flex items-center gap-2">
                                <span id="stat-flash-agi" class="text-amber-300 font-bold text-base w-8 text-right inline-block transition-transform" x-text="{{ $totalAttributes['agi'] ?? 0 }} + added.agi"></span>
                                <div class="flex gap-1" x-show="points > 0">
                                    <button @click="add('agi', 1)" class="w-6 h-6 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white rounded-lg text-xs flex items-center justify-center font-extrabold shadow active:scale-90 transition-transform duration-75" title="Dodaj 1">+1</button>
                                    <button x-show="points >= 5" @click="add('agi', 5)" class="w-6 h-6 bg-gradient-to-r from-amber-600 to-yellow-600 hover:from-amber-500 hover:to-yellow-500 text-stone-950 rounded-lg text-xs flex items-center justify-center font-extrabold shadow active:scale-90 transition-transform duration-75" title="Dodaj 5">+5</button>
                                </div>
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
                @elseif($activeTab === 'pets')
                    <div class="mt-4">
                        @include('livewire.city.pets')
                    </div>
                @elseif($activeTab === 'collections')
                    <div class="mt-4">
                        @livewire('profile.collections-tab', ['character' => $character])
                    </div>
                @elseif($activeTab === 'skills')
                    <div class="mt-4">
                        @livewire('profile.skills-tab', ['character' => $character])
                    </div>
                @endif
            </div>
        </div>

        <!-- Right Side: Inventory -->
        <div class="bg-gradient-to-b from-stone-900/95 via-stone-900/90 to-stone-950/95 border-2 border-amber-700/60 rounded-3xl shadow-2xl backdrop-blur-md p-6 flex flex-col h-full relative overflow-hidden">
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-amber-500/5 via-transparent to-transparent pointer-events-none"></div>

            <div class="flex justify-between items-center mb-4 border-b border-amber-900/40 pb-3 relative z-10">
                <div class="flex items-center gap-2">
                    <span class="text-amber-400 text-xl">🎒</span>
                    <h2 class="text-2xl font-bold text-amber-300 medieval-font drop-shadow-md">Ekwipunek</h2>
                </div>
                <div class="text-amber-300 font-bold flex gap-3 text-sm">
                    <div class="bg-stone-950/90 border border-amber-800/60 px-3 py-1 rounded-xl shadow-inner flex items-center gap-1.5">
                        <span class="text-yellow-400 drop-shadow">🪙</span>
                        <span>{{ number_format($character->gold) }}</span>
                    </div>
                    <div class="bg-stone-950/90 border border-cyan-800/60 px-3 py-1 rounded-xl shadow-inner flex items-center gap-1.5 text-cyan-300">
                        <span class="text-cyan-400 drop-shadow">💎</span>
                        <span>{{ number_format(auth()->user()->gems) }}</span>
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-2.5 mb-3 relative z-10">
                {{-- Category Filters --}}
                <div class="flex flex-wrap gap-1.5 items-center">
                    <button wire:click="setInventoryFilter('all')" class="px-3 py-1.5 text-xs rounded-xl font-bold transition-all duration-200 medieval-font {{ $inventoryFilter === 'all' ? 'bg-gradient-to-r from-amber-600 to-yellow-600 text-stone-950 shadow-[0_0_10px_rgba(245,158,11,0.4)]' : 'bg-stone-950/70 border border-amber-900/40 text-stone-300 hover:bg-stone-800 hover:text-amber-300' }}">Wszystko</button>
                    <button wire:click="setInventoryFilter('weapon')" class="px-3 py-1.5 text-xs rounded-xl font-bold transition-all duration-200 medieval-font {{ $inventoryFilter === 'weapon' ? 'bg-gradient-to-r from-amber-600 to-yellow-600 text-stone-950 shadow-[0_0_10px_rgba(245,158,11,0.4)]' : 'bg-stone-950/70 border border-amber-900/40 text-stone-300 hover:bg-stone-800 hover:text-amber-300' }}">Bronie</button>
                    <button wire:click="setInventoryFilter('armor')" class="px-3 py-1.5 text-xs rounded-xl font-bold transition-all duration-200 medieval-font {{ $inventoryFilter === 'armor' ? 'bg-gradient-to-r from-amber-600 to-yellow-600 text-stone-950 shadow-[0_0_10px_rgba(245,158,11,0.4)]' : 'bg-stone-950/70 border border-amber-900/40 text-stone-300 hover:bg-stone-800 hover:text-amber-300' }}">Pancerz</button>
                    <button wire:click="setInventoryFilter('accessory')" class="px-3 py-1.5 text-xs rounded-xl font-bold transition-all duration-200 medieval-font {{ $inventoryFilter === 'accessory' ? 'bg-gradient-to-r from-amber-600 to-yellow-600 text-stone-950 shadow-[0_0_10px_rgba(245,158,11,0.4)]' : 'bg-stone-950/70 border border-amber-900/40 text-stone-300 hover:bg-stone-800 hover:text-amber-300' }}">Akcesoria</button>
                    <button wire:click="setInventoryFilter('material')" class="px-3 py-1.5 text-xs rounded-xl font-bold transition-all duration-200 medieval-font {{ $inventoryFilter === 'material' ? 'bg-gradient-to-r from-amber-600 to-yellow-600 text-stone-950 shadow-[0_0_10px_rgba(245,158,11,0.4)]' : 'bg-stone-950/70 border border-amber-900/40 text-stone-300 hover:bg-stone-800 hover:text-amber-300' }}">Materiały</button>
                    <button wire:click="setInventoryFilter('consumable')" class="px-3 py-1.5 text-xs rounded-xl font-bold transition-all duration-200 medieval-font {{ $inventoryFilter === 'consumable' ? 'bg-gradient-to-r from-amber-600 to-yellow-600 text-stone-950 shadow-[0_0_10px_rgba(245,158,11,0.4)]' : 'bg-stone-950/70 border border-amber-900/40 text-stone-300 hover:bg-stone-800 hover:text-amber-300' }}">Mikstury</button>
                </div>

                {{-- Action Row (Stackuj Button) --}}
                <div class="flex justify-between items-center pt-1">
                    <span class="text-stone-400 text-xs font-medium">Miejsca: <strong class="text-amber-300">{{ count($inventory) }} / 25</strong></span>
                    <button wire:click="stackItems" class="px-3 py-1.5 text-xs rounded-xl bg-gradient-to-r from-emerald-700 to-teal-700 hover:from-emerald-600 hover:to-teal-600 text-emerald-100 font-bold flex items-center gap-1.5 shadow transition-all duration-200 border border-emerald-500/40 transform active:scale-95" title="Połącz powtarzające się materiały">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path></svg>
                        Połącz przedmioty (Stackuj)
                    </button>
                </div>
            </div>

            <!-- Inventory Grid -->
            <div id="inventory-grid" 
                 x-data="{ isInventoryDragOver: false }"
                 @dragover.prevent="if (window.currentDragItem && (window.currentDragItem.source === 'equipped' || window.currentDragItem.source === 'equipped_pet')) isInventoryDragOver = true"
                 @dragleave="isInventoryDragOver = false"
                 @drop.prevent="
                     if (window.currentDragItem && window.currentDragItem.source === 'equipped') {
                         let dragItem = window.currentDragItem;
                         isInventoryDragOver = false;
                         flyItem('equip-slot-' + dragItem.slot, 'inventory-grid', () => $wire.unequipItem(dragItem.id));
                     } else if (window.currentDragItem && window.currentDragItem.source === 'equipped_pet') {
                         let dragItem = window.currentDragItem;
                         isInventoryDragOver = false;
                         flyItem('equip-slot-pet', 'inventory-grid', () => $wire.toggleEquipPet(dragItem.id));
                     } else {
                         isInventoryDragOver = false;
                     }
                 "
                 class="grid grid-cols-4 sm:grid-cols-5 gap-2 bg-gray-800 p-2 rounded flex-grow content-start transition-all"
                 :class="{ 'ring-4 ring-blue-400 border-2 border-blue-400 bg-blue-950/40': isInventoryDragOver }"
            >
                @foreach($inventory as $item)
                    @php
                        $isRustySwordTutorial = $gameStage == 6 && $item->template_id === '01k4jpx94j70x2vv10b835prm4';
                    @endphp
                    <div id="backpack-item-{{ $item->id }}" x-data="{ 
                        open: false, 
                        hoverTimeout: null,
                        isDraggingThis: false,
                        posClass: 'sm:bottom-full sm:mb-2',
                        checkPosition() { 
                            this.posClass = this.$el.getBoundingClientRect().top < window.innerHeight / 2 ? 'sm:top-full sm:mt-2' : 'sm:bottom-full sm:mb-2'; 
                        }
                    }" @click.outside="open = false" 
                         wire:loading.class="opacity-50 scale-95 pointer-events-none" wire:target="equipItem('{{ $item->id }}')"
                         draggable="true"
                         @dragstart="
                             open = false; 
                             clearTimeout(hoverTimeout);
                             isDraggingThis = true;
                             window.currentDragItem = { 
                                 id: '{{ $item->id }}', 
                                 slot: '{{ $item->template->slot ?? '' }}', 
                                 type: '{{ $item->template->type ?? '' }}', 
                                 levelReq: {{ $item->template->level_requirement ?? 1 }}, 
                                 charLevel: {{ $character->level }}, 
                                 source: 'backpack', 
                                 domId: 'backpack-item-{{ $item->id }}' 
                             };
                         "
                         @dragend="isDraggingThis = false; window.currentDragItem = null;"
                         @dblclick="
                             open = false;
                             clearTimeout(hoverTimeout);
                             @if($character->level < ($item->template->level_requirement ?? 1))
                                 $dispatch('notify', { type: 'error', message: 'Zbyt niski poziom aby założyć ten przedmiot!' });
                             @else
                                 @if(in_array($item->template->type ?? '', ['weapon', 'armor', 'accessory']) && ($item->template->slot ?? null))
                                     flyItem('backpack-item-{{ $item->id }}', 'equip-slot-{{ $item->template->slot }}', () => $wire.equipItem('{{ $item->id }}'));
                                 @elseif(($item->template->type ?? '') === 'consumable')
                                     $wire.consumeItem('{{ $item->id }}');
                                 @elseif(($item->template->type ?? '') === 'egg')
                                     flyItem('backpack-item-{{ $item->id }}', 'equip-slot-pet', () => $wire.placeEgg('{{ $item->id }}'));
                                 @endif
                             @endif
                         "
                         class="aspect-square bg-gray-700 border rounded flex items-center justify-center cursor-grab active:cursor-grabbing hover:border-green-400 relative transition-all duration-300 {{ count($item->roll_stats['enchants'] ?? []) > 0 ? 'enchanted-border border-gray-600' : 'border-gray-600' }}"
                         :class="{ 
                             'animate-[pulse_1.5s_ease-in-out_infinite] ring-4 ring-amber-500 scale-105 shadow-[0_0_15px_rgba(245,158,11,0.6)] z-10': {{ $isRustySwordTutorial ? 'true' : 'false' }} && !open,
                             'opacity-40 scale-95 border-amber-400': isDraggingThis
                         }"
                         @mouseenter="clearTimeout(hoverTimeout); checkPosition(); open = true" 
                         @mouseleave="hoverTimeout = setTimeout(() => { open = false }, 250)" 
                         @click="clearTimeout(hoverTimeout); checkPosition(); open = true">
                        
                        @if($item->template->icon)
                            <div class="text-center text-xs text-white flex flex-col items-center w-full h-full justify-center">
                                <img src="{{ route('assets.items', ['filename' => $item->template->icon]) }}" class="w-full h-full object-contain drop-shadow-lg p-1" alt="{{ $item->template->name }}">
                                
                                <div class="absolute bottom-0 right-0 flex flex-col items-end gap-0.5 pointer-events-none">
                                    @if($item->stack_size > 1)
                                        <span class="text-blue-300 font-bold text-[10px] bg-black/70 px-1 rounded-tl">{{ $item->stack_size }}x</span>
                                    @endif
                                </div>
                                @if(in_array($item->template->type ?? '', ['weapon', 'armor', 'accessory']))
                                    <span class="absolute bottom-0 left-0 text-amber-500 font-bold text-[10px] bg-black/80 px-1 rounded-tr border-t border-r border-gray-700/50 pointer-events-none">+{{ $item->upgrade_level ?? 0 }}</span>
                                @endif
                            </div>
                        @else
                            <div class="text-center text-xs text-white">
                                <span class="block truncate w-14">{{ $item->template->name }}</span>
                                @if($item->upgrade_level > 0)
                                    <span class="text-yellow-400">+{{ $item->upgrade_level }}</span>
                                @endif
                                @if($item->stack_size > 1)
                                    <span class="text-blue-300 font-bold block">{{ $item->stack_size }}x</span>
                                @endif
                            </div>
                        @endif

                        <!-- Tooltip / Modal -->
                        <!-- Tooltip / Modal -->
                        <div x-show="open" x-transition.opacity style="display: none;" 
                             :class="posClass"
                             class="fixed inset-0 sm:absolute sm:inset-auto sm:left-1/2 sm:-translate-x-1/2 sm:w-auto z-[200] sm:z-50 flex items-center justify-center sm:block bg-black/80 sm:bg-transparent backdrop-blur-sm sm:backdrop-blur-none p-4 sm:p-0 cursor-default" @click.stop="open = false">
                            <div class="relative w-full max-w-xs sm:w-auto sm:max-w-none">
                                <button @click="open = false" class="absolute top-2 right-2 text-gray-400 hover:text-white text-lg font-bold sm:hidden z-10">✕</button>
                                <x-item-tooltip :item="$item" :equippedItem="$equipped[$item->template->slot ?? ''] ?? null">
                                    <x-slot:actions>
                                        <div class="flex flex-col gap-2 w-full">
                                            @if($character->level < $item->template->level_requirement)
                                                <p class="text-red-500 font-bold text-center mb-2">Zbyt niski poziom!</p>
                                            @else
                                                @if($item->template->type === 'weapon' || $item->template->type === 'armor' || $item->template->type === 'accessory')
                                                    <button @click="open = false; flyItem('backpack-item-{{ $item->id }}', 'equip-slot-{{ $item->template->slot }}', () => $wire.equipItem('{{ $item->id }}'))" class="w-full bg-green-600 hover:bg-green-500 text-white font-bold py-2 rounded transition-colors shadow">
                                                        Załóż sprzęt
                                                    </button>
                                                @elseif($item->template->type === 'consumable')
                                                    <button wire:click="consumeItem('{{ $item->id }}')" @click="open = false" class="w-full bg-indigo-600 hover:bg-indigo-500 text-white font-bold py-2 rounded transition-colors shadow">
                                                        Użyj przedmiotu
                                                    </button>
                                                @endif
                                            @endif
                                            
                                            @if(!($item->bound_to_character ?? false) && ($item->template->is_tradeable ?? true))
                                                <button wire:click.stop="openSellModal('{{ $item->id }}'); open = false;" class="w-full bg-yellow-600 hover:bg-yellow-500 text-white py-2 rounded font-bold shadow transition-colors">
                                                    Wystaw na targowisko
                                                </button>
                                            @endif
                                        </div>
                                    </x-slot:actions>
                                </x-item-tooltip>
                                <!-- Arrow (Desktop only) -->
                                <div class="hidden sm:block absolute left-1/2 -translate-x-1/2 w-4 h-4 bg-gray-900 transform rotate-45 z-[-1]"
                                     :class="posClass === 'sm:top-full sm:mt-2' ? '-top-2 border-t border-l border-slate-600' : '-bottom-2 border-b border-r border-slate-600'"></div>
                            </div>
                        </div>
                    </div>
                @endforeach

                <!-- Empty Slots Filler -->
                @php $emptySlots = max(0, 25 - count($inventory)) @endphp
                @for($i = 0; $i < $emptySlots; $i++)
                    <div class="empty-slot aspect-square bg-gray-800 border border-gray-700 rounded"></div>
                @endfor
            </div>
        </div>

    </div>

    {{-- Sell Modal --}}
    @if($sellingItemUlid)
        <div class="fixed inset-0 bg-black/80 flex items-center justify-center z-[100] p-4">
            <div class="bg-gray-900 border-2 border-yellow-600 rounded-lg shadow-2xl p-6 max-w-md w-full relative">
                <button wire:click="closeSellModal" class="absolute top-4 right-4 text-gray-400 hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
                
                <h3 class="text-2xl font-bold text-yellow-500 mb-4 medieval-font">Wystaw na Targowisko</h3>
                
                @php
                    $sellItem = \App\Infrastructure\Persistence\ItemInstance::find($sellingItemUlid);
                @endphp
                
                @if($sellItem)
                    <div class="flex items-center space-x-3 mb-6 bg-gray-800 p-3 rounded border border-gray-700">
                        <div class="text-3xl">
                            @if($sellItem->template->slot === 'weapon') ⚔️
                            @elseif($sellItem->template->slot === 'head') 🪖
                            @elseif($sellItem->template->slot === 'chest') 🛡️
                            @elseif($sellItem->template->slot === 'legs') 👖
                            @elseif($sellItem->template->slot === 'boots') 👢
                            @else 📦
                            @endif
                        </div>
                        <div>
                            <div class="font-bold text-amber-200">{{ $sellItem->template->name }}</div>
                            <div class="text-xs text-gray-400">{{ ucfirst($sellItem->rarity) }} | Poz: {{ $sellItem->template->level_requirement }}</div>
                        </div>
                    </div>
                @endif
                
                <div class="space-y-4 mb-6">
                    <div>
                        <label class="block text-sm font-bold text-gray-300 mb-1">Cena</label>
                        <input type="number" wire:model="sellPrice" min="1" class="w-full bg-gray-800 border border-gray-600 rounded p-2 text-white" placeholder="Wpisz cenę...">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-bold text-gray-300 mb-1">Waluta</label>
                        <select wire:model="sellCurrency" class="w-full bg-gray-800 border border-gray-600 rounded p-2 text-white">
                            <option value="gold">Złoto 💰</option>
                            <option value="gems">Klejnoty 💎</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-bold text-gray-300 mb-1">Czas trwania i opłata</label>
                        <select wire:model="sellDuration" class="w-full bg-gray-800 border border-gray-600 rounded p-2 text-white">
                            <option value="24">24 godziny (Koszt: 100 złota)</option>
                            <option value="48">48 godzin (Koszt: 250 złota)</option>
                            <option value="72">72 godziny (Koszt: 500 złota)</option>
                        </select>
                    </div>
                </div>
                
                <div class="flex justify-end gap-3">
                    <button wire:click="closeSellModal" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded font-bold transition">Anuluj</button>
                    <button wire:click="sellItem" class="px-4 py-2 bg-yellow-600 hover:bg-yellow-500 text-white rounded font-bold transition">Wystaw Przedmiot</button>
                </div>
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
    <script>
        window.flyItem = async function(sourceId, targetId, wireCall) {
            let sourceEl = document.getElementById(sourceId);
            let targetEl = document.getElementById(targetId);
            
            if (!sourceEl || !targetEl) {
                if (wireCall) wireCall();
                return;
            }
            
            if (targetId === 'inventory-grid') {
                let firstEmptySlot = targetEl.querySelector('.empty-slot');
                if (firstEmptySlot) {
                    targetEl = firstEmptySlot;
                }
            }
            
            let rect1 = sourceEl.getBoundingClientRect();
            let rect2 = targetEl.getBoundingClientRect();
            
            if (targetId === 'inventory-grid' && !targetEl.classList.contains('empty-slot')) {
                // Fallback if inventory is full: keep original item size and target grid center
                rect2 = {
                    top: rect2.top + rect2.height / 2 - rect1.height / 2,
                    left: rect2.left + rect2.width / 2 - rect1.width / 2,
                    width: rect1.width,
                    height: rect1.height
                };
            }
            
            let targetHasItem = sourceId.includes('backpack') && targetEl.querySelector('.text-center.text-xs.text-white') !== null;
            
            let ghost = sourceEl.cloneNode(true);
            let tooltip = ghost.querySelector('[x-show]');
            if(tooltip) tooltip.remove();
            
            let spinner = document.createElement('div');
            spinner.innerHTML = `<svg class="animate-spin h-6 w-6 text-white drop-shadow-md" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>`;
            spinner.className = 'absolute inset-0 bg-black/40 flex items-center justify-center rounded z-20';
            ghost.appendChild(spinner);
            
            ghost.style.position = 'fixed';
            ghost.style.top = rect1.top + 'px';
            ghost.style.left = rect1.left + 'px';
            ghost.style.width = rect1.width + 'px';
            ghost.style.height = rect1.height + 'px';
            ghost.style.zIndex = '9999';
            ghost.style.transition = 'all 0.5s cubic-bezier(0.25, 1, 0.5, 1)';
            ghost.style.margin = '0';
            ghost.style.pointerEvents = 'none';
            document.body.appendChild(ghost);
            
            let oldGhost = null;
            if (targetHasItem) {
                oldGhost = targetEl.cloneNode(true);
                let oldTooltip = oldGhost.querySelector('[x-show]');
                if(oldTooltip) oldTooltip.remove();
                
                oldGhost.style.position = 'fixed';
                oldGhost.style.top = rect2.top + 'px';
                oldGhost.style.left = rect2.left + 'px';
                oldGhost.style.width = rect2.width + 'px';
                oldGhost.style.height = rect2.height + 'px';
                oldGhost.style.zIndex = '9998';
                oldGhost.style.transition = 'all 0.5s cubic-bezier(0.25, 1, 0.5, 1)';
                oldGhost.style.margin = '0';
                oldGhost.style.pointerEvents = 'none';
                document.body.appendChild(oldGhost);
                
                // Hide only inner content for target
                Array.from(targetEl.children).forEach(c => {
                    if(!c.classList.contains('absolute') && c.tagName !== 'SCRIPT') {
                        c.style.opacity = '0';
                    }
                });
            }
            
            // Hide only inner content for source, leaving the slot background visible
            Array.from(sourceEl.children).forEach(c => {
                if(!c.classList.contains('absolute') && c.tagName !== 'SCRIPT') {
                    c.style.opacity = '0';
                }
            });
            
            // 1. New item hovers up
            setTimeout(() => {
                ghost.style.transform = 'scale(1.1) translateY(-20px)';
            }, 10);
            await new Promise(r => setTimeout(r, 300));
            
            // 2. Old item flies to backpack
            if (targetHasItem) {
                oldGhost.style.top = rect1.top + 'px';
                oldGhost.style.left = rect1.left + 'px';
                oldGhost.style.opacity = '0.7';
                await new Promise(r => setTimeout(r, 400));
            }
            
            let livewirePromise = null;
            if (wireCall) {
                livewirePromise = wireCall();
            }
            
            // 3. New item flies to equip slot
            ghost.style.transition = 'all 0.6s cubic-bezier(0.25, 1, 0.5, 1)';
            ghost.style.transform = 'scale(1) translateY(0)';
            ghost.style.top = rect2.top + 'px';
            ghost.style.left = rect2.left + 'px';
            ghost.style.width = rect2.width + 'px';
            ghost.style.height = rect2.height + 'px';
            ghost.style.opacity = '0.7';
            
            await new Promise(r => setTimeout(r, 600));
            
            try {
                if (livewirePromise) await livewirePromise;
                
                let operationFailed = false;
                if (sourceId.includes('backpack')) {
                    operationFailed = document.getElementById(sourceId) !== null;
                } else {
                    let sourceSlot = document.getElementById(sourceId);
                    operationFailed = sourceSlot && sourceSlot.querySelector('.text-center.text-xs.text-white') !== null;
                }
                
                if (operationFailed) {
                    let currentSourceRect = document.getElementById(sourceId).getBoundingClientRect();
                    ghost.style.transition = 'all 0.5s cubic-bezier(0.25, 1, 0.5, 1)';
                    ghost.style.top = currentSourceRect.top + 'px';
                    ghost.style.left = currentSourceRect.left + 'px';
                    ghost.style.width = currentSourceRect.width + 'px';
                    ghost.style.height = currentSourceRect.height + 'px';
                    ghost.style.opacity = '1';
                    
                    if (oldGhost) {
                        let currentTargetRect = document.getElementById(targetId).getBoundingClientRect();
                        oldGhost.style.transition = 'all 0.5s cubic-bezier(0.25, 1, 0.5, 1)';
                        oldGhost.style.top = currentTargetRect.top + 'px';
                        oldGhost.style.left = currentTargetRect.left + 'px';
                        oldGhost.style.width = currentTargetRect.width + 'px';
                        oldGhost.style.height = currentTargetRect.height + 'px';
                        oldGhost.style.opacity = '1';
                    }
                    
                    if (spinner) spinner.remove();
                    await new Promise(r => setTimeout(r, 500));
                }
            } catch (e) {
                console.error(e);
            }
            
            ghost.remove();
            if (oldGhost) oldGhost.remove();
            
            let currentSource = document.getElementById(sourceId);
            if(currentSource) {
                Array.from(currentSource.children).forEach(c => c.style.opacity = '');
            }
            
            let currentTarget = document.getElementById(targetId);
            if(currentTarget) {
                Array.from(currentTarget.children).forEach(c => c.style.opacity = '');
            }
        }
    </script>
</div>
