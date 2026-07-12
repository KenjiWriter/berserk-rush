<div x-data="{ travelingTo: '' }"
    class="min-h-screen bg-gradient-to-b from-blue-900/90 via-indigo-800/90 to-purple-900/90 text-amber-100 relative overflow-hidden">
    
    {{-- Entering Location Transition Overlay --}}
    <div x-show="travelingTo !== ''"
         x-transition:enter="transition ease-in-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         class="fixed inset-0 z-[100] flex items-center justify-center bg-black/80 backdrop-blur-sm"
         style="display: none;">
         
         <div class="relative w-96 h-48 flex flex-col items-center justify-center bg-contain bg-center bg-no-repeat drop-shadow-2xl filter saturate-150"
              style="background-image: url('{{ asset('img/avatars/plate.png') }}');">
             
             <div class="absolute inset-0 flex items-center justify-center flex-col mt-4">
                 <svg class="animate-spin h-10 w-10 text-amber-400 mb-2 drop-shadow-md" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                 <span class="text-amber-100 font-bold text-xl drop-shadow-[0_2px_2px_rgba(0,0,0,0.8)] text-center px-4" style="font-family: 'Cinzel', serif;" x-text="'Przenoszenie do ' + travelingTo"></span>
             </div>
         </div>
    </div>
    {{-- Background city image --}}
    <div class="absolute inset-0 bg-cover bg-center bg-no-repeat opacity-60"
        style="background-image: url('{{ asset('img/city-background.png') }}');">
    </div>

    {{-- Dark overlay for readability --}}
    <div class="absolute inset-0 bg-gradient-to-b from-slate-900/40 via-slate-800/50 to-slate-900/60"></div>

    {{-- Floating motes of light --}}
    <div class="absolute inset-0 pointer-events-none">
        <div class="magic-mote magic-mote-1"></div>
        <div class="magic-mote magic-mote-2"></div>
        <div class="magic-mote magic-mote-3"></div>
        <div class="magic-mote magic-mote-4"></div>
        <div class="magic-mote magic-mote-5"></div>
    </div>

    @php
        $gameStage = auth()->user()->game_stage;
    @endphp

    @if($gameStage == 3)
        <livewire:global.tutorial-overlay :step="4" :rewardItemTemplateId="'01k4jpx94j70x2vv10b835prm4'" />
    @elseif($gameStage == 4)
        <livewire:global.tutorial-overlay :step="5" />
    @elseif($gameStage == 8)
        <livewire:global.tutorial-overlay :step="9" />
    @elseif($gameStage == 13 && $character->character_points > 0)
        <livewire:global.tutorial-overlay :step="14" />
    @elseif($gameStage == 15)
        <livewire:global.tutorial-overlay :step="16" :rewardGold="150" />
    @elseif($gameStage == 16)
        <livewire:global.tutorial-overlay :step="17" />
    @elseif($gameStage == 20)
        <livewire:global.tutorial-overlay :step="21" :rewardItemTemplateId="'01KX77GX1KG1K0ZJGTKRWV3DT6'" />
    @endif

    <div class="relative container mx-auto px-4 py-8 min-h-screen">
        {{-- Header with character info --}}
        <div class="flex flex-col md:flex-row items-center md:justify-between mb-8 gap-4 text-center md:text-left">
            <div
                class="bg-gradient-to-r from-amber-50/95 to-amber-100/95 border-4 border-amber-700 rounded-lg p-4 shadow-2xl backdrop-blur-sm w-full md:w-auto">
                <div class="flex flex-col sm:flex-row items-center space-y-2 sm:space-y-0 sm:space-x-3">
                    {{-- Character avatar --}}
                    <div
                        class="w-12 h-12 border-2 border-amber-700 rounded-full overflow-hidden bg-gradient-to-b from-amber-200 to-amber-300">
                        @if ($character->avatar && file_exists(public_path('img/avatars/' . $character->avatar . '.png')))
                            <img src="{{ asset('img/avatars/' . $character->avatar . '.png') }}"
                                alt="Avatar {{ $character->avatar }}" class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-lg text-amber-700">
                                ⚔️
                            </div>
                        @endif
                    </div>

                    {{-- Character info --}}
                    <div>
                        <h2 class="text-xl font-bold text-amber-900 medieval-font">{{ $character->name }}</h2>
                        <div class="text-sm text-amber-700">
                            Poziom {{ $character->level }} • {{ $character->xp }} XP •
                            {{ number_format($character->gold) }} złota
                        </div>
                    </div>
                </div>
            </div>

            {{-- Back buttons --}}
            <div class="flex flex-wrap justify-center md:justify-end gap-2 md:gap-4 w-full md:w-auto">
                @if(auth()->user()->permission_level >= 9)
                    <a href="{{ route('admin.dashboard') }}"
                        class="bg-gradient-to-r from-red-800 to-red-900 hover:from-red-700 hover:to-red-800 text-amber-100 font-bold py-2 px-4 rounded-lg transition-all duration-200 transform hover:scale-105 shadow-lg medieval-font flex items-center">
                        ⚙️ Admin Panel
                    </a>
                @endif
                <button wire:click="backToHomepage"
                    class="bg-gradient-to-r from-slate-600 to-slate-700 hover:from-slate-700 hover:to-slate-800 text-amber-200 font-bold py-2 px-4 rounded-lg transition-all duration-200 transform hover:scale-105 shadow-lg medieval-font flex items-center">
                    🏠 Powrót do gry
                </button>
            </div>
        </div>

        {{-- City title --}}
        <div class="text-center mb-12">
            <h1
                class="text-5xl font-bold bg-gradient-to-r from-amber-300 via-yellow-400 to-amber-500 bg-clip-text text-transparent medieval-font drop-shadow-2xl mb-2">
                🏰 Miasto Berserków
            </h1>
            <p class="text-xl text-amber-200 font-semibold drop-shadow-lg">
                Wybierz gdzie chcesz się udać
            </p>
        </div>

        {{-- City layout (Desktop) --}}
        <div class="hidden lg:grid lg:grid-cols-3 gap-8 max-w-6xl mx-auto">
            {{-- Left side buildings --}}
            <div class="space-y-6">
                {{-- Profile --}}
                <div class="relative group {{ in_array($gameStage, [5, 14]) ? 'z-10' : '' }}">
                    <button wire:click="goTo('profile')" @click="travelingTo = 'Profil'; $dispatch('play-audio', { type: 'profile' })" @mouseenter="$dispatch('play-audio', { type: 'hover' })" @mouseleave="$dispatch('play-audio', { type: 'hover' })"
                        class="w-full bg-gradient-to-br from-blue-50/90 to-blue-100/90 border-4 border-blue-700 rounded-lg p-6 shadow-2xl backdrop-blur-sm hover:from-blue-100/95 hover:to-blue-200/95 transition-all duration-300 transform hover:scale-105 hover:shadow-3xl {{ in_array($gameStage, [5, 14]) ? 'animate-[pulse_1.5s_ease-in-out_infinite] ring-4 ring-amber-500 scale-105 shadow-[0_0_20px_rgba(245,158,11,0.6)] relative z-10' : '' }}" wire:loading.attr="disabled" wire:loading.class="opacity-50 cursor-not-allowed" wire:target="goTo('profile')">
                        {{-- Decorative corners --}}
                        <div
                            class="absolute top-0 left-0 w-6 h-6 bg-blue-800 transform rotate-45 -translate-x-3 -translate-y-3">
                        </div>
                        <div
                            class="absolute top-0 right-0 w-6 h-6 bg-blue-800 transform rotate-45 translate-x-3 -translate-y-3">
                        </div>
                        <div
                            class="absolute bottom-0 left-0 w-6 h-6 bg-blue-800 transform rotate-45 -translate-x-3 translate-y-3">
                        </div>
                        <div
                            class="absolute bottom-0 right-0 w-6 h-6 bg-blue-800 transform rotate-45 translate-x-3 translate-y-3">
                        </div>

                        <div class="relative text-center">
                            <div class="text-6xl mb-4">
                                <div wire:loading.remove wire:target="goTo('profile')">👤</div>
                                <div wire:loading wire:target="goTo('profile')"><svg class="animate-spin h-10 w-10 mx-auto text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg></div>
                            </div>
                            <h3 class="text-2xl font-bold text-blue-900 medieval-font mb-2">Postać</h3>
                            <p class="text-blue-800 font-semibold">Ekwipunek i statystyki</p>
                        </div>
                    </button>
                </div>

                {{-- Armorsmith --}}
                <div class="relative group">
                    <button wire:click="goTo('armorsmith')" @click="travelingTo = 'Zbrojmistrz'; $dispatch('play-audio', { type: 'shop' })" @mouseenter="$dispatch('play-audio', { type: 'hover' })" @mouseleave="$dispatch('play-audio', { type: 'hover' })"
                        class="w-full bg-gradient-to-br from-amber-50/90 to-amber-100/90 border-4 border-amber-700 rounded-lg p-6 shadow-2xl backdrop-blur-sm hover:from-amber-100/95 hover:to-amber-200/95 transition-all duration-300 transform hover:scale-105 hover:shadow-3xl" wire:loading.attr="disabled" wire:loading.class="opacity-50 cursor-not-allowed" wire:target="goTo('armorsmith')">
                        {{-- Decorative corners --}}
                        <div
                            class="absolute top-0 left-0 w-6 h-6 bg-amber-800 transform rotate-45 -translate-x-3 -translate-y-3">
                        </div>
                        <div
                            class="absolute top-0 right-0 w-6 h-6 bg-amber-800 transform rotate-45 translate-x-3 -translate-y-3">
                        </div>
                        <div
                            class="absolute bottom-0 left-0 w-6 h-6 bg-amber-800 transform rotate-45 -translate-x-3 translate-y-3">
                        </div>
                        <div
                            class="absolute bottom-0 right-0 w-6 h-6 bg-amber-800 transform rotate-45 translate-x-3 translate-y-3">
                        </div>

                        <div class="relative text-center">
                            <div class="text-6xl mb-4">
                                <div wire:loading.remove wire:target="goTo('armorsmith')">🛡️</div>
                                <div wire:loading wire:target="goTo('armorsmith')"><svg class="animate-spin h-10 w-10 mx-auto text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg></div>
                            </div>
                            <h3 class="text-2xl font-bold text-amber-900 medieval-font mb-2">Zbrojmistrz</h3>
                            <p class="text-amber-800 font-semibold">Zbroje i tarcze dla wojowników</p>
                        </div>
                    </button>
                </div>

                {{-- Weaponsmith --}}
                <div class="relative group {{ $gameStage == 17 ? 'z-10' : '' }}">
                    <button wire:click="goTo('weaponsmith')" @click="travelingTo = 'Brońmistrz'" @mouseenter="$dispatch('play-audio', { type: 'hover' })" @mouseleave="$dispatch('play-audio', { type: 'hover' })"
                        class="w-full bg-gradient-to-br from-amber-50/90 to-amber-100/90 border-4 border-amber-700 rounded-lg p-6 shadow-2xl backdrop-blur-sm hover:from-amber-100/95 hover:to-amber-200/95 transition-all duration-300 transform hover:scale-105 hover:shadow-3xl {{ $gameStage == 17 ? 'animate-[pulse_1.5s_ease-in-out_infinite] ring-4 ring-amber-500 scale-105 shadow-[0_0_20px_rgba(245,158,11,0.6)] relative z-10' : '' }}" wire:loading.attr="disabled" wire:loading.class="opacity-50 cursor-not-allowed" wire:target="goTo('weaponsmith')">
                        {{-- Decorative corners --}}
                        <div
                            class="absolute top-0 left-0 w-6 h-6 bg-amber-800 transform rotate-45 -translate-x-3 -translate-y-3">
                        </div>
                        <div
                            class="absolute top-0 right-0 w-6 h-6 bg-amber-800 transform rotate-45 translate-x-3 -translate-y-3">
                        </div>
                        <div
                            class="absolute bottom-0 left-0 w-6 h-6 bg-amber-800 transform rotate-45 -translate-x-3 translate-y-3">
                        </div>
                        <div
                            class="absolute bottom-0 right-0 w-6 h-6 bg-amber-800 transform rotate-45 translate-x-3 translate-y-3">
                        </div>

                        <div class="relative text-center">
                            <div class="text-6xl mb-4">
                                <div wire:loading.remove wire:target="goTo('weaponsmith')">⚔️</div>
                                <div wire:loading wire:target="goTo('weaponsmith')"><svg class="animate-spin h-10 w-10 mx-auto text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg></div>
                            </div>
                            <h3 class="text-2xl font-bold text-amber-900 medieval-font mb-2">Brońmistrz</h3>
                            <p class="text-amber-800 font-semibold">Miecze, łuki i magiczne berła</p>
                        </div>
                    </button>
                </div>
            </div>

            {{-- Center - Main road & Market/Mail --}}
            <div class="flex flex-col items-center justify-center space-y-8">
                
                {{-- Market --}}
                <div class="relative group w-full max-w-xs">
                    <button wire:click="goTo('market')" @click="travelingTo = 'Targowisko'; $dispatch('play-audio', { type: 'shop' })" @mouseenter="$dispatch('play-audio', { type: 'hover' })" @mouseleave="$dispatch('play-audio', { type: 'hover' })"
                        class="w-full bg-gradient-to-br from-yellow-700/90 to-yellow-900/90 border-4 border-yellow-500 rounded-lg p-4 shadow-2xl backdrop-blur-sm hover:from-yellow-600/95 hover:to-yellow-800/95 transition-all duration-300 transform hover:scale-105 hover:shadow-3xl" wire:loading.attr="disabled" wire:loading.class="opacity-50 cursor-not-allowed" wire:target="goTo('market')">
                        <div class="relative text-center">
                            <div class="text-5xl mb-2">
                                <div wire:loading.remove wire:target="goTo('market')">⚖️</div>
                                <div wire:loading wire:target="goTo('market')"><svg class="animate-spin h-10 w-10 mx-auto text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg></div>
                            </div>
                            <h3 class="text-xl font-bold text-amber-100 medieval-font mb-1">Targowisko</h3>
                            <p class="text-amber-200/80 text-sm font-semibold">Handel z graczami</p>
                        </div>
                    </button>
                </div>

                {{-- Arena --}}
                <div class="relative group w-full max-w-xs">
                    <button wire:click="goTo('arena')" @click="travelingTo = 'Arena'; $dispatch('play-audio', { type: 'combat' })" @mouseenter="$dispatch('play-audio', { type: 'hover' })" @mouseleave="$dispatch('play-audio', { type: 'hover' })"
                        class="w-full bg-gradient-to-br from-orange-700/90 to-orange-900/90 border-4 border-orange-500 rounded-lg p-4 shadow-2xl backdrop-blur-sm hover:from-orange-600/95 hover:to-orange-800/95 transition-all duration-300 transform hover:scale-105 hover:shadow-3xl" wire:loading.attr="disabled" wire:loading.class="opacity-50 cursor-not-allowed" wire:target="goTo('arena')">
                        <div class="relative text-center">
                            <div class="text-5xl mb-2">
                                <div wire:loading.remove wire:target="goTo('arena')">🏟️</div>
                                <div wire:loading wire:target="goTo('arena')"><svg class="animate-spin h-10 w-10 mx-auto text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg></div>
                            </div>
                            <h3 class="text-xl font-bold text-amber-100 medieval-font mb-1">Arena Gladiatorów</h3>
                            <p class="text-orange-200/80 text-sm font-semibold">Walki PvP o chwałę</p>
                        </div>
                    </button>
                </div>

                <div class="text-center my-4 hidden lg:block">
                    <div class="w-24 h-24 mx-auto bg-gradient-to-b from-stone-400 to-stone-600 rounded-full border-4 border-stone-700 shadow-2xl flex items-center justify-center mb-2">
                        <div class="text-3xl">🏰</div>
                    </div>
                    <h3 class="text-lg font-bold text-amber-300 medieval-font">Plac Centralny</h3>
                </div>

                {{-- Guilds --}}
                <div class="relative group w-full max-w-xs">
                    <button wire:click="goTo('guild')" @click="travelingTo = 'Gildia'" @mouseenter="$dispatch('play-audio', { type: 'hover' })" @mouseleave="$dispatch('play-audio', { type: 'hover' })"
                        class="w-full bg-gradient-to-br from-red-700/90 to-red-900/90 border-4 border-red-500 rounded-lg p-4 shadow-2xl backdrop-blur-sm hover:from-red-600/95 hover:to-red-800/95 transition-all duration-300 transform hover:scale-105 hover:shadow-3xl" wire:loading.attr="disabled" wire:loading.class="opacity-50 cursor-not-allowed" wire:target="goTo('guild')">
                        <div class="relative text-center">
                            <div class="text-5xl mb-2">
                                <div wire:loading.remove wire:target="goTo('guild')">🚩</div>
                                <div wire:loading wire:target="goTo('guild')"><svg class="animate-spin h-10 w-10 mx-auto text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg></div>
                            </div>
                            <h3 class="text-xl font-bold text-amber-100 medieval-font mb-1">Gildia</h3>
                            <p class="text-red-200/80 text-sm font-semibold">Zjednocz się z innymi</p>
                        </div>
                    </button>
                </div>

                {{-- Quests --}}
                <div class="relative group w-full max-w-xs">
                    <button wire:click="goTo('quests')" @click="travelingTo = 'Tablica Wyzwań'; $dispatch('play-audio', { type: 'tab' })" @mouseenter="$dispatch('play-audio', { type: 'hover' })" @mouseleave="$dispatch('play-audio', { type: 'hover' })"
                        class="w-full bg-gradient-to-br from-teal-700/90 to-teal-900/90 border-4 border-teal-500 rounded-lg p-4 shadow-2xl backdrop-blur-sm hover:from-teal-600/95 hover:to-teal-800/95 transition-all duration-300 transform hover:scale-105 hover:shadow-3xl relative" wire:loading.attr="disabled" wire:loading.class="opacity-50 cursor-not-allowed" wire:target="goTo('quests')">
                        <div class="relative text-center">
                            <div class="text-5xl mb-2">
                                <div wire:loading.remove wire:target="goTo('quests')">📜</div>
                                <div wire:loading wire:target="goTo('quests')"><svg class="animate-spin h-10 w-10 mx-auto text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg></div>
                            </div>
                            <h3 class="text-xl font-bold text-amber-100 medieval-font mb-1">Tablica Wyzwań</h3>
                            <p class="text-teal-200/80 text-sm font-semibold">Misje i zadania</p>
                        </div>
                        @if(isset($completedQuestsCount) && $completedQuestsCount > 0)
                            <div class="absolute -top-3 -right-3 w-8 h-8 bg-amber-500 rounded-full flex items-center justify-center text-slate-900 text-lg font-bold border-2 border-amber-200 shadow-lg animate-bounce">
                                !
                            </div>
                        @endif
                    </button>
                </div>

                {{-- Mailbox --}}
                <div class="relative group w-full max-w-xs">
                    <button wire:click="goTo('mailbox')" @click="travelingTo = 'Poczta'; $dispatch('play-audio', { type: 'tab' })" @mouseenter="$dispatch('play-audio', { type: 'hover' })" @mouseleave="$dispatch('play-audio', { type: 'hover' })"
                        class="w-full bg-gradient-to-br from-blue-700/90 to-blue-900/90 border-4 border-blue-500 rounded-lg p-4 shadow-2xl backdrop-blur-sm hover:from-blue-600/95 hover:to-blue-800/95 transition-all duration-300 transform hover:scale-105 hover:shadow-3xl relative" wire:loading.attr="disabled" wire:loading.class="opacity-50 cursor-not-allowed" wire:target="goTo('mailbox')">
                        <div class="relative text-center">
                            <div class="text-5xl mb-2">
                                <div wire:loading.remove wire:target="goTo('mailbox')">✉️</div>
                                <div wire:loading wire:target="goTo('mailbox')"><svg class="animate-spin h-10 w-10 mx-auto text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg></div>
                            </div>
                            <h3 class="text-xl font-bold text-amber-100 medieval-font mb-1">Poczta</h3>
                            <p class="text-blue-200/80 text-sm font-semibold">
                                Wiadomości
                                @php
                                    $unread = \App\Infrastructure\Persistence\Mail::where('to_character_id', $character->id)->where('claimed', false)->count();
                                @endphp
                                @if($unread > 0)
                                    <span class="inline-flex items-center justify-center w-5 h-5 ml-1 text-xs font-bold text-white bg-red-500 rounded-full">
                                        {{ $unread }}
                                    </span>
                                @endif
                            </p>
                        </div>
                    </button>
                </div>
            </div>

            {{-- Right side buildings --}}
            <div class="space-y-6">
                {{-- Witch --}}
                <div class="relative group">
                    <button wire:click="goTo('witch')" @click="travelingTo = 'Wiedźma'" @mouseenter="$dispatch('play-audio', { type: 'hover' })" @mouseleave="$dispatch('play-audio', { type: 'hover' })"
                        class="w-full bg-gradient-to-br from-purple-50/90 to-purple-100/90 border-4 border-purple-700 rounded-lg p-6 shadow-2xl backdrop-blur-sm hover:from-purple-100/95 hover:to-purple-200/95 transition-all duration-300 transform hover:scale-105 hover:shadow-3xl" wire:loading.attr="disabled" wire:loading.class="opacity-50 cursor-not-allowed" wire:target="goTo('witch')">
                        {{-- Decorative corners --}}
                        <div
                            class="absolute top-0 left-0 w-6 h-6 bg-purple-800 transform rotate-45 -translate-x-3 -translate-y-3">
                        </div>
                        <div
                            class="absolute top-0 right-0 w-6 h-6 bg-purple-800 transform rotate-45 translate-x-3 -translate-y-3">
                        </div>
                        <div
                            class="absolute bottom-0 left-0 w-6 h-6 bg-purple-800 transform rotate-45 -translate-x-3 translate-y-3">
                        </div>
                        <div
                            class="absolute bottom-0 right-0 w-6 h-6 bg-purple-800 transform rotate-45 translate-x-3 translate-y-3">
                        </div>

                        <div class="relative text-center">
                            <div class="text-6xl mb-4">
                                <div wire:loading.remove wire:target="goTo('witch')">🧙‍♀️</div>
                                <div wire:loading wire:target="goTo('witch')"><svg class="animate-spin h-10 w-10 mx-auto text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg></div>
                            </div>
                            <h3 class="text-2xl font-bold text-purple-900 medieval-font mb-2">Wiedźma</h3>
                            <p class="text-purple-800 font-semibold">Alchemia i magiczne mikstury</p>
                        </div>
                    </button>
                </div>

                {{-- Wizard --}}
                <div class="relative group">
                    <button wire:click="goTo('wizard')" @click="travelingTo = 'Czarodziej'; $dispatch('play-audio', { type: 'shop' })" @mouseenter="$dispatch('play-audio', { type: 'hover' })" @mouseleave="$dispatch('play-audio', { type: 'hover' })"
                        class="w-full bg-gradient-to-br from-indigo-50/90 to-indigo-100/90 border-4 border-indigo-700 rounded-lg p-6 shadow-2xl backdrop-blur-sm hover:from-indigo-100/95 hover:to-indigo-200/95 transition-all duration-300 transform hover:scale-105 hover:shadow-3xl" wire:loading.attr="disabled" wire:loading.class="opacity-50 cursor-not-allowed" wire:target="goTo('wizard')">
                        {{-- Decorative corners --}}
                        <div class="absolute top-0 left-0 w-6 h-6 bg-indigo-800 transform rotate-45 -translate-x-3 -translate-y-3"></div>
                        <div class="absolute top-0 right-0 w-6 h-6 bg-indigo-800 transform rotate-45 translate-x-3 -translate-y-3"></div>
                        <div class="absolute bottom-0 left-0 w-6 h-6 bg-indigo-800 transform rotate-45 -translate-x-3 translate-y-3"></div>
                        <div class="absolute bottom-0 right-0 w-6 h-6 bg-indigo-800 transform rotate-45 translate-x-3 translate-y-3"></div>

                        <div class="relative text-center">
                            <div class="text-6xl mb-4">
                                <div wire:loading.remove wire:target="goTo('wizard')">🧙‍♂️</div>
                                <div wire:loading wire:target="goTo('wizard')"><svg class="animate-spin h-10 w-10 mx-auto text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg></div>
                            </div>
                            <h3 class="text-2xl font-bold text-indigo-900 medieval-font mb-2">Czarodziej</h3>
                            <p class="text-indigo-800 font-semibold">Magiczne bonusy przedmiotów</p>
                        </div>
                    </button>
                </div>

                {{-- Adventure --}}
                <div class="relative group {{ $gameStage == 9 ? 'z-10' : '' }}">
                    <button wire:click="goTo('adventure')" @click="travelingTo = 'Wyprawy'; $dispatch('play-audio', { type: 'combat' })" @mouseenter="$dispatch('play-audio', { type: 'hover' })" @mouseleave="$dispatch('play-audio', { type: 'hover' })"
                        class="w-full bg-gradient-to-br from-green-50/90 to-green-100/90 border-4 border-green-700 rounded-lg p-6 shadow-2xl backdrop-blur-sm hover:from-green-100/95 hover:to-green-200/95 transition-all duration-300 transform hover:scale-105 hover:shadow-3xl {{ $gameStage == 9 ? 'animate-[pulse_1.5s_ease-in-out_infinite] ring-4 ring-amber-500 scale-105 shadow-[0_0_20px_rgba(245,158,11,0.6)] relative z-10' : '' }}" wire:loading.attr="disabled" wire:loading.class="opacity-50 cursor-not-allowed" wire:target="goTo('adventure')">
                        {{-- Decorative corners --}}
                        <div
                            class="absolute top-0 left-0 w-6 h-6 bg-green-800 transform rotate-45 -translate-x-3 -translate-y-3">
                        </div>
                        <div
                            class="absolute top-0 right-0 w-6 h-6 bg-green-800 transform rotate-45 translate-x-3 -translate-y-3">
                        </div>
                        <div
                            class="absolute bottom-0 left-0 w-6 h-6 bg-green-800 transform rotate-45 -translate-x-3 translate-y-3">
                        </div>
                        <div
                            class="absolute bottom-0 right-0 w-6 h-6 bg-green-800 transform rotate-45 translate-x-3 translate-y-3">
                        </div>

                        <div class="relative text-center">
                            <div class="text-6xl mb-4">
                                <div wire:loading.remove wire:target="goTo('adventure')">🗺️</div>
                                <div wire:loading wire:target="goTo('adventure')"><svg class="animate-spin h-10 w-10 mx-auto text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg></div>
                            </div>
                            <h3 class="text-2xl font-bold text-green-900 medieval-font mb-2">Przygoda</h3>
                            <p class="text-green-800 font-semibold">Wyrusz na ekspedycje</p>
                        </div>
                    </button>
                </div>
            </div>
        </div>

        {{-- Mobile layout --}}
        <div class="lg:hidden mt-8">
            <div class="grid grid-cols-2 gap-4">
                <button wire:click="goTo('profile')" @click="travelingTo = 'Profil'; $dispatch('play-audio', { type: 'profile' })" @mouseenter="$dispatch('play-audio', { type: 'hover' })" @mouseleave="$dispatch('play-audio', { type: 'hover' })"
                    class="bg-gradient-to-br from-blue-50/90 to-blue-100/90 border-4 border-blue-700 rounded-lg p-4 text-center shadow-xl {{ in_array($gameStage, [5, 14]) ? 'animate-[pulse_1.5s_ease-in-out_infinite] ring-4 ring-amber-500 scale-105 shadow-[0_0_15px_rgba(245,158,11,0.6)] relative z-10' : '' }}" wire:loading.attr="disabled" wire:loading.class="opacity-50 cursor-not-allowed" wire:target="goTo('profile')">
                    <div class="text-4xl mb-2">
                                <div wire:loading.remove wire:target="goTo('profile')">👤</div>
                                <div wire:loading wire:target="goTo('profile')"><svg class="animate-spin h-10 w-10 mx-auto text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg></div>
                            </div>
                    <div class="font-bold text-blue-900 medieval-font">Postać</div>
                </button>

                <button wire:click="goTo('armorsmith')" @click="travelingTo = 'Zbrojmistrz'; $dispatch('play-audio', { type: 'shop' })" @mouseenter="$dispatch('play-audio', { type: 'hover' })" @mouseleave="$dispatch('play-audio', { type: 'hover' })"
                    class="bg-gradient-to-br from-amber-50/90 to-amber-100/90 border-4 border-amber-700 rounded-lg p-4 text-center shadow-xl" wire:loading.attr="disabled" wire:loading.class="opacity-50 cursor-not-allowed" wire:target="goTo('armorsmith')">
                    <div class="text-4xl mb-2">
                                <div wire:loading.remove wire:target="goTo('armorsmith')">🛡️</div>
                                <div wire:loading wire:target="goTo('armorsmith')"><svg class="animate-spin h-10 w-10 mx-auto text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg></div>
                            </div>
                    <div class="font-bold text-amber-900 medieval-font">Zbrojmistrz</div>
                </button>

                <button wire:click="goTo('weaponsmith')" @click="travelingTo = 'Brońmistrz'" @mouseenter="$dispatch('play-audio', { type: 'hover' })" @mouseleave="$dispatch('play-audio', { type: 'hover' })"
                    class="bg-gradient-to-br from-amber-50/90 to-amber-100/90 border-4 border-amber-700 rounded-lg p-4 text-center shadow-xl {{ $gameStage == 17 ? 'animate-[pulse_1.5s_ease-in-out_infinite] ring-4 ring-amber-500 scale-105 shadow-[0_0_15px_rgba(245,158,11,0.6)] relative z-10' : '' }}" wire:loading.attr="disabled" wire:loading.class="opacity-50 cursor-not-allowed" wire:target="goTo('weaponsmith')">
                    <div class="text-4xl mb-2">
                                <div wire:loading.remove wire:target="goTo('weaponsmith')">⚔️</div>
                                <div wire:loading wire:target="goTo('weaponsmith')"><svg class="animate-spin h-10 w-10 mx-auto text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg></div>
                            </div>
                    <div class="font-bold text-amber-900 medieval-font">Brońmistrz</div>
                </button>

                <button wire:click="goTo('witch')" @click="travelingTo = 'Wiedźma'" @mouseenter="$dispatch('play-audio', { type: 'hover' })" @mouseleave="$dispatch('play-audio', { type: 'hover' })"
                    class="bg-gradient-to-br from-purple-50/90 to-purple-100/90 border-4 border-purple-700 rounded-lg p-4 text-center shadow-xl" wire:loading.attr="disabled" wire:loading.class="opacity-50 cursor-not-allowed" wire:target="goTo('witch')">
                    <div class="text-4xl mb-2">
                                <div wire:loading.remove wire:target="goTo('witch')">🧙‍♀️</div>
                                <div wire:loading wire:target="goTo('witch')"><svg class="animate-spin h-10 w-10 mx-auto text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg></div>
                            </div>
                    <div class="font-bold text-purple-900 medieval-font">Wiedźma</div>
                </button>

                <button wire:click="goTo('wizard')" @click="travelingTo = 'Czarodziej'; $dispatch('play-audio', { type: 'shop' })" @mouseenter="$dispatch('play-audio', { type: 'hover' })" @mouseleave="$dispatch('play-audio', { type: 'hover' })"
                    class="bg-gradient-to-br from-indigo-50/90 to-indigo-100/90 border-4 border-indigo-700 rounded-lg p-4 text-center shadow-xl" wire:loading.attr="disabled" wire:loading.class="opacity-50 cursor-not-allowed" wire:target="goTo('wizard')">
                    <div class="text-4xl mb-2">
                                <div wire:loading.remove wire:target="goTo('wizard')">🧙‍♂️</div>
                                <div wire:loading wire:target="goTo('wizard')"><svg class="animate-spin h-10 w-10 mx-auto text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg></div>
                            </div>
                    <div class="font-bold text-indigo-900 medieval-font">Czarodziej</div>
                </button>

                <button wire:click="goTo('adventure')" @click="travelingTo = 'Wyprawy'; $dispatch('play-audio', { type: 'combat' })" @mouseenter="$dispatch('play-audio', { type: 'hover' })" @mouseleave="$dispatch('play-audio', { type: 'hover' })"
                    class="col-span-2 bg-gradient-to-br from-green-50/90 to-green-100/90 border-4 border-green-700 rounded-lg p-4 text-center shadow-xl {{ $gameStage == 9 ? 'animate-[pulse_1.5s_ease-in-out_infinite] ring-4 ring-amber-500 scale-105 shadow-[0_0_15px_rgba(245,158,11,0.6)] relative z-10' : '' }}" wire:loading.attr="disabled" wire:loading.class="opacity-50 cursor-not-allowed" wire:target="goTo('adventure')">
                    <div class="text-4xl mb-2">
                                <div wire:loading.remove wire:target="goTo('adventure')">🗺️</div>
                                <div wire:loading wire:target="goTo('adventure')"><svg class="animate-spin h-10 w-10 mx-auto text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg></div>
                            </div>
                    <div class="font-bold text-green-900 medieval-font">Przygoda</div>
                </button>
                
                <button wire:click="goTo('market')" @click="travelingTo = 'Targowisko'; $dispatch('play-audio', { type: 'shop' })" @mouseenter="$dispatch('play-audio', { type: 'hover' })" @mouseleave="$dispatch('play-audio', { type: 'hover' })"
                    class="bg-gradient-to-br from-yellow-700/90 to-yellow-900/90 border-4 border-yellow-500 rounded-lg p-4 text-center shadow-xl" wire:loading.attr="disabled" wire:loading.class="opacity-50 cursor-not-allowed" wire:target="goTo('market')">
                    <div class="text-4xl mb-2">
                                <div wire:loading.remove wire:target="goTo('market')">⚖️</div>
                                <div wire:loading wire:target="goTo('market')"><svg class="animate-spin h-10 w-10 mx-auto text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg></div>
                            </div>
                    <div class="font-bold text-amber-100 medieval-font">Targowisko</div>
                </button>

                <button wire:click="goTo('arena')" @click="travelingTo = 'Arena'; $dispatch('play-audio', { type: 'combat' })" @mouseenter="$dispatch('play-audio', { type: 'hover' })" @mouseleave="$dispatch('play-audio', { type: 'hover' })"
                    class="bg-gradient-to-br from-orange-700/90 to-orange-900/90 border-4 border-orange-500 rounded-lg p-4 text-center shadow-xl" wire:loading.attr="disabled" wire:loading.class="opacity-50 cursor-not-allowed" wire:target="goTo('arena')">
                    <div class="text-4xl mb-2">
                                <div wire:loading.remove wire:target="goTo('arena')">🏟️</div>
                                <div wire:loading wire:target="goTo('arena')"><svg class="animate-spin h-10 w-10 mx-auto text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg></div>
                            </div>
                    <div class="font-bold text-amber-100 medieval-font">Arena</div>
                </button>

                <button wire:click="goTo('guild')" @click="travelingTo = 'Gildia'" @mouseenter="$dispatch('play-audio', { type: 'hover' })" @mouseleave="$dispatch('play-audio', { type: 'hover' })"
                    class="bg-gradient-to-br from-red-700/90 to-red-900/90 border-4 border-red-500 rounded-lg p-4 text-center shadow-xl" wire:loading.attr="disabled" wire:loading.class="opacity-50 cursor-not-allowed" wire:target="goTo('guild')">
                    <div class="text-4xl mb-2">
                                <div wire:loading.remove wire:target="goTo('guild')">🚩</div>
                                <div wire:loading wire:target="goTo('guild')"><svg class="animate-spin h-10 w-10 mx-auto text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg></div>
                            </div>
                    <div class="font-bold text-amber-100 medieval-font">Gildia</div>
                </button>

                <button wire:click="goTo('quests')" @click="travelingTo = 'Tablica Wyzwań'; $dispatch('play-audio', { type: 'tab' })" @mouseenter="$dispatch('play-audio', { type: 'hover' })" @mouseleave="$dispatch('play-audio', { type: 'hover' })"
                    class="bg-gradient-to-br from-teal-700/90 to-teal-900/90 border-4 border-teal-500 rounded-lg p-4 text-center shadow-xl relative" wire:loading.attr="disabled" wire:loading.class="opacity-50 cursor-not-allowed" wire:target="goTo('quests')">
                    <div class="text-4xl mb-2">
                                <div wire:loading.remove wire:target="goTo('quests')">📜</div>
                                <div wire:loading wire:target="goTo('quests')"><svg class="animate-spin h-10 w-10 mx-auto text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg></div>
                            </div>
                    <div class="font-bold text-amber-100 medieval-font">Wyzwania</div>
                    @if(isset($completedQuestsCount) && $completedQuestsCount > 0)
                        <div class="absolute -top-2 -right-2 w-6 h-6 bg-amber-500 rounded-full flex items-center justify-center text-slate-900 text-sm font-bold border-2 border-amber-200 shadow-lg animate-bounce">
                            !
                        </div>
                    @endif
                </button>

                <button wire:click="goTo('mailbox')" @click="travelingTo = 'Poczta'; $dispatch('play-audio', { type: 'tab' })" @mouseenter="$dispatch('play-audio', { type: 'hover' })" @mouseleave="$dispatch('play-audio', { type: 'hover' })"
                    class="bg-gradient-to-br from-blue-700/90 to-blue-900/90 border-4 border-blue-500 rounded-lg p-4 text-center shadow-xl relative" wire:loading.attr="disabled" wire:loading.class="opacity-50 cursor-not-allowed" wire:target="goTo('mailbox')">
                    <div class="text-4xl mb-2">
                                <div wire:loading.remove wire:target="goTo('mailbox')">✉️</div>
                                <div wire:loading wire:target="goTo('mailbox')"><svg class="animate-spin h-10 w-10 mx-auto text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg></div>
                            </div>
                    <div class="font-bold text-amber-100 medieval-font">Poczta</div>
                    @if(isset($unread) && $unread > 0)
                        <div class="absolute top-2 right-2 w-6 h-6 bg-red-500 rounded-full flex items-center justify-center text-white text-xs font-bold border-2 border-slate-900 shadow-lg">
                            {{ $unread }}
                        </div>
                    @endif
                </button>
            </div>
        </div>
    </div>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&display=swap');

        .medieval-font {
            font-family: 'Cinzel', serif;
        }

        /* Magic motes */
        .magic-mote {
            position: absolute;
            background: radial-gradient(circle, rgba(147, 197, 253, 0.8) 0%, rgba(59, 130, 246, 0.4) 50%, transparent 100%);
            border-radius: 50%;
            pointer-events: none;
            animation: float-magic 20s infinite linear;
        }

        .magic-mote-1 {
            width: 6px;
            height: 6px;
            left: 15%;
            animation-delay: 0s;
            animation-duration: 25s;
        }

        .magic-mote-2 {
            width: 4px;
            height: 4px;
            left: 35%;
            animation-delay: 5s;
            animation-duration: 20s;
        }

        .magic-mote-3 {
            width: 8px;
            height: 8px;
            left: 55%;
            animation-delay: 10s;
            animation-duration: 30s;
        }

        .magic-mote-4 {
            width: 5px;
            height: 5px;
            left: 75%;
            animation-delay: 15s;
            animation-duration: 22s;
        }

        .magic-mote-5 {
            width: 7px;
            height: 7px;
            left: 85%;
            animation-delay: 20s;
            animation-duration: 28s;
        }

        @keyframes float-magic {
            0% {
                transform: translateY(100vh) translateX(0px) rotate(0deg);
                opacity: 0;
            }

            10% {
                opacity: 1;
            }

            90% {
                opacity: 1;
            }

            100% {
                transform: translateY(-100px) translateX(50px) rotate(360deg);
                opacity: 0;
            }
        }
    </style>
</div>
