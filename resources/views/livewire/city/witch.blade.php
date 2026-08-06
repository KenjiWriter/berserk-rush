<div class="min-h-screen text-amber-100 relative overflow-hidden select-none" style="background: radial-gradient(circle at 50% 0%, #1e1b4b 0%, #0f172a 60%, #020617 100%); font-family: 'Cinzel', serif;">
    @php
        $gameStage = auth()->user()->game_stage;
    @endphp

    @if($gameStage == 31)
        <livewire:global.tutorial-overlay :step="32" />
    @elseif($gameStage == 33)
        <livewire:global.tutorial-overlay :step="34" :rewardXp="200" :rewardGold="250" />
    @elseif($gameStage == 42)
        <livewire:global.tutorial-overlay :step="43" />
    @endif

    <!-- Static Background Image with Dark & Purple Vignette Overlay -->
    <div class="absolute inset-0 bg-cover bg-center opacity-35 mix-blend-luminosity pointer-events-none" style="background-image: url('{{ asset('img/witch-bg.png') }}');"></div>
    <div class="absolute inset-0 bg-gradient-to-b from-purple-950/70 via-slate-950/85 to-black pointer-events-none"></div>

    <!-- Ambient Magical Glow Effects -->
    <div class="absolute top-0 left-1/4 w-[500px] h-[500px] bg-purple-600/15 rounded-full filter blur-3xl opacity-60 animate-pulse pointer-events-none"></div>
    <div class="absolute bottom-1/4 right-1/4 w-[500px] h-[500px] bg-fuchsia-600/15 rounded-full filter blur-3xl opacity-60 animate-pulse pointer-events-none" style="animation-delay: 2s;"></div>

    <div class="relative container mx-auto px-4 py-6 sm:py-8 min-h-screen z-10 max-w-7xl flex flex-col">
        
        {{-- Header Section --}}
        <div class="flex flex-col md:flex-row items-center justify-between gap-4 mb-6 sm:mb-8 border-b-2 border-purple-900/60 pb-4 sm:pb-6 bg-gradient-to-b from-purple-950/90 via-slate-950/80 to-transparent p-4 rounded-2xl shadow-2xl backdrop-blur-md text-center sm:text-left">
            <div class="flex flex-col sm:flex-row items-center gap-3 sm:gap-4">
                <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl bg-gradient-to-b from-fuchsia-800 via-purple-950 to-black border-2 border-fuchsia-500 flex items-center justify-center text-2xl sm:text-3xl text-fuchsia-300 shadow-[0_0_20px_rgba(217,70,239,0.4)] shrink-0">
                    <i class="fa-solid fa-mortar-pestle"></i>
                </div>
                <div>
                    <h1 class="text-2xl sm:text-4xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-fuchsia-300 via-purple-300 to-amber-200 medieval-font tracking-wider uppercase drop-shadow">
                        Chata Wiedźmy
                    </h1>
                    <p class="text-xs sm:text-sm text-purple-300/70 font-sans tracking-wide">
                        Magiczne eliksiry, starożytne receptury i zakazane wywary
                    </p>
                </div>
            </div>
            
            <div class="flex flex-wrap items-center justify-center sm:justify-end gap-3 sm:gap-4 w-full md:w-auto">
                {{-- Gold Badge --}}
                <div class="bg-gradient-to-b from-stone-950 via-stone-900 to-black border-2 border-amber-600/80 px-4 py-2 rounded-xl shadow-[inset_0_2px_4px_rgba(0,0,0,0.8),0_0_15px_rgba(245,158,11,0.2)] flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-amber-950/80 border border-amber-500/60 flex items-center justify-center text-amber-400 text-base shrink-0">
                        <i class="fa-solid fa-coins"></i>
                    </div>
                    <div class="text-left">
                        <span class="text-[9px] text-amber-400/80 font-extrabold uppercase tracking-widest block leading-none">POSIADANE ZŁOTO</span>
                        <span class="text-lg sm:text-xl font-black text-amber-300 drop-shadow">{{ number_format($character->gold, 0, ',', ' ') }}</span>
                    </div>
                </div>

                {{-- Back Button --}}
                <button wire:click="backToHub" @click="$dispatch('location-leave', { text: 'Podróż do Miasta...', icon: 'fa-solid fa-archway', url: '{{ route('city.hub', $character->id) }}' }); $dispatch('play-audio', { type: 'tab' })"
                    class="px-4 py-2.5 rounded-xl bg-gradient-to-b from-slate-800 via-slate-900 to-stone-950 text-amber-200 font-extrabold text-xs uppercase tracking-widest border-2 border-slate-700 hover:border-fuchsia-500 hover:text-fuchsia-200 shadow-[inset_0_1px_0_rgba(255,255,255,0.1),0_4px_10px_rgba(0,0,0,0.8)] transition-all duration-200 flex items-center gap-2 group cursor-pointer">
                    <i class="fa-solid fa-archway text-amber-400 group-hover:scale-110 transition-transform"></i>
                    <span>Powrót do Miasta</span>
                </button>
            </div>
        </div>

        {{-- Messages --}}
        @if($message)
            <div class="mb-6 mx-auto max-w-4xl w-full">
                <div class="p-4 rounded-xl backdrop-blur-md border shadow-xl {{ $messageType === 'success' ? 'bg-emerald-950/80 border-emerald-500/60 text-emerald-200 shadow-[0_0_20px_rgba(16,185,129,0.2)]' : 'bg-red-950/80 border-red-500/60 text-red-200 shadow-[0_0_20px_rgba(239,68,68,0.2)]' }} flex items-center gap-3 animate-[fade-in_0.3s_ease-out]">
                    <i class="fa-solid {{ $messageType === 'success' ? 'fa-sparkles text-emerald-400 text-xl' : 'fa-triangle-exclamation text-red-400 text-xl' }}"></i>
                    <span class="font-bold text-sm sm:text-base font-sans">{{ $message }}</span>
                </div>
            </div>
        @endif

        {{-- Navigation Tabs --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 sm:gap-4 mb-6 sm:mb-8 max-w-5xl mx-auto w-full">
            <button wire:click="switchTab('shop')" 
                class="relative px-3 sm:px-6 lg:px-8 py-2.5 sm:py-3 rounded-xl font-bold transition-all duration-300 overflow-hidden group border-2 medieval-font text-xs sm:text-base lg:text-lg tracking-wider cursor-pointer text-center flex items-center justify-center
                {{ $activeTab === 'shop' 
                    ? 'bg-gradient-to-r from-fuchsia-800 to-purple-800 border-fuchsia-400 text-white shadow-[0_0_25px_rgba(217,70,239,0.4)] scale-[1.02] sm:scale-105 z-10' 
                    : 'bg-stone-950/70 border-purple-900/60 text-purple-300 hover:bg-purple-900/50 hover:border-purple-500 hover:text-purple-100 backdrop-blur-md' }}">
                <div class="absolute inset-0 bg-gradient-to-t from-black/30 to-transparent"></div>
                <span class="relative flex flex-row items-center justify-center gap-1.5 sm:gap-2 leading-tight">
                    <i class="fa-solid fa-shop text-amber-400 text-sm sm:text-base shrink-0"></i>
                    <span class="truncate">Sklep Alchemiczny</span>
                </span>
            </button>
            
            <button wire:click="switchTab('crafting')" 
                class="relative px-3 sm:px-6 lg:px-8 py-2.5 sm:py-3 rounded-xl font-bold transition-all duration-300 overflow-hidden group border-2 medieval-font text-xs sm:text-base lg:text-lg tracking-wider cursor-pointer text-center flex items-center justify-center
                {{ $activeTab === 'crafting' 
                    ? 'bg-gradient-to-r from-emerald-800 to-teal-800 border-emerald-400 text-white shadow-[0_0_25px_rgba(16,185,129,0.4)] scale-[1.02] sm:scale-105 z-10' 
                    : 'bg-stone-950/70 border-purple-900/60 text-purple-300 hover:bg-emerald-950/50 hover:border-emerald-500 hover:text-emerald-100 backdrop-blur-md' }}">
                <div class="absolute inset-0 bg-gradient-to-t from-black/30 to-transparent"></div>
                <span class="relative flex flex-row items-center justify-center gap-1.5 sm:gap-2 leading-tight">
                    <i class="fa-solid fa-flask-vial text-emerald-400 text-sm sm:text-base shrink-0"></i>
                    <span class="truncate">Warzenie Mikstur</span>
                </span>
            </button>

            <button wire:click="switchTab('enchant')"
                class="relative px-3 sm:px-6 lg:px-8 py-2.5 sm:py-3 rounded-xl font-bold transition-all duration-300 overflow-hidden group border-2 medieval-font text-xs sm:text-base lg:text-lg tracking-wider cursor-pointer text-center flex items-center justify-center
                {{ $activeTab === 'enchant'
                    ? 'bg-gradient-to-r from-indigo-800 to-purple-800 border-indigo-400 text-white shadow-[0_0_25px_rgba(99,102,241,0.4)] scale-[1.02] sm:scale-105 z-10'
                    : 'bg-stone-950/70 border-purple-900/60 text-purple-300 hover:bg-indigo-950/50 hover:border-indigo-500 hover:text-indigo-100 backdrop-blur-md' }} {{ $gameStage == 31 ? 'ring-4 ring-amber-500 animate-pulse' : '' }}">
                <div class="absolute inset-0 bg-gradient-to-t from-black/30 to-transparent"></div>
                <span class="relative flex flex-row items-center justify-center gap-1.5 sm:gap-2 leading-tight">
                    <i class="fa-solid fa-hat-wizard text-indigo-300 text-sm sm:text-base shrink-0"></i>
                    <span class="truncate">Zaczarowanie</span>
                </span>
            </button>

            <button wire:click="switchTab('mirror')"
                class="relative px-3 sm:px-6 lg:px-8 py-2.5 sm:py-3 rounded-xl font-bold transition-all duration-300 overflow-hidden group border-2 medieval-font text-xs sm:text-base lg:text-lg tracking-wider cursor-pointer text-center flex items-center justify-center
                {{ $activeTab === 'mirror'
                    ? 'bg-gradient-to-r from-purple-800 to-fuchsia-800 border-purple-400 text-white shadow-[0_0_25px_rgba(168,85,247,0.4)] scale-[1.02] sm:scale-105 z-10'
                    : 'bg-stone-950/70 border-purple-900/60 text-purple-300 hover:bg-purple-950/50 hover:border-purple-500 hover:text-purple-100 backdrop-blur-md' }} {{ in_array($gameStage, [41, 42]) ? 'ring-4 ring-amber-500 animate-pulse' : '' }}">
                <div class="absolute inset-0 bg-gradient-to-t from-black/30 to-transparent"></div>
                <span class="relative flex flex-row items-center justify-center gap-1.5 sm:gap-2 leading-tight">
                    <i class="fa-solid fa-gem text-purple-300 text-sm sm:text-base shrink-0"></i>
                    <span class="truncate">Lustro</span>
                </span>
            </button>
        </div>

        {{-- Main Content Area --}}
        <div class="w-full flex-1">
            @if($activeTab === 'shop')
                <div class="space-y-8 animate-[fade-in_0.4s_ease-out]">
                    
                    {{-- Special Potion Highlight --}}
                    <div class="relative bg-gradient-to-r from-fuchsia-950/90 via-purple-950/80 to-slate-950/90 border-2 border-fuchsia-500/50 rounded-2xl p-6 md:p-8 shadow-[0_0_35px_rgba(217,70,239,0.25)] backdrop-blur-xl overflow-hidden group">
                        <div class="absolute top-0 right-0 w-80 h-80 bg-fuchsia-600/15 rounded-full filter blur-3xl opacity-50 group-hover:opacity-100 transition-opacity duration-700 pointer-events-none"></div>
                        
                        <div class="relative flex flex-col md:flex-row items-center justify-between gap-6">
                            <div class="flex-1 text-center md:text-left">
                                <div class="inline-flex items-center gap-2 bg-fuchsia-950/90 border border-fuchsia-400/60 text-fuchsia-300 text-xs font-extrabold uppercase tracking-widest px-3 py-1 rounded-full mb-3 shadow-lg">
                                    <i class="fa-solid fa-wand-magic-sparkles text-amber-400"></i>
                                    Oferta Specjalna Wiedźmy
                                </div>
                                <h3 class="text-2xl sm:text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-fuchsia-200 via-amber-200 to-purple-200 medieval-font mb-2 drop-shadow">
                                    Eliksir Wiedzy Absolutnej
                                </h3>
                                <p class="text-fuchsia-200/80 mb-3 font-sans text-sm sm:text-base leading-relaxed max-w-2xl italic">
                                    "Zbyt dużo tej mikstury spali twój umysł, wędrowcze. Dam ci tylko jedną porcję na dobę."
                                </p>
                                <div class="inline-flex items-center gap-2 bg-amber-950/40 border border-amber-500/40 px-3 py-1.5 rounded-lg text-amber-300 font-semibold text-xs sm:text-sm">
                                    <i class="fa-solid fa-circle-up text-amber-400"></i>
                                    <span>+20% zdobywanego doświadczenia z potworów przez godzinę</span>
                                </div>
                            </div>
                            
                            <div class="flex flex-col items-center justify-center bg-black/50 p-5 rounded-2xl border border-fuchsia-500/30 backdrop-blur-md min-w-[250px] shadow-2xl">
                                <div class="text-amber-300 font-extrabold text-xl mb-4 flex items-center gap-2 drop-shadow-md">
                                    <i class="fa-solid fa-coins text-amber-400"></i>
                                    <span>100 000 Złota</span>
                                </div>
                                @if($canBuySpecial)
                                    <button wire:click="buySpecialExpPotion" wire:loading.attr="disabled" 
                                        class="w-full relative overflow-hidden bg-gradient-to-r from-fuchsia-600 via-purple-600 to-fuchsia-700 hover:from-fuchsia-500 hover:to-purple-500 text-white font-extrabold py-3 px-6 rounded-xl shadow-[0_0_20px_rgba(217,70,239,0.5)] border border-fuchsia-400/60 transition-all duration-300 transform hover:scale-105 active:scale-95 medieval-font tracking-wider cursor-pointer">
                                        <span wire:loading.remove wire:target="buySpecialExpPotion" class="flex items-center justify-center gap-2">
                                            <i class="fa-solid fa-cart-shopping"></i>
                                            Kup Eliksir
                                        </span>
                                        <span wire:loading wire:target="buySpecialExpPotion" class="flex items-center justify-center gap-2">
                                            <i class="fa-solid fa-spinner animate-spin text-fuchsia-300"></i>
                                            Warzenie...
                                        </span>
                                    </button>
                                @else
                                    <div class="w-full text-center">
                                        <div class="text-xs text-fuchsia-300 font-extrabold mb-1.5 uppercase tracking-widest flex items-center justify-center gap-1.5">
                                            <i class="fa-solid fa-clock text-fuchsia-400"></i>
                                            Kolejna porcja za:
                                        </div>
                                        <div class="text-white font-mono text-lg font-bold bg-black/60 py-2 px-4 rounded-xl border border-fuchsia-500/40 shadow-inner"
                                             x-data="{ 
                                                end: new Date('{{ $specialCooldown->toIso8601String() }}').getTime(),
                                                timeLeft: '',
                                                update() {
                                                    let diff = this.end - new Date().getTime();
                                                    if(diff <= 0) { this.timeLeft = 'Gotowe!'; return; }
                                                    let h = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                                                    let m = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                                                    let s = Math.floor((diff % (1000 * 60)) / 1000);
                                                    this.timeLeft = (h < 10 ? '0' + h : h) + 'h ' + (m < 10 ? '0' + m : m) + 'm ' + (s < 10 ? '0' + s : s) + 's';
                                                }
                                             }"
                                             x-init="update(); setInterval(() => update(), 1000)"
                                             x-text="timeLeft">
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Regular Shop Grid --}}
                    <div>
                        <div class="flex items-center gap-4 mb-6">
                            <div class="h-px bg-gradient-to-r from-transparent via-purple-500/50 to-transparent flex-1"></div>
                            <h3 class="text-xl sm:text-2xl font-extrabold text-purple-200 medieval-font tracking-widest uppercase flex items-center gap-2">
                                <i class="fa-solid fa-boxes-stacked text-purple-400"></i>
                                Półki Sklepowe
                            </h3>
                            <div class="h-px bg-gradient-to-r from-transparent via-purple-500/50 to-transparent flex-1"></div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                            @forelse($shopItems as $mi)
                                <div class="bg-gradient-to-b from-stone-950/80 to-purple-950/40 border border-purple-800/40 rounded-2xl p-5 shadow-xl backdrop-blur-md flex flex-col justify-between transition-all duration-300 hover:bg-purple-950/60 hover:border-fuchsia-500/60 hover:shadow-[0_0_25px_rgba(168,85,247,0.25)] hover:-translate-y-1">
                                    <div class="mb-4">
                                        <div class="flex justify-between items-start mb-3">
                                            <div class="w-12 h-12 rounded-xl bg-purple-950/90 border border-purple-500/40 flex items-center justify-center p-2 shadow-inner">
                                                @if($mi->template && $mi->template->icon)
                                                    <img src="{{ route('assets.items', ['filename' => $mi->template->icon]) }}" class="w-full h-full object-contain filter drop-shadow" alt="">
                                                @else
                                                    <i class="fa-solid fa-flask text-2xl text-purple-400"></i>
                                                @endif
                                            </div>
                                            @if($mi->is_limited)
                                                <span class="text-[11px] font-extrabold bg-red-950/90 text-red-300 px-2.5 py-1 rounded-lg border border-red-500/50 shadow-sm flex items-center gap-1">
                                                    <i class="fa-solid fa-box"></i>
                                                    Limit: {{ $mi->max_quantity - $mi->sold_quantity }}
                                                </span>
                                            @endif
                                        </div>
                                        <h4 class="text-lg font-bold text-amber-200 medieval-font mb-1.5">{{ $mi->template->name }}</h4>
                                        <p class="text-xs sm:text-sm text-purple-200/70 font-sans min-h-[38px] leading-relaxed">{{ $mi->template->description ?? 'Tajemniczy wywar alchemiczny nieznanego pochodzenia.' }}</p>
                                    </div>
                                    
                                    <div class="flex items-center justify-between mt-auto pt-4 border-t border-purple-800/30">
                                        <div class="text-amber-300 font-extrabold flex items-center gap-1.5 text-sm bg-black/40 px-3 py-1.5 rounded-lg border border-amber-500/20 shadow-inner">
                                            <i class="fa-solid fa-coins text-amber-400"></i>
                                            <span>{{ number_format($shopPrices[$mi->id] ?? 0, 0, ',', ' ') }}</span>
                                        </div>
                                        <button wire:click="buyItem({{ $mi->id }})" wire:loading.attr="disabled" 
                                            class="bg-gradient-to-r from-purple-700 to-indigo-700 hover:from-purple-600 hover:to-indigo-600 text-white font-extrabold py-2 px-5 rounded-xl shadow-md border border-purple-400/50 transition-all duration-200 transform hover:scale-105 active:scale-95 text-xs uppercase tracking-wider cursor-pointer">
                                            <span wire:loading.remove wire:target="buyItem({{ $mi->id }})" class="flex items-center gap-1.5">
                                                <i class="fa-solid fa-cart-plus"></i>
                                                Kup
                                            </span>
                                            <span wire:loading wire:target="buyItem({{ $mi->id }})" class="flex items-center gap-1.5">
                                                <i class="fa-solid fa-spinner animate-spin"></i>
                                            </span>
                                        </button>
                                    </div>
                                </div>
                            @empty
                                <div class="col-span-full text-center py-12 bg-black/40 rounded-2xl border border-purple-900/40 backdrop-blur-sm">
                                    <i class="fa-solid fa-box-open text-4xl text-purple-400/40 mb-3 block"></i>
                                    <p class="text-purple-300/70 font-medium font-sans">Półki świecą pustkami. Wiedźma poszła zbierać składniki.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

            @elseif($activeTab === 'crafting')
                <div class="space-y-6 animate-[fade-in_0.4s_ease-out]">
                    <div class="text-center mb-8 bg-stone-950/80 border border-emerald-900/60 p-4 sm:p-6 rounded-2xl shadow-xl max-w-3xl mx-auto backdrop-blur-sm relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-emerald-600/10 rounded-full blur-3xl pointer-events-none"></div>
                        <h3 class="text-2xl sm:text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-emerald-300 via-teal-300 to-green-400 medieval-font mb-2 drop-shadow">
                            Kocioł Alchemiczny
                        </h3>
                        <p class="text-xs sm:text-sm text-emerald-200/80 font-sans leading-relaxed">
                            Wybierz recepturę i zgromadź odpowiednie składniki. Resztą zajmie się magia kociołka.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        @forelse($recipes as $recipe)
                            <div class="relative bg-gradient-to-b from-stone-950/80 via-emerald-950/30 to-black border border-emerald-800/40 rounded-2xl p-6 shadow-xl backdrop-blur-md transition-all duration-300 hover:bg-emerald-950/50 hover:border-emerald-500/60 hover:shadow-[0_0_25px_rgba(16,185,129,0.2)] flex flex-col justify-between">
                                <div>
                                    <div class="flex items-center gap-4 mb-5 border-b border-emerald-800/30 pb-4">
                                        <div class="w-14 h-14 rounded-xl bg-emerald-950/90 border border-emerald-500/40 flex items-center justify-center p-2.5 shadow-inner relative group shrink-0">
                                            @if(isset($recipe['result_icon']) && $recipe['result_icon'])
                                                <img src="{{ route('assets.items', ['filename' => $recipe['result_icon']]) }}" class="w-full h-full object-contain filter drop-shadow" alt="">
                                            @else
                                                <i class="fa-solid fa-flask-vial text-2xl text-emerald-400"></i>
                                            @endif
                                        </div>
                                        <div>
                                            <h4 class="text-xl font-bold text-emerald-100 medieval-font tracking-wide">{{ $recipe['result_name'] }}</h4>
                                            @if(isset($recipe['result_description']) && $recipe['result_description'])
                                                <p class="text-xs text-emerald-300/60 font-sans mt-0.5">{{ $recipe['result_description'] }}</p>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <div class="mb-5">
                                        <h5 class="text-[11px] text-emerald-400/80 uppercase tracking-widest font-extrabold mb-3 flex items-center gap-1.5">
                                            <i class="fa-solid fa-list-check"></i>
                                            Wymagane Składniki:
                                        </h5>
                                        <div class="space-y-2.5">
                                            @foreach($recipe['ingredients'] as $ing)
                                                <div class="relative group cursor-help hover:z-[100] flex items-center justify-between bg-black/40 p-2.5 rounded-xl border {{ $ing['ok'] ? 'border-emerald-500/40' : 'border-red-500/40' }} transition-colors">
                                                    <div class="flex items-center gap-3">
                                                        <div class="w-7 h-7 rounded-lg bg-stone-900 border border-stone-700 flex items-center justify-center p-1 shrink-0">
                                                            @if(isset($ing['icon']) && $ing['icon'])
                                                                <img src="{{ route('assets.items', ['filename' => $ing['icon']]) }}" class="w-full h-full object-contain" alt="">
                                                            @else
                                                                <i class="fa-solid fa-mortar-pestle text-xs text-stone-400"></i>
                                                            @endif
                                                        </div>
                                                        <span class="text-xs sm:text-sm font-semibold font-sans {{ $ing['ok'] ? 'text-emerald-200' : 'text-red-300' }}">{{ $ing['name'] }}</span>
                                                    </div>
                                                    <div class="flex items-center gap-2">
                                                        <span class="text-xs font-mono font-bold {{ $ing['ok'] ? 'text-emerald-400 bg-emerald-950/80 border-emerald-800/60' : 'text-red-400 bg-red-950/80 border-red-800/60' }} px-2 py-0.5 rounded-md border">
                                                            {{ $ing['owned'] }} / {{ $ing['required'] }}
                                                        </span>
                                                        @if($ing['ok'])
                                                            <i class="fa-solid fa-circle-check text-emerald-400 text-base"></i>
                                                        @else
                                                            <i class="fa-solid fa-circle-xmark text-red-400 text-base"></i>
                                                        @endif
                                                    </div>

                                                    <!-- Monster Drops Tooltip -->
                                                    <div class="absolute top-full left-1/2 transform -translate-x-1/2 mt-2 w-60 bg-stone-950/95 border border-emerald-800/80 rounded-xl p-3 text-xs text-gray-300 hidden group-hover:block z-[9999] shadow-2xl backdrop-blur-md pointer-events-none">
                                                        <div class="font-bold text-emerald-400 mb-1.5 border-b border-stone-800 pb-1 text-center text-[11px] tracking-wider uppercase flex items-center justify-center gap-1.5">
                                                            <i class="fa-solid fa-skull text-red-400"></i>
                                                            Do zdobycia z potworów
                                                        </div>
                                                        @if(isset($ing['dropped_by']) && count($ing['dropped_by']) > 0)
                                                            @php
                                                                $uniqueDrops = collect($ing['dropped_by'])->unique(fn($d) => is_array($d) ? (($d['monster'] ?? '') . '_' . ($d['map'] ?? '')) : $d)->values();
                                                            @endphp
                                                            <div class="flex flex-wrap justify-center gap-1.5 mt-2">
                                                                @foreach($uniqueDrops as $drop)
                                                                    <span class="bg-stone-900 border border-stone-700 text-emerald-200 text-[10px] px-2 py-0.5 rounded-md">
                                                                        @if(is_array($drop))
                                                                            {{ $drop['monster'] }}@if(!empty($drop['map']))<span class="text-amber-400/80"> · {{ $drop['map'] }}</span>@endif
                                                                        @else
                                                                            {{ $drop }}
                                                                        @endif
                                                                    </span>
                                                                @endforeach
                                                            </div>
                                                        @else
                                                            <div class="text-[10px] text-stone-500 text-center italic mt-1.5">Brak danych o potworach dropujących ten surowiec.</div>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="flex items-center justify-between mt-4 bg-black/50 p-3 rounded-xl border border-white/10">
                                    <div class="flex items-center gap-2">
                                        <span class="text-[11px] text-emerald-400/80 uppercase tracking-widest font-extrabold">Koszt:</span>
                                        <span class="text-amber-300 font-extrabold bg-amber-950/60 px-2.5 py-1 rounded-lg border border-amber-500/40 flex items-center gap-1.5 text-xs shadow-inner">
                                            <i class="fa-solid fa-coins text-amber-400"></i>
                                            <span>{{ number_format($recipe['gold_cost'], 0, ',', ' ') }}</span>
                                        </span>
                                    </div>
                                    
                                    <button wire:click="craftPotion('{{ $recipe['id'] }}')"
                                        wire:loading.attr="disabled"
                                        @if(!$recipe['can_craft']) disabled @endif
                                        class="relative overflow-hidden font-extrabold py-2.5 px-6 rounded-xl shadow-lg border transition-all duration-300 medieval-font tracking-wider uppercase text-xs cursor-pointer
                                        {{ $recipe['can_craft']
                                            ? 'bg-gradient-to-r from-emerald-700 via-teal-700 to-emerald-800 hover:from-emerald-600 hover:to-teal-600 text-white border-emerald-400/60 shadow-[0_0_15px_rgba(16,185,129,0.4)] transform hover:scale-105 active:scale-95'
                                            : 'bg-stone-900 text-stone-500 border-stone-800 cursor-not-allowed opacity-60' }}">
                                        <span wire:loading.remove wire:target="craftPotion('{{ $recipe['id'] }}')" class="flex items-center gap-1.5">
                                            <i class="fa-solid fa-wand-magic-sparkles"></i>
                                            Uwarz Miksturę
                                        </span>
                                        <span wire:loading wire:target="craftPotion('{{ $recipe['id'] }}')" class="flex items-center gap-2">
                                            <i class="fa-solid fa-spinner animate-spin text-emerald-300"></i>
                                            Bulgocze...
                                        </span>
                                    </button>
                                </div>
                            </div>
                        @empty
                            <div class="col-span-full text-center py-12 bg-black/40 rounded-2xl border border-emerald-900/40 backdrop-blur-sm">
                                <i class="fa-solid fa-scroll text-4xl text-emerald-400/40 mb-3 block"></i>
                                <p class="text-emerald-300/70 font-medium font-sans">Brak znanych receptur alchemicznych.</p>
                            </div>
                        @endforelse
                    </div>
                </div>

            @elseif($activeTab === 'enchant')
                <div class="animate-[fade-in_0.4s_ease-out]">
                    <div class="flex flex-col lg:flex-row gap-8">

                        {{-- ENCHANTING TABLE (CENTER) --}}
                        <div class="lg:w-2/3 flex flex-col items-center justify-center relative min-h-[500px] border border-indigo-800/30 rounded-2xl bg-black/40 backdrop-blur-sm p-8 shadow-2xl">

                            {{-- Action Messages --}}
                            @if($actionMessage)
                                <div class="absolute top-4 left-1/2 -translate-x-1/2 w-3/4 text-center p-3 rounded-lg border backdrop-blur-md z-50 animate-fade-in-down {{ $actionType === 'success' ? 'bg-green-900/80 border-green-500 text-green-200 shadow-[0_0_20px_rgba(34,197,94,0.5)]' : 'bg-red-900/80 border-red-500 text-red-200 shadow-[0_0_20px_rgba(239,68,68,0.5)]' }}">
                                    <p class="font-bold text-lg">{{ $actionMessage }}</p>
                                </div>
                            @endif

                            @if($activeItem)
                                @php
                                    $enchants = $activeItem->roll_stats['enchants'] ?? [];
                                    $enchantCount = count($enchants);
                                    $nextChance = [75, 50, 40, 30, 20][$enchantCount] ?? 0;

                                    // Zablokowane bonusy nie są przelosowywane (patrz RerollEnchantments), ale
                                    // każdy zablokowany slot PODWAJA całkowity koszt - to premium za "ochronę"
                                    // bonusu przed rerollem, nie zniżka za mniejszy zakres losowania.
                                    $enchantLocks = $activeItem->getEnchantLocks();
                                    $unlockedCount = $enchantCount - count($enchantLocks);
                                    $rerollGoldCost = max(200, $unlockedCount * 200) * (2 ** count($enchantLocks));
                                    $rerollGemCost = max(2, $unlockedCount * 2) * (2 ** count($enchantLocks));

                                    // Jedyne płaskie (nie-procentowe) bonusy w całej puli zaklęć - reszta
                                    // (w tym attack_power/magic_attack/hp_bonus/defense) jest wyrażona w %.
                                    $flatBonusKeys = ['str_bonus', 'agi_bonus', 'int_bonus', 'vit_bonus'];

                                    $bonusIcon = function (string $key): string {
                                        return match (true) {
                                            $key === 'attack_power' => 'fa-khanda',
                                            $key === 'magic_attack' => 'fa-wand-magic-sparkles',
                                            $key === 'crit_chance' => 'fa-bullseye',
                                            $key === 'dodge_chance' => 'fa-wind',
                                            $key === 'hp_bonus' => 'fa-heart',
                                            $key === 'defense' => 'fa-shield-halved',
                                            $key === 'poison_chance' => 'fa-skull-crossbones',
                                            $key === 'stun_chance' => 'fa-bolt-lightning',
                                            str_starts_with($key, 'strong_vs_') => 'fa-khanda',
                                            str_starts_with($key, 'resist_') => 'fa-shield',
                                            str_starts_with($key, 'double_') => 'fa-coins',
                                            in_array($key, ['str_bonus', 'agi_bonus', 'int_bonus', 'vit_bonus'], true) => 'fa-dumbbell',
                                            default => 'fa-star',
                                        };
                                    };
                                @endphp

                                {{-- Magical Runes Ring --}}
                                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[270px] h-[270px] sm:w-[350px] sm:h-[350px] rounded-full border-2 border-dashed border-purple-500/30 animate-[spin_20s_linear_infinite] pointer-events-none"></div>
                                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[310px] h-[310px] sm:w-[450px] sm:h-[450px] rounded-full border border-indigo-500/20 animate-[spin_30s_linear_infinite_reverse] pointer-events-none">
                                    <div class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 text-purple-400/50 text-2xl medieval-font blur-[1px]">ᚠ</div>
                                    <div class="absolute bottom-0 left-1/2 -translate-x-1/2 translate-y-1/2 text-purple-400/50 text-2xl medieval-font blur-[1px]">ᛞ</div>
                                    <div class="absolute left-0 top-1/2 -translate-x-1/2 -translate-y-1/2 text-purple-400/50 text-2xl medieval-font blur-[1px]">ᚱ</div>
                                    <div class="absolute right-0 top-1/2 translate-x-1/2 -translate-y-1/2 text-purple-400/50 text-2xl medieval-font blur-[1px]">ᚷ</div>
                                </div>

                                {{-- Item on Table --}}
                                <div class="relative z-10 flex flex-col items-center">
                                    <div class="relative group cursor-pointer" wire:click="deselectItem">
                                        {{-- Glow behind item based on enchants --}}
                                        <div class="absolute inset-0 bg-purple-600 rounded-full blur-[40px] opacity-{{ min(100, max(20, $enchantCount * 25)) }} transition-all duration-500"></div>

                                        @if($actionType === 'success')
                                            {{-- Epic particle burst --}}
                                            <div class="absolute inset-0 z-20 pointer-events-none flex items-center justify-center">
                                                <div class="absolute w-32 h-32 rounded-full border-4 border-yellow-300 shadow-[0_0_50px_rgba(253,224,71,1)] animate-[ping-fast_0.5s_ease-out_forwards]"></div>
                                                <div class="absolute w-full h-full bg-yellow-300/30 blur-2xl rounded-full animate-[fade-out-slow_1.5s_ease-out_forwards]"></div>

                                                {{-- Floating stars --}}
                                                <div class="absolute text-yellow-300 text-2xl animate-[float-up-left_1s_ease-out_forwards]">✨</div>
                                                <div class="absolute text-yellow-200 text-xl animate-[float-up-right_1.2s_ease-out_forwards]">⭐</div>
                                                <div class="absolute text-white text-3xl animate-[float-down-left_0.9s_ease-out_forwards]">✨</div>
                                                <div class="absolute text-yellow-400 text-xl animate-[float-down-right_1.1s_ease-out_forwards]">⭐</div>
                                            </div>
                                        @endif

                                        @if($activeItem->template->icon)
                                            <img src="{{ route('assets.items', ['filename' => $activeItem->template->icon]) }}"
                                                 class="w-32 h-32 object-contain relative z-10 drop-shadow-[0_0_15px_rgba(168,85,247,0.8)] {{ $actionType === 'success' ? 'animate-[epic-success_1s_ease-out]' : 'animate-bounce-slow' }}"
                                                 alt="{{ $activeItem->template->name }}">
                                        @else
                                            <div class="w-32 h-32 bg-gray-800 rounded-lg flex items-center justify-center border-2 border-purple-500 shadow-[0_0_20px_rgba(168,85,247,0.5)] relative z-10 {{ $actionType === 'success' ? 'animate-[epic-success_1s_ease-out]' : 'animate-bounce-slow' }}">
                                                <i class="fa-solid fa-question text-4xl text-purple-300"></i>
                                            </div>
                                        @endif
                                        <div class="absolute -bottom-4 left-1/2 -translate-x-1/2 bg-black/80 px-3 py-1 rounded-full text-xs text-gray-400 opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
                                            Kliknij, aby odłożyć
                                        </div>
                                    </div>

                                    <h3 class="mt-8 font-bold text-2xl text-purple-200 drop-shadow-[0_2px_4px_rgba(0,0,0,0.8)] medieval-font text-center">
                                        {{ $activeItem->template->name }}
                                        @if($activeItem->upgrade_level > 0)<span class="text-yellow-400">+{{ $activeItem->upgrade_level }}</span>@endif
                                    </h3>

                                    {{-- Current Enchants Display --}}
                                    <div class="mt-4 bg-indigo-950/60 border border-purple-500/40 rounded-xl p-4 min-w-[300px] shadow-[inset_0_0_20px_rgba(0,0,0,0.5)] backdrop-blur-md">
                                        <div class="text-purple-300 font-bold mb-2 text-center border-b border-purple-500/30 pb-2 flex items-center justify-center gap-2">
                                            <i class="fa-solid fa-sparkles"></i> Magiczne Moce ({{ $enchantCount }}/5) <i class="fa-solid fa-sparkles"></i>
                                        </div>
                                        @if($enchantCount > 0)
                                            <div class="space-y-2">
                                                @foreach($enchants as $bonusType => $bonusValue)
                                                    @php
                                                        $isPct = !in_array($bonusType, $flatBonusKeys, true);
                                                        $isNegative = $bonusValue < 0;
                                                        $displayValue = ($bonusValue > 0 ? '+' : '') . $bonusValue . ($isPct ? '%' : '');
                                                        $isLocked = in_array($bonusType, $enchantLocks, true);
                                                    @endphp
                                                    <div class="flex justify-between items-center rounded px-3 py-1.5 border {{ $isLocked ? 'bg-amber-950/30 border-amber-600/50' : ($isNegative ? 'bg-red-950/40 border-red-800/60' : 'bg-black/40 border-purple-900/50') }}">
                                                        <span class="flex items-center gap-2 {{ $isNegative ? 'text-red-200' : 'text-indigo-200' }}">
                                                            <i class="fa-solid {{ $bonusIcon($bonusType) }} {{ $isNegative ? 'text-red-400' : 'text-purple-400' }} w-4 text-center"></i>
                                                            {{ \App\Domain\Wizard\EnchantmentStrategy::bonusLabel($bonusType) }}
                                                        </span>
                                                        <div class="flex items-center gap-3">
                                                            <span class="font-bold text-lg {{ $isNegative ? 'text-red-400 drop-shadow-[0_0_5px_rgba(248,113,113,0.5)]' : 'text-yellow-300 drop-shadow-[0_0_5px_rgba(253,224,71,0.5)]' }}">{{ $displayValue }}</span>
                                                            <button wire:click="toggleEnchantLock('{{ $bonusType }}')"
                                                                    title="{{ $isLocked ? 'Odblokuj - ten bonus będzie mógł zostać przelosowany' : 'Zablokuj - ten bonus zostanie pominięty przy przelosowaniu' }}"
                                                                    class="w-7 h-7 rounded-lg flex items-center justify-center text-sm shrink-0 transition-colors border {{ $isLocked ? 'bg-amber-600/80 border-amber-400 text-amber-100 hover:bg-amber-500' : 'bg-black/40 border-gray-600 text-gray-500 hover:text-amber-300 hover:border-amber-500/60' }}">
                                                                <i class="fa-solid {{ $isLocked ? 'fa-lock' : 'fa-lock-open' }}"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="text-gray-500 text-center italic py-4">Przedmiot czeka na nasycenie magią...</div>
                                        @endif
                                     </div>

                                     {{-- Bonus z Ulepszenia Kuźni (+3) --}}
                                     @php
                                         $upgradeBonuses = $activeItem->getUpgradeBonuses();
                                     @endphp
                                     <div class="mt-4 bg-amber-950/40 border border-amber-500/50 rounded-xl p-4 min-w-[300px] shadow-[0_0_15px_rgba(217,119,6,0.2)] backdrop-blur-md">
                                         <div class="text-amber-300 font-bold mb-2 text-center border-b border-amber-500/30 pb-2 flex items-center justify-center gap-2">
                                             <i class="fa-solid fa-hammer text-amber-400"></i> Bonus z Ulepszenia Kuźni (+3) <i class="fa-solid fa-sparkles text-amber-400"></i>
                                         </div>
                                         @if(count($upgradeBonuses) > 0)
                                             <div class="space-y-2">
                                                 @foreach($upgradeBonuses as $ubType => $ubValue)
                                                     @php
                                                         $isPct = !in_array($ubType, $flatBonusKeys, true);
                                                         $ubDisplay = ($ubValue > 0 ? '+' : '') . $ubValue . ($isPct ? '%' : '');
                                                     @endphp
                                                     <div class="flex justify-between items-center bg-black/50 border border-amber-600/40 rounded px-3 py-2">
                                                         <span class="flex items-center gap-2 text-amber-100 font-medium">
                                                             <i class="fa-solid {{ $bonusIcon($ubType) }} text-amber-400 w-4 text-center"></i>
                                                             {{ \App\Domain\Wizard\EnchantmentStrategy::bonusLabel($ubType) }}
                                                         </span>
                                                         <span class="font-bold text-lg text-amber-300 drop-shadow-[0_0_5px_rgba(252,211,77,0.6)]">{{ $ubDisplay }}</span>
                                                     </div>
                                                 @endforeach
                                             </div>
                                             <div class="mt-3 text-xs text-amber-200/80 text-center italic">
                                                 Przelosowanie zmienia <strong>wyłącznie wartość liczbową</strong> tego bonusu (typ statystyki pozostaje ten sam).
                                             </div>
                                             <div class="mt-3 grid grid-cols-2 gap-3">
                                                 <button wire:click="rerollUpgradeBonus('gold')" wire:loading.attr="disabled" class="bg-amber-900/60 hover:bg-amber-800 text-amber-200 border border-amber-500/60 hover:border-amber-400 font-bold py-2 px-3 rounded-lg transition-all text-xs shadow-md">
                                                     <span wire:loading.remove wire:target="rerollUpgradeBonus"><i class="fa-solid fa-coins mr-1 text-amber-400"></i> Wartość (3500 <i class="fa-solid fa-coins"></i>)</span>
                                                     <span wire:loading wire:target="rerollUpgradeBonus" class="animate-pulse">Losowanie...</span>
                                                 </button>
                                                 <button wire:click="rerollUpgradeBonus('gems')" wire:loading.attr="disabled" class="bg-blue-950/60 hover:bg-blue-900 text-blue-300 border border-blue-500/60 hover:border-blue-400 font-bold py-2 px-3 rounded-lg transition-all text-xs shadow-md">
                                                     <span wire:loading.remove wire:target="rerollUpgradeBonus"><i class="fa-solid fa-gem mr-1 text-blue-400"></i> Wartość (35 <i class="fa-solid fa-gem"></i>)</span>
                                                     <span wire:loading wire:target="rerollUpgradeBonus" class="animate-pulse">Losowanie...</span>
                                                 </button>
                                             </div>
                                         @else
                                             <div class="text-amber-400/70 text-center text-xs italic py-2">
                                                 Brak bonusu Kuźni. Przedmiot musisz ulepszyć do co najmniej +3 u Kowala.
                                             </div>
                                         @endif
                                     </div>

                                     {{-- Action Buttons --}}
                                    <div class="mt-8 flex flex-col gap-4 w-full max-w-md relative z-20">
                                        @if($enchantCount < 5)
                                            <div class="text-center text-sm text-purple-300 mb-1">Szansa na sukces: <span class="font-bold text-white">{{ $nextChance }}%</span></div>
                                            <div class="grid grid-cols-2 gap-4 {{ $gameStage == 32 ? 'ring-4 ring-amber-500 animate-pulse rounded-xl p-1 z-30' : '' }}">
                                                <button wire:click="enchant('gold')" wire:loading.attr="disabled" class="group relative overflow-hidden bg-gradient-to-br from-yellow-700 to-yellow-900 hover:from-yellow-600 hover:to-yellow-800 text-white font-bold py-3 px-4 rounded-xl shadow-[0_0_15px_rgba(202,138,4,0.4)] transition-all transform hover:scale-105 border border-yellow-500/50">
                                                    <div class="absolute inset-0 bg-white/20 group-hover:translate-x-full -translate-x-full skew-x-12 transition-transform duration-700"></div>
                                                    <span wire:loading.remove wire:target="enchant"><i class="fa-solid fa-coins mr-1"></i> Zaklnij (500)</span>
                                                    <span wire:loading wire:target="enchant" class="animate-pulse">Zaklinanie...</span>
                                                </button>
                                                <button wire:click="enchant('gems')" wire:loading.attr="disabled" class="group relative overflow-hidden bg-gradient-to-br from-blue-700 to-blue-900 hover:from-blue-600 hover:to-blue-800 text-white font-bold py-3 px-4 rounded-xl shadow-[0_0_15px_rgba(59,130,246,0.4)] transition-all transform hover:scale-105 border border-blue-500/50">
                                                    <div class="absolute inset-0 bg-white/20 group-hover:translate-x-full -translate-x-full skew-x-12 transition-transform duration-700"></div>
                                                    <span wire:loading.remove wire:target="enchant"><i class="fa-solid fa-gem mr-1"></i> Zaklnij (5)</span>
                                                    <span wire:loading wire:target="enchant" class="animate-pulse">Zaklinanie...</span>
                                                </button>
                                            </div>
                                        @else
                                            <div class="bg-purple-900/50 border border-purple-500/50 text-purple-200 font-bold py-3 px-4 rounded-xl text-center shadow-inner">
                                                Przedmiot osiągnął maksymalną moc.
                                            </div>
                                        @endif

                                        @if($enchantCount > 0)
                                            <div class="mt-4 pt-4 border-t border-purple-800/50">
                                                @if($unlockedCount > 0)
                                                    <div class="text-center text-sm text-gray-400 mb-2">
                                                        Przelosuj odblokowane bonusy od nowa
                                                        @if(count($enchantLocks) > 0)
                                                            <span class="text-amber-400 font-semibold">({{ $unlockedCount }}/{{ $enchantCount }} odblokowanych - {{ count($enchantLocks) }} 🔒 zostanie bez zmian, cena x{{ 2 ** count($enchantLocks) }} za ochronę)</span>
                                                        @endif:
                                                    </div>
                                                    <div class="grid grid-cols-2 gap-4">
                                                        <button wire:click="reroll('gold')" wire:loading.attr="disabled" class="bg-slate-800 hover:bg-slate-700 text-yellow-500 border border-slate-600 hover:border-yellow-600/50 font-bold py-2 px-4 rounded-lg transition-colors text-sm shadow-md">
                                                            <span wire:loading.remove wire:target="reroll">Reroll ({{ $rerollGoldCost }} <i class="fa-solid fa-coins"></i>)</span>
                                                            <span wire:loading wire:target="reroll">Przelosowywanie...</span>
                                                        </button>
                                                        <button wire:click="reroll('gems')" wire:loading.attr="disabled" class="bg-slate-800 hover:bg-slate-700 text-blue-400 border border-slate-600 hover:border-blue-600/50 font-bold py-2 px-4 rounded-lg transition-colors text-sm shadow-md">
                                                            <span wire:loading.remove wire:target="reroll">Reroll ({{ $rerollGemCost }} <i class="fa-solid fa-gem"></i>)</span>
                                                            <span wire:loading wire:target="reroll">Przelosowywanie...</span>
                                                        </button>
                                                    </div>
                                                @else
                                                    <div class="text-center text-sm text-amber-300/90 bg-amber-950/30 border border-amber-700/40 rounded-lg py-2 px-3">
                                                        <i class="fa-solid fa-lock mr-1"></i> Wszystkie bonusy są zablokowane - odblokuj przynajmniej jeden 🔓, żeby móc przelosować.
                                                    </div>
                                                @endif
                                            </div>

                                            @if($unlockedCount > 0)
                                                {{-- BONUS SWITCHER (SWITCHBOT) SECTION --}}
                                                <div class="mt-6 pt-5 border-t border-purple-800/50"
                                                     x-data="{
                                                         isRunning: false,
                                                         timer: null,
                                                         isVip: {{ auth()->user()->hasPremium() ? 'true' : 'false' }},
                                                         rollInterval: {{ auth()->user()->hasPremium() ? 150 : 300 }},
                                                         currency: 'gold',
                                                         targetCriteria: [
                                                             { type: '', min: 1 }
                                                         ],
                                                         rollCount: 0,
                                                         statusText: '',
                                                         statusType: '',

                                                         init() {
                                                             this.$watch('$wire.activeItemId', () => this.stopSwitcher());
                                                         },
                                                         addCriterion() {
                                                             if (this.targetCriteria.length < 5) {
                                                                 this.targetCriteria.push({ type: '', min: 1 });
                                                             }
                                                         },
                                                         removeCriterion(index) {
                                                             if (this.targetCriteria.length > 1) {
                                                                 this.targetCriteria.splice(index, 1);
                                                             }
                                                         },
                                                         async startSwitcher() {
                                                             const validCriteria = this.targetCriteria.filter(c => c.type && c.min !== null && c.min !== '');
                                                             if (validCriteria.length === 0) {
                                                                 this.statusText = 'Wybierz co najmniej jeden docelowy bonus!';
                                                                 this.statusType = 'error';
                                                                 return;
                                                             }

                                                             this.isRunning = true;
                                                             this.statusText = 'Bonus Switcher aktywny... Poszukiwanie bonusów...';
                                                             this.statusType = 'info';
                                                             this.rollCount = 0;

                                                             const loop = async () => {
                                                                 if (!this.isRunning) return;

                                                                 try {
                                                                     const res = await $wire.rerollStep(this.currency, validCriteria);

                                                                     if (!res.success) {
                                                                         this.stopSwitcher();
                                                                         this.statusText = res.error || 'Brak wystarczających środków na dalsze losowanie.';
                                                                         this.statusType = 'error';
                                                                         $dispatch('play-audio', { type: 'enchant-fail' });
                                                                         return;
                                                                     }

                                                                     this.rollCount++;
                                                                     $dispatch('stats-updated', { gold: res.gold, gems: res.gems });

                                                                     if (res.isMatch) {
                                                                         this.stopSwitcher();
                                                                         this.statusText = 'SUKCES! Wylosowano wszystkie wyznaczone bonusy!';
                                                                         this.statusType = 'success';
                                                                         $dispatch('play-audio', { type: 'enchant-success' });
                                                                         return;
                                                                     }

                                                                     if (this.isRunning) {
                                                                         this.timer = setTimeout(loop, this.rollInterval);
                                                                     }
                                                                 } catch (err) {
                                                                     this.stopSwitcher();
                                                                     this.statusText = 'Błąd połączenia podczas przelosowywania.';
                                                                     this.statusType = 'error';
                                                                 }
                                                             };

                                                             loop();
                                                         },
                                                         stopSwitcher() {
                                                             this.isRunning = false;
                                                             if (this.timer) {
                                                                 clearTimeout(this.timer);
                                                                 this.timer = null;
                                                             }
                                                         }
                                                     }">
                                                    <div class="bg-gradient-to-b from-purple-950/80 via-slate-950/90 to-black border-2 border-purple-600/60 rounded-2xl p-4 sm:p-5 shadow-[0_0_25px_rgba(147,51,234,0.3)] backdrop-blur-md">
                                                        {{-- Header --}}
                                                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-purple-800/40 pb-3 mb-4">
                                                            <div class="flex items-center gap-2">
                                                                <div class="w-8 h-8 rounded-lg bg-purple-900/80 border border-purple-400/60 flex items-center justify-center text-purple-300 shadow-md">
                                                                    <i class="fa-solid fa-rotate text-sm"></i>
                                                                </div>
                                                                <div>
                                                                    <h4 class="font-extrabold text-base text-amber-200 medieval-font tracking-wide">Bonus Switcher (Switchbot)</h4>
                                                                    <p class="text-[11px] text-purple-300/70 font-sans">Automatyczne losowanie do momentu trafienia wybranych bonusów</p>
                                                                </div>
                                                            </div>

                                                            {{-- VIP Speed Badge --}}
                                                            @if(auth()->user()->hasPremium())
                                                                <span class="inline-flex items-center gap-1.5 bg-gradient-to-r from-amber-950 to-amber-900 text-amber-300 border border-amber-500/80 px-3 py-1 rounded-full text-xs font-bold shadow-[0_0_12px_rgba(245,158,11,0.4)] animate-pulse">
                                                                    <i class="fa-solid fa-crown text-amber-400"></i>
                                                                    VIP 2x Szybciej (150ms)
                                                                </span>
                                                            @else
                                                                <span class="inline-flex items-center gap-1.5 bg-slate-900/90 text-slate-300 border border-slate-700/80 px-2.5 py-1 rounded-full text-[11px] font-semibold">
                                                                    <i class="fa-solid fa-stopwatch text-indigo-400"></i>
                                                                    Prędkość: 300ms
                                                                </span>
                                                            @endif
                                                        </div>

                                                        {{-- Target Criteria Inputs --}}
                                                        <div class="mb-4">
                                                            <label class="block text-xs font-extrabold text-purple-300 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                                                                <i class="fa-solid fa-bullseye text-amber-400"></i>
                                                                Docelowe Bonusy i Min. Wartości:
                                                            </label>

                                                            <div class="space-y-2">
                                                                <template x-for="(criterion, index) in targetCriteria" :key="index">
                                                                    <div class="flex items-center gap-2 bg-black/50 p-2 rounded-xl border border-purple-900/50">
                                                                        <span class="text-xs text-purple-400 font-bold w-5 text-center" x-text="index + 1 + '.'"></span>

                                                                        <select x-model="criterion.type"
                                                                                :disabled="isRunning"
                                                                                class="flex-1 bg-stone-900 border border-purple-700/60 rounded-lg px-2.5 py-1.5 text-xs text-amber-100 focus:outline-none focus:border-amber-500 disabled:opacity-50">
                                                                            <option value="">-- Wybierz bonus --</option>
                                                                            @foreach($possibleBonuses as $bonusKey => $range)
                                                                                @php
                                                                                    $isPct = !in_array($bonusKey, ['str_bonus', 'agi_bonus', 'int_bonus', 'vit_bonus'], true);
                                                                                    $unit = $isPct ? '%' : '';
                                                                                @endphp
                                                                                <option value="{{ $bonusKey }}">{{ \App\Domain\Wizard\EnchantmentStrategy::bonusLabel($bonusKey) }} (max +{{ $range[1] }}{{ $unit }})</option>
                                                                            @endforeach
                                                                        </select>

                                                                        <div class="flex items-center gap-1 shrink-0">
                                                                            <span class="text-xs text-purple-300/80 font-bold">Min:</span>
                                                                            <input type="number"
                                                                                   x-model.number="criterion.min"
                                                                                   :disabled="isRunning"
                                                                                   placeholder="1"
                                                                                   class="w-16 bg-stone-900 border border-purple-700/60 rounded-lg px-2 py-1.5 text-xs font-mono text-amber-300 text-center focus:outline-none focus:border-amber-500 disabled:opacity-50" />
                                                                        </div>

                                                                        <button @click="removeCriterion(index)"
                                                                                :disabled="isRunning || targetCriteria.length <= 1"
                                                                                class="w-7 h-7 rounded-lg bg-red-950/60 border border-red-700/60 text-red-400 hover:bg-red-900 hover:text-red-200 flex items-center justify-center text-xs transition-colors disabled:opacity-30 disabled:cursor-not-allowed">
                                                                            <i class="fa-solid fa-trash"></i>
                                                                        </button>
                                                                    </div>
                                                                </template>
                                                            </div>

                                                            <div class="mt-2 flex justify-between items-center">
                                                                <button @click="addCriterion()"
                                                                        :disabled="isRunning || targetCriteria.length >= 5"
                                                                        class="text-xs text-purple-300 hover:text-purple-100 font-bold flex items-center gap-1 cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed">
                                                                    <i class="fa-solid fa-plus-circle text-amber-400"></i> Dodaj kolejny cel (max 5)
                                                                </button>

                                                                {{-- Currency Selector --}}
                                                                <div class="flex items-center gap-3 bg-black/40 px-3 py-1 rounded-lg border border-purple-900/40">
                                                                    <label class="inline-flex items-center gap-1 text-xs text-amber-300 font-bold cursor-pointer">
                                                                        <input type="radio" name="switchbot_currency" value="gold" x-model="currency" :disabled="isRunning" class="text-amber-500 focus:ring-0">
                                                                        <i class="fa-solid fa-coins text-amber-400"></i> Złoto
                                                                    </label>
                                                                    <label class="inline-flex items-center gap-1 text-xs text-blue-300 font-bold cursor-pointer">
                                                                        <input type="radio" name="switchbot_currency" value="gems" x-model="currency" :disabled="isRunning" class="text-blue-500 focus:ring-0">
                                                                        <i class="fa-solid fa-gem text-blue-400"></i> Klejnoty
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        {{-- Status & Controls --}}
                                                        <div class="pt-3 border-t border-purple-900/40">
                                                            <div x-show="statusText" class="mb-3 text-center p-2 rounded-lg text-xs font-bold border shadow-inner"
                                                                 :class="{
                                                                     'bg-green-950/80 border-green-500 text-green-200': statusType === 'success',
                                                                     'bg-red-950/80 border-red-500 text-red-200': statusType === 'error',
                                                                     'bg-purple-950/80 border-purple-500 text-purple-200 animate-pulse': statusType === 'info'
                                                                 }"
                                                                 x-text="statusText">
                                                            </div>

                                                            <div class="flex items-center justify-between gap-3">
                                                                <div class="text-xs text-purple-300 font-mono">
                                                                    Wykonane rzuty: <span class="text-amber-300 font-bold text-sm" x-text="rollCount">0</span>
                                                                </div>

                                                                <div class="flex items-center gap-2">
                                                                    <button x-show="!isRunning"
                                                                            @click="startSwitcher()"
                                                                            class="bg-gradient-to-r from-emerald-600 via-teal-600 to-emerald-700 hover:from-emerald-500 hover:to-teal-500 text-white font-extrabold py-2.5 px-5 rounded-xl shadow-[0_0_15px_rgba(16,185,129,0.4)] border border-emerald-400/60 transition-all duration-200 transform hover:scale-105 text-xs uppercase tracking-wider cursor-pointer flex items-center gap-2">
                                                                        <i class="fa-solid fa-play"></i> Uruchom Switchera
                                                                    </button>

                                                                    <button x-show="isRunning"
                                                                            @click="stopSwitcher(); statusText = 'Zatrzymano losowanie.'; statusType = 'error';"
                                                                            class="bg-gradient-to-r from-red-600 to-red-800 hover:from-red-500 hover:to-red-700 text-white font-extrabold py-2.5 px-5 rounded-xl shadow-[0_0_15px_rgba(239,68,68,0.5)] border border-red-400/60 transition-all duration-200 transform hover:scale-105 text-xs uppercase tracking-wider cursor-pointer flex items-center gap-2 animate-pulse">
                                                                        <i class="fa-solid fa-stop"></i> Zatrzymaj
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        @endif

                                        {{-- Możliwe zaklęcia dla tego typu przedmiotu --}}
                                        @if(!empty($possibleBonuses))
                                            <div class="mt-6 pt-4 border-t border-indigo-800/40 w-full">
                                                <div class="text-center text-sm text-indigo-300 font-bold mb-3 flex items-center justify-center gap-2">
                                                    <i class="fa-solid fa-list-ul"></i> Możliwe Zaklęcia dla tego typu przedmiotu
                                                </div>
                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                                                    @foreach($possibleBonuses as $bonusKey => $range)
                                                        @php
                                                            // Jedyne płaskie (nie-procentowe) bonusy w całej puli zaklęć -
                                                            // wszystko inne (w tym attack_power/magic_attack, patrz
                                                            // EnchantmentStrategy) jest wyrażone w %.
                                                            $isPct = !in_array($bonusKey, ['str_bonus', 'agi_bonus', 'int_bonus', 'vit_bonus'], true);
                                                            $suffix = $isPct ? '%' : '';
                                                            // Afiksy z zakresem obejmującym wynik ujemny (attack_power/magic_attack) -
                                                            // ryzykowny "hazard", oznaczony osobno i innym formatem zakresu.
                                                            $isRisky = $range[0] < 0;
                                                            $rangeLabel = $isRisky
                                                                ? "{$range[0]}{$suffix} do +{$range[1]}{$suffix}"
                                                                : "+{$range[0]}-{$range[1]}{$suffix}";
                                                        @endphp
                                                        <div class="flex justify-between items-center bg-black/30 rounded-lg px-3 py-1.5 border {{ $isRisky ? 'border-red-900/60' : 'border-indigo-900/40' }}">
                                                            <span class="text-gray-300 flex items-center gap-1.5">
                                                                {{ \App\Domain\Wizard\EnchantmentStrategy::bonusLabel($bonusKey) }}
                                                                @if($isRisky)
                                                                    <i class="fa-solid fa-dice text-red-400 text-[10px]" title="Ryzykowny afiks - możliwy wynik ujemny!"></i>
                                                                @endif
                                                            </span>
                                                            <span class="font-mono font-bold {{ $isRisky ? 'text-red-300' : 'text-indigo-300' }}">{{ $rangeLabel }}</span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @else
                                {{-- Empty State --}}
                                <div class="flex flex-col items-center justify-center text-center opacity-70 animate-pulse my-auto">
                                    <div class="w-32 h-32 rounded-full border-2 border-dashed border-purple-500/50 flex items-center justify-center mb-6 shadow-[0_0_30px_rgba(168,85,247,0.2)]">
                                        <i class="fa-solid fa-sparkles text-5xl text-purple-400"></i>
                                    </div>
                                    <h3 class="text-2xl font-bold text-purple-300 medieval-font mb-2">Stół do Zaklinania</h3>
                                    <p class="text-indigo-200 max-w-sm">Wybierz przedmiot ze swojego ekwipunku, aby nasycić go starożytną magią.</p>
                                </div>
                            @endif
                        </div>

                        {{-- INVENTORY / EQUIPPED ITEMS (SIDEBAR) --}}
                        <div class="lg:w-1/3 flex flex-col bg-black/40 border border-indigo-900/50 rounded-2xl p-4 backdrop-blur-sm max-h-[800px]">
                            <div class="mb-4 pb-2 border-b border-indigo-900/50">
                                <h3 class="font-bold text-lg text-indigo-300 flex items-center gap-2">
                                    <i class="fa-solid fa-sack"></i> Twój Ekwipunek
                                </h3>
                                <p class="text-xs text-gray-400 mt-1">Kliknij przedmiot, aby położyć go na stole.</p>
                            </div>

                            <div class="flex-1 overflow-y-auto pr-2 custom-scrollbar space-y-3">
                                @forelse($enchantableItems as $item)
                                    @php
                                        $enchantsCount = count($item->roll_stats['enchants'] ?? []);
                                        $isActive = $activeItemId === $item->id;
                                    @endphp
                                    <div wire:key="witch-enchant-item-{{ $item->id }}" class="relative" x-data="smartTooltip()"
                                         :class="{ 'z-50': showInfo, 'z-10': !showInfo }"
                                         @mouseenter="openTooltip()"
                                         @mouseleave="closeTooltip()"
                                         @click="toggleTooltip(); $wire.selectItemToEnchant('{{ $item->id }}')"
                                         @resize.window.debounce.100ms="updatePosition()"
                                         @tooltip-updated.window="updatePosition()">

                                        <div class="relative flex items-center p-3 rounded-xl border transition-all cursor-pointer overflow-hidden group
                                                    {{ $isActive ? 'bg-purple-900/40 border-purple-500 shadow-[0_0_15px_rgba(168,85,247,0.4)]' : 'bg-gray-900/60 border-gray-700 hover:border-purple-500/50 hover:bg-gray-800/80' }}">

                                            @if($isActive)
                                                <div class="absolute inset-0 bg-gradient-to-r from-purple-600/20 to-transparent"></div>
                                            @endif

                                            @if($item->location === 'equipped')
                                                <div class="absolute top-0 right-0 bg-blue-900/80 text-blue-200 text-[9px] font-bold px-1.5 py-0.5 rounded-bl-lg">
                                                    Założone
                                                </div>
                                            @endif

                                            <div class="w-12 h-12 flex-shrink-0 bg-black/50 rounded-lg p-1 border border-gray-700 mr-3 relative">
                                                @if($item->template->icon)
                                                    <img src="{{ route('assets.items', ['filename' => $item->template->icon]) }}" class="w-full h-full object-contain" alt="">
                                                @endif
                                                @if($enchantsCount > 0)
                                                    <div class="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-purple-600 border border-purple-300 flex items-center justify-center text-[9px] font-bold text-white shadow-[0_0_5px_rgba(168,85,247,1)]">
                                                        {{ $enchantsCount }}
                                                    </div>
                                                @endif
                                                @if($item->stack_size > 1)
                                                    <span class="absolute bottom-0 right-0 text-white font-bold text-[9px] bg-black/80 px-1 py-0.5 rounded border border-gray-600 shadow">x{{ $item->stack_size }}</span>
                                                @endif
                                            </div>

                                            <div class="flex-1 min-w-0 z-10">
                                                <div class="font-bold text-sm text-gray-200 truncate group-hover:text-purple-300 transition-colors">
                                                    {{ $item->template->name }}
                                                    @if($item->upgrade_level > 0)<span class="text-yellow-500">+{{ $item->upgrade_level }}</span>@endif
                                                </div>
                                                <div class="text-xs text-gray-500 capitalize">{{ $item->template->type }}</div>
                                            </div>

                                            @if($isActive)
                                                <div class="ml-2 text-purple-400 animate-pulse">
                                                    <i class="fa-solid fa-bolt"></i>
                                                </div>
                                            @endif
                                        </div>

                                        <!-- Infobox Przedmiotu (teleportowany do <body> - ucieka spod
                                             `backdrop-blur`/`overflow` przodków, które łamią zarówno
                                             position:absolute jak i position:fixed, patrz updatePosition()
                                             w layouts/app.blade.php) -->
                                        <template x-teleport="body">
                                            <div x-show="showInfo" x-transition.opacity x-ref="tooltipContainer" data-tooltip-container
                                                 :style="tooltipStyle"
                                                 class="absolute z-[100] bottom-full left-1/2 -translate-x-1/2 mb-2 w-auto pointer-events-auto">
                                                <x-item-tooltip :item="$item" :equippedItem="$equipped[$item->template->slot ?? ''] ?? null">
                                                    <x-slot:actions>
                                                        <button wire:click.stop="selectItemToEnchant('{{ $item->id }}'); showInfo = false;" class="w-full bg-purple-700 hover:bg-purple-600 text-white text-xs font-bold py-1.5 rounded transition">
                                                            Wybierz na Stół Zaklęć
                                                        </button>
                                                    </x-slot:actions>
                                                </x-item-tooltip>
                                            </div>
                                        </template>
                                    </div>
                                @empty
                                    <div class="text-center py-8 text-gray-500">
                                        Brak pasujących przedmiotów.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            @elseif($activeTab === 'mirror')
                <div class="max-w-3xl mx-auto space-y-6 animate-[fade-in_0.4s_ease-out]">
                    <div class="relative bg-gradient-to-r from-purple-950/90 via-fuchsia-950/70 to-slate-950/90 border-2 border-purple-500/50 rounded-2xl p-6 md:p-8 shadow-[0_0_35px_rgba(168,85,247,0.25)] backdrop-blur-xl overflow-hidden">
                        <div class="absolute top-0 right-0 w-80 h-80 bg-purple-600/15 rounded-full filter blur-3xl opacity-50 pointer-events-none"></div>
                        <div class="relative flex items-center gap-4 mb-4">
                            <div class="w-14 h-14 rounded-2xl bg-gradient-to-b from-purple-800 via-purple-950 to-black border-2 border-purple-400 flex items-center justify-center text-2xl text-purple-300 shadow-[0_0_20px_rgba(168,85,247,0.4)] shrink-0">
                                <i class="fa-solid fa-gem"></i>
                            </div>
                            <div>
                                <h2 class="text-xl sm:text-2xl font-extrabold text-purple-200 medieval-font tracking-wider uppercase">Magiczne Lustro</h2>
                                <p class="text-xs sm:text-sm text-purple-300/70 font-sans">Wiedźma stworzy Twoje zwierciadlane odbicie, które poluje w Twoim imieniu</p>
                            </div>
                        </div>

                        @if($character->level < 30)
                            <div class="relative bg-stone-900/90 border-2 border-stone-800 rounded-xl p-6 text-center opacity-80">
                                <i class="fa-solid fa-lock text-3xl text-amber-500/60 mb-3"></i>
                                <p class="text-stone-400 font-bold font-sans">Zablokowane! Wymagany 30 poziom postaci.</p>
                            </div>
                        @else
                            <div class="relative space-y-4">
                                @if($hasMirrorAccess)
                                    <div class="bg-emerald-950/60 border border-emerald-600/60 rounded-xl p-4 flex items-center gap-3">
                                        <i class="fa-solid fa-circle-check text-emerald-400 text-lg"></i>
                                        <span class="text-emerald-200 font-bold font-sans text-sm">Dostęp aktywny do {{ $mirrorAccessUntil->format('d.m.Y H:i') }}</span>
                                    </div>
                                    <p class="text-purple-300/70 text-xs font-sans">Możesz dokupić kolejne 7 dni już teraz — zostaną doliczone do obecnego terminu.</p>
                                @else
                                    <p class="text-purple-300/70 text-sm font-sans">Nie posiadasz aktywnego dostępu do Lustra. Wykup go poniżej, a następnie wybierz mapę i uruchom sesję w zakładce <span class="text-purple-200 font-bold">Postać &amp; Ekwipunek → Lustro</span>.</p>
                                @endif

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <button wire:click="buyMirrorAccess('gold')" wire:loading.attr="disabled"
                                        {{ $character->gold < 5000000 ? 'disabled' : '' }}
                                        class="flex flex-col items-center gap-2 p-5 rounded-xl border-2 font-sans transition-all duration-200 {{ $character->gold < 5000000 ? 'bg-stone-900/70 border-stone-800 text-stone-600 cursor-not-allowed opacity-60' : 'bg-gradient-to-b from-amber-900/60 to-stone-950 border-amber-600/70 text-amber-200 hover:border-amber-400 hover:scale-[1.02] cursor-pointer shadow-lg' }}">
                                        <i class="fa-solid fa-coins text-2xl text-amber-400"></i>
                                        <span class="font-black text-lg">5 000 000</span>
                                        <span class="text-[10px] uppercase tracking-widest opacity-80">Złota za 7 dni</span>
                                    </button>
                                    <button wire:click="buyMirrorAccess('gems')" wire:loading.attr="disabled"
                                        {{ $character->gems < 200 ? 'disabled' : '' }}
                                        class="flex flex-col items-center gap-2 p-5 rounded-xl border-2 font-sans transition-all duration-200 {{ $character->gems < 200 ? 'bg-stone-900/70 border-stone-800 text-stone-600 cursor-not-allowed opacity-60' : 'bg-gradient-to-b from-fuchsia-900/60 to-stone-950 border-fuchsia-500/70 text-fuchsia-200 hover:border-fuchsia-400 hover:scale-[1.02] cursor-pointer shadow-lg' }}">
                                        <i class="fa-solid fa-gem text-2xl text-fuchsia-400"></i>
                                        <span class="font-black text-lg">200</span>
                                        <span class="text-[10px] uppercase tracking-widest opacity-80">Gemów za 7 dni</span>
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>

    </div>

    <style>
        .animate-bounce-slow {
            animation: bounce-slow 4s infinite ease-in-out;
        }
        @keyframes bounce-slow {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .animate-fade-in-down {
            animation: fade-in-down 0.5s ease-out forwards;
        }
        @keyframes fade-in-down {
            0% { opacity: 0; transform: translate(-50%, -20px); }
            100% { opacity: 1; transform: translate(-50%, 0); }
        }

        /* Epic success animations (Zaczarowanie) */
        @keyframes epic-success {
            0% { transform: scale(1) rotate(0deg); filter: brightness(1) drop-shadow(0 0 15px rgba(168,85,247,0.8)); }
            20% { transform: scale(1.4) rotate(5deg); filter: brightness(2) drop-shadow(0 0 50px rgba(253,224,71,1)); }
            40% { transform: scale(1.1) rotate(-5deg); }
            60% { transform: scale(1.2) rotate(2deg); filter: brightness(1.5) drop-shadow(0 0 30px rgba(253,224,71,0.8)); }
            100% { transform: scale(1) rotate(0deg); filter: brightness(1) drop-shadow(0 0 15px rgba(168,85,247,0.8)); }
        }
        @keyframes ping-fast {
            0% { transform: scale(1); opacity: 1; }
            100% { transform: scale(3); opacity: 0; }
        }
        @keyframes fade-out-slow {
            0% { opacity: 1; transform: scale(0.5); }
            100% { opacity: 0; transform: scale(2); }
        }
        @keyframes float-up-left {
            0% { transform: translate(0, 0) scale(0.5); opacity: 1; }
            100% { transform: translate(-100px, -100px) scale(1.5) rotate(-45deg); opacity: 0; }
        }
        @keyframes float-up-right {
            0% { transform: translate(0, 0) scale(0.5); opacity: 1; }
            100% { transform: translate(100px, -120px) scale(1.2) rotate(45deg); opacity: 0; }
        }
        @keyframes float-down-left {
            0% { transform: translate(0, 0) scale(0.8); opacity: 1; }
            100% { transform: translate(-120px, 80px) scale(1.5) rotate(-30deg); opacity: 0; }
        }
        @keyframes float-down-right {
            0% { transform: translate(0, 0) scale(1); opacity: 1; }
            100% { transform: translate(110px, 90px) scale(0.8) rotate(60deg); opacity: 0; }
        }
    </style>
</div>
