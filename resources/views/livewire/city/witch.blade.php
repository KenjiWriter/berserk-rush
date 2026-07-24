<div class="min-h-screen text-amber-100 relative overflow-hidden select-none" style="background: radial-gradient(circle at 50% 0%, #1e1b4b 0%, #0f172a 60%, #020617 100%); font-family: 'Cinzel', serif;">
    <!-- Static Background Image with Dark & Purple Vignette Overlay -->
    <div class="absolute inset-0 bg-cover bg-center opacity-35 mix-blend-luminosity pointer-events-none" style="background-image: url('{{ asset('img/witch-bg.png') }}');"></div>
    <div class="absolute inset-0 bg-gradient-to-b from-purple-950/70 via-slate-950/85 to-black pointer-events-none"></div>

    <!-- Ambient Magical Glow Effects -->
    <div class="absolute top-0 left-1/4 w-[500px] h-[500px] bg-purple-600/15 rounded-full filter blur-3xl opacity-60 animate-pulse pointer-events-none"></div>
    <div class="absolute bottom-1/4 right-1/4 w-[500px] h-[500px] bg-fuchsia-600/15 rounded-full filter blur-3xl opacity-60 animate-pulse pointer-events-none" style="animation-delay: 2s;"></div>

    <div class="relative container mx-auto px-4 py-6 sm:py-8 min-h-screen z-10 max-w-7xl flex flex-col">
        
        {{-- Header Section --}}
        <div class="flex flex-col md:flex-row items-center justify-between gap-4 mb-8 border-b-2 border-purple-900/60 pb-6 bg-gradient-to-b from-purple-950/90 via-slate-950/80 to-transparent p-4 rounded-2xl shadow-2xl backdrop-blur-md">
            <div class="flex items-center space-x-4">
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
            
            <div class="flex flex-wrap items-center gap-4">
                {{-- Gold Badge --}}
                <div class="bg-gradient-to-b from-stone-950 via-stone-900 to-black border-2 border-amber-600/80 px-4 py-2 rounded-xl shadow-[inset_0_2px_4px_rgba(0,0,0,0.8),0_0_15px_rgba(245,158,11,0.2)] flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-amber-950/80 border border-amber-500/60 flex items-center justify-center text-amber-400 text-base shrink-0">
                        <i class="fa-solid fa-coins"></i>
                    </div>
                    <div>
                        <span class="text-[9px] text-amber-400/80 font-extrabold uppercase tracking-widest block leading-none">POSIADANE ZŁOTO</span>
                        <span class="text-lg sm:text-xl font-black text-amber-300 drop-shadow">{{ number_format($character->gold, 0, ',', ' ') }}</span>
                    </div>
                </div>

                {{-- Back Button --}}
                <button wire:click="backToHub" @click="$dispatch('location-leave', { text: 'Podróż do Miasta...', icon: 'fa-solid fa-archway' }); $dispatch('play-audio', { type: 'tab' })"
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
        <div class="flex justify-center gap-4 mb-8">
            <button wire:click="switchTab('shop')" 
                class="relative px-6 sm:px-8 py-3 rounded-xl font-bold transition-all duration-300 overflow-hidden group border-2 medieval-font text-base sm:text-lg tracking-wider cursor-pointer
                {{ $activeTab === 'shop' 
                    ? 'bg-gradient-to-r from-fuchsia-800 to-purple-800 border-fuchsia-400 text-white shadow-[0_0_25px_rgba(217,70,239,0.4)] scale-105' 
                    : 'bg-stone-950/70 border-purple-900/60 text-purple-300 hover:bg-purple-900/50 hover:border-purple-500 hover:text-purple-100 backdrop-blur-md' }}">
                <div class="absolute inset-0 bg-gradient-to-t from-black/30 to-transparent"></div>
                <span class="relative flex items-center gap-2">
                    <i class="fa-solid fa-shop text-amber-400"></i>
                    Sklep Alchemiczny
                </span>
            </button>
            
            <button wire:click="switchTab('crafting')" 
                class="relative px-6 sm:px-8 py-3 rounded-xl font-bold transition-all duration-300 overflow-hidden group border-2 medieval-font text-base sm:text-lg tracking-wider cursor-pointer
                {{ $activeTab === 'crafting' 
                    ? 'bg-gradient-to-r from-emerald-800 to-teal-800 border-emerald-400 text-white shadow-[0_0_25px_rgba(16,185,129,0.4)] scale-105' 
                    : 'bg-stone-950/70 border-purple-900/60 text-purple-300 hover:bg-emerald-950/50 hover:border-emerald-500 hover:text-emerald-100 backdrop-blur-md' }}">
                <div class="absolute inset-0 bg-gradient-to-t from-black/30 to-transparent"></div>
                <span class="relative flex items-center gap-2">
                    <i class="fa-solid fa-flask-vial text-emerald-400"></i>
                    Warzenie Mikstur
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
                                    <span>+20% zdobywanego doświadczenia z potworów przez 10 minut</span>
                                </div>
                            </div>
                            
                            <div class="flex flex-col items-center justify-center bg-black/50 p-5 rounded-2xl border border-fuchsia-500/30 backdrop-blur-md min-w-[250px] shadow-2xl">
                                <div class="text-amber-300 font-extrabold text-xl mb-4 flex items-center gap-2 drop-shadow-md">
                                    <i class="fa-solid fa-coins text-amber-400"></i>
                                    <span>1 500 Złota</span>
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
                                                            <div class="flex flex-wrap justify-center gap-1.5 mt-2">
                                                                @foreach(array_unique($ing['dropped_by']) as $monsterName)
                                                                    <span class="bg-stone-900 border border-stone-700 text-emerald-200 text-[10px] px-2 py-0.5 rounded-md">{{ $monsterName }}</span>
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
            @endif
        </div>
        
    </div>
</div>
