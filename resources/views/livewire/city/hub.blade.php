<div x-data="{ travelingTo: '', mouseX: 0, mouseY: 0 }"
    @mousemove="if(window.innerWidth >= 1024) { mouseX = $event.clientX; mouseY = $event.clientY; }"
    class="min-h-screen bg-black text-amber-100 relative overflow-hidden flex flex-col">
    
    {{-- Entering Location Transition Overlay --}}
    <div x-show="$data.travelingTo !== ''"
         x-transition:enter="transition ease-in-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         class="fixed inset-0 z-[100] flex items-center justify-center bg-black/80 backdrop-blur-sm"
         style="display: none;">
         
         <div class="relative w-96 h-48 flex flex-col items-center justify-center bg-contain bg-center bg-no-repeat drop-shadow-2xl filter saturate-150"
              style="background-image: url('{{ asset('img/avatars/plate.png') }}');">
             
             <div class="absolute inset-0 flex items-center justify-center flex-col mt-4">
                 <svg class="animate-spin h-10 w-10 text-amber-400 mb-2 drop-shadow-md" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                 <span class="text-amber-100 font-bold text-xl drop-shadow-[0_2px_2px_rgba(0,0,0,0.8)] text-center px-4" style="font-family: 'Cinzel', serif;" x-text="'Przenoszenie do ' + $data.travelingTo"></span>
             </div>
         </div>
    </div>

    {{-- Background city image with Parallax --}}
    <div class="absolute inset-0 bg-cover bg-center bg-no-repeat transition-transform duration-300 ease-out"
        :style="`background-image: url('${'{{ asset('img/city-background.png') }}'}'); transform: scale(1.05) translate(${(mouseX - window.innerWidth/2) * 0.01}px, ${(mouseY - window.innerHeight/2) * 0.01}px);`">
    </div>

    {{-- Warm lighting overlay (Golden Hour / Magical) --}}
    <div class="absolute inset-0 bg-gradient-to-br from-amber-900/40 via-purple-900/30 to-orange-900/50 mix-blend-overlay"></div>
    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent"></div>

    {{-- Floating motes & Clouds --}}
    <div class="absolute inset-0 pointer-events-none overflow-hidden">
        {{-- Sun rays --}}
        <div class="absolute top-0 left-1/4 w-1/2 h-full bg-gradient-to-b from-amber-300/10 to-transparent transform -skew-x-12 blur-3xl"></div>
        
        <div class="magic-mote magic-mote-1 bg-amber-300 shadow-[0_0_10px_#fcd34d]"></div>
        <div class="magic-mote magic-mote-2 bg-orange-300 shadow-[0_0_10px_#fdba74]"></div>
        <div class="magic-mote magic-mote-3 bg-purple-300 shadow-[0_0_10px_#d8b4fe]"></div>
        <div class="magic-mote magic-mote-4 bg-amber-400 shadow-[0_0_10px_#fbbf24]"></div>
        <div class="magic-mote magic-mote-5 bg-yellow-200 shadow-[0_0_10px_#fef08a]"></div>
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
        <livewire:global.tutorial-overlay :step="21" :rewardItemTemplateId="'01k4jpx94j70x2vv10b835arm1'" />
    @elseif($gameStage == 22)
        <livewire:global.tutorial-overlay :step="23" />
    @endif

    <div class="relative w-full px-6 md:px-10 lg:px-12 py-6 md:py-8 min-h-screen flex flex-col z-10">
        {{-- Admin Link Header --}}
        @if(auth()->user()->permission_level >= 9)
            <div class="flex justify-end mb-4">
                <a href="{{ route('admin.dashboard') }}"
                    class="bg-red-950/80 border border-red-700/50 hover:bg-red-900 hover:border-red-500 text-red-200 font-bold py-2 px-4 rounded-xl transition-all duration-300 shadow-lg medieval-font flex items-center backdrop-blur-sm text-xs">
                    ⚙️ Admin
                </a>
            </div>
        @endif

        {{-- City title --}}
        <div class="text-center mb-10">
            <h1 class="text-4xl md:text-6xl font-bold bg-gradient-to-b from-amber-200 via-amber-400 to-orange-500 bg-clip-text text-transparent medieval-font drop-shadow-[0_5px_5px_rgba(0,0,0,0.8)] mb-3 filter drop-shadow-lg">
                Miasto Berserków
            </h1>
            <p class="text-lg md:text-xl text-amber-200/80 font-medium tracking-wide">
                Gdzie skierujesz swoje kroki?
            </p>
        </div>

        {{-- Desktop Asymmetrical Bento Layout --}}
        <div class="hidden lg:grid grid-cols-12 gap-5 w-full flex-grow auto-rows-[180px] xl:auto-rows-[200px]">
            
            {{-- ADVENTURE (Hero card, 8 cols, 2 rows) --}}
            <div class="col-span-8 row-span-2 relative group rounded-3xl overflow-hidden border-2 border-green-900/50 shadow-2xl transition-all duration-300 hover:border-green-500/80 hover:shadow-[0_0_30px_rgba(34,197,94,0.3)] {{ $gameStage == 9 ? 'animate-[pulse_1.5s_ease-in-out_infinite] ring-4 ring-amber-500 z-10' : '' }}"
                 x-data="{ tiltX: 0, tiltY: 0 }"
                 @mousemove="const r = $el.getBoundingClientRect(); tiltX = ((($event.clientY - r.top)/r.height)-0.5)*-8; tiltY = ((($event.clientX - r.left)/r.width)-0.5)*8;"
                 @mouseleave="tiltX = 0; tiltY = 0"
                 :style="`transform: perspective(1200px) rotateX(${tiltX}deg) rotateY(${tiltY}deg) scale(${tiltX !== 0 || tiltY !== 0 ? 1.01 : 1}); transition: transform 0.1s ease-out;`">
                <button wire:click="goTo('adventure')" @click="travelingTo = 'Wyprawy'; $dispatch('play-audio', { type: 'combat' })" @mouseenter="$dispatch('play-audio', { type: 'hover' })" 
                    class="w-full h-full text-left relative" wire:loading.attr="disabled">
                    <div class="absolute inset-0 bg-cover bg-center transition-transform duration-700 group-hover:scale-110" style="background-image: url('{{ asset('img/adventure-background.png') }}');"></div>
                    <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent"></div>
                    <div class="absolute inset-0 bg-green-900/20 mix-blend-color group-hover:bg-green-900/0 transition-colors duration-500"></div>
                    
                    <div class="absolute bottom-0 left-0 p-8">
                        <div wire:loading wire:target="goTo('adventure')" class="mb-3"><svg class="animate-spin h-10 w-10 text-green-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg></div>
                        <h3 class="text-4xl font-bold text-amber-100 medieval-font mb-2 drop-shadow-lg group-hover:text-amber-300 transition-colors">Przygoda</h3>
                        <p class="text-green-200/90 font-medium text-lg">Wyrusz na dzikie terytoria, walcz z potworami i zdobywaj łupy.</p>
                    </div>
                </button>
            </div>

            {{-- PROFILE (Tall card, 4 cols, 2 rows) --}}
            <div class="col-span-4 row-span-2 relative group rounded-3xl overflow-hidden border-2 border-blue-900/50 shadow-2xl transition-all duration-300 hover:border-blue-500/80 hover:shadow-[0_0_30px_rgba(59,130,246,0.3)] {{ in_array($gameStage, [5, 14]) ? 'animate-[pulse_1.5s_ease-in-out_infinite] ring-4 ring-amber-500 z-10' : '' }}"
                 x-data="{ tiltX: 0, tiltY: 0 }"
                 @mousemove="const r = $el.getBoundingClientRect(); tiltX = ((($event.clientY - r.top)/r.height)-0.5)*-10; tiltY = ((($event.clientX - r.left)/r.width)-0.5)*10;"
                 @mouseleave="tiltX = 0; tiltY = 0"
                 :style="`transform: perspective(1000px) rotateX(${tiltX}deg) rotateY(${tiltY}deg) scale(${tiltX !== 0 || tiltY !== 0 ? 1.02 : 1}); transition: transform 0.1s ease-out;`">
                <button wire:click="goTo('profile')" @click="travelingTo = 'Profil'; $dispatch('play-audio', { type: 'profile' })" @mouseenter="$dispatch('play-audio', { type: 'hover' })" 
                    class="w-full h-full text-center relative flex flex-col items-center justify-center" wire:loading.attr="disabled">
                    <div class="absolute inset-0 bg-cover bg-center transition-transform duration-700 group-hover:scale-110 opacity-60 mix-blend-luminosity" style="background-image: url('{{ asset('img/profile-bg.png') }}');"></div>
                    <div class="absolute inset-0 bg-gradient-to-t from-black/95 via-black/50 to-black/30"></div>
                    
                    <div class="relative z-10 p-6 flex flex-col items-center">
                        <div wire:loading wire:target="goTo('profile')" class="mb-4"><svg class="animate-spin h-10 w-10 text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg></div>
                        <h3 class="text-3xl font-bold text-blue-200 medieval-font mb-2 group-hover:text-blue-100 transition-colors drop-shadow-md">Postać</h3>
                        <p class="text-blue-300/80 font-medium">Zarządzaj ekwipunkiem<br>i atrybutami bohatera</p>
                    </div>
                </button>
            </div>

            {{-- MARKET (4 cols, 1 row) --}}
            <div class="col-span-4 row-span-1 relative group rounded-3xl overflow-hidden border border-yellow-900/50 shadow-lg transition-all duration-300 hover:border-yellow-500/80 hover:shadow-[0_0_20px_rgba(234,179,8,0.2)]"
                 x-data="{ tiltX: 0, tiltY: 0 }" @mousemove="const r = $el.getBoundingClientRect(); tiltX = ((($event.clientY - r.top)/r.height)-0.5)*-12; tiltY = ((($event.clientX - r.left)/r.width)-0.5)*12;" @mouseleave="tiltX = 0; tiltY = 0" :style="`transform: perspective(1000px) rotateX(${tiltX}deg) rotateY(${tiltY}deg) scale(${tiltX !== 0 || tiltY !== 0 ? 1.02 : 1}); transition: transform 0.1s ease-out;`">
                <button wire:click="goTo('market')" @click="travelingTo = 'Targowisko'; $dispatch('play-audio', { type: 'shop' })" @mouseenter="$dispatch('play-audio', { type: 'hover' })" class="w-full h-full flex items-center p-6 relative">
                    <div class="absolute inset-0 bg-cover bg-center transition-transform duration-500 group-hover:scale-110 opacity-55 mix-blend-luminosity" style="background-image: url('{{ asset('img/market-bg.png') }}');"></div>
                    <div class="absolute inset-0 bg-gradient-to-r from-black/90 via-black/60 to-black/30"></div>
                    <div class="relative z-10 text-left">
                        <div wire:loading wire:target="goTo('market')" class="mb-1"><svg class="animate-spin h-8 w-8 text-yellow-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg></div>
                        <h3 class="text-2xl font-bold text-yellow-400 medieval-font group-hover:text-yellow-300 drop-shadow-md">Targowisko</h3>
                        <p class="text-yellow-200/70 text-sm font-medium">Handel z graczami</p>
                    </div>
                </button>
            </div>

            {{-- ARENA (5 cols, 1 row) --}}
            <div class="col-span-5 row-span-1 relative group rounded-3xl overflow-hidden border border-orange-900/50 shadow-lg transition-all duration-300 hover:border-orange-500/80 hover:shadow-[0_0_20px_rgba(249,115,22,0.2)]"
                 x-data="{ tiltX: 0, tiltY: 0 }" @mousemove="const r = $el.getBoundingClientRect(); tiltX = ((($event.clientY - r.top)/r.height)-0.5)*-12; tiltY = ((($event.clientX - r.left)/r.width)-0.5)*12;" @mouseleave="tiltX = 0; tiltY = 0" :style="`transform: perspective(1000px) rotateX(${tiltX}deg) rotateY(${tiltY}deg) scale(${tiltX !== 0 || tiltY !== 0 ? 1.02 : 1}); transition: transform 0.1s ease-out;`">
                <button wire:click="goTo('arena')" @click="travelingTo = 'Arena'; $dispatch('play-audio', { type: 'combat' })" @mouseenter="$dispatch('play-audio', { type: 'hover' })" class="w-full h-full flex items-center justify-start p-6 relative">
                    <div class="absolute inset-0 bg-cover bg-center transition-transform duration-500 group-hover:scale-110 opacity-55 mix-blend-luminosity" style="background-image: url('{{ asset('img/arena-bg.png') }}');"></div>
                    <div class="absolute inset-0 bg-gradient-to-r from-black/90 via-black/60 to-black/30"></div>
                    <div class="relative z-10 text-left">
                        <div wire:loading wire:target="goTo('arena')" class="mb-1"><svg class="animate-spin h-8 w-8 text-orange-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg></div>
                        <h3 class="text-2xl font-bold text-orange-400 medieval-font group-hover:text-orange-300 drop-shadow-md">Arena Gladiatorów</h3>
                        <p class="text-orange-200/70 text-sm font-medium">Pojedynki PvP i rankingi</p>
                    </div>
                </button>
            </div>

            {{-- MAILBOX (3 cols, 1 row) --}}
            <div class="col-span-3 row-span-1 relative group rounded-3xl overflow-hidden border border-sky-900/50 shadow-lg transition-all duration-300 hover:border-sky-500/80 hover:shadow-[0_0_20px_rgba(14,165,233,0.2)]"
                 x-data="{ tiltX: 0, tiltY: 0 }" @mousemove="const r = $el.getBoundingClientRect(); tiltX = ((($event.clientY - r.top)/r.height)-0.5)*-12; tiltY = ((($event.clientX - r.left)/r.width)-0.5)*12;" @mouseleave="tiltX = 0; tiltY = 0" :style="`transform: perspective(1000px) rotateX(${tiltX}deg) rotateY(${tiltY}deg) scale(${tiltX !== 0 || tiltY !== 0 ? 1.02 : 1}); transition: transform 0.1s ease-out;`">
                <button wire:click="goTo('mailbox')" @click="travelingTo = 'Poczta'; $dispatch('play-audio', { type: 'tab' })" @mouseenter="$dispatch('play-audio', { type: 'hover' })" class="w-full h-full flex flex-col items-center justify-center p-4 relative">
                    <div class="absolute inset-0 bg-cover bg-center transition-transform duration-500 group-hover:scale-110 opacity-55 mix-blend-luminosity" style="background-image: url('{{ asset('img/mailbox-bg.png') }}');"></div>
                    <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/60 to-black/30"></div>
                    <div class="relative z-10 text-center">
                        <div wire:loading wire:target="goTo('mailbox')" class="mb-1"><svg class="animate-spin h-7 w-7 text-sky-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg></div>
                        <h3 class="text-xl font-bold text-sky-300 medieval-font group-hover:text-sky-200 drop-shadow">Poczta</h3>
                        <p class="text-sky-200/70 text-xs font-medium mt-0.5">Skrzynka i wiadomości</p>
                        @php $unread = \App\Infrastructure\Persistence\Mail::where('to_character_id', $character->id)->where('claimed', false)->count(); @endphp
                        @if($unread > 0)
                            <div class="absolute -top-1 -right-4 w-6 h-6 bg-red-600 rounded-full flex items-center justify-center text-white text-xs font-bold shadow-lg animate-pulse">{{ $unread }}</div>
                        @endif
                    </div>
                </button>
            </div>

            {{-- ARMORSMITH (3 cols, 1 row) --}}
            <div class="col-span-3 row-span-1 relative group rounded-3xl overflow-hidden border border-stone-700/50 shadow-lg transition-all duration-300 hover:border-amber-500/80 hover:shadow-[0_0_20px_rgba(245,158,11,0.2)]"
                 x-data="{ tiltX: 0, tiltY: 0 }" @mousemove="const r = $el.getBoundingClientRect(); tiltX = ((($event.clientY - r.top)/r.height)-0.5)*-12; tiltY = ((($event.clientX - r.left)/r.width)-0.5)*12;" @mouseleave="tiltX = 0; tiltY = 0" :style="`transform: perspective(1000px) rotateX(${tiltX}deg) rotateY(${tiltY}deg) scale(${tiltX !== 0 || tiltY !== 0 ? 1.02 : 1}); transition: transform 0.1s ease-out;`">
                <button wire:click="goTo('armorsmith')" @click="travelingTo = 'Zbrojmistrz'; $dispatch('play-audio', { type: 'shop' })" @mouseenter="$dispatch('play-audio', { type: 'hover' })" class="w-full h-full text-left relative">
                    <div class="absolute inset-0 bg-cover bg-center transition-transform duration-500 group-hover:scale-110 opacity-60 mix-blend-luminosity" style="background-image: url('{{ asset('img/armormaster.png') }}');"></div>
                    <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/60 to-black/20"></div>
                    <div class="absolute inset-0 p-4 flex flex-col justify-end">
                        <div wire:loading wire:target="goTo('armorsmith')" class="mb-1"><svg class="animate-spin h-6 w-6 text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg></div>
                        <h3 class="text-xl font-bold text-amber-300 medieval-font drop-shadow">Zbrojmistrz</h3>
                        <p class="text-amber-200/70 text-xs font-medium mt-0.5">Pancerze i ochrona</p>
                    </div>
                </button>
            </div>

            {{-- WEAPONSMITH (3 cols, 1 row) --}}
            <div class="col-span-3 row-span-1 relative group rounded-3xl overflow-hidden border border-stone-700/50 shadow-lg transition-all duration-300 hover:border-amber-500/80 hover:shadow-[0_0_20px_rgba(245,158,11,0.2)] {{ $gameStage == 17 ? 'animate-[pulse_1.5s_ease-in-out_infinite] ring-4 ring-amber-500 z-10' : '' }}"
                 x-data="{ tiltX: 0, tiltY: 0 }" @mousemove="const r = $el.getBoundingClientRect(); tiltX = ((($event.clientY - r.top)/r.height)-0.5)*-12; tiltY = ((($event.clientX - r.left)/r.width)-0.5)*12;" @mouseleave="tiltX = 0; tiltY = 0" :style="`transform: perspective(1000px) rotateX(${tiltX}deg) rotateY(${tiltY}deg) scale(${tiltX !== 0 || tiltY !== 0 ? 1.02 : 1}); transition: transform 0.1s ease-out;`">
                <button wire:click="goTo('weaponsmith')" @click="travelingTo = 'Brońmistrz'; $dispatch('play-audio', { type: 'shop' })" @mouseenter="$dispatch('play-audio', { type: 'hover' })" class="w-full h-full text-left relative">
                    <div class="absolute inset-0 bg-cover bg-center transition-transform duration-500 group-hover:scale-110 opacity-60 mix-blend-luminosity" style="background-image: url('{{ asset('img/swordmaster.png') }}');"></div>
                    <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/60 to-black/20"></div>
                    <div class="absolute inset-0 p-4 flex flex-col justify-end">
                        <div wire:loading wire:target="goTo('weaponsmith')" class="mb-1"><svg class="animate-spin h-6 w-6 text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg></div>
                        <h3 class="text-xl font-bold text-amber-300 medieval-font drop-shadow">Brońmistrz</h3>
                        <p class="text-amber-200/70 text-xs font-medium mt-0.5">Miecze i uzbrojenie</p>
                    </div>
                </button>
            </div>

            {{-- QUESTS (6 cols, 1 row) --}}
            <div class="col-span-6 row-span-1 relative group rounded-3xl overflow-hidden border border-teal-900/50 shadow-lg transition-all duration-300 hover:border-teal-500/80 hover:shadow-[0_0_20px_rgba(20,184,166,0.2)] {{ $gameStage == 23 ? 'animate-[pulse_1.5s_ease-in-out_infinite] ring-4 ring-amber-500 z-10' : '' }}"
                 x-data="{ tiltX: 0, tiltY: 0 }" @mousemove="const r = $el.getBoundingClientRect(); tiltX = ((($event.clientY - r.top)/r.height)-0.5)*-12; tiltY = ((($event.clientX - r.left)/r.width)-0.5)*12;" @mouseleave="tiltX = 0; tiltY = 0" :style="`transform: perspective(1000px) rotateX(${tiltX}deg) rotateY(${tiltY}deg) scale(${tiltX !== 0 || tiltY !== 0 ? 1.02 : 1}); transition: transform 0.1s ease-out;`">
                <button wire:click="goTo('quests')" @click="travelingTo = 'Tablica Wyzwań'; $dispatch('play-audio', { type: 'tab' })" @mouseenter="$dispatch('play-audio', { type: 'hover' })" class="w-full h-full flex items-center justify-start p-6 relative">
                    <div class="absolute inset-0 bg-cover bg-center transition-transform duration-500 group-hover:scale-110 opacity-55 mix-blend-luminosity" style="background-image: url('{{ asset('img/quest-board-bg.png') }}');"></div>
                    <div class="absolute inset-0 bg-gradient-to-r from-black/90 via-black/60 to-black/30"></div>
                    @if(isset($completedQuestsCount) && $completedQuestsCount > 0)
                        <div class="absolute top-4 right-4 z-20 w-7 h-7 bg-amber-500 rounded-full inline-flex items-center justify-center text-slate-900 text-sm font-black leading-none shadow-[0_0_10px_rgba(245,158,11,0.8)] animate-bounce select-none pb-[1px]">!</div>
                    @endif
                    <div class="relative z-10 text-left">
                        <div wire:loading wire:target="goTo('quests')" class="mb-1"><svg class="animate-spin h-8 w-8 text-teal-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg></div>
                        <h3 class="text-2xl font-bold text-teal-400 medieval-font group-hover:text-teal-300 drop-shadow-md">Tablica Wyzwań</h3>
                        <p class="text-teal-200/70 text-sm font-medium">Misje, zadania i zlecenia</p>
                    </div>
                </button>
            </div>

            {{-- WITCH (3 cols, 1 row) --}}
            <div class="col-span-3 row-span-1 relative group rounded-3xl overflow-hidden border border-fuchsia-900/50 shadow-lg transition-all duration-300 hover:border-fuchsia-500/80 hover:shadow-[0_0_20px_rgba(217,70,239,0.2)]"
                 x-data="{ tiltX: 0, tiltY: 0 }" @mousemove="const r = $el.getBoundingClientRect(); tiltX = ((($event.clientY - r.top)/r.height)-0.5)*-12; tiltY = ((($event.clientX - r.left)/r.width)-0.5)*12;" @mouseleave="tiltX = 0; tiltY = 0" :style="`transform: perspective(1000px) rotateX(${tiltX}deg) rotateY(${tiltY}deg) scale(${tiltX !== 0 || tiltY !== 0 ? 1.02 : 1}); transition: transform 0.1s ease-out;`">
                <button wire:click="goTo('witch')" @click="travelingTo = 'Wiedźma'; $dispatch('play-audio', { type: 'shop' })" @mouseenter="$dispatch('play-audio', { type: 'hover' })" class="w-full h-full flex flex-col items-center justify-center p-4 relative">
                    <div class="absolute inset-0 bg-cover bg-center transition-transform duration-500 group-hover:scale-110 opacity-55 mix-blend-luminosity" style="background-image: url('{{ asset('img/witch-bg.png') }}');"></div>
                    <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/60 to-black/30"></div>
                    <div class="relative z-10 text-center">
                        <div wire:loading wire:target="goTo('witch')" class="mb-1"><svg class="animate-spin h-7 w-7 text-fuchsia-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg></div>
                        <h3 class="text-xl font-bold text-fuchsia-300 medieval-font group-hover:text-fuchsia-200 drop-shadow">Wiedźma</h3>
                        <p class="text-fuchsia-200/70 text-xs font-medium mt-0.5">Kupuj i wytwarzaj mikstury</p>
                    </div>
                </button>
            </div>

            {{-- WIZARD (3 cols, 1 row) --}}
            <div class="col-span-3 row-span-1 relative group rounded-3xl overflow-hidden border border-indigo-900/50 shadow-lg transition-all duration-300 hover:border-indigo-500/80 hover:shadow-[0_0_20px_rgba(99,102,241,0.2)]"
                 x-data="{ tiltX: 0, tiltY: 0 }" @mousemove="const r = $el.getBoundingClientRect(); tiltX = ((($event.clientY - r.top)/r.height)-0.5)*-12; tiltY = ((($event.clientX - r.left)/r.width)-0.5)*12;" @mouseleave="tiltX = 0; tiltY = 0" :style="`transform: perspective(1000px) rotateX(${tiltX}deg) rotateY(${tiltY}deg) scale(${tiltX !== 0 || tiltY !== 0 ? 1.02 : 1}); transition: transform 0.1s ease-out;`">
                <button wire:click="goTo('wizard')" @click="travelingTo = 'Czarodziej'; $dispatch('play-audio', { type: 'shop' })" @mouseenter="$dispatch('play-audio', { type: 'hover' })" class="w-full h-full flex flex-col items-center justify-center p-4 relative">
                    <div class="absolute inset-0 bg-cover bg-center transition-transform duration-500 group-hover:scale-110 opacity-55 mix-blend-luminosity" style="background-image: url('{{ asset('img/wizard-bg.png') }}');"></div>
                    <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/60 to-black/30"></div>
                    <div class="relative z-10 text-center">
                        <div wire:loading wire:target="goTo('wizard')" class="mb-1"><svg class="animate-spin h-7 w-7 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg></div>
                        <h3 class="text-xl font-bold text-indigo-300 medieval-font group-hover:text-indigo-200 drop-shadow">Czarodziej</h3>
                        <p class="text-indigo-200/70 text-xs font-medium mt-0.5">Zaklinanie przedmioty i czary</p>
                    </div>
                </button>
            </div>

            {{-- WARLOCK (3 cols, 1 row) --}}
            <div class="col-span-3 row-span-1 relative group rounded-3xl overflow-hidden border border-green-900/50 shadow-lg transition-all duration-300 hover:border-green-500/80 hover:shadow-[0_0_20px_rgba(34,197,94,0.2)]"
                 x-data="{ tiltX: 0, tiltY: 0 }" @mousemove="const r = $el.getBoundingClientRect(); tiltX = ((($event.clientY - r.top)/r.height)-0.5)*-12; tiltY = ((($event.clientX - r.left)/r.width)-0.5)*12;" @mouseleave="tiltX = 0; tiltY = 0" :style="`transform: perspective(1000px) rotateX(${tiltX}deg) rotateY(${tiltY}deg) scale(${tiltX !== 0 || tiltY !== 0 ? 1.02 : 1}); transition: transform 0.1s ease-out;`">
                <button wire:click="goTo('warlock')" @click="travelingTo = 'Czarnoksiężnik'; $dispatch('play-audio', { type: 'shop' })" @mouseenter="$dispatch('play-audio', { type: 'hover' })" class="w-full h-full flex flex-col items-center justify-center p-4 relative">
                    <div class="absolute inset-0 bg-cover bg-center transition-transform duration-500 group-hover:scale-110 opacity-55 mix-blend-luminosity" style="background-image: url('{{ asset('img/warlock-bg.png') }}');"></div>
                    <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/60 to-black/30"></div>
                    <div class="relative z-10 text-center">
                        <div wire:loading wire:target="goTo('warlock')" class="mb-1"><svg class="animate-spin h-7 w-7 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg></div>
                        <h3 class="text-xl font-bold text-green-300 medieval-font group-hover:text-green-200 drop-shadow">Czarnoksiężnik</h3>
                        <p class="text-green-200/70 text-xs font-medium mt-0.5">Rytuały i mroczna magia</p>
                    </div>
                </button>
            </div>

            {{-- GUILD (3 cols, 1 row) --}}
            <div class="col-span-3 row-span-1 relative group rounded-3xl overflow-hidden border border-red-900/50 shadow-lg transition-all duration-300 hover:border-red-500/80 hover:shadow-[0_0_20px_rgba(239,68,68,0.2)]"
                 x-data="{ tiltX: 0, tiltY: 0 }" @mousemove="const r = $el.getBoundingClientRect(); tiltX = ((($event.clientY - r.top)/r.height)-0.5)*-12; tiltY = ((($event.clientX - r.left)/r.width)-0.5)*12;" @mouseleave="tiltX = 0; tiltY = 0" :style="`transform: perspective(1000px) rotateX(${tiltX}deg) rotateY(${tiltY}deg) scale(${tiltX !== 0 || tiltY !== 0 ? 1.02 : 1}); transition: transform 0.1s ease-out;`">
                <button wire:click="goTo('guild')" @click="travelingTo = 'Gildia'; $dispatch('play-audio', { type: 'hover' })" @mouseenter="$dispatch('play-audio', { type: 'hover' })" class="w-full h-full flex flex-col items-center justify-center p-4 relative">
                    <div class="absolute inset-0 bg-cover bg-center transition-transform duration-500 group-hover:scale-110 opacity-55 mix-blend-luminosity" style="background-image: url('{{ asset('img/guild-bg.png') }}');"></div>
                    <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/60 to-black/30"></div>
                    <div class="relative z-10 text-center">
                        <div wire:loading wire:target="goTo('guild')" class="mb-1"><svg class="animate-spin h-7 w-7 text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg></div>
                        <h3 class="text-xl font-bold text-red-400 medieval-font group-hover:text-red-300 drop-shadow">Gildia</h3>
                        <p class="text-red-200/70 text-xs font-medium mt-0.5">Siedziba i sojusze</p>
                    </div>
                </button>
            </div>
        </div>

        {{-- Mobile Layout (Horizontal Carousel & Main Actions) --}}
        <div class="lg:hidden mt-4 flex flex-col space-y-6 relative z-10">
            {{-- Main Actions --}}
            <div class="grid grid-cols-2 gap-3">
                <button wire:click="goTo('adventure')" @click="travelingTo = 'Wyprawy'; $dispatch('play-audio', { type: 'combat' })"
                    class="col-span-2 relative overflow-hidden rounded-2xl border-2 border-green-700/80 shadow-xl p-6 {{ $gameStage == 9 ? 'animate-[pulse_1.5s_ease-in-out_infinite] ring-2 ring-amber-500' : '' }}" wire:loading.attr="disabled">
                    <div class="absolute inset-0 bg-cover bg-center opacity-50 mix-blend-luminosity" style="background-image: url('{{ asset('img/adventure-background.png') }}');"></div>
                    <div class="absolute inset-0 bg-gradient-to-r from-green-950 via-green-900/80 to-transparent"></div>
                    <div class="relative flex items-center justify-between">
                        <div class="text-left">
                            <div class="font-bold text-3xl text-green-400 medieval-font mb-1">Przygoda</div>
                            <div class="text-green-200/70 text-sm font-medium">Wyrusz w nieznane</div>
                        </div>
                    </div>
                </button>

                <button wire:click="goTo('profile')" @click="travelingTo = 'Profil'; $dispatch('play-audio', { type: 'profile' })"
                    class="relative overflow-hidden rounded-2xl border border-blue-800/80 shadow-lg p-6 flex flex-col items-center justify-center min-h-[120px] {{ in_array($gameStage, [5, 14]) ? 'animate-[pulse_1.5s_ease-in-out_infinite] ring-2 ring-amber-500' : '' }}" wire:loading.attr="disabled">
                    <div class="absolute inset-0 bg-cover bg-center opacity-60 mix-blend-luminosity" style="background-image: url('{{ asset('img/profile-bg.png') }}');"></div>
                    <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/60 to-black/30"></div>
                    <div class="relative z-10 font-bold text-blue-300 medieval-font text-center text-xl">Postać</div>
                </button>

                <button wire:click="goTo('quests')" @click="travelingTo = 'Tablica Wyzwań'; $dispatch('play-audio', { type: 'tab' })"
                    class="relative overflow-hidden rounded-2xl border border-teal-800/80 shadow-lg p-6 flex flex-col items-center justify-center min-h-[120px] {{ $gameStage == 23 ? 'animate-[pulse_1.5s_ease-in-out_infinite] ring-2 ring-amber-500' : '' }}" wire:loading.attr="disabled">
                    <div class="absolute inset-0 bg-cover bg-center opacity-60 mix-blend-luminosity" style="background-image: url('{{ asset('img/quest-board-bg.png') }}');"></div>
                    <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/60 to-black/30"></div>
                    @if(isset($completedQuestsCount) && $completedQuestsCount > 0)
                        <div class="absolute top-2 right-4 w-6 h-6 bg-amber-500 rounded-full flex items-center justify-center text-slate-900 text-sm font-bold shadow-lg animate-bounce z-20">!</div>
                    @endif
                    <div class="relative z-10 font-bold text-teal-300 medieval-font text-center text-xl">Wyzwania</div>
                </button>
            </div>

            {{-- Carousel Title --}}
            <div class="flex items-center space-x-4 px-2 mt-2">
                <div class="h-px bg-gradient-to-r from-transparent to-amber-700/50 flex-grow"></div>
                <h3 class="text-amber-500 medieval-font text-2xl drop-shadow-md">Dzielnica Handlowa</h3>
                <div class="h-px bg-gradient-to-l from-transparent to-amber-700/50 flex-grow"></div>
            </div>

            {{-- Shop Grid --}}
            <div class="grid grid-cols-2 gap-4 pb-6">

                {{-- Armorsmith --}}
                <div class="col-span-1">
                    <button wire:click="goTo('armorsmith')" @click="travelingTo = 'Zbrojmistrz'" class="w-full h-36 rounded-3xl border-2 border-amber-800/50 overflow-hidden relative shadow-lg" wire:loading.attr="disabled">
                        <div class="absolute inset-0 bg-cover bg-center opacity-55 mix-blend-luminosity" style="background-image: url('{{ asset('img/armormaster.png') }}');"></div>
                        <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent"></div>
                        <div class="absolute bottom-0 w-full p-4 text-center">
                            <div class="font-bold text-amber-300 medieval-font text-lg">Zbrojmistrz</div>
                        </div>
                    </button>
                </div>

                {{-- Weaponsmith --}}
                <div class="col-span-1">
                    <button wire:click="goTo('weaponsmith')" @click="travelingTo = 'Brońmistrz'" class="w-full h-36 rounded-3xl border-2 border-amber-800/50 overflow-hidden relative shadow-lg {{ $gameStage == 17 ? 'animate-[pulse_1.5s_ease-in-out_infinite] ring-2 ring-amber-500' : '' }}" wire:loading.attr="disabled">
                        <div class="absolute inset-0 bg-cover bg-center opacity-55 mix-blend-luminosity" style="background-image: url('{{ asset('img/swordmaster.png') }}');"></div>
                        <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent"></div>
                        <div class="absolute bottom-0 w-full p-4 text-center">
                            <div class="font-bold text-amber-300 medieval-font text-lg">Brońmistrz</div>
                        </div>
                    </button>
                </div>

                {{-- Market --}}
                <div class="col-span-1">
                    <button wire:click="goTo('market')" @click="travelingTo = 'Targowisko'" class="w-full h-36 rounded-3xl border-2 border-yellow-800/50 overflow-hidden relative shadow-lg" wire:loading.attr="disabled">
                        <div class="absolute inset-0 bg-cover bg-center opacity-55 mix-blend-luminosity" style="background-image: url('{{ asset('img/market-bg.png') }}');"></div>
                        <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent"></div>
                        <div class="absolute bottom-0 w-full p-4 text-center">
                            <div class="font-bold text-yellow-400 medieval-font text-lg">Targowisko</div>
                        </div>
                    </button>
                </div>

                {{-- Arena --}}
                <div class="col-span-1">
                    <button wire:click="goTo('arena')" @click="travelingTo = 'Arena'" class="w-full h-36 rounded-3xl border-2 border-orange-800/50 overflow-hidden relative shadow-lg" wire:loading.attr="disabled">
                        <div class="absolute inset-0 bg-cover bg-center opacity-55 mix-blend-luminosity" style="background-image: url('{{ asset('img/arena-bg.png') }}');"></div>
                        <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent"></div>
                        <div class="absolute bottom-0 w-full p-4 text-center">
                            <div class="font-bold text-orange-400 medieval-font text-lg">Arena</div>
                        </div>
                    </button>
                </div>

                {{-- Witch --}}
                <div class="col-span-1">
                    <button wire:click="goTo('witch')" @click="travelingTo = 'Wiedźma'" class="w-full h-36 rounded-3xl border-2 border-fuchsia-800/50 overflow-hidden relative shadow-lg" wire:loading.attr="disabled">
                        <div class="absolute inset-0 bg-cover bg-center opacity-55 mix-blend-luminosity" style="background-image: url('{{ asset('img/witch-bg.png') }}');"></div>
                        <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent"></div>
                        <div class="absolute bottom-0 w-full p-4 text-center">
                            <div class="font-bold text-fuchsia-400 medieval-font text-lg">Wiedźma</div>
                        </div>
                    </button>
                </div>

                {{-- Wizard --}}
                <div class="col-span-1">
                    <button wire:click="goTo('wizard')" @click="travelingTo = 'Czarodziej'" class="w-full h-36 rounded-3xl border-2 border-indigo-800/50 overflow-hidden relative shadow-lg" wire:loading.attr="disabled">
                        <div class="absolute inset-0 bg-cover bg-center opacity-55 mix-blend-luminosity" style="background-image: url('{{ asset('img/wizard-bg.png') }}');"></div>
                        <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent"></div>
                        <div class="absolute bottom-0 w-full p-4 text-center">
                            <div class="font-bold text-indigo-400 medieval-font text-lg">Czarodziej</div>
                        </div>
                    </button>
                </div>

                {{-- Warlock --}}
                <div class="col-span-1">
                    <button wire:click="goTo('warlock')" @click="travelingTo = 'Czarnoksiężnik'" class="w-full h-36 rounded-3xl border-2 border-green-800/50 overflow-hidden relative shadow-lg" wire:loading.attr="disabled">
                        <div class="absolute inset-0 bg-cover bg-center opacity-55 mix-blend-luminosity" style="background-image: url('{{ asset('img/warlock-bg.png') }}');"></div>
                        <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent"></div>
                        <div class="absolute bottom-0 w-full p-4 text-center">
                            <div class="font-bold text-green-400 medieval-font text-lg">Czarnoksiężnik</div>
                        </div>
                    </button>
                </div>

                {{-- Guild --}}
                <div class="col-span-1">
                    <button wire:click="goTo('guild')" @click="travelingTo = 'Gildia'" class="w-full h-36 rounded-3xl border-2 border-red-800/50 overflow-hidden relative shadow-lg" wire:loading.attr="disabled">
                        <div class="absolute inset-0 bg-cover bg-center opacity-55 mix-blend-luminosity" style="background-image: url('{{ asset('img/guild-bg.png') }}');"></div>
                        <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent"></div>
                        <div class="absolute bottom-0 w-full p-4 text-center">
                            <div class="font-bold text-red-500 medieval-font text-lg">Gildia</div>
                        </div>
                    </button>
                </div>

                {{-- Mailbox --}}
                <div class="col-span-1">
                    <button wire:click="goTo('mailbox')" @click="travelingTo = 'Poczta'" class="w-full h-36 rounded-3xl border-2 border-sky-800/50 overflow-hidden relative shadow-lg" wire:loading.attr="disabled">
                        <div class="absolute inset-0 bg-cover bg-center opacity-55 mix-blend-luminosity" style="background-image: url('{{ asset('img/mailbox-bg.png') }}');"></div>
                        <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent"></div>
                        <div class="absolute bottom-0 w-full p-4 text-center relative">
                            @if(isset($unread) && $unread > 0)
                                <div class="absolute top-2 right-4 w-6 h-6 bg-red-600 rounded-full flex items-center justify-center text-white text-xs font-bold shadow-lg z-20">{{ $unread }}</div>
                            @endif
                            <div class="font-bold text-sky-400 medieval-font text-lg">Poczta</div>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

