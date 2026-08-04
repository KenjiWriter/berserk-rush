<div class="min-h-screen bg-slate-950 text-amber-100 relative overflow-hidden">
    {{-- Bright Background Parallax Image & Ambient Gold Overlay --}}
    <div class="fixed inset-0 bg-cover bg-center opacity-75 mix-blend-normal scale-105 pointer-events-none" style="background-image: url('{{ asset('img/profile-bg.png') }}');"></div>
    <div class="fixed inset-0 bg-gradient-to-b from-slate-950/60 via-slate-900/40 to-slate-950/70 pointer-events-none"></div>
    <div class="fixed inset-0 bg-[radial-gradient(ellipse_at_center,_var(--tw-gradient-stops))] from-amber-500/10 via-black/20 to-black/70 pointer-events-none"></div>

    <div class="relative w-full px-2 xs:px-3 sm:px-4 md:px-6 lg:px-6 xl:px-8 py-3 sm:py-5 md:py-6 pb-24 lg:pb-8 min-h-screen z-10">
        @php
            $gameStage = auth()->user()->game_stage;
        @endphp

        @if($gameStage == 5)
            <livewire:global.tutorial-overlay :step="6" />
        @elseif($gameStage == 7)
            <livewire:global.tutorial-overlay :step="8" :rewardXp="50" />
        @endif

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 sm:gap-5 lg:gap-6 items-start mt-2">
        
        <div class="bg-gradient-to-b from-slate-900/90 via-stone-900/85 to-slate-950/90 border-2 border-amber-500/70 rounded-2xl sm:rounded-3xl shadow-[0_0_30px_rgba(245,158,11,0.15)] backdrop-blur-md p-3 xs:p-4 sm:p-6 flex flex-col h-full relative overflow-visible">
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-amber-400/10 via-transparent to-transparent pointer-events-none"></div>


            <!-- Zestawy Ekwipunku: szybka zmiana (Arena PvP / Wojna Gildii / Set 1-3) -->
            <div class="flex flex-wrap justify-center gap-1.5 xs:gap-2 mb-2" x-data="{ openSet: null }">
                @foreach($equipmentSets as $setType => $set)
                    @php
                        $setIcon = match($setType) {
                            'pvp' => 'fa-shield-halved',
                            'guild_war' => 'fa-flag',
                            default => 'fa-star',
                        };
                    @endphp
                    <div class="relative" @click.outside="openSet === '{{ $setType }}' && (openSet = null)">
                        <button type="button" @click="openSet = (openSet === '{{ $setType }}' ? null : '{{ $setType }}')"
                                class="flex items-center gap-1.5 px-2 xs:px-2.5 py-1 xs:py-1.5 rounded-lg border-2 text-[9px] xs:text-[11px] font-semibold transition-colors
                                    {{ $set['locked'] ? 'border-stone-700 bg-stone-900/60 text-stone-500' : 'border-amber-600/60 bg-stone-900/80 text-amber-200 hover:border-amber-400' }}">
                            <i class="fa-solid {{ $setIcon }}"></i>
                            <span>{{ $set['label'] }}</span>
                            @if($set['locked'])
                                <i class="fa-solid fa-lock text-stone-500"></i>
                            @elseif($set['configuredCount'] > 0)
                                <span class="text-green-400">({{ $set['configuredCount'] }}/6)</span>
                            @endif
                        </button>

                        <div x-show="openSet === '{{ $setType }}'" x-transition.opacity style="display: none;" @click.stop
                             class="absolute z-[999] top-full left-1/2 -translate-x-1/2 mt-2 w-56 bg-stone-900 border border-amber-500/50 rounded-xl shadow-2xl p-3 text-left">
                            @if($set['locked'])
                                <p class="text-stone-400 text-xs mb-2">Ten zestaw jest dostępny wyłącznie dla graczy Premium.</p>
                            @else
                                <div class="grid grid-cols-6 gap-1 mb-2">
                                    @foreach($set['slots'] as $slot => $item)
                                        <div class="w-7 h-7 bg-stone-800 border border-stone-700 rounded flex items-center justify-center" title="{{ ucfirst($slot) }}">
                                            @if($item && $item->template && $item->template->icon)
                                                <img src="{{ route('assets.items', ['filename' => $item->template->icon]) }}" class="w-full h-full object-contain p-0.5" alt="{{ $item->template->name }}">
                                            @else
                                                <i class="fa-solid fa-circle-question text-stone-600 text-[10px]"></i>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                                <div class="mb-2 pt-1 border-t border-stone-800">
                                    <div class="text-[10px] text-amber-300/80 font-semibold mb-1">Umiejętności w zestawie:</div>
                                    <div class="flex gap-1.5">
                                        @forelse($set['skills'] as $setSkill)
                                            <div class="w-7 h-7 bg-purple-950/80 border border-purple-500/60 rounded flex items-center justify-center overflow-hidden" title="{{ $setSkill->skill?->name }}">
                                                @if($setSkill->skill?->icon)
                                                    <img src="{{ route('assets.skills.icons', ['filename' => $setSkill->skill->icon]) }}" class="w-full h-full object-contain p-0.5" alt="{{ $setSkill->skill->name }}">
                                                @else
                                                    <span class="text-[8px] font-bold text-amber-200">{{ mb_substr($setSkill->skill?->name ?? '', 0, 2) }}</span>
                                                @endif
                                            </div>
                                        @empty
                                            <span class="text-stone-500 text-[10px]">Brak (użyje domyślnych)</span>
                                        @endforelse
                                    </div>
                                </div>
                                @if($set['configuredCount'] === 0)
                                    <p class="text-stone-500 text-[10px] mb-2">Nieskonfigurowany - użyje aktualnego ekwipunku.</p>
                                @endif
                            @endif
                            <div class="flex flex-col gap-1.5">
                                <button wire:click="saveEquipmentSet('{{ $setType }}')" @click="openSet = null"
                                        @if($set['locked']) disabled @endif
                                        class="w-full bg-amber-700 hover:bg-amber-600 disabled:opacity-40 disabled:cursor-not-allowed text-white text-xs font-bold py-1.5 rounded">
                                    Zapisz zestaw (ekwipunek + skille)
                                </button>
                                @if($set['isWearable'])
                                    <button wire:click="applyEquipmentSet('{{ $setType }}')" @click="openSet = null"
                                            @if($set['locked'] || $set['configuredCount'] === 0) disabled @endif
                                            class="w-full bg-emerald-700 hover:bg-emerald-600 disabled:opacity-40 disabled:cursor-not-allowed text-white text-xs font-bold py-1.5 rounded">
                                        Załóż ten zestaw
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Equipment Slots & Portrait -->
            <div class="flex justify-center items-start gap-1.5 xs:gap-2 sm:gap-3 md:gap-4 lg:gap-4 xl:gap-6 mb-3 mt-1 sm:mt-2">
                <!-- Left Slots -->
                <div class="flex flex-col gap-1.5 xs:gap-2 sm:gap-3">
                    @foreach(['head', 'chest', 'main_hand'] as $slot)
                        <div id="equip-slot-{{ $slot }}" wire:key="equip-slot-{{ $slot }}-{{ $equipped[$slot]->id ?? 'empty' }}" x-data="{ open: false, hoverTimeout: null, isDragOver: false, isDragInvalid: false }" @click.outside="open = false"
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
                             class="w-12 h-12 xs:w-14 xs:h-14 sm:w-16 sm:h-16 lg:w-16 lg:h-16 xl:w-20 xl:h-20 bg-stone-900/90 border-2 {{ isset($equipped[$slot]) ? 'border-amber-500/80 cursor-grab active:cursor-grabbing hover:border-amber-400 shadow-[0_0_15px_rgba(245,158,11,0.2)]' : 'border-stone-700/80 hover:border-amber-500/40' }} rounded-xl sm:rounded-2xl flex items-center justify-center relative transition-all duration-200 {{ isset($equipped[$slot]) && count($equipped[$slot]->roll_stats['enchants'] ?? []) > 0 ? 'enchanted-border' : '' }}"
                             :class="{ 
                                 'ring-4 ring-green-400 border-green-400 bg-green-900/40 scale-105 shadow-[0_0_15px_rgba(74,222,128,0.6)]': isDragOver,
                                 'ring-4 ring-red-500 border-red-500 bg-red-900/40': isDragInvalid,
                                 '!z-[99999] relative': open
                             }"
                             @if(isset($equipped[$slot])) @click="clearTimeout(hoverTimeout); open = !open" @endif>
                            @if(isset($equipped[$slot]))
                                @if($equipped[$slot]->template->icon)
                                    <div class="text-center text-xs text-white flex flex-col items-center w-full h-full justify-center p-0.5 sm:p-1 relative">
                                        <img src="{{ route('assets.items', ['filename' => $equipped[$slot]->template->icon]) }}" class="w-full h-full object-contain drop-shadow-xl p-0.5 sm:p-1" alt="{{ $equipped[$slot]->template->name }}">
                                        <x-item-upgrade-overlay :level="$equipped[$slot]->upgrade_level ?? 0" :type="$equipped[$slot]->template->type ?? ''" />
                                    </div>
                                @else
                                    <div class="text-center text-xs text-white p-0.5 sm:p-1">
                                        <span class="block truncate w-10 xs:w-12 sm:w-16 text-amber-200 font-semibold text-[9px] xs:text-[10px] sm:text-xs">{{ $equipped[$slot]->template->name }}</span>
                                        @if($equipped[$slot]->upgrade_level > 0)
                                            <span class="text-yellow-400 font-bold text-[9px] xs:text-[10px] sm:text-xs">+{{ $equipped[$slot]->upgrade_level }}</span>
                                        @endif
                                    </div>
                                @endif
                                <!-- Tooltip / Modal -->
                                <div x-show="open" x-transition.opacity style="display: none;" class="fixed inset-0 sm:absolute sm:inset-auto sm:top-full sm:left-1/2 sm:-translate-x-1/2 sm:mt-2 sm:w-auto z-[99999] sm:z-[99999] flex items-center justify-center sm:block bg-black/80 sm:bg-transparent backdrop-blur-sm sm:backdrop-blur-none p-4 sm:p-0 cursor-default" @click.stop="open = false">
                                    <template x-if="open">
                                        <div class="relative w-full max-w-[420px] sm:w-auto sm:max-w-none">
                                            <x-item-tooltip :item="$equipped[$slot]">
                                                <x-slot:actions>
                                                    <div class="flex flex-col gap-2 w-full">
                                                        <button wire:click.stop="unequipItem('{{ $equipped[$slot]->id }}')" @click.stop="open = false; flyItem('equip-slot-{{ $slot }}', 'inventory-grid')" class="w-full bg-red-600 hover:bg-red-500 text-white font-bold py-2 rounded shadow">
                                                            Zdejmij przedmiot
                                                        </button>
                                                        @if(!($equipped[$slot]->bound_to_character ?? false) && ($equipped[$slot]->template->is_tradeable ?? true))
                                                            <button wire:click.stop="openSellModal('{{ $equipped[$slot]->id }}'); open = false;" class="w-full bg-yellow-600 hover:bg-yellow-500 text-white py-2 rounded font-bold shadow transition-colors">
                                                                Wystaw na targowisko
                                                            </button>
                                                        @endif
                                                    </div>
                                                </x-slot:actions>
                                            </x-item-tooltip>
                                            <!-- Arrow (Desktop only) -->
                                            <div class="hidden sm:block absolute -top-2 left-1/2 -translate-x-1/2 w-4 h-4 bg-gray-900 border-t border-l border-slate-600 transform rotate-45 z-[-1]"></div>
                                        </div>
                                    </template>
                                </div>
                            @else
                                <span class="text-stone-400 text-[9px] xs:text-[10px] sm:text-xs font-semibold capitalize px-0.5 text-center truncate">{{ ucfirst($slot) }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>

                <!-- Portrait & Info -->
                <div class="flex flex-col items-center w-36 xs:w-44 sm:w-48 lg:w-44 xl:w-52 shrink-0 min-w-0 max-w-full" x-data="{ avatarModalOpen: false }">
                    <div class="w-full h-[180px] xs:h-[210px] sm:h-[240px] lg:h-[230px] xl:h-[260px] bg-stone-950/90 border-2 sm:border-4 border-amber-600/80 rounded-xl sm:rounded-2xl overflow-hidden flex items-center justify-center mb-2 sm:mb-3 shadow-[0_0_20px_rgba(245,158,11,0.25)] relative group">
                        @if(auth()->user()->hasCustomAvatar())
                            {{-- Indywidualny avatar ustawiony przez admina --}}
                            <img src="{{ auth()->user()->getCustomAvatarUrl() }}" alt="Avatar" class="object-cover w-full h-full">
                        @elseif($character->avatar && file_exists(public_path('img/avatars/' . $character->avatar . '.png')))
                            <img src="{{ asset('img/avatars/' . $character->avatar . '.png') }}" alt="Avatar" class="object-cover w-full h-full">
                        @else
                            <div class="text-gray-500 flex flex-col items-center">
                                <svg class="w-12 h-12 sm:w-16 sm:h-16 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                <span>No Avatar</span>
                            </div>
                        @endif

                        @unless(auth()->user()->hasCustomAvatar())
                            <button @click="avatarModalOpen = true" class="absolute top-2 right-2 w-7 h-7 sm:w-8 sm:h-8 bg-amber-600/80 hover:bg-amber-500 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity shadow-lg backdrop-blur-sm border border-amber-400 text-xs sm:text-sm">
                                <i class="fa-solid fa-pen text-amber-100"></i>
                            </button>
                        @endunless
                    </div>


                    <!-- Avatar Modal -->
                    <div x-show="avatarModalOpen" style="display: none;" class="fixed inset-0 z-[150] flex items-center justify-center bg-black/80 backdrop-blur-sm p-4 text-left cursor-default">
                        <div class="bg-gray-900 border border-amber-500 p-4 sm:p-6 rounded-xl w-full max-w-2xl shadow-2xl relative" @click.outside="avatarModalOpen = false">
                            <button @click="avatarModalOpen = false" class="absolute top-3 right-3 sm:top-4 sm:right-4 text-gray-400 hover:text-white text-xl font-bold">✕</button>
                            <h3 class="text-xl sm:text-2xl font-bold text-amber-400 mb-4 sm:mb-6 border-b border-amber-900 pb-2 medieval-font">Wybierz Avatar</h3>
                            
                            <h4 class="text-sm sm:text-lg font-bold text-gray-300 mb-3 sm:mb-4">Podstawowe Avatary</h4>
                            <div class="grid grid-cols-4 sm:grid-cols-6 gap-3 sm:gap-4 mb-6 sm:mb-8">
                                @foreach($baseAvatars as $avatar)
                                    <button @click="$wire.changeAvatar('{{ $avatar }}', false); avatarModalOpen = false" class="aspect-square border-2 border-gray-600 hover:border-amber-500 rounded-lg overflow-hidden {{ $character->avatar === $avatar ? 'ring-4 ring-amber-500 border-amber-500' : '' }}">
                                        <img src="{{ asset('img/avatars/' . $avatar . '.png') }}" class="w-full h-full object-cover">
                                    </button>
                                @endforeach
                            </div>

                            @if(!empty(auth()->user()->unlocked_avatars))
                                <h4 class="text-sm sm:text-lg font-bold text-yellow-400 mb-3 sm:mb-4 flex items-center gap-2"><i class="fa-solid fa-gem text-cyan-400"></i> Avatary Premium</h4>
                                <div class="grid grid-cols-4 sm:grid-cols-6 gap-3 sm:gap-4">
                                    @foreach(auth()->user()->unlocked_avatars as $premiumAvatar)
                                        <button @click="$wire.changeAvatar('{{ $premiumAvatar }}', true); avatarModalOpen = false" class="aspect-square border-2 border-yellow-600 hover:border-yellow-400 rounded-lg overflow-hidden {{ $character->avatar === 'premium/' . $premiumAvatar ? 'ring-4 ring-yellow-400 border-yellow-400 shadow-[0_0_15px_rgba(250,204,21,0.6)]' : '' }}">
                                            <img src="{{ asset('img/avatars/premium/' . $premiumAvatar . '.png') }}" class="w-full h-full object-cover">
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                    @if($character->activeTitle)
                        <div class="text-[9px] xs:text-[10px] sm:text-[11px] text-amber-400 font-bold uppercase tracking-widest mt-1 -mb-0.5 drop-shadow-md text-center truncate w-full max-w-full px-1" title="{{ $character->activeTitle->prefix }}">
                            {{ $character->activeTitle->prefix }}
                        </div>
                    @endif
                    <div class="flex items-center justify-center gap-1 w-full max-w-full min-w-0 px-1">
                        <h2 class="text-sm xs:text-base sm:text-lg lg:text-xl font-extrabold text-amber-300 text-center medieval-font drop-shadow-md truncate min-w-0" title="{{ $character->name }}">{{ $character->name }}</h2>
                        <button wire:click="openNameChangeModal" class="text-amber-400/70 hover:text-amber-200 transition-colors p-0.5 cursor-pointer shrink-0" title="Zmień nick postaci">
                            <i class="fa-solid fa-pen-to-square text-xs sm:text-sm"></i>
                        </button>
                    </div>

                    <!-- Name Change Modal -->
                    @if($showNameChangeModal)
                        <div class="fixed inset-0 z-[160] flex items-center justify-center bg-black/80 backdrop-blur-sm p-4 text-left cursor-default">
                            <div class="bg-gradient-to-b from-stone-900 via-slate-900 to-stone-950 border-2 border-amber-500/80 p-5 sm:p-6 rounded-2xl w-full max-w-md shadow-[0_0_30px_rgba(245,158,11,0.25)] relative overflow-hidden" @click.outside="$wire.closeNameChangeModal()">
                                <button wire:click="closeNameChangeModal" class="absolute top-3 right-3 text-stone-400 hover:text-white text-xl font-bold cursor-pointer">✕</button>
                                
                                <h3 class="text-xl font-bold text-amber-300 mb-2 medieval-font flex items-center gap-2 border-b border-amber-500/30 pb-3">
                                    <i class="fa-solid fa-pen-to-square text-amber-400"></i> Zmień Nick Postaci
                                </h3>

                                <div class="space-y-4 my-4">
                                    <div class="bg-stone-950/80 p-3 rounded-xl border border-stone-800 flex items-center justify-between">
                                        <span class="text-xs text-stone-400">Obecny nick:</span>
                                        <span class="text-sm font-bold text-amber-200 medieval-font">{{ $character->name }}</span>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-stone-300 mb-1.5 uppercase tracking-wider">Nowy nick (3-16 znaków):</label>
                                        <input type="text" wire:model="newName" wire:keydown.enter="changeCharacterName"
                                            class="w-full bg-stone-950 border-2 border-amber-500/40 rounded-xl py-2.5 px-3 text-amber-100 text-sm font-semibold focus:outline-none focus:border-amber-400 transition shadow-inner placeholder-stone-600"
                                            placeholder="Wprowadź nowy nick..." maxlength="16" autofocus />
                                    </div>

                                    {{-- Price Banner --}}
                                    <div class="p-3 rounded-xl border flex items-center justify-between {{ ($character->name_changes_count ?? 0) === 0 ? 'bg-emerald-950/40 border-emerald-500/40 text-emerald-300' : 'bg-amber-950/40 border-amber-500/40 text-amber-300' }}">
                                        <div class="flex items-center gap-2 text-xs font-bold">
                                            @if(($character->name_changes_count ?? 0) === 0)
                                                <i class="fa-solid fa-gift text-emerald-400 text-base"></i>
                                                <span>Pierwsza zmiana: <strong class="text-emerald-200 uppercase tracking-wider">ZA DARMO!</strong></span>
                                            @else
                                                <i class="fa-solid fa-gem text-cyan-400 text-base"></i>
                                                <span>Koszt zmiany: <strong class="text-yellow-400">200 Gemów</strong></span>
                                            @endif
                                        </div>
                                        @if(($character->name_changes_count ?? 0) > 0)
                                            <span class="text-[11px] text-stone-400 font-medium">Posiadasz: <strong class="text-cyan-300">{{ auth()->user()->gems }}</strong></span>
                                        @endif
                                    </div>
                                </div>

                                <div class="flex items-center justify-end gap-3 border-t border-amber-500/20 pt-4">
                                    <button wire:click="closeNameChangeModal" type="button" class="px-4 py-2 bg-stone-800 hover:bg-stone-700 text-stone-300 font-bold rounded-xl text-xs transition cursor-pointer">
                                        Anuluj
                                    </button>
                                    <button wire:click="changeCharacterName" type="button"
                                        class="px-5 py-2.5 bg-gradient-to-r from-amber-600 via-amber-500 to-yellow-500 hover:from-amber-500 hover:to-yellow-400 text-stone-950 font-black rounded-xl text-xs transition shadow-lg flex items-center gap-1.5 uppercase tracking-wider medieval-font cursor-pointer"
                                        wire:loading.attr="disabled" wire:target="changeCharacterName">
                                        <span wire:loading.remove wire:target="changeCharacterName" class="flex items-center gap-1">
                                            <i class="fa-solid fa-check"></i> Potwierdź zmianę
                                        </span>
                                        <span wire:loading wire:target="changeCharacterName" class="flex items-center gap-1">
                                            <i class="fa-solid fa-spinner animate-spin"></i> Zapisywanie...
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif
                    <p class="text-amber-200/70 font-semibold text-xs sm:text-sm">Poziom {{ $character->level }}</p>
                    <div class="bg-amber-950/80 border border-amber-500/60 text-amber-300 px-2.5 sm:px-4 py-0.5 sm:py-1 rounded-full text-[10px] xs:text-xs font-bold shadow-[0_0_10px_rgba(245,158,11,0.2)] my-1 sm:my-1.5 medieval-font text-center truncate max-w-full flex items-center justify-center gap-1">
                        <i class="fa-solid fa-bolt text-yellow-400"></i> Moc Bojowa: {{ \App\Support\NumberHelper::formatShort($character->getTotalCombatPower()) }}
                    </div>
                    
                    @php 
                        $xpRequired = app(\App\Application\Characters\LevelUpService::class)->xpToNext($character->level);
                        $xpPercent = min(100, ($character->xp / max(1, $xpRequired)) * 100);
                    @endphp
                    <div class="w-full bg-gray-900 rounded-full h-4 sm:h-5 relative border border-gray-700 shadow-inner overflow-hidden cursor-help" title="Doświadczenie: {{ $character->xp }} / {{ $xpRequired }}">
                        <div class="bg-gradient-to-r from-blue-700 to-blue-400 h-full transition-all duration-300" style="width: {{ $xpPercent }}%"></div>
                        <span class="absolute inset-0 flex items-center justify-center text-[9px] sm:text-[10px] text-white font-bold drop-shadow-[0_1px_1px_rgba(0,0,0,0.8)]">
                            XP: {{ \App\Support\NumberHelper::formatShort($character->xp) }} / {{ \App\Support\NumberHelper::formatShort($xpRequired) }}
                        </span>
                    </div>
                    <div class="w-full mt-2.5 sm:mt-3 border-t border-stone-800 pt-2 sm:pt-2.5">
                        <h3 class="text-[10px] xs:text-xs font-bold text-amber-400/90 uppercase tracking-widest mb-1.5 sm:mb-2 text-center medieval-font">Wyposażone Skille</h3>
                        <div class="flex justify-center gap-2 sm:gap-3.5">
                            @for($i = 0; $i < 3; $i++)
                                @php
                                    $equippedSkill = $character->equippedSkills[$i] ?? null;
                                @endphp
                                <div x-data="{ open: false, hoverTimeout: null }" @click.outside="open = false"
                                     class="w-11 h-11 xs:w-13 xs:h-13 sm:w-14 sm:h-14 md:w-16 md:h-16 rounded-xl sm:rounded-2xl border-2 border-stone-700 bg-stone-900/90 flex flex-col items-center justify-center relative shadow-md transition-all duration-200 {{ $equippedSkill ? 'border-amber-500/80 hover:border-amber-400 bg-gradient-to-br from-amber-950/60 via-stone-900 to-amber-900/40 cursor-pointer shadow-[0_0_12px_rgba(245,158,11,0.25)] hover:scale-105' : 'border-dashed border-stone-700/80' }}"
                                     :class="{ '!z-[99999] relative': open }"
                                     @if($equippedSkill) 
                                        @click="clearTimeout(hoverTimeout); open = !open" 
                                     @endif>
                                    @if($equippedSkill)
                                        @if($equippedSkill->skill->icon)
                                            <img src="{{ route('assets.skills.icons', ['filename' => $equippedSkill->skill->icon]) }}" class="w-7 h-7 xs:w-8 xs:h-8 sm:w-9 sm:h-9 md:w-11 md:h-11 object-contain drop-shadow-md p-0.5 sm:p-1" alt="{{ $equippedSkill->skill->name }}">
                                        @else
                                            <i class="fa-solid fa-star text-sky-300 text-base sm:text-xl"></i>
                                        @endif
                                        <div class="absolute -bottom-1 -right-1 bg-stone-950 border border-amber-500/70 text-amber-300 text-[8px] sm:text-[10px] font-extrabold px-1 py-0.2 sm:px-1.5 sm:py-0.5 rounded-md shadow-lg leading-none">
                                            L{{ $equippedSkill->level }}
                                        </div>

                                        <!-- Infobox -->
                                        <div x-show="open" x-transition.opacity style="display: none;" class="fixed inset-0 sm:absolute sm:inset-auto sm:bottom-full sm:left-1/2 sm:-translate-x-1/2 sm:mb-2 sm:w-auto z-[99999] sm:z-[99999] flex items-center justify-center sm:block bg-black/80 sm:bg-transparent backdrop-blur-sm sm:backdrop-blur-none p-4 sm:p-0 cursor-default" @click.stop="open = false">
                                            <div class="relative w-[280px] sm:w-[320px] bg-stone-900 border-2 border-amber-500 rounded-xl shadow-[0_0_20px_rgba(245,158,11,0.4)] p-4 text-left">
                                                <button @click="open = false" class="absolute top-2 right-2 text-gray-400 hover:text-white text-lg font-bold sm:hidden z-10">✕</button>
                                                
                                                <div class="flex items-center gap-3 mb-3 border-b border-stone-800 pb-3">
                                                    @if($equippedSkill->skill->icon)
                                                        <img src="{{ route('assets.skills.icons', ['filename' => $equippedSkill->skill->icon]) }}" class="w-10 h-10 object-contain bg-stone-950 rounded-lg p-1 border border-amber-500/50">
                                                    @endif
                                                    <div>
                                                        <h4 class="text-amber-400 font-bold text-sm medieval-font">{{ $equippedSkill->skill->name }}</h4>
                                                        <p class="text-stone-400 text-xs">Poziom {{ $equippedSkill->level }}</p>
                                                    </div>
                                                </div>
                                                
                                                <div class="text-stone-300 text-xs mb-3 space-y-2">
                                                    <p>{{ $equippedSkill->skill->description }}</p>
                                                    <div class="flex flex-wrap gap-1.5 text-[11px] pt-1">
                                                        <span class="bg-indigo-950/80 text-indigo-300 px-2 py-0.5 rounded border border-indigo-700/50">Odnowienie: {{ $equippedSkill->skill->base_cooldown }} tur</span>
                                                        @if($equippedSkill->skill->base_duration > 0)
                                                            <span class="bg-purple-950/80 text-purple-300 px-2 py-0.5 rounded border border-purple-700/50">Czas trwania: {{ $equippedSkill->skill->base_duration }} tur</span>
                                                        @endif
                                                        @if($equippedSkill->skill->type === 'dot_poison')
                                                            <span class="bg-emerald-950/80 text-emerald-300 px-2 py-0.5 rounded border border-emerald-700/50">Trucizna: {{ $equippedSkill->skill->base_value + ($equippedSkill->level * $equippedSkill->skill->scaling_value) }}% aktualnego HP</span>
                                                        @elseif($equippedSkill->skill->type === 'dot_fire')
                                                            <span class="bg-rose-950/80 text-rose-300 px-2 py-0.5 rounded border border-rose-700/50">Ogień: {{ $equippedSkill->skill->base_value + ($equippedSkill->level * $equippedSkill->skill->scaling_value) }}% max HP</span>
                                                        @elseif($equippedSkill->skill->type === 'buff_damage')
                                                            <span class="bg-sky-950/80 text-sky-300 px-2 py-0.5 rounded border border-sky-700/50">Wzmocnienie: +{{ $equippedSkill->skill->base_value + ($equippedSkill->level * $equippedSkill->skill->scaling_value) }}% obrażeń</span>
                                                        @endif
                                                    </div>
                                                </div>

                                                <button @click="open = false; $wire.unequipSkill('{{ $equippedSkill->id }}')" class="w-full bg-rose-900/80 hover:bg-rose-800 text-rose-100 border border-rose-600/60 font-bold py-1.5 px-3 rounded-lg text-xs transition-colors shadow">
                                                    Zdejmij skill
                                                </button>
                                            </div>
                                            <!-- Arrow (Desktop only) -->
                                            <div class="hidden sm:block absolute left-1/2 -bottom-2 -translate-x-1/2 w-4 h-4 bg-stone-900 border-b-2 border-r-2 border-amber-500 transform rotate-45 z-10"></div>
                                        </div>
                                    @else
                                        <i class="fa-solid fa-star text-stone-600 text-xs sm:text-sm"></i>
                                    @endif
                                </div>
                            @endfor
                        </div>
                    </div>
                </div>

                <!-- Right Slots -->
                <div class="flex flex-col gap-1.5 xs:gap-2 sm:gap-3">
                    @foreach(['neck', 'ring', 'feet'] as $slot)
                        <div id="equip-slot-{{ $slot }}" wire:key="equip-slot-{{ $slot }}-{{ $equipped[$slot]->id ?? 'empty' }}" x-data="{ open: false, hoverTimeout: null, isDragOver: false, isDragInvalid: false }" @click.outside="open = false"
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
                             class="w-12 h-12 xs:w-14 xs:h-14 sm:w-16 sm:h-16 lg:w-16 lg:h-16 xl:w-20 xl:h-20 bg-stone-900/90 border-2 {{ isset($equipped[$slot]) ? 'border-amber-500/80 cursor-grab active:cursor-grabbing hover:border-amber-400 shadow-[0_0_15px_rgba(245,158,11,0.2)]' : 'border-stone-700/80 hover:border-amber-500/40' }} rounded-xl sm:rounded-2xl flex items-center justify-center relative transition-all duration-200 {{ isset($equipped[$slot]) && count($equipped[$slot]->roll_stats['enchants'] ?? []) > 0 ? 'enchanted-border' : '' }}"
                             :class="{ 
                                 'ring-4 ring-green-400 border-green-400 bg-green-900/40 scale-105 shadow-[0_0_15px_rgba(74,222,128,0.6)]': isDragOver,
                                 'ring-4 ring-red-500 border-red-500 bg-red-900/40': isDragInvalid,
                                 '!z-[99999] relative': open
                             }"
                             @if(isset($equipped[$slot])) @click="clearTimeout(hoverTimeout); open = !open" @endif>
                            @if(isset($equipped[$slot]))
                                @if($equipped[$slot]->template->icon)
                                    <div class="text-center text-xs text-white flex flex-col items-center w-full h-full justify-center p-0.5 sm:p-1 relative">
                                        <img src="{{ route('assets.items', ['filename' => $equipped[$slot]->template->icon]) }}" class="w-full h-full object-contain drop-shadow-xl p-0.5 sm:p-1" alt="{{ $equipped[$slot]->template->name }}">
                                        <x-item-upgrade-overlay :level="$equipped[$slot]->upgrade_level ?? 0" :type="$equipped[$slot]->template->type ?? ''" />
                                    </div>

                                @else
                                    <div class="text-center text-xs text-white p-0.5 sm:p-1">
                                        <span class="block truncate w-10 xs:w-12 sm:w-16 text-amber-200 font-semibold text-[9px] xs:text-[10px] sm:text-xs">{{ $equipped[$slot]->template->name }}</span>
                                        @if($equipped[$slot]->upgrade_level > 0)
                                            <span class="text-yellow-400 font-bold text-[9px] xs:text-[10px] sm:text-xs">+{{ $equipped[$slot]->upgrade_level }}</span>
                                        @endif
                                    </div>
                                @endif
                                <!-- Tooltip / Modal -->
                                <div x-show="open" x-transition.opacity style="display: none;" class="fixed inset-0 sm:absolute sm:inset-auto sm:top-full sm:left-1/2 sm:-translate-x-1/2 sm:mt-2 sm:w-auto z-[99999] sm:z-[99999] flex items-center justify-center sm:block bg-black/80 sm:bg-transparent backdrop-blur-sm sm:backdrop-blur-none p-4 sm:p-0 cursor-default" @click.stop="open = false">
                                    <template x-if="open">
                                        <div class="relative w-full max-w-[420px] sm:w-auto sm:max-w-none">
                                            <x-item-tooltip :item="$equipped[$slot]">
                                                <x-slot:actions>
                                                    <div class="flex flex-col gap-2 w-full">
                                                        <button wire:click.stop="unequipItem('{{ $equipped[$slot]->id }}')" @click.stop="open = false; flyItem('equip-slot-{{ $slot }}', 'inventory-grid')" class="w-full bg-red-600 hover:bg-red-500 text-white font-bold py-2 rounded shadow">
                                                            Zdejmij przedmiot
                                                        </button>
                                                        @if(!($equipped[$slot]->bound_to_character ?? false) && ($equipped[$slot]->template->is_tradeable ?? true))
                                                            <button wire:click.stop="openSellModal('{{ $equipped[$slot]->id }}'); open = false;" class="w-full bg-yellow-600 hover:bg-yellow-500 text-white py-2 rounded font-bold shadow transition-colors">
                                                                Wystaw na targowisko
                                                            </button>
                                                        @endif
                                                    </div>
                                                </x-slot:actions>
                                            </x-item-tooltip>
                                            <!-- Arrow (Desktop only) -->
                                            <div class="hidden sm:block absolute -top-2 left-1/2 -translate-x-1/2 w-4 h-4 bg-gray-900 border-t border-l border-slate-600 transform rotate-45 z-[-1]"></div>
                                        </div>
                                    </template>
                                </div>
                            @else
                                <span class="text-stone-400 text-[9px] xs:text-[10px] sm:text-xs font-semibold capitalize px-0.5 text-center truncate">{{ ucfirst($slot) }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Stats -->
            <div class="bg-stone-950/80 border border-amber-900/60 rounded-2xl p-3 sm:p-4 mt-3 shadow-inner relative z-10"
                 wire:key="attributes-panel-{{ $character->id }}">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-amber-900/60 pb-2 mb-3">
                    <div class="grid grid-cols-3 sm:grid-cols-5 gap-1 flex-grow text-[11px] sm:text-xs md:text-sm">
                        <button wire:click="setTab('attributes')" class="w-full text-center py-1.5 px-1 font-bold rounded-t-lg transition-all duration-200 medieval-font flex items-center justify-center {{ $activeTab === 'attributes' ? 'text-amber-300 border-b-2 border-amber-400 bg-amber-500/15 shadow-[0_0_10px_rgba(245,158,11,0.2)]' : 'text-stone-400 hover:text-amber-200 hover:bg-stone-800/40' }}">
                            Atrybuty
                        </button>
                        <button wire:click="setTab('stats')" class="w-full text-center py-1.5 px-1 font-bold rounded-t-lg transition-all duration-200 medieval-font flex items-center justify-center {{ $activeTab === 'stats' ? 'text-amber-300 border-b-2 border-amber-400 bg-amber-500/15 shadow-[0_0_10px_rgba(245,158,11,0.2)]' : 'text-stone-400 hover:text-amber-200 hover:bg-stone-800/40' }}">
                            Statystyki
                        </button>
                        <button wire:click="setTab('collections')" class="w-full text-center py-1.5 px-1 font-bold rounded-t-lg transition-all duration-200 medieval-font flex items-center justify-center {{ $activeTab === 'collections' ? 'text-amber-300 border-b-2 border-amber-400 bg-amber-500/15 shadow-[0_0_10px_rgba(245,158,11,0.2)]' : 'text-stone-400 hover:text-amber-200 hover:bg-stone-800/40' }}">
                            Kolekcje<span class="hidden xs:inline"> & Tytuły</span>
                        </button>
                        <button wire:click="setTab('skills')" class="w-full text-center py-1.5 px-1 font-bold rounded-t-lg transition-all duration-200 medieval-font flex items-center justify-center {{ $activeTab === 'skills' ? 'text-amber-300 border-b-2 border-amber-400 bg-amber-500/15 shadow-[0_0_10px_rgba(245,158,11,0.2)]' : 'text-stone-400 hover:text-amber-200 hover:bg-stone-800/40' }}">
                            Umiejętności
                        </button>
                        <button wire:click="setTab('mirror')" title="{{ $character->level < 30 ? 'Zablokowane (Poz. 30)' : '' }}" class="w-full text-center py-1.5 px-1 font-bold rounded-t-lg transition-all duration-200 medieval-font flex items-center justify-center relative {{ $character->level < 30 ? 'text-stone-600 opacity-60 grayscale cursor-not-allowed hover:bg-transparent' : ($activeTab === 'mirror' ? 'text-purple-300 border-b-2 border-purple-400 bg-purple-500/20 shadow-[0_0_10px_rgba(168,85,247,0.3)]' : 'text-stone-400 hover:text-purple-200 hover:bg-stone-800/40') }}">
                            @if($character->level < 30)
                                <i class="fa-solid fa-lock text-amber-500/60 mr-1 text-xs"></i> Lustro
                            @else
                                <i class="fa-solid fa-gem text-purple-400 mr-1 text-xs"></i> Lustro
                                @if($character->hasActiveMirror())
                                    <span class="absolute -top-1 -right-1 flex h-2.5 w-2.5">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-purple-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-purple-500"></span>
                                    </span>
                                @endif
                            @endif
                        </button>
                    </div>
                    @if($activeTab === 'attributes')
                        <div class="flex items-center gap-2 self-end sm:self-center">
                            @if($character->character_points > 0)
                                <span class="text-green-400 font-bold text-xs sm:text-sm animate-pulse bg-green-900/40 px-2.5 py-1 rounded-xl border border-green-700 whitespace-nowrap">Punkty: {{ $character->character_points }}</span>
                            @endif
                            <span wire:loading wire:target="addAttributePoints" class="flex items-center gap-1.5 text-[10px] sm:text-[11px] text-amber-300 font-semibold bg-stone-900/90 border border-amber-700/50 rounded-xl px-2 py-1 whitespace-nowrap">
                                <i class="fa-solid fa-spinner fa-spin"></i> Zapisywanie...
                            </span>
                        </div>
                    @endif
                </div>
                @if($activeTab === 'attributes')
                    <div class="mb-3 flex flex-wrap items-center justify-between gap-2 text-xs bg-amber-950/50 border border-amber-600/50 rounded-xl px-3 py-2 shadow-inner">
                        <div class="flex items-center gap-2 text-amber-300">
                            <i class="fa-solid fa-crosshairs text-amber-400 text-sm"></i>
                            <span>Typ broni: <strong class="text-amber-100 uppercase font-bold">{{ $activeWeaponType }}</strong></span>
                        </div>
                        <span class="text-amber-400/90 text-[11px] font-medium flex items-center gap-1 bg-amber-900/40 px-2 py-0.5 rounded border border-amber-700/50">
                            <i class="fa-solid fa-bolt text-yellow-400"></i> Atrybuty wpływające na atak są podświetlone
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 sm:gap-x-6 gap-y-3 sm:gap-y-4 text-xs sm:text-sm mt-3 sm:mt-4 relative"
                         wire:loading.class="opacity-60 pointer-events-none" wire:target="addAttributePoints">

                        <!-- STR -->
                        <div class="flex justify-between items-center group p-2 rounded-xl transition-all duration-200 {{ in_array('str', $activeScalingStats) ? 'bg-gradient-to-r from-amber-950/60 via-stone-900/60 to-amber-950/30 border border-amber-500/60 shadow-[0_0_12px_rgba(245,158,11,0.2)]' : 'border border-transparent' }}">
                            <div class="flex items-center gap-1.5">
                                <span class="font-medium cursor-help border-b border-dashed border-stone-600 {{ in_array('str', $activeScalingStats) ? 'text-amber-200 font-bold' : 'text-stone-300' }}" title="Siła: Zwiększa obrażenia broni typu Topór (x2), Miecz, Łuk i Dzwon.">Strength (STR):</span>
                                @if(in_array('str', $activeScalingStats))
                                    <span class="bg-amber-500/20 text-amber-300 border border-amber-500/60 px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider flex items-center gap-1 shadow-[0_0_8px_rgba(245,158,11,0.3)]">
                                        <i class="fa-solid fa-bolt text-yellow-400 text-[10px]"></i> Atak
                                    </span>
                                @endif
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="flex items-center gap-1">
                                    <span class="text-amber-300 font-bold text-base">{{ \App\Support\NumberHelper::formatShort($baseAttributes['str'] ?? 0) }}</span>
                                    <span class="font-bold text-xs sm:text-sm {{ ($bonusAttributes['str'] ?? 0) > 0 ? 'text-emerald-400' : 'text-stone-500/80' }}">(+{{ \App\Support\NumberHelper::formatShort($bonusAttributes['str'] ?? 0) }})</span>
                                </div>
                                @if($character->character_points > 0)
                                    <div class="flex gap-1">
                                        <button wire:click="addAttributePoints('str', 1)" wire:loading.attr="disabled" wire:target="addAttributePoints" class="w-6 h-6 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white rounded-lg text-xs flex items-center justify-center font-extrabold shadow active:scale-90 transition-transform duration-75 disabled:opacity-50" title="Dodaj 1">+1</button>
                                        @if($character->character_points >= 5)
                                            <button wire:click="addAttributePoints('str', 5)" wire:loading.attr="disabled" wire:target="addAttributePoints" class="w-6 h-6 bg-gradient-to-r from-amber-600 to-yellow-600 hover:from-amber-500 hover:to-yellow-500 text-stone-950 rounded-lg text-xs flex items-center justify-center font-extrabold shadow active:scale-90 transition-transform duration-75 disabled:opacity-50" title="Dodaj 5">+5</button>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- INT -->
                        <div class="flex justify-between items-center group p-2 rounded-xl transition-all duration-200 {{ in_array('int', $activeScalingStats) ? 'bg-gradient-to-r from-amber-950/60 via-stone-900/60 to-amber-950/30 border border-amber-500/60 shadow-[0_0_12px_rgba(245,158,11,0.2)]' : 'border border-transparent' }}">
                            <div class="flex items-center gap-1.5">
                                <span class="font-medium cursor-help border-b border-dashed border-stone-600 {{ in_array('int', $activeScalingStats) ? 'text-amber-200 font-bold' : 'text-stone-300' }}" title="Inteligencja: Zwiększa obrażenia broni typu Różdżka (x2) i Dzwon.">Intelligence (INT):</span>
                                @if(in_array('int', $activeScalingStats))
                                    <span class="bg-amber-500/20 text-amber-300 border border-amber-500/60 px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider flex items-center gap-1 shadow-[0_0_8px_rgba(245,158,11,0.3)]">
                                        <i class="fa-solid fa-bolt text-yellow-400 text-[10px]"></i> Atak
                                    </span>
                                @endif
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="flex items-center gap-1">
                                    <span class="text-amber-300 font-bold text-base">{{ \App\Support\NumberHelper::formatShort($baseAttributes['int'] ?? 0) }}</span>
                                    <span class="font-bold text-xs sm:text-sm {{ ($bonusAttributes['int'] ?? 0) > 0 ? 'text-emerald-400' : 'text-stone-500/80' }}">(+{{ \App\Support\NumberHelper::formatShort($bonusAttributes['int'] ?? 0) }})</span>
                                </div>
                                @if($character->character_points > 0)
                                    <div class="flex gap-1">
                                        <button wire:click="addAttributePoints('int', 1)" wire:loading.attr="disabled" wire:target="addAttributePoints" class="w-6 h-6 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white rounded-lg text-xs flex items-center justify-center font-extrabold shadow active:scale-90 transition-transform duration-75 disabled:opacity-50" title="Dodaj 1">+1</button>
                                        @if($character->character_points >= 5)
                                            <button wire:click="addAttributePoints('int', 5)" wire:loading.attr="disabled" wire:target="addAttributePoints" class="w-6 h-6 bg-gradient-to-r from-amber-600 to-yellow-600 hover:from-amber-500 hover:to-yellow-500 text-stone-950 rounded-lg text-xs flex items-center justify-center font-extrabold shadow active:scale-90 transition-transform duration-75 disabled:opacity-50" title="Dodaj 5">+5</button>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- VIT -->
                        <div class="flex justify-between items-center group p-2 rounded-xl transition-all duration-200 {{ in_array('vit', $activeScalingStats) ? 'bg-gradient-to-r from-amber-950/60 via-stone-900/60 to-amber-950/30 border border-amber-500/60 shadow-[0_0_12px_rgba(245,158,11,0.2)]' : 'border border-transparent' }}">
                            <div class="flex items-center gap-1.5">
                                <span class="font-medium cursor-help border-b border-dashed border-stone-600 {{ in_array('vit', $activeScalingStats) ? 'text-amber-200 font-bold' : 'text-stone-300' }}" title="Witalność: Zwiększa maksymalną ilość Punktów Życia (HP) oraz redukcję obrażeń.">Vitality (VIT):</span>
                                @if(in_array('vit', $activeScalingStats))
                                    <span class="bg-amber-500/20 text-amber-300 border border-amber-500/60 px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider flex items-center gap-1 shadow-[0_0_8px_rgba(245,158,11,0.3)]">
                                        <i class="fa-solid fa-bolt text-yellow-400 text-[10px]"></i> Atak
                                    </span>
                                @endif
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="flex items-center gap-1">
                                    <span class="text-amber-300 font-bold text-base">{{ \App\Support\NumberHelper::formatShort($baseAttributes['vit'] ?? 0) }}</span>
                                    <span class="font-bold text-xs sm:text-sm {{ ($bonusAttributes['vit'] ?? 0) > 0 ? 'text-emerald-400' : 'text-stone-500/80' }}">(+{{ \App\Support\NumberHelper::formatShort($bonusAttributes['vit'] ?? 0) }})</span>
                                </div>
                                @if($character->character_points > 0)
                                    <div class="flex gap-1">
                                        <button wire:click="addAttributePoints('vit', 1)" wire:loading.attr="disabled" wire:target="addAttributePoints" class="w-6 h-6 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white rounded-lg text-xs flex items-center justify-center font-extrabold shadow active:scale-90 transition-transform duration-75 disabled:opacity-50" title="Dodaj 1">+1</button>
                                        @if($character->character_points >= 5)
                                            <button wire:click="addAttributePoints('vit', 5)" wire:loading.attr="disabled" wire:target="addAttributePoints" class="w-6 h-6 bg-gradient-to-r from-amber-600 to-yellow-600 hover:from-amber-500 hover:to-yellow-500 text-stone-950 rounded-lg text-xs flex items-center justify-center font-extrabold shadow active:scale-90 transition-transform duration-75 disabled:opacity-50" title="Dodaj 5">+5</button>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- AGI -->
                        <div class="flex justify-between items-center group p-2 rounded-xl transition-all duration-200 {{ in_array('agi', $activeScalingStats) ? 'bg-gradient-to-r from-amber-950/60 via-stone-900/60 to-amber-950/30 border border-amber-500/60 shadow-[0_0_12px_rgba(245,158,11,0.2)]' : 'border border-transparent' }}">
                            <div class="flex items-center gap-1.5">
                                <span class="font-medium cursor-help border-b border-dashed border-stone-600 {{ in_array('agi', $activeScalingStats) ? 'text-amber-200 font-bold' : 'text-stone-300' }}" title="Zręczność: Zwiększa obrażenia broni typu Łuk, Miecz i Sztylet. Decyduje też o kolejności ataku i unikach.">Agility (AGI):</span>
                                @if(in_array('agi', $activeScalingStats))
                                    <span class="bg-amber-500/20 text-amber-300 border border-amber-500/60 px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider flex items-center gap-1 shadow-[0_0_8px_rgba(245,158,11,0.3)]">
                                        <i class="fa-solid fa-bolt text-yellow-400 text-[10px]"></i> Atak
                                    </span>
                                @endif
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="flex items-center gap-1">
                                    <span class="text-amber-300 font-bold text-base">{{ \App\Support\NumberHelper::formatShort($baseAttributes['agi'] ?? 0) }}</span>
                                    <span class="font-bold text-xs sm:text-sm {{ ($bonusAttributes['agi'] ?? 0) > 0 ? 'text-emerald-400' : 'text-stone-500/80' }}">(+{{ \App\Support\NumberHelper::formatShort($bonusAttributes['agi'] ?? 0) }})</span>
                                </div>
                                @if($character->character_points > 0)
                                    <div class="flex gap-1">
                                        <button wire:click="addAttributePoints('agi', 1)" wire:loading.attr="disabled" wire:target="addAttributePoints" class="w-6 h-6 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white rounded-lg text-xs flex items-center justify-center font-extrabold shadow active:scale-90 transition-transform duration-75 disabled:opacity-50" title="Dodaj 1">+1</button>
                                        @if($character->character_points >= 5)
                                            <button wire:click="addAttributePoints('agi', 5)" wire:loading.attr="disabled" wire:target="addAttributePoints" class="w-6 h-6 bg-gradient-to-r from-amber-600 to-yellow-600 hover:from-amber-500 hover:to-yellow-500 text-stone-950 rounded-lg text-xs flex items-center justify-center font-extrabold shadow active:scale-90 transition-transform duration-75 disabled:opacity-50" title="Dodaj 5">+5</button>
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
                            <span class="text-green-400 font-bold text-base">{{ \App\Support\NumberHelper::formatShort($derivedStats['max_hp']) }}</span>
                        </div>
                        <div class="flex justify-between items-center pb-2 border-b border-gray-700/50">
                            <span class="text-gray-400 font-semibold cursor-help border-b border-dashed border-gray-600" title="Maksymalna pula many używana do rzucania umiejętności bojowych.">Max Mana:</span>
                            <span class="text-cyan-400 font-bold text-base">{{ \App\Support\NumberHelper::formatShort($derivedStats['max_mana']) }}</span>
                        </div>
                        <div class="flex justify-between items-center pb-2 border-b border-gray-700/50">
                            <span class="text-gray-400 font-semibold cursor-help border-b border-dashed border-gray-600" title="Obrażenia fizyczne ataków podstawowych i umiejętności fizycznych. Zależą od Siły (STR), Zręczności (AGI) oraz fizycznej siły ataku broni.">Base Damage (Fizyczne):</span>
                            <span class="text-red-400 font-bold text-base">{{ \App\Support\NumberHelper::formatShort($derivedStats['base_damage_min']) }} - {{ \App\Support\NumberHelper::formatShort($derivedStats['base_damage_max']) }}</span>
                        </div>
                        <div class="flex justify-between items-center pb-2 border-b border-gray-700/50">
                            <span class="text-gray-400 font-semibold cursor-help border-b border-dashed border-gray-600" title="Obrażenia magiczne czarów i umiejętności magicznych (Różdżka, Dzwon). Zależą od Inteligencji (INT) oraz magicznej siły ataku broni.">Magic Damage (Magiczne):</span>
                            <span class="text-purple-400 font-bold text-base">{{ \App\Support\NumberHelper::formatShort($derivedStats['magic_damage_min']) }} - {{ \App\Support\NumberHelper::formatShort($derivedStats['magic_damage_max']) }}</span>
                        </div>
                        <div class="flex justify-between items-center pb-2 border-b border-gray-700/50">
                            <span class="text-gray-400 font-semibold cursor-help border-b border-dashed border-gray-600" title="Redukuje nadchodzące obrażenia w walce.">Damage Reduction:</span>
                            <span class="text-blue-400 font-bold text-base">{{ \App\Support\NumberHelper::formatShort($derivedStats['damage_reduction']) }}</span>
                        </div>
                        <div class="flex justify-between items-center pb-2 border-b border-gray-700/50 group relative">
                            <span class="text-gray-400 font-semibold cursor-help border-b border-dashed border-gray-600" title="Szansa na zadanie ataku krytycznego (1.5x obrażeń). Zależy od Zręczności (AGI) oraz założonych przedmiotów. Pamiętaj: im więcej AGI ma przeciwnik, tym Twoja szansa na kryt i unik maleje.">Crit Chance:</span>
                            <span class="text-yellow-500 font-bold text-base">{{ $derivedStats['crit_chance'] }}%</span>
                        </div>
                        <div class="flex justify-between items-center pb-2 border-b border-gray-700/50 group relative">
                            <span class="text-gray-400 font-semibold cursor-help border-b border-dashed border-gray-600" title="Szansa na całkowite uniknięcie ciosu przeciwnika. Zależy od Zręczności (AGI) oraz wyposażenia. Pamiętaj: im więcej AGI posiada przeciwnik, tym Twoja efektywna szansa na unik w walce się zmniejsza.">Dodge Chance:</span>
                            <span class="text-emerald-400 font-bold text-base">{{ $derivedStats['dodge_chance'] }}%</span>
                        </div>
                        <div class="col-span-1 md:col-span-2 mt-2 p-2.5 bg-amber-950/40 border border-amber-800/40 rounded-xl text-xs text-amber-200/90 flex items-center gap-2">
                            <i class="fa-solid fa-circle-info text-amber-400 text-sm shrink-0"></i>
                            <span>Szansa na <strong>trafienie krytyczne</strong> oraz <strong>unik</strong> zależy od Twojej Zręczności (AGI) oraz bonifikaty z wyposażenia. W walce z przeciwnikiem wyższa Zręczność przeciwnika redukuje Twoje efektywne szanse na unik oraz krytyka.</span>
                        </div>

                        @php
                            // Pełna lista modyfikatorów bojowych z zaklęć ekwipunku (otrucie/
                            // ogłuszenie, odporności, "silny przeciwko" rasie/bohaterom) -
                            // pokazywana ZAWSZE (jak Crit/Dodge Chance wyżej), z wartością 0%
                            // gdy postać nie ma jeszcze danego zaklęcia. Etykiety scentralizowane
                            // w EnchantmentStrategy::bonusLabel().
                            $combatModifierList = [
                                'poison_chance', 'stun_chance', 'resist_poison', 'resist_stun',
                                'strong_vs_demons', 'strong_vs_undead', 'strong_vs_animals', 'strong_vs_orcs', 'strong_vs_hero',
                                'resist_demons', 'resist_undead', 'resist_animals', 'resist_orcs',
                            ];
                            $economyModifierList = ['double_exp_chance', 'double_gold_chance', 'double_drop_chance'];
                        @endphp
                        <div class="col-span-1 md:col-span-2 mt-2">
                            <div class="text-gray-400 font-semibold text-xs uppercase tracking-wide mb-2">Modyfikatory Bojowe (z Ekwipunku)</div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                @foreach($combatModifierList as $key)
                                    @php $value = $eqStats[$key] ?? 0; @endphp
                                    <div class="flex justify-between items-center bg-stone-900/60 border border-stone-700/50 rounded-lg px-3 py-1.5">
                                        <span class="text-gray-300 text-xs">{{ \App\Domain\Wizard\EnchantmentStrategy::bonusLabel($key) }}</span>
                                        <span class="font-bold text-sm {{ $value > 0 ? 'text-amber-300' : 'text-stone-500/80' }}">{{ $value }}%</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="col-span-1 md:col-span-2 mt-2">
                            <div class="text-gray-400 font-semibold text-xs uppercase tracking-wide mb-2">Bonusy Ekonomiczne (z Ekwipunku)</div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                @foreach($economyModifierList as $key)
                                    @php $value = $eqStats[$key] ?? 0; @endphp
                                    <div class="flex justify-between items-center bg-stone-900/60 border border-stone-700/50 rounded-lg px-3 py-1.5">
                                        <span class="text-gray-300 text-xs">{{ \App\Domain\Wizard\EnchantmentStrategy::bonusLabel($key) }}</span>
                                        <span class="font-bold text-sm {{ $value > 0 ? 'text-emerald-300' : 'text-stone-500/80' }}">{{ $value }}%</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @elseif($activeTab === 'collections')
                    <div class="mt-4">
                        @livewire('profile.collections-tab', ['character' => $character])
                    </div>
                @elseif($activeTab === 'skills')
                    <div class="mt-4">
                        @livewire('profile.skills-tab', ['character' => $character])
                    </div>
                @elseif($activeTab === 'mirror')
                    <div class="mt-4 bg-stone-950 border-2 border-purple-900/80 rounded-2xl p-4 sm:p-5 shadow-xl relative overflow-hidden">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-purple-900/60 pb-3 mb-4 gap-2">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-purple-950 border border-purple-600/80 flex items-center justify-center shrink-0">
                                    <i class="fa-solid fa-wand-magic-sparkles text-purple-300 text-lg"></i>
                                </div>
                                <div>
                                    <h3 class="text-base sm:text-lg font-bold text-purple-200 medieval-font">Magiczne Lustro (Multitasking)</h3>
                                    <p class="text-[11px] text-purple-300/70">Przygoda toczy się w tle! W tym czasie możesz walczyć z bossami, ulepszać eq i bić dungeony.</p>
                                </div>
                            </div>

                            @if($activeMirrorSession)
                                <span class="px-3 py-1 bg-purple-950 border border-purple-400 text-purple-300 text-[11px] font-bold rounded-full flex items-center gap-1.5 self-start sm:self-auto">
                                    <i class="fa-solid fa-arrows-rotate fa-spin"></i> LUSTRO W TOKU
                                </span>
                            @endif
                        </div>

                        @if($claimedRewardsSummary)
                            <div class="mb-5 p-4 rounded-xl bg-stone-900 border-2 border-emerald-600/80 shadow-md relative">
                                <button wire:click="closeRewardsSummary" class="absolute top-2 right-2 text-stone-400 hover:text-white font-bold text-lg cursor-pointer">✕</button>
                                <h4 class="text-sm sm:text-base font-bold text-emerald-300 medieval-font mb-2 flex items-center gap-2">
                                    <i class="fa-solid fa-gift text-yellow-400"></i> Nagrody z Lustra Zostały Przyznane!
                                </h4>
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 my-3">
                                    <div class="bg-stone-950 border border-emerald-800/60 p-2.5 rounded-lg flex items-center gap-2">
                                        <i class="fa-solid fa-bolt text-yellow-400 text-lg"></i>
                                        <div>
                                            <span class="block text-[10px] text-stone-400 uppercase font-bold">Doświadczenie:</span>
                                            <span class="text-xs sm:text-sm font-extrabold text-amber-300">+{{ number_format($claimedRewardsSummary['xp']) }} XP</span>
                                        </div>
                                    </div>
                                    <div class="bg-stone-950 border border-emerald-800/60 p-2.5 rounded-lg flex items-center gap-2">
                                        <i class="fa-solid fa-coins text-yellow-400 text-lg"></i>
                                        <div>
                                            <span class="block text-[10px] text-stone-400 uppercase font-bold">Złoto:</span>
                                            <span class="text-xs sm:text-sm font-extrabold text-yellow-300">+{{ number_format($claimedRewardsSummary['gold']) }}</span>
                                        </div>
                                    </div>
                                    <div class="bg-stone-950 border border-emerald-800/60 p-2.5 rounded-lg flex items-center gap-2 col-span-2 sm:col-span-1">
                                        <i class="fa-solid fa-clock text-cyan-400 text-lg"></i>
                                        <div>
                                            <span class="block text-[10px] text-stone-400 uppercase font-bold">Czas trwania:</span>
                                            <span class="text-xs sm:text-sm font-extrabold text-cyan-200">{{ $claimedRewardsSummary['elapsed_minutes'] }} min</span>
                                        </div>
                                    </div>
                                </div>

                                @if(!empty($claimedRewardsSummary['materials']))
                                    <div class="mt-2">
                                        <span class="block text-xs font-bold text-emerald-200 mb-1">Zdobyte materiały:</span>
                                        <div class="flex flex-wrap gap-2">
                                            @foreach($claimedRewardsSummary['materials'] as $mat)
                                                <div class="px-2.5 py-1 bg-stone-950 border border-amber-500/50 rounded-lg flex items-center gap-2 text-xs font-bold text-amber-200">
                                                    <i class="fa-solid fa-cube text-amber-400"></i>
                                                    <span>{{ $mat['name'] }}</span>
                                                    <span class="text-emerald-400">x{{ $mat['quantity'] }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif

                        @if($activeMirrorSession)
                            @php
                                $rewards = $mirrorRewardsPreview ?? $activeMirrorSession->calculateCurrentRewards();
                                $remainingSec = $activeMirrorSession->getRemainingSeconds();
                            @endphp
                            <div x-data="{
                                    remainingSec: {{ $remainingSec }},
                                    get timerFormatted() {
                                        if (this.remainingSec <= 0) return '00:00:00';
                                        let h = Math.floor(this.remainingSec / 3600);
                                        let m = Math.floor((this.remainingSec % 3600) / 60);
                                        let s = this.remainingSec % 60;
                                        return String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
                                    },
                                    init() {
                                        setInterval(() => {
                                            if (this.remainingSec > 0) this.remainingSec--;
                                        }, 1000);
                                    }
                                 }"
                                 wire:poll.10s
                                 class="space-y-4">
                                <div class="bg-stone-900 border border-purple-800/80 rounded-xl p-3.5 flex flex-col md:flex-row items-center justify-between gap-4">
                                    <div class="flex items-center gap-3 w-full md:w-auto">
                                        <div class="w-12 h-12 rounded-xl bg-purple-950 border-2 border-purple-500 flex items-center justify-center shrink-0 shadow-md">
                                            <i class="fa-solid fa-map-location-dot text-purple-300 text-xl"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <span class="text-[10px] text-purple-300 uppercase tracking-widest font-bold block">Mapa w tle:</span>
                                            <h4 class="text-base font-bold text-amber-300 medieval-font truncate">{{ $activeMirrorSession->map ? $activeMirrorSession->map->name : 'Przygoda' }}</h4>
                                            <p class="text-[11px] text-stone-400">Planowany czas: <strong>{{ $activeMirrorSession->duration_hours }}h</strong> (Stawka 60%)</p>
                                        </div>
                                    </div>

                                    <div class="text-center md:text-right shrink-0">
                                        <span class="text-[10px] text-stone-400 uppercase font-bold block">Pozostało do końca:</span>
                                        <div class="text-xl sm:text-2xl font-black text-purple-300 medieval-font tracking-wider" x-text="timerFormatted">
                                            00:00:00
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                                    <div class="bg-stone-900 border border-purple-900/60 p-3 rounded-xl flex items-center gap-3">
                                        <i class="fa-solid fa-bolt text-yellow-400 text-xl drop-shadow"></i>
                                        <div>
                                            <span class="text-[10px] text-stone-400 uppercase font-bold block">Naliczone EXP:</span>
                                            <span class="text-sm font-extrabold text-amber-300">+{{ number_format($rewards['xp']) }}</span>
                                        </div>
                                    </div>
                                    <div class="bg-stone-900 border border-purple-900/60 p-3 rounded-xl flex items-center gap-3">
                                        <i class="fa-solid fa-coins text-yellow-400 text-xl drop-shadow"></i>
                                        <div>
                                            <span class="text-[10px] text-stone-400 uppercase font-bold block">Naliczone Złoto:</span>
                                            <span class="text-sm font-extrabold text-yellow-300">+{{ number_format($rewards['gold']) }}</span>
                                        </div>
                                    </div>
                                    <div class="bg-stone-900 border border-purple-900/60 p-3 rounded-xl flex items-center gap-3">
                                        <i class="fa-solid fa-cubes text-amber-400 text-xl drop-shadow"></i>
                                        <div>
                                            <span class="text-[10px] text-stone-400 uppercase font-bold block">Przechowywane materiały:</span>
                                            <span class="text-sm font-extrabold text-amber-200">{{ count($rewards['materials']) }} rodzajów</span>
                                        </div>
                                    </div>
                                </div>

                                {{-- Mały plecak na wyleciałe materiały --}}
                                <div class="bg-stone-900 border border-purple-900/70 rounded-xl p-3">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-xs font-bold text-amber-300 flex items-center gap-1.5 medieval-font uppercase tracking-wider">
                                            <i class="fa-solid fa-bag-shopping text-amber-400"></i> Zgromadzone Łupy (Materiały z Mapy)
                                        </span>
                                        <span class="text-[10px] text-stone-400 font-medium">Przedmioty: {{ count($rewards['materials']) }}</span>
                                    </div>

                                    @if(empty($rewards['materials']))
                                        <div class="p-3 bg-stone-950/80 rounded-lg border border-stone-800 text-center">
                                            <p class="text-xs text-stone-400">Przygoda w toku... Materiały rzemieślnicze z mapy wypadają okresowo (co ~15 min).</p>
                                            <div class="grid grid-cols-6 gap-2 mt-2">
                                                @for($i = 0; $i < 6; $i++)
                                                    <div class="h-10 border border-dashed border-stone-800 rounded-lg flex items-center justify-center bg-stone-950/40">
                                                        <i class="fa-solid fa-box-open text-stone-700 text-xs"></i>
                                                    </div>
                                                @endfor
                                            </div>
                                        </div>
                                    @else
                                        <div class="grid grid-cols-3 sm:grid-cols-6 gap-2 bg-stone-950/80 p-2 rounded-lg border border-stone-800">
                                            @foreach($rewards['materials'] as $matId => $matInfo)
                                                @php $mat = $matInfo['item_template']; $qty = $matInfo['quantity']; @endphp
                                                <div class="bg-stone-900 border border-amber-500/50 rounded-lg p-2 flex flex-col items-center justify-center relative group" title="{{ $mat->name }}">
                                                    @if($mat->icon)
                                                        <img src="{{ route('assets.items', ['filename' => $mat->icon]) }}" class="w-8 h-8 object-contain" alt="{{ $mat->name }}" />
                                                    @else
                                                        <i class="fa-solid fa-cube text-amber-400 text-lg"></i>
                                                    @endif
                                                    <span class="text-[10px] font-bold text-amber-200 truncate w-full text-center mt-1">{{ $mat->name }}</span>
                                                    <span class="absolute -top-1 -right-1 bg-amber-600 text-stone-950 font-black text-[9px] px-1.5 rounded-full shadow">x{{ $qty }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>

                                <div class="pt-2">
                                    <button wire:click="stopMirror"
                                            wire:loading.attr="disabled"
                                            wire:target="stopMirror"
                                            class="w-full py-3 bg-red-800 hover:bg-red-700 text-white font-black text-xs sm:text-sm uppercase tracking-wider rounded-xl border border-red-500 shadow-md transition-all duration-200 flex items-center justify-center gap-2 cursor-pointer">
                                        <span wire:loading.remove wire:target="stopMirror" class="flex items-center gap-2">
                                            <i class="fa-solid fa-hand-paper text-base"></i> PRZERWIJ LUSTRO I ODBIERZ NAGRODY
                                        </span>
                                        <span wire:loading wire:target="stopMirror" class="flex items-center gap-2">
                                            <i class="fa-solid fa-spinner animate-spin text-base"></i> Naliczanie nagród...
                                        </span>
                                    </button>
                                </div>
                            </div>
                        @elseif(!$character->hasMirrorAccess())
                            <div class="p-6 rounded-xl bg-stone-900 border-2 border-purple-900/80 text-center space-y-3">
                                <i class="fa-solid fa-lock text-3xl text-purple-500/60"></i>
                                <p class="text-sm text-purple-200 font-bold">Nie posiadasz dostępu do Lustra.</p>
                                <p class="text-xs text-stone-400">Kup dostęp na 7 dni u Wiedźmy, aby móc uruchamiać sesje Lustra.</p>
                                <a href="{{ route('city.witch', $character->id) }}" wire:navigate
                                   class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-purple-800 hover:bg-purple-700 border border-purple-500 text-purple-100 text-xs font-bold uppercase tracking-wider shadow-md">
                                    <i class="fa-solid fa-mortar-pestle"></i> Idź do Wiedźmy
                                </a>
                            </div>
                        @else
                            <div class="space-y-5">
                                {{-- Wybór mapy za pomocą Selectbox --}}
                                <div>
                                    <label class="block text-xs font-bold text-purple-300 uppercase tracking-wider mb-2">1. Wybierz Mapę do Przygody w Tle:</label>
                                    <div class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-center">
                                        <select wire:model.live="selectedMirrorMapId"
                                                class="w-full sm:w-2/3 bg-stone-900 border-2 border-purple-600/80 rounded-xl px-3.5 py-2.5 text-amber-100 text-xs sm:text-sm font-semibold focus:outline-none focus:border-purple-400 shadow-inner">
                                            @foreach($mirrorMaps as $m)
                                                @php
                                                    $isAccessible = $m->isAccessibleBy($character);
                                                    $rates = $mapRates[$m->id] ?? ['exp_per_minute' => 0, 'gold_per_minute' => 0, 'has_record' => false];
                                                @endphp
                                                <option value="{{ $m->id }}" @if(!$isAccessible) disabled @endif class="bg-stone-900 text-amber-100 py-1">
                                                    {{ $m->name }} (Poz. {{ $m->level_range }})
                                                    @if($rates['has_record'])
                                                        — ⚡ {{ number_format($rates['exp_per_minute']) }}/min | 💰 {{ number_format($rates['gold_per_minute']) }}/min
                                                    @else
                                                        — ⚠️ 0/min (Brak walki)
                                                    @endif
                                                    @if(!$isAccessible) [Wymagany wyższy poziom] @endif
                                                </option>
                                            @endforeach
                                        </select>

                                        @php
                                            $activeMapObj = $mirrorMaps->firstWhere('id', (int)$selectedMirrorMapId) ?? $mirrorMaps->first();
                                            $activeRates = $mapRates[$activeMapObj->id ?? 0] ?? ['exp_per_minute' => 0, 'gold_per_minute' => 0, 'has_record' => false];
                                        @endphp

                                        <div class="flex-1 bg-stone-900 border border-purple-800/80 rounded-xl p-2.5 flex items-center justify-around text-xs">
                                            <div class="text-amber-300 font-bold flex items-center gap-1.5" title="Twój zarejestrowany wynik XP/min">
                                                <i class="fa-solid fa-bolt text-yellow-400 text-sm"></i>
                                                <span>{{ number_format($activeRates['exp_per_minute']) }}/min</span>
                                            </div>
                                            <div class="text-yellow-300 font-bold flex items-center gap-1.5" title="Twój zarejestrowany wynik Złota/min">
                                                <i class="fa-solid fa-coins text-yellow-400 text-sm"></i>
                                                <span>{{ number_format($activeRates['gold_per_minute']) }}/min</span>
                                            </div>
                                        </div>
                                    </div>

                                    @if(!$activeRates['has_record'])
                                        <div class="mt-2.5 p-3 rounded-xl bg-amber-950/70 border border-amber-500/50 text-amber-200 text-xs flex items-center gap-2.5">
                                            <i class="fa-solid fa-triangle-exclamation text-amber-400 text-lg shrink-0"></i>
                                            <div>
                                                <strong class="font-bold block text-amber-300">Brak zarejestrowanych wyników walki!</strong>
                                                <span>Nie stoczyłeś jeszcze walki na mapie "<strong>{{ $activeMapObj->name ?? 'Wybranej' }}</strong>". Wyrusz na tę mapę w przygodzie i stocz przynajmniej 1 walkę, aby zapisać swoją prędkość zdobywania EXP i Złota.</span>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                {{-- Wybór czasu trwania (Suwak + Przyciski) --}}
                                @php
                                    $isVip = auth()->user()->hasPremium();
                                    $maxHoursAllowed = $isVip ? 10 : 6;
                                @endphp
                                <div class="bg-stone-900 border border-purple-900/80 p-3.5 rounded-xl">
                                    <div class="flex items-center justify-between mb-2">
                                        <label class="block text-xs font-bold text-purple-300 uppercase tracking-wider">2. Czas trwania Lustra:</label>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-black text-purple-200 bg-purple-950 border border-purple-400/80 px-3 py-1 rounded-xl shadow-sm">
                                                {{ $selectedMirrorDurationHours }} {{ $selectedMirrorDurationHours === 1 ? 'godzina' : ($selectedMirrorDurationHours < 5 ? 'godziny' : 'godzin') }}
                                            </span>
                                            @if($isVip)
                                                <span class="text-[10px] text-amber-400 font-bold flex items-center gap-1 bg-amber-950 px-2 py-0.5 rounded border border-amber-500/50"><i class="fa-solid fa-crown text-yellow-400"></i> VIP (do 10h)</span>
                                            @else
                                                <span class="text-[10px] text-stone-400 font-medium">Bez VIP max 6h</span>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Range Slider --}}
                                    <div class="my-3 px-1">
                                        <input type="range" min="1" max="{{ $maxHoursAllowed }}" step="1"
                                               wire:model.live="selectedMirrorDurationHours"
                                               class="w-full h-3 bg-stone-950 rounded-lg appearance-none cursor-pointer accent-purple-500 border border-purple-500/40 shadow-inner">
                                        <div class="flex justify-between text-[10px] font-bold text-stone-500 px-1 mt-1">
                                            <span>1h</span>
                                            <span>3h</span>
                                            <span>6h</span>
                                            @if($isVip)
                                                <span>10h</span>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Preset buttons --}}
                                    <div class="flex flex-wrap gap-1.5 mt-2">
                                        @for($h = 1; $h <= 10; $h++)
                                            @php
                                                $lockedForUser = $h > $maxHoursAllowed;
                                                $isHSelected = (int)$selectedMirrorDurationHours === $h;
                                            @endphp
                                            <button type="button"
                                                    @if($lockedForUser) disabled title="Wymagany status VIP dla czasu > 6 godzin" @else wire:click="selectMirrorDuration({{ $h }})" @endif
                                                    class="px-2.5 py-1 rounded-lg border font-black text-xs transition-all flex items-center gap-1 cursor-pointer
                                                           {{ $lockedForUser ? 'border-stone-800 bg-stone-950 text-stone-600 cursor-not-allowed' : ($isHSelected ? 'border-purple-400 bg-purple-600 text-white shadow-md scale-105' : 'border-stone-800 bg-stone-950 text-stone-300 hover:border-purple-500 hover:text-white') }}">
                                                <span>{{ $h }}h</span>
                                                @if($lockedForUser)
                                                    <i class="fa-solid fa-lock text-[9px] text-amber-500"></i>
                                                @endif
                                            </button>
                                        @endfor
                                    </div>
                                </div>

                                @php
                                    $activeMapObj = $mirrorMaps->firstWhere('id', (int)$selectedMirrorMapId) ?? $mirrorMaps->first();
                                    $selectedRates = $mapRates[$activeMapObj->id ?? 0] ?? ['exp_per_minute' => 0, 'gold_per_minute' => 0, 'has_record' => false];
                                    $totalMins = $selectedMirrorDurationHours * 60;
                                    $estExp = (int) floor($totalMins * $selectedRates['exp_per_minute'] * 0.40);
                                    $estGold = (int) floor($totalMins * $selectedRates['gold_per_minute'] * 0.40);
                                @endphp
                                <div class="bg-stone-900 border border-purple-600/80 rounded-xl p-3.5 flex flex-col sm:flex-row items-center justify-between gap-3">
                                    <div>
                                        <span class="text-[11px] font-bold text-purple-300 uppercase tracking-wider block">Prognozowany plon z Lustra:</span>
                                        <div class="flex items-center gap-3 mt-1">
                                            <span class="text-xs sm:text-sm font-extrabold text-amber-300"><i class="fa-solid fa-bolt text-yellow-400"></i> ~{{ number_format($estExp) }} XP</span>
                                            <span class="text-xs sm:text-sm font-extrabold text-yellow-300"><i class="fa-solid fa-coins text-yellow-400"></i> ~{{ number_format($estGold) }} Złota</span>
                                            <span class="text-xs font-semibold text-amber-200"><i class="fa-solid fa-cubes text-amber-400"></i> + Materiały</span>
                                        </div>
                                    </div>

                                    <button wire:click="startMirror"
                                            @if(!$selectedRates['has_record']) disabled title="Musisz najpierw stoczyć min. 1 walkę na tej mapie!" @endif
                                            wire:loading.attr="disabled"
                                            wire:target="startMirror"
                                            class="w-full sm:w-auto px-6 py-2.5 rounded-xl border text-white font-black text-xs uppercase tracking-wider transition-all duration-200 flex items-center justify-center gap-2
                                                   {{ !$selectedRates['has_record'] ? 'bg-stone-800 border-stone-700 text-stone-500 cursor-not-allowed opacity-60' : 'bg-purple-800 hover:bg-purple-700 border-purple-500 shadow-md cursor-pointer' }}">
                                        <span wire:loading.remove wire:target="startMirror" class="flex items-center gap-2">
                                            <i class="fa-solid fa-play"></i> URUCHOM LUSTRO
                                        </span>
                                        <span wire:loading wire:target="startMirror" class="flex items-center gap-2">
                                            <i class="fa-solid fa-spinner animate-spin"></i> Uruchamianie...
                                        </span>
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <!-- Right Side: Inventory -->
        <div class="bg-gradient-to-b from-stone-900/95 via-stone-900/90 to-stone-950/95 border-2 border-amber-700/60 rounded-2xl sm:rounded-3xl shadow-2xl backdrop-blur-md p-3 xs:p-4 sm:p-6 flex flex-col h-full relative overflow-visible">
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-amber-500/5 via-transparent to-transparent pointer-events-none"></div>

            <div class="flex flex-col xs:flex-row justify-between items-start xs:items-center gap-2 mb-3 border-b border-amber-900/40 pb-2.5 relative z-10">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-box-archive text-amber-400 text-lg sm:text-xl"></i>
                    <h2 class="text-xl sm:text-2xl font-bold text-amber-300 medieval-font drop-shadow-md">Ekwipunek</h2>
                </div>
                <div class="text-amber-300 font-bold flex gap-2 sm:gap-3 text-xs sm:text-sm">
                    <div class="bg-stone-950/90 border border-amber-800/60 px-2 sm:px-3 py-1 rounded-xl shadow-inner flex items-center gap-1.5">
                        <i class="fa-solid fa-coins text-yellow-400 drop-shadow"></i>
                        <span>{{ number_format($character->gold) }}</span>
                    </div>
                    <div class="bg-stone-950/90 border border-cyan-800/60 px-2 sm:px-3 py-1 rounded-xl shadow-inner flex items-center gap-1.5 text-cyan-300">
                        <i class="fa-solid fa-gem text-cyan-400 drop-shadow"></i>
                        <span>{{ number_format(auth()->user()->gems) }}</span>
                    </div>
                </div>
            </div>

            {{-- Plecak vs Magazyn vs Materiały Sub-Tabs --}}
            <div class="flex border-b border-amber-900/50 mb-2 relative z-10 text-xs sm:text-sm font-bold flex-wrap">
                <button wire:click="setInventoryTab('backpack')" class="py-1.5 px-3 font-bold rounded-t-lg transition-all medieval-font flex items-center gap-1.5 {{ $inventoryTab === 'backpack' ? 'text-amber-300 border-b-2 border-amber-400 bg-amber-500/20 shadow' : 'text-stone-400 hover:text-amber-200 hover:bg-stone-800/40' }}">
                    <i class="fa-solid fa-bag-shopping"></i> Plecak ({{ $backpackCount }}/{{ $backpackCapacity }}{{ auth()->user()->hasPremium() ? ' VIP' : '' }})
                </button>
                <button wire:click="setInventoryTab('stash')" class="py-1.5 px-3 font-bold rounded-t-lg transition-all medieval-font flex items-center gap-1.5 {{ $inventoryTab === 'stash' ? 'text-amber-300 border-b-2 border-amber-400 bg-amber-500/20 shadow' : 'text-stone-400 hover:text-amber-200 hover:bg-stone-800/40' }}">
                    <i class="fa-solid fa-vault"></i> Magazyn ({{ count($playerStashItems) }}/{{ $stashCapacity }})
                </button>
                <button wire:click="setInventoryTab('materials')" class="py-1.5 px-3 font-bold rounded-t-lg transition-all medieval-font flex items-center gap-1.5 {{ $inventoryTab === 'materials' ? 'text-amber-300 border-b-2 border-amber-400 bg-amber-500/20 shadow' : 'text-stone-400 hover:text-amber-200 hover:bg-stone-800/40' }}">
                    <i class="fa-solid fa-cubes"></i> Materiały ({{ $materialStashCount }}/{{ $materialStashCapacity }})
                </button>
            </div>

            <div class="flex flex-col gap-2.5 mb-3 relative z-10">
                {{-- Category Filters --}}
                <div class="flex flex-wrap gap-1 xs:gap-1.5 items-center">
                    <button wire:click="setInventoryFilter('all')" class="px-2 xs:px-3 py-1 text-[10px] xs:text-xs rounded-lg font-bold transition-all duration-200 medieval-font {{ $inventoryFilter === 'all' ? 'bg-gradient-to-r from-amber-600 to-yellow-600 text-stone-950 shadow-[0_0_10px_rgba(245,158,11,0.4)]' : 'bg-stone-950/70 border border-amber-900/40 text-stone-300 hover:bg-stone-800 hover:text-amber-300' }}">Wszystko</button>
                    <button wire:click="setInventoryFilter('weapon')" class="px-2 xs:px-3 py-1 text-[10px] xs:text-xs rounded-lg font-bold transition-all duration-200 medieval-font {{ $inventoryFilter === 'weapon' ? 'bg-gradient-to-r from-amber-600 to-yellow-600 text-stone-950 shadow-[0_0_10px_rgba(245,158,11,0.4)]' : 'bg-stone-950/70 border border-amber-900/40 text-stone-300 hover:bg-stone-800 hover:text-amber-300' }}">Bronie</button>
                    <button wire:click="setInventoryFilter('armor')" class="px-2 xs:px-3 py-1 text-[10px] xs:text-xs rounded-lg font-bold transition-all duration-200 medieval-font {{ $inventoryFilter === 'armor' ? 'bg-gradient-to-r from-amber-600 to-yellow-600 text-stone-950 shadow-[0_0_10px_rgba(245,158,11,0.4)]' : 'bg-stone-950/70 border border-amber-900/40 text-stone-300 hover:bg-stone-800 hover:text-amber-300' }}">Pancerz</button>
                    <button wire:click="setInventoryFilter('accessory')" class="px-2 xs:px-3 py-1 text-[10px] xs:text-xs rounded-lg font-bold transition-all duration-200 medieval-font {{ $inventoryFilter === 'accessory' ? 'bg-gradient-to-r from-amber-600 to-yellow-600 text-stone-950 shadow-[0_0_10px_rgba(245,158,11,0.4)]' : 'bg-stone-950/70 border border-amber-900/40 text-stone-300 hover:bg-stone-800 hover:text-amber-300' }}">Akcesoria</button>
                    <button wire:click="setInventoryFilter('material')" class="px-2 xs:px-3 py-1 text-[10px] xs:text-xs rounded-lg font-bold transition-all duration-200 medieval-font {{ $inventoryFilter === 'material' ? 'bg-gradient-to-r from-amber-600 to-yellow-600 text-stone-950 shadow-[0_0_10px_rgba(245,158,11,0.4)]' : 'bg-stone-950/70 border border-amber-900/40 text-stone-300 hover:bg-stone-800 hover:text-amber-300' }}">Materiały</button>
                    <button wire:click="setInventoryFilter('consumable')" class="px-2 xs:px-3 py-1 text-[10px] xs:text-xs rounded-lg font-bold transition-all duration-200 medieval-font {{ $inventoryFilter === 'consumable' ? 'bg-gradient-to-r from-amber-600 to-yellow-600 text-stone-950 shadow-[0_0_10px_rgba(245,158,11,0.4)]' : 'bg-stone-950/70 border border-amber-900/40 text-stone-300 hover:bg-stone-800 hover:text-amber-300' }}">Mikstury</button>
                </div>

                {{-- Action Row --}}
                <div class="flex justify-between items-center pt-1">
                    @if($inventoryTab === 'backpack')
                        <span class="text-stone-400 text-xs font-medium">Miejsca w plecaku: <strong class="text-amber-300">{{ $backpackCount }} / {{ $backpackCapacity }}</strong> @if(auth()->user()->hasPremium()) <span class="bg-amber-500/20 text-amber-300 text-[10px] px-1.5 py-0.5 rounded border border-amber-500/40 font-bold ml-1">VIP</span> @endif</span>
                        <div class="flex items-center gap-1.5">
                            <button wire:click="sortInventory" class="px-2.5 py-1 xs:px-3 xs:py-1.5 text-xs rounded-xl bg-gradient-to-r from-amber-700 to-yellow-700 hover:from-amber-600 hover:to-yellow-600 text-amber-100 font-bold flex items-center gap-1.5 shadow transition-all duration-200 border border-amber-500/40 transform active:scale-95" title="Sortuj przedmioty w plecaku (kategoriami od najmocniejszego do najsłabszego)">
                                <i class="fa-solid fa-arrow-down-wide-short"></i>
                                Sortuj plecak
                            </button>
                            <button wire:click="stackItems" class="px-2.5 py-1 xs:px-3 xs:py-1.5 text-xs rounded-xl bg-gradient-to-r from-emerald-700 to-teal-700 hover:from-emerald-600 hover:to-teal-600 text-emerald-100 font-bold flex items-center gap-1.5 shadow transition-all duration-200 border border-emerald-500/40 transform active:scale-95" title="Połącz powtarzające się materiały">
                                <i class="fa-solid fa-layer-group"></i>
                                Połącz przedmioty
                            </button>
                            <button wire:click="toggleBulkStashMode" class="px-2.5 py-1 xs:px-3 xs:py-1.5 text-xs rounded-xl font-bold flex items-center gap-1.5 shadow transition-all duration-200 border transform active:scale-95 {{ $bulkStashMode ? 'bg-cyan-600 hover:bg-cyan-500 border-cyan-400 text-white' : 'bg-gradient-to-r from-slate-800 to-slate-700 hover:from-slate-700 hover:to-slate-600 text-cyan-300 border-cyan-500/40' }}">
                                <i class="fa-solid {{ $bulkStashMode ? 'fa-square-check' : 'fa-list-check' }}"></i>
                                Zaznacz wiele
                            </button>
                        </div>
                    @elseif($inventoryTab === 'materials')
                        <span class="text-stone-400 text-xs font-medium">Magazyn Materiałów: <strong class="text-amber-300">{{ $materialStashCount }} / {{ $materialStashCapacity }} slotów</strong></span>
                        <div class="flex items-center gap-1.5">
                            <button wire:click="stackItems" class="px-2.5 py-1 xs:px-3 xs:py-1.5 text-xs rounded-xl bg-gradient-to-r from-emerald-700 to-teal-700 hover:from-emerald-600 hover:to-teal-600 text-emerald-100 font-bold flex items-center gap-1.5 shadow transition-all duration-200 border border-emerald-500/40 transform active:scale-95" title="Połącz stosy materiałów">
                                <i class="fa-solid fa-layer-group"></i>
                                Połącz stosy
                            </button>
                            <button wire:click="toggleBulkStashMode" class="px-2.5 py-1 xs:px-3 xs:py-1.5 text-xs rounded-xl font-bold flex items-center gap-1.5 shadow transition-all duration-200 border transform active:scale-95 {{ $bulkStashMode ? 'bg-cyan-600 hover:bg-cyan-500 border-cyan-400 text-white' : 'bg-gradient-to-r from-slate-800 to-slate-700 hover:from-slate-700 hover:to-slate-600 text-cyan-300 border-cyan-500/40' }}">
                                <i class="fa-solid {{ $bulkStashMode ? 'fa-square-check' : 'fa-list-check' }}"></i>
                                Zaznacz wiele
                            </button>
                        </div>
                    @else
                        <span class="text-stone-400 text-xs font-medium">Magazyn Gracza: <strong class="text-amber-300">{{ count($playerStashItems) }} / {{ $stashCapacity }} slotów</strong></span>
                        <button wire:click="expandStash" class="px-2.5 py-1 xs:px-3 xs:py-1.5 text-xs rounded-xl bg-gradient-to-r from-amber-600 to-yellow-600 hover:from-amber-500 hover:to-yellow-500 text-stone-950 font-bold flex items-center gap-1.5 shadow transition-all duration-200 border border-amber-400 transform active:scale-95">
                            <i class="fa-solid fa-plus"></i> Powiększ (+1 Slot - 50 💎)
                        </button>
                    @endif
                </div>

                @if($bulkStashMode && in_array($inventoryTab, ['backpack', 'materials']))
                    <div class="bg-slate-950/80 p-2.5 rounded-lg border border-cyan-500/30 flex flex-col gap-2 animate-[fade-in_0.2s_ease-out]">
                        <div class="flex items-center justify-between flex-wrap gap-1.5">
                            <span class="text-[11px] font-semibold text-gray-400">Zaznaczanie:</span>
                            <div class="flex items-center gap-1 flex-wrap">
                                <button wire:click="selectAllForStash" class="px-2 py-0.5 bg-cyan-950/80 hover:bg-cyan-900 text-cyan-300 text-[11px] font-semibold rounded border border-cyan-700 transition">
                                    Wszystkie
                                </button>
                                @if(count($selectedStashItemIds) > 0)
                                    <button wire:click="clearStashSelection" class="px-2 py-0.5 bg-red-950/80 hover:bg-red-900 text-red-300 text-[11px] font-semibold rounded border border-red-700 transition">
                                        Wyczyść
                                    </button>
                                @endif
                            </div>
                        </div>

                        @if(count($selectedStashItemIds) > 0)
                            <div class="p-2 bg-cyan-950/60 border border-cyan-500/50 rounded flex items-center justify-between gap-2">
                                <div class="text-xs text-cyan-200">
                                    Wybrano: <span class="font-bold text-white">{{ count($selectedStashItemIds) }}</span> szt.
                                </div>
                                <button wire:click="moveSelectedToStash" class="px-3 py-1 bg-gradient-to-r from-cyan-700 to-blue-600 hover:from-cyan-600 hover:to-blue-500 text-white font-extrabold text-xs rounded shadow transition flex items-center gap-1">
                                    <i class="fa-solid fa-vault"></i>
                                    Przenieś do magazynu
                                </button>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            <!-- Inventory Grid -->
            <div id="inventory-grid" 
                 x-data="{ 
                     isInventoryDragOver: false,
                     activeItemId: null
                 }"
                 @dragover.prevent="if (window.currentDragItem && window.currentDragItem.source === 'equipped') isInventoryDragOver = true"
                 @dragleave="isInventoryDragOver = false"
                 @drop.prevent="
                     if (window.currentDragItem && window.currentDragItem.source === 'equipped') {
                         let dragItem = window.currentDragItem;
                         isInventoryDragOver = false;
                         flyItem('equip-slot-' + dragItem.slot, 'inventory-grid', () => $wire.unequipItem(dragItem.id));
                     } else {
                         isInventoryDragOver = false;
                     }
                 "
                 class="grid grid-cols-5 gap-1.5 xs:gap-2 bg-gray-800/90 p-1.5 xs:p-2 rounded-xl flex-grow content-start transition-all"
                 :class="{ 'ring-4 ring-blue-400 border-2 border-blue-400 bg-blue-950/40': isInventoryDragOver }"
            >
                @if($inventoryTab === 'backpack')
                    @foreach($inventory as $item)
                        @php
                            $isRustySwordTutorial = $gameStage == 6 && $item->template_id === '01k4jpx94j70x2vv10b835prm4';
                        @endphp
                        <div id="backpack-item-{{ $item->id }}" wire:key="backpack-item-{{ $item->id }}" x-data="{ 
                            open: false, 
                            hoverTimeout: null,
                            isDraggingThis: false,
                            posClass: 'sm:bottom-full sm:mb-2',
                            tooltipStyle: {},
                            init() {
                                this._onInventoryUpdated = () => { this.open = false; };
                                window.addEventListener('inventory-updated', this._onInventoryUpdated);
                                this._onCloseAllTooltips = () => { this.forceClose(); };
                                window.addEventListener('close-all-tooltips', this._onCloseAllTooltips);
                            },
                            destroy() {
                                window.removeEventListener('inventory-updated', this._onInventoryUpdated);
                                window.removeEventListener('close-all-tooltips', this._onCloseAllTooltips);
                            },
                            checkPosition() { 
                                if (window.innerWidth < 640) return;
                                this.$nextTick(() => {
                                    const triggerRect = this.$el.getBoundingClientRect();
                                    const tooltipEl = this.$refs.tooltipContainer || this.$el.querySelector('[data-tooltip-container]');
                                    if (!triggerRect.width || !tooltipEl) return;
                                    const tooltipRect = tooltipEl.getBoundingClientRect();
                                    const triggerCenter = triggerRect.left + triggerRect.width / 2;
                                    const halfWidth = tooltipRect.width / 2;
                                    const minMargin = 12;
                                    let style = {};
                                    if (triggerCenter - halfWidth < minMargin) {
                                        style.left = (minMargin - triggerRect.left) + 'px';
                                        style.transform = 'none';
                                    } else if (triggerCenter + halfWidth > window.innerWidth - minMargin) {
                                        style.left = ((window.innerWidth - minMargin) - triggerRect.left - tooltipRect.width) + 'px';
                                        style.transform = 'none';
                                    } else {
                                        style.left = '50%';
                                        style.transform = 'translateX(-50%)';
                                    }
                                    if (triggerRect.top < tooltipRect.height + 16) {
                                        style.top = '100%';
                                        style.bottom = 'auto';
                                        style.marginTop = '8px';
                                        style.marginBottom = '0';
                                        this.posClass = 'sm:top-full sm:mt-2';
                                    } else {
                                        style.bottom = '100%';
                                        style.top = 'auto';
                                        style.marginBottom = '8px';
                                        style.marginTop = '0';
                                        this.posClass = 'sm:bottom-full sm:mb-2';
                                    }
                                    this.tooltipStyle = style;
                                });
                            },
                            toggleTooltip() {
                                if ({{ $bulkStashMode ? 'true' : 'false' }}) return;
                                clearTimeout(this.hoverTimeout);
                                if (this.open) {
                                    this.forceClose();
                                } else {
                                    window.dispatchEvent(new CustomEvent('close-all-tooltips'));
                                    if (activeItemId && activeItemId !== '{{ $item->id }}') {
                                        window.dispatchEvent(new CustomEvent('close-item-tooltip', { detail: { id: activeItemId } }));
                                    }
                                    activeItemId = '{{ $item->id }}';
                                    this.open = true;
                                    this.checkPosition();
                                }
                            },
                            openTooltip() {},
                            closeTooltip() {},
                            forceClose() {
                                clearTimeout(this.hoverTimeout);
                                this.open = false;
                                if (activeItemId === '{{ $item->id }}') {
                                    activeItemId = null;
                                }
                            }
                        }" @click.outside="forceClose()"
                              @close-item-tooltip.window="if ($event.detail.id === '{{ $item->id }}') forceClose()"
                              wire:loading.class="opacity-50 scale-95 pointer-events-none" wire:target="equipItem('{{ $item->id }}')"
                              draggable="true"
                              @dragstart="
                                  forceClose(); 
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
                                  forceClose();
                                  @if($character->level < ($item->template->level_requirement ?? 1))
                                      $dispatch('notify', { type: 'error', message: 'Zbyt niski poziom aby założyć ten przedmiot!' });
                                  @else
                                      @if(in_array($item->template->type ?? '', ['weapon', 'armor', 'accessory']) && ($item->template->slot ?? null))
                                          flyItem('backpack-item-{{ $item->id }}', 'equip-slot-{{ $item->template->slot }}', () => $wire.equipItem('{{ $item->id }}'));
                                      @elseif(($item->template->type ?? '') === 'consumable')
                                          $wire.consumeItem('{{ $item->id }}');
                                      @elseif(($item->template->type ?? '') === 'egg')
                                          $dispatch('notify', { type: 'info', message: 'Przejdź do zakładki Pety w menu, aby umieścić jajko w inkubatorze.' });
                                      @endif
                                  @endif
                              "
                              class="aspect-square bg-gray-700 border rounded flex items-center justify-center cursor-grab active:cursor-grabbing hover:border-green-400 relative transition-all duration-300 {{ count($item->roll_stats['enchants'] ?? []) > 0 ? 'enchanted-border border-gray-600' : 'border-gray-600' }}"
                              :class="{ 
                                  'animate-[pulse_1.5s_ease-in-out_infinite] ring-4 ring-amber-500 scale-105 shadow-[0_0_15px_rgba(245,158,11,0.6)] z-10': {{ $isRustySwordTutorial ? 'true' : 'false' }} && !open,
                                  'opacity-40 scale-95 border-amber-400': isDraggingThis,
                                  '!z-[99999]': open && !{{ $bulkStashMode ? 'true' : 'false' }}
                              }"
                              @click="if ({{ $bulkStashMode ? 'true' : 'false' }}) { $wire.toggleSelectStashItem('{{ $item->id }}'); } else { toggleTooltip(); }">

                            <div wire:click.stop="toggleSelectStashItem('{{ $item->id }}')"
                                 class="absolute inset-0 z-30 rounded cursor-pointer flex items-start justify-end p-1 transition-all {{ $bulkStashMode ? '' : 'hidden' }} {{ in_array($item->id, $selectedStashItemIds) ? 'bg-cyan-500/30 border-2 border-cyan-400 shadow-[0_0_10px_rgba(6,182,212,0.5)]' : 'bg-cyan-950/10 border-2 border-slate-700/50 hover:border-cyan-400/50 hover:bg-cyan-500/10' }}">
                                <div class="w-5 h-5 rounded flex items-center justify-center text-xs font-bold {{ in_array($item->id, $selectedStashItemIds) ? 'bg-cyan-500 text-slate-950' : 'bg-slate-900/90 text-gray-400 border border-gray-600' }}">
                                    @if(in_array($item->id, $selectedStashItemIds))
                                        <i class="fa-solid fa-check"></i>
                                    @endif
                                </div>
                            </div>

                            @if($item->template->icon)
                                <div class="text-center text-xs text-white flex flex-col items-center w-full h-full justify-center relative">
                                    <img src="{{ route('assets.items', ['filename' => $item->template->icon]) }}" class="w-full h-full object-contain drop-shadow-lg p-1" alt="{{ $item->template->name }}">

                                    <div class="absolute bottom-0 right-0 flex flex-col items-end gap-0.5 pointer-events-none">
                                        @if($item->stack_size > 1)
                                            <span class="text-blue-300 font-bold text-[10px] bg-black/70 px-1 rounded-tl">{{ $item->stack_size }}x</span>
                                        @endif
                                    </div>
                                    <x-item-upgrade-overlay :level="$item->upgrade_level ?? 0" :type="$item->template->type ?? ''" />
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
                            <div x-show="open && !{{ $bulkStashMode ? 'true' : 'false' }}" x-transition.opacity style="display: none;" 
                                 :style="window.innerWidth >= 640 ? tooltipStyle : {}"
                                 class="fixed inset-0 sm:absolute sm:inset-auto sm:left-1/2 sm:-translate-x-1/2 sm:w-auto z-[99999] sm:z-[99999] flex items-center justify-center sm:block bg-black/80 sm:bg-transparent backdrop-blur-sm sm:backdrop-blur-none p-4 sm:p-0 cursor-default" @click.stop="forceClose()">
                                <template x-if="open">
                                    <div class="relative w-full max-w-[420px] sm:w-auto sm:max-w-none" x-ref="tooltipContainer" data-tooltip-container>
                                        <x-item-tooltip :item="$item" :equippedItem="$equipped[$item->template->slot ?? ''] ?? null" :dropSources="($item->template->type ?? null) === 'material' ? ($materialDropSources[$item->template_id] ?? []) : null">
                                            <x-slot:actions>
                                                <div class="flex flex-col gap-2 w-full">
                                                    @if($character->level < $item->template->level_requirement)
                                                        <p class="text-red-500 font-bold text-center mb-2">Zbyt niski poziom!</p>
                                                    @else
                                                        @if($item->template->type === 'weapon' || $item->template->type === 'armor' || $item->template->type === 'accessory')
                                                            <button wire:click.stop="equipItem('{{ $item->id }}')" @click.stop="forceClose(); flyItem('backpack-item-{{ $item->id }}', 'equip-slot-{{ strtolower($item->template->slot) }}')" class="w-full bg-green-600 hover:bg-green-500 text-white font-bold py-2 rounded transition-colors shadow">
                                                                Załóż sprzęt
                                                            </button>
                                                        @elseif($item->template->type === 'consumable')
                                                            @if(($item->template->sub_type ?? '') === 'chest')
                                                                <div class="flex flex-col gap-1.5 w-full">
                                                                    <button @click.stop="$wire.consumeItem('{{ $item->id }}', 1); $nextTick(() => forceClose())" class="w-full bg-amber-600 hover:bg-amber-500 text-white font-bold py-1.5 rounded transition-colors shadow medieval-font flex items-center justify-center gap-1.5 cursor-pointer text-xs">
                                                                        <i class="fa-solid fa-box-open"></i> Otwórz 1x
                                                                    </button>
                                                                    @if(($item->stack_size ?? 1) >= 2)
                                                                        <button @click.stop="$wire.consumeItem('{{ $item->id }}', 2); $nextTick(() => forceClose())" class="w-full bg-amber-700 hover:bg-amber-600 text-amber-100 font-bold py-1.5 rounded transition-colors shadow medieval-font flex items-center justify-center gap-1.5 cursor-pointer text-xs border border-amber-500/50">
                                                                            <i class="fa-solid fa-boxes-packing"></i> Otwórz 2x Na Raz
                                                                        </button>
                                                                    @endif
                                                                    @if(($item->stack_size ?? 1) >= 3)
                                                                        <button @click.stop="$wire.consumeItem('{{ $item->id }}', 3); $nextTick(() => forceClose())" class="w-full bg-yellow-600 hover:bg-yellow-500 text-slate-950 font-black py-1.5 rounded transition-colors shadow medieval-font flex items-center justify-center gap-1.5 cursor-pointer text-xs border border-amber-300">
                                                                            <i class="fa-solid fa-fire text-amber-950"></i> Otwórz 3x Na Raz
                                                                        </button>
                                                                    @endif
                                                                </div>
                                                            @else
                                                                <button @click.stop="$wire.consumeItem('{{ $item->id }}'); $nextTick(() => forceClose())" class="w-full bg-amber-600 hover:bg-amber-500 text-white font-bold py-2 rounded transition-colors shadow medieval-font flex items-center justify-center gap-1.5 cursor-pointer">
                                                                    <i class="fa-solid fa-flask"></i> Użyj przedmiotu
                                                                </button>
                                                            @endif
                                                        @elseif(($item->template->type ?? '') === 'egg')
                                                            <a href="{{ route('city.pets', $character) }}" wire:navigate @click.stop class="w-full bg-gradient-to-r from-amber-600 to-amber-500 hover:from-amber-500 hover:to-amber-400 text-white font-bold py-2 rounded transition-colors shadow medieval-font flex items-center justify-center gap-1.5 cursor-pointer border border-amber-400">
                                                                <i class="fa-solid fa-egg text-amber-200"></i> Idź do Petów
                                                            </a>
                                                        @endif
                                                    @endif

                                                    <button @click.stop="$wire.moveToStash('{{ $item->id }}'); $nextTick(() => forceClose())" class="w-full bg-cyan-700 hover:bg-cyan-600 text-white font-bold py-2 rounded transition-colors shadow flex items-center justify-center gap-1.5 cursor-pointer">
                                                        <i class="fa-solid fa-vault"></i> Przenieś do magazynu
                                                    </button>
                                                    
                                                    @if(!($item->bound_to_character ?? false) && ($item->template->is_tradeable ?? true))
                                                        <button @click.stop="$wire.openSellModal('{{ $item->id }}'); $nextTick(() => forceClose())" class="w-full bg-yellow-600 hover:bg-yellow-500 text-white py-2 rounded font-bold shadow transition-colors cursor-pointer">
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
                                </template>
                            </div>
                        </div>
                    @endforeach

                    <!-- Empty Slots Filler -->
                    @php $emptySlots = max(0, $backpackCapacity - count($inventory)) @endphp
                    @for($i = 0; $i < $emptySlots; $i++)
                        <div class="empty-slot aspect-square bg-gray-800 border border-gray-700 rounded"></div>
                    @endfor
                @elseif($inventoryTab === 'materials')
                    {{-- Material Stash View --}}
                    @foreach($materialStashItems as $item)
                        <div id="material-item-{{ $item->id }}" wire:key="material-item-{{ $item->id }}" x-data="{
                            showInfo: false,
                            hoverTimeout: null,
                            posClass: 'sm:bottom-full sm:mb-2',
                            tooltipStyle: {},
                            init() {
                                this._onInventoryUpdated = () => { this.showInfo = false; };
                                window.addEventListener('inventory-updated', this._onInventoryUpdated);
                                this._onCloseAllTooltips = () => { this.showInfo = false; };
                                window.addEventListener('close-all-tooltips', this._onCloseAllTooltips);
                            },
                            destroy() {
                                window.removeEventListener('inventory-updated', this._onInventoryUpdated);
                                window.removeEventListener('close-all-tooltips', this._onCloseAllTooltips);
                            },
                            checkPosition() {
                                if (window.innerWidth < 640) return;
                                this.$nextTick(() => {
                                    const triggerRect = this.$el.getBoundingClientRect();
                                    const tooltipEl = this.$refs.tooltipContainer || this.$el.querySelector('[data-tooltip-container]');
                                    if (!triggerRect.width || !tooltipEl) return;
                                    const tooltipRect = tooltipEl.getBoundingClientRect();
                                    const triggerCenter = triggerRect.left + triggerRect.width / 2;
                                    const halfWidth = tooltipRect.width / 2;
                                    const minMargin = 12;
                                    let style = {};
                                    if (triggerCenter - halfWidth < minMargin) {
                                        style.left = (minMargin - triggerRect.left) + 'px';
                                        style.transform = 'none';
                                    } else if (triggerCenter + halfWidth > window.innerWidth - minMargin) {
                                        style.left = ((window.innerWidth - minMargin) - triggerRect.left - tooltipRect.width) + 'px';
                                        style.transform = 'none';
                                    } else {
                                        style.left = '50%';
                                        style.transform = 'translateX(-50%)';
                                    }
                                    if (triggerRect.top < tooltipRect.height + 16) {
                                        style.top = '100%';
                                        style.bottom = 'auto';
                                        style.marginTop = '8px';
                                        style.marginBottom = '0';
                                        this.posClass = 'sm:top-full sm:mt-2';
                                    } else {
                                        style.bottom = '100%';
                                        style.top = 'auto';
                                        style.marginBottom = '8px';
                                        style.marginTop = '0';
                                        this.posClass = 'sm:bottom-full sm:mb-2';
                                    }
                                    this.tooltipStyle = style;
                                });
                            },
                            openTooltip() {
                                if ({{ $bulkStashMode ? 'true' : 'false' }}) return;
                                clearTimeout(this.hoverTimeout);
                                this.showInfo = true;
                                this.checkPosition();
                            },
                            closeTooltip() {
                                clearTimeout(this.hoverTimeout);
                                this.hoverTimeout = setTimeout(() => { this.showInfo = false; }, 120);
                            },
                            toggleTooltip() {
                                if ({{ $bulkStashMode ? 'true' : 'false' }}) return;
                                clearTimeout(this.hoverTimeout);
                                this.showInfo = !this.showInfo;
                                if (this.showInfo) this.checkPosition();
                            }
                        }"
                             wire:key="material-item-slot-{{ $item->id }}"
                             :class="{ 'z-50': showInfo && !{{ $bulkStashMode ? 'true' : 'false' }}, 'z-10': !showInfo }"
                             @click="if ({{ $bulkStashMode ? 'true' : 'false' }}) { $wire.toggleSelectStashItem('{{ $item->id }}'); } else { toggleTooltip(); }"
                             @resize.window.debounce.100ms="checkPosition()"
                             @tooltip-updated.window="checkPosition()"
                             class="aspect-square bg-amber-950/40 border border-amber-600/50 hover:border-amber-400 rounded flex items-center justify-center cursor-pointer relative transition-all duration-200 shadow">

                            <div wire:click.stop="toggleSelectStashItem('{{ $item->id }}')"
                                 class="absolute inset-0 z-30 rounded cursor-pointer flex items-start justify-end p-1 transition-all {{ $bulkStashMode ? '' : 'hidden' }} {{ in_array($item->id, $selectedStashItemIds) ? 'bg-cyan-500/30 border-2 border-cyan-400 shadow-[0_0_10px_rgba(6,182,212,0.5)]' : 'bg-cyan-950/10 border-2 border-slate-700/50 hover:border-cyan-400/50 hover:bg-cyan-500/10' }}">
                                <div class="w-5 h-5 rounded flex items-center justify-center text-xs font-bold {{ in_array($item->id, $selectedStashItemIds) ? 'bg-cyan-500 text-slate-950' : 'bg-slate-900/90 text-gray-400 border border-gray-600' }}">
                                    @if(in_array($item->id, $selectedStashItemIds))
                                        <i class="fa-solid fa-check"></i>
                                    @endif
                                </div>
                            </div>

                            <div class="text-center text-xs text-white flex flex-col items-center w-full h-full justify-center p-1">
                                @if($item->template->icon)
                                    <img src="{{ route('assets.items', ['filename' => $item->template->icon]) }}" class="w-full h-full object-contain drop-shadow-md" alt="{{ $item->template->name }}">
                                @else
                                    <span class="block truncate w-14 text-[10px]">{{ $item->template->name }}</span>
                                @endif
                                <span class="absolute bottom-0.5 right-0.5 text-amber-200 font-extrabold text-[10px] bg-black/80 border border-amber-600/60 px-1 py-0.2 rounded shadow">x{{ $item->stack_size }}</span>
                            </div>

                            <!-- Infobox Materiału -->
                            <div x-ref="tooltipContainer"
                                 data-tooltip-container="true"
                                 x-show="showInfo && !{{ $bulkStashMode ? 'true' : 'false' }}" 
                                 x-transition.opacity 
                                 style="display: none;" 
                                 :style="tooltipStyle"
                                 :class="posClass"
                                 class="fixed inset-0 sm:absolute sm:inset-auto z-[99999] flex items-center justify-center sm:block bg-black/80 sm:bg-transparent backdrop-blur-sm sm:backdrop-blur-none p-4 sm:p-0 cursor-default" 
                                 @click.stop="closeTooltip()">
                                <div class="relative w-full max-w-[420px] sm:w-auto sm:max-w-none" @click.stop>
                                    <x-item-tooltip :item="$item" :dropSources="$materialDropSources[$item->template_id] ?? []">
                                        <x-slot:actions>
                                            <div class="flex flex-col gap-2 w-full">
                                                @if(($item->template->type ?? '') === 'consumable')
                                                    @if(($item->template->sub_type ?? '') === 'chest')
                                                        <div class="flex flex-col gap-1.5 w-full">
                                                            <button @click.stop="$wire.consumeItem('{{ $item->id }}', 1); $nextTick(() => closeTooltip())" class="w-full bg-amber-600 hover:bg-amber-500 text-white font-bold py-1.5 rounded transition-colors shadow medieval-font flex items-center justify-center gap-1.5 cursor-pointer text-xs">
                                                                <i class="fa-solid fa-box-open"></i> Otwórz 1x
                                                            </button>
                                                            @if(($item->stack_size ?? 1) >= 2)
                                                                <button @click.stop="$wire.consumeItem('{{ $item->id }}', 2); $nextTick(() => closeTooltip())" class="w-full bg-amber-700 hover:bg-amber-600 text-amber-100 font-bold py-1.5 rounded transition-colors shadow medieval-font flex items-center justify-center gap-1.5 cursor-pointer text-xs border border-amber-500/50">
                                                                    <i class="fa-solid fa-boxes-packing"></i> Otwórz 2x Na Raz
                                                                </button>
                                    @if(($item->stack_size ?? 1) >= 3)
                                                                <button @click.stop="$wire.consumeItem('{{ $item->id }}', 3); $nextTick(() => closeTooltip())" class="w-full bg-yellow-600 hover:bg-yellow-500 text-slate-950 font-black py-1.5 rounded transition-colors shadow medieval-font flex items-center justify-center gap-1.5 cursor-pointer text-xs border border-amber-300">
                                                                    <i class="fa-solid fa-fire text-amber-950"></i> Otwórz 3x Na Raz
                                                                </button>
                                                            @endif
                                                        @else
                                                            <button @click.stop="$wire.consumeItem('{{ $item->id }}'); $nextTick(() => closeTooltip())" class="w-full bg-amber-600 hover:bg-amber-500 text-white font-bold py-1.5 rounded transition-colors shadow text-xs flex items-center justify-center gap-1.5 medieval-font cursor-pointer">
                                                                <i class="fa-solid fa-flask"></i> Użyj przedmiotu
                                                            </button>
                                                        @endif
                                                    @elseif(($item->template->type ?? '') === 'egg')
                                                        <a href="{{ route('city.pets', $character) }}" wire:navigate @click.stop class="w-full bg-amber-600 hover:bg-amber-500 text-white font-bold py-1.5 rounded transition-colors shadow text-xs flex items-center justify-center gap-1.5 medieval-font cursor-pointer border border-amber-400">
                                                            <i class="fa-solid fa-egg text-amber-200"></i> Idź do Petów
                                                        </a>
                                                    @endif
                                                @endif
                                                <button wire:click.stop="moveToStash('{{ $item->id }}')" @click.stop="closeTooltip()" class="w-full bg-cyan-700 hover:bg-cyan-600 text-white font-bold py-1.5 rounded transition-colors shadow flex items-center justify-center gap-1.5 text-xs">
                                                    <i class="fa-solid fa-vault"></i> Przenieś do magazynu
                                                </button>

                                                @if(!($item->bound_to_character ?? false) && ($item->template->is_tradeable ?? true))
                                                    <button wire:click.stop="openSellModal('{{ $item->id }}'); closeTooltip();" class="w-full bg-yellow-600 hover:bg-yellow-500 text-white py-1.5 rounded font-bold shadow transition-colors text-xs">
                                                        Wystaw na targowisko
                                                    </button>
                                                @endif
                                            </div>
                                        </x-slot:actions>
                                    </x-item-tooltip>
                                    <div class="hidden sm:block absolute left-1/2 -translate-x-1/2 w-4 h-4 bg-gray-900 transform rotate-45 z-[-1]"
                                         :class="posClass.includes('sm:top-full') ? '-top-2 border-t border-l border-slate-600' : '-bottom-2 border-b border-r border-slate-600'"></div>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <!-- Material Stash Empty Slots Filler -->
                    @php $emptyMatSlots = max(0, min(25, $materialStashCapacity - count($materialStashItems))) @endphp
                    @for($i = 0; $i < $emptyMatSlots; $i++)
                        <div class="empty-slot aspect-square bg-stone-900/40 border border-amber-900/20 rounded flex items-center justify-center text-stone-700 text-xs">
                            <i class="fa-solid fa-cubes text-[10px] opacity-20"></i>
                        </div>
                    @endfor
                @else
                    {{-- Stash View --}}
                    @foreach($playerStashItems as $item)
                        <div id="stash-item-{{ $item->id }}" x-data="{ 
                            open: false, 
                            hoverTimeout: null,
                            posClass: 'sm:bottom-full sm:mb-2',
                            checkPosition() { 
                                this.posClass = this.$el.getBoundingClientRect().top < window.innerHeight / 2 ? 'sm:top-full sm:mt-2' : 'sm:bottom-full sm:mb-2'; 
                            },
                            toggleTooltip() {
                                clearTimeout(this.hoverTimeout);
                                if (this.open) {
                                    this.forceClose();
                                } else {
                                    window.dispatchEvent(new CustomEvent('close-all-tooltips'));
                                    this.checkPosition();
                                    activeItemId = '{{ $item->id }}';
                                    this.open = true;
                                }
                            },
                            openTooltip() {},
                            closeTooltip() {},
                            forceClose() {
                                clearTimeout(this.hoverTimeout);
                                this.open = false;
                                if (activeItemId === '{{ $item->id }}') {
                                    activeItemId = null;
                                }
                            }
                        }" @click.outside="forceClose()" 
                             wire:loading.class="opacity-50 scale-95 pointer-events-none" wire:target="withdrawFromStash('{{ $item->id }}')"
                             @dblclick="forceClose(); $wire.withdrawFromStash('{{ $item->id }}')"
                             class="aspect-square bg-stone-800 border-2 border-cyan-700/60 rounded flex items-center justify-center cursor-pointer hover:border-cyan-400 relative transition-all duration-300 shadow"
                             :class="{ '!z-[99999] relative': open }"
                             @click="toggleTooltip()">
                            
                            @if($item->template->icon)
                                <div class="text-center text-xs text-white flex flex-col items-center w-full h-full justify-center relative">
                                    <img src="{{ route('assets.items', ['filename' => $item->template->icon]) }}" class="w-full h-full object-contain drop-shadow-lg p-1" alt="{{ $item->template->name }}">
                                    <div class="absolute bottom-0 right-0 flex flex-col items-end gap-0.5 pointer-events-none">
                                        @if($item->stack_size > 1)
                                            <span class="text-blue-300 font-bold text-[10px] bg-black/70 px-1 rounded-tl">{{ $item->stack_size }}x</span>
                                        @endif
                                    </div>
                                    <x-item-upgrade-overlay :level="$item->upgrade_level ?? 0" :type="$item->template->type ?? ''" />
                                </div>
                            @else
                                <div class="text-center text-xs text-white">
                                    <span class="block truncate w-14">{{ $item->template->name }}</span>
                                    @if($item->upgrade_level > 0)
                                        <span class="text-yellow-400">+{{ $item->upgrade_level }}</span>
                                    @endif
                                </div>
                            @endif

                            <!-- Tooltip -->
                            <div x-show="open && !{{ $bulkStashMode ? 'true' : 'false' }}" x-transition.opacity style="display: none;" 
                                 :class="posClass"
                                 class="fixed inset-0 sm:absolute sm:inset-auto sm:left-1/2 sm:-translate-x-1/2 sm:w-auto z-[99999] sm:z-[99999] flex items-center justify-center sm:block bg-black/80 sm:bg-transparent backdrop-blur-sm sm:backdrop-blur-none p-4 sm:p-0 cursor-default" @click.stop="forceClose()">
                                <template x-if="open">
                                    <div class="relative w-full max-w-xs sm:w-auto sm:max-w-none">
                                        <button @click="forceClose()" class="absolute top-2 right-2 text-gray-400 hover:text-white text-lg font-bold sm:hidden z-10">✕</button>
                                        <x-item-tooltip :item="$item">
                                            <x-slot:actions>
                                                <div class="flex flex-col gap-2 w-full">
                                                    <button wire:click.stop="withdrawFromStash('{{ $item->id }}')" @click.stop="forceClose()" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-2 rounded transition-colors shadow flex items-center justify-center gap-1.5">
                                                        <i class="fa-solid fa-bag-shopping"></i> Wyciągnij do plecaka
                                                    </button>
                                                </div>
                                            </x-slot:actions>
                                        </x-item-tooltip>
                                        <!-- Arrow (Desktop only) -->
                                        <div class="hidden sm:block absolute left-1/2 -translate-x-1/2 w-4 h-4 bg-gray-900 transform rotate-45 z-[-1]"
                                             :class="posClass === 'sm:top-full sm:mt-2' ? '-top-2 border-t border-l border-slate-600' : '-bottom-2 border-b border-r border-slate-600'"></div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    @endforeach

                    <!-- Stash Empty Slots Filler -->
                    @php $emptyStashSlots = max(0, $stashCapacity - count($playerStashItems)) @endphp
                    @for($i = 0; $i < $emptyStashSlots; $i++)
                        <div class="empty-slot aspect-square bg-stone-900/60 border border-cyan-900/40 rounded flex items-center justify-center text-stone-700 text-xs">
                            <i class="fa-solid fa-lock text-[10px] opacity-40"></i>
                        </div>
                    @endfor
                @endif
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
                        <div class="text-3xl flex items-center justify-center w-10 h-10">
                            @if($sellItem->template->slot === 'weapon') <i class="fa-solid fa-khanda text-amber-400"></i>
                            @elseif($sellItem->template->slot === 'head') <i class="fa-solid fa-user-shield text-amber-400"></i>
                            @elseif($sellItem->template->slot === 'chest') <i class="fa-solid fa-shield-halved text-amber-400"></i>
                            @elseif($sellItem->template->slot === 'legs') <i class="fa-solid fa-vest text-amber-400"></i>
                            @elseif($sellItem->template->slot === 'boots') <i class="fa-solid fa-shoe-prints text-amber-400"></i>
                            @else <i class="fa-solid fa-box text-amber-400"></i>
                            @endif
                        </div>
                        <div>
                            <div class="font-bold text-amber-200">{{ $sellItem->template->name }}</div>
                            <div class="text-xs text-gray-400">{{ ucfirst($sellItem->rarity) }} | Poz: {{ $sellItem->template->level_requirement }}</div>
                        </div>
                    </div>
                @endif
                
                <div class="space-y-4 mb-6">
                    @php
                        $modalItem = \App\Infrastructure\Persistence\ItemInstance::find($sellingItemUlid);
                        $isStackable = $modalItem && ($modalItem->stack_size ?? 1) > 1;
                        $maxQty = $modalItem ? (int)($modalItem->stack_size ?? 1) : 1;
                    @endphp

                    @if($isStackable)
                    <div>
                        <label class="block text-sm font-bold text-gray-300 mb-1">
                            Ilość <span class="text-amber-400 font-extrabold">(max: {{ $maxQty }})</span>
                        </label>
                        <div class="flex items-center gap-3">
                            <input type="range"
                                   wire:model.live="sellQuantity"
                                   min="1" max="{{ $maxQty }}"
                                   class="flex-1 h-2 bg-gray-700 rounded-lg appearance-none cursor-pointer accent-amber-500">
                            <input type="number"
                                   wire:model.live="sellQuantity"
                                   min="1" max="{{ $maxQty }}"
                                   class="w-20 bg-gray-800 border border-gray-600 rounded p-2 text-white text-center font-bold">
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Możesz sprzedać część lub całość stosu.</p>
                    </div>
                    @endif

                    <div>
                        <label class="block text-sm font-bold text-gray-300 mb-1">Cena @if($isStackable)<span class="text-amber-400/70 font-normal text-xs">(za całą wybraną ilość)</span>@endif
                            <span class="text-amber-500/80 font-normal text-xs ml-1">(np. 2k, 2kk, 1.5kk, 2m)</span>
                        </label>
                        <input type="text" wire:model.live.debounce.150ms="sellPrice"
                               class="w-full bg-gray-800 border border-gray-600 rounded p-2 text-white font-mono text-base focus:border-amber-500 focus:outline-none"
                               placeholder="np. 100, 2.5k, 2kk, 10m">
                        @php
                            $parsedSellPrice = \App\Support\PriceParser::parse($sellPrice ?? '');
                        @endphp
                        @if($parsedSellPrice > 0)
                            <div class="mt-1.5 text-xs text-amber-300 font-semibold flex items-center justify-between bg-amber-950/50 border border-amber-600/40 rounded px-3 py-1.5 shadow-sm">
                                <span class="flex items-center gap-1.5">
                                    <i class="fa-solid fa-calculator text-amber-400"></i>
                                    <span>Przeliczona cena:</span>
                                </span>
                                <span class="font-bold text-yellow-300 text-sm tracking-wide">
                                    {{ \App\Support\PriceParser::format($parsedSellPrice) }} 
                                    @if(($sellCurrency ?? 'gold') === 'gems')
                                        <i class="fa-solid fa-gem text-cyan-400 ml-1"></i>
                                    @else
                                        <i class="fa-solid fa-coins text-yellow-400 ml-1"></i>
                                    @endif
                                </span>
                            </div>
                        @endif
                    </div>
                    
                    <div>
                        <label class="block text-sm font-bold text-gray-300 mb-1">Waluta</label>
                        <select wire:model="sellCurrency" class="w-full bg-gray-800 border border-gray-600 rounded p-2 text-white">
                            <option value="gold">Złoto</option>
                            <option value="gems">Klejnoty</option>
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

    {{-- Case Opening CS-style Modal --}}
    <livewire:city.case-opening-modal />
</div>
