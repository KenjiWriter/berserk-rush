<div class="min-h-screen bg-gradient-to-b from-purple-950 via-indigo-950 to-purple-950 text-amber-100 relative overflow-hidden font-sans">
    <!-- Magical Background Effects -->
    <div class="absolute top-0 left-1/4 w-96 h-96 bg-purple-600/20 rounded-full mix-blend-screen filter blur-3xl opacity-50 animate-pulse"></div>
    <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-fuchsia-600/20 rounded-full mix-blend-screen filter blur-3xl opacity-50 animate-pulse" style="animation-delay: 2s;"></div>
    <div class="absolute inset-0 bg-[url('/img/noise.png')] opacity-[0.03] mix-blend-overlay"></div>

    <div class="relative container mx-auto px-4 py-8 min-h-screen flex flex-col">
        
        {{-- Header Bar --}}
        <div class="flex flex-col md:flex-row items-center justify-between mb-10 gap-4">
            <div class="bg-purple-900/40 border border-purple-500/30 rounded-xl p-4 shadow-2xl backdrop-blur-md flex items-center gap-4">
                <div class="text-4xl filter drop-shadow-[0_0_8px_rgba(168,85,247,0.8)]">🧙‍♀️</div>
                <div>
                    <h2 class="text-2xl font-bold text-purple-100 medieval-font tracking-wider uppercase">Chata Wiedźmy</h2>
                    <p class="text-sm text-purple-300 font-medium">{{ $character->name }}</p>
                </div>
            </div>
            
            <div class="flex gap-4 items-center">
                <div class="bg-amber-900/40 border border-amber-500/40 text-amber-200 font-bold py-2 px-6 rounded-xl shadow-[0_0_15px_rgba(245,158,11,0.2)] backdrop-blur-md flex items-center gap-3">
                    <span class="text-2xl drop-shadow-md">💰</span>
                    <span class="text-lg tracking-wide">{{ number_format($character->gold, 0, ',', ' ') }} Złota</span>
                </div>

                <button wire:click="backToHub" @click="$dispatch('location-leave')" class="group relative overflow-hidden bg-slate-800/80 hover:bg-slate-700 text-amber-100 font-bold py-3 px-6 rounded-xl transition-all duration-300 shadow-[0_0_15px_rgba(0,0,0,0.5)] border border-slate-600 backdrop-blur-md flex items-center gap-2">
                    <div class="absolute inset-0 w-full h-full bg-gradient-to-r from-transparent via-white/10 to-transparent -translate-x-full group-hover:animate-[shimmer_1.5s_infinite]"></div>
                    <span class="text-xl group-hover:-translate-x-1 transition-transform">🏰</span>
                    <span class="medieval-font tracking-wide">Powrót</span>
                </button>
            </div>
        </div>

        {{-- Messages --}}
        @if($message)
            <div class="mb-6 mx-auto max-w-4xl w-full">
                <div class="p-4 rounded-xl backdrop-blur-md border shadow-lg {{ $messageType === 'success' ? 'bg-emerald-900/50 border-emerald-500/50 text-emerald-100' : 'bg-red-900/50 border-red-500/50 text-red-100' }} flex items-center gap-3 animate-[fade-in_0.3s_ease-out]">
                    <span class="text-2xl">{{ $messageType === 'success' ? '✨' : '🔥' }}</span>
                    <span class="font-semibold text-lg">{{ $message }}</span>
                </div>
            </div>
        @endif

        {{-- Navigation Tabs --}}
        <div class="flex justify-center gap-4 mb-8">
            <button wire:click="switchTab('shop')" 
                class="relative px-8 py-3 rounded-xl font-bold transition-all duration-300 overflow-hidden group border-2 medieval-font text-lg tracking-wider
                {{ $activeTab === 'shop' 
                    ? 'bg-purple-600 border-purple-400 text-white shadow-[0_0_20px_rgba(147,51,234,0.5)]' 
                    : 'bg-purple-900/30 border-purple-700/50 text-purple-300 hover:bg-purple-800/50 hover:border-purple-500 hover:text-purple-100 backdrop-blur-sm' }}">
                <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent"></div>
                <span class="relative flex items-center gap-2">🛒 Sklep Alchemiczny</span>
            </button>
            
            <button wire:click="switchTab('crafting')" 
                class="relative px-8 py-3 rounded-xl font-bold transition-all duration-300 overflow-hidden group border-2 medieval-font text-lg tracking-wider
                {{ $activeTab === 'crafting' 
                    ? 'bg-emerald-700 border-emerald-400 text-white shadow-[0_0_20px_rgba(4,120,87,0.5)]' 
                    : 'bg-purple-900/30 border-purple-700/50 text-purple-300 hover:bg-emerald-900/40 hover:border-emerald-600 hover:text-emerald-100 backdrop-blur-sm' }}">
                <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent"></div>
                <span class="relative flex items-center gap-2">⚗️ Warzenie Mikstur</span>
            </button>
        </div>

        {{-- Main Content Area --}}
        <div class="max-w-6xl mx-auto w-full flex-1">
            @if($activeTab === 'shop')
                <div class="space-y-8 animate-[fade-in_0.4s_ease-out]">
                    
                    {{-- Special Potion Highlight --}}
                    <div class="relative bg-gradient-to-r from-fuchsia-900/60 to-purple-900/60 border border-fuchsia-500/50 rounded-2xl p-6 md:p-8 shadow-[0_0_30px_rgba(192,38,211,0.2)] backdrop-blur-xl overflow-hidden group">
                        <div class="absolute top-0 right-0 w-64 h-64 bg-fuchsia-500/20 rounded-full mix-blend-screen filter blur-3xl opacity-0 group-hover:opacity-100 transition-opacity duration-700"></div>
                        
                        <div class="relative flex flex-col md:flex-row items-center justify-between gap-6">
                            <div class="flex-1 text-center md:text-left">
                                <div class="inline-block bg-fuchsia-950/80 border border-fuchsia-400/50 text-fuchsia-200 text-xs font-bold uppercase tracking-widest px-3 py-1 rounded-full mb-3 shadow-lg">
                                    🌟 Oferta Specjalna
                                </div>
                                <h3 class="text-3xl font-bold text-fuchsia-100 medieval-font mb-2 drop-shadow-md">
                                    Eliksir Wiedzy Absolutnej
                                </h3>
                                <p class="text-fuchsia-200/80 mb-4 font-medium text-lg max-w-2xl">
                                    "Zbyt dużo tej mikstury spali twój umysł, wędrowcze. Dam ci jedną na dobę."<br>
                                    <span class="text-amber-300 font-semibold">+20% zdobywanego doświadczenia z potworów przez 10 minut.</span>
                                </p>
                            </div>
                            
                            <div class="flex flex-col items-center justify-center bg-black/30 p-5 rounded-2xl border border-white/5 backdrop-blur-md min-w-[240px]">
                                <div class="text-amber-300 font-bold text-xl mb-4 flex items-center gap-2 drop-shadow-md">
                                    💰 1500 Złota
                                </div>
                                @if($canBuySpecial)
                                    <button wire:click="buySpecialExpPotion" wire:loading.attr="disabled" class="w-full relative overflow-hidden bg-gradient-to-r from-fuchsia-600 to-purple-600 hover:from-fuchsia-500 hover:to-purple-500 text-white font-bold py-3 px-6 rounded-xl shadow-[0_0_15px_rgba(192,38,211,0.5)] border border-fuchsia-400/50 transition-all duration-300 transform hover:scale-105 active:scale-95 medieval-font tracking-wide">
                                        <span wire:loading.remove>Kup Miksturę</span>
                                        <span wire:loading class="flex items-center justify-center gap-2">
                                            <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                            Warzenie...
                                        </span>
                                    </button>
                                @else
                                    <div class="w-full text-center">
                                        <div class="text-xs text-fuchsia-300 font-bold mb-1 uppercase tracking-wider">Kolejna porcja za:</div>
                                        <div class="text-white font-mono text-xl font-bold bg-black/40 py-2 px-4 rounded-lg border border-fuchsia-500/30 shadow-inner"
                                             x-data="{ 
                                                end: new Date('{{ $specialCooldown->toIso8601String() }}').getTime(),
                                                timeLeft: '',
                                                update() {
                                                    let diff = this.end - new Date().getTime();
                                                    if(diff <= 0) { this.timeLeft = 'Gotowe!'; return; }
                                                    let h = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                                                    let m = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                                                    let s = Math.floor((diff % (1000 * 60)) / 1000);
                                                    this.timeLeft = h + 'h ' + m + 'm ' + s + 's';
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
                            <div class="h-px bg-gradient-to-r from-transparent to-purple-500/50 flex-1"></div>
                            <h3 class="text-2xl font-bold text-purple-200 medieval-font tracking-widest uppercase">Półki Sklepowe</h3>
                            <div class="h-px bg-gradient-to-r from-purple-500/50 to-transparent flex-1"></div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                            @forelse($shopItems as $mi)
                                <div class="bg-indigo-950/40 border border-indigo-500/30 rounded-2xl p-5 shadow-lg backdrop-blur-md flex flex-col justify-between transition-all duration-300 hover:bg-indigo-900/50 hover:border-indigo-400/50 hover:shadow-[0_0_20px_rgba(79,70,229,0.2)] hover:-translate-y-1">
                                    <div class="mb-4">
                                        <div class="flex justify-between items-start mb-3">
                                            <div class="bg-indigo-900/80 p-3 rounded-xl border border-indigo-500/30 text-3xl shadow-inner">
                                                🍾
                                            </div>
                                            @if($mi->is_limited)
                                                <span class="text-xs font-bold bg-red-900/80 text-red-200 px-2 py-1 rounded-md border border-red-500/50 shadow-sm">
                                                    Limit: {{ $mi->max_quantity - $mi->sold_quantity }}
                                                </span>
                                            @endif
                                        </div>
                                        <h4 class="text-xl font-bold text-indigo-100 medieval-font mb-2">{{ $mi->template->name }}</h4>
                                        <p class="text-sm text-indigo-300/80 min-h-[40px]">{{ $mi->template->description ?? 'Tajemniczy wywar nieznanego pochodzenia.' }}</p>
                                    </div>
                                    
                                    <div class="flex items-center justify-between mt-auto pt-4 border-t border-indigo-500/20">
                                        <div class="text-amber-300 font-bold flex items-center gap-1.5 drop-shadow-md bg-black/20 px-3 py-1.5 rounded-lg border border-white/5">
                                            <span>💰</span>
                                            <span>{{ number_format($shopPrices[$mi->id] ?? 0, 0, ',', ' ') }}</span>
                                        </div>
                                        <button wire:click="buyItem({{ $mi->id }})" wire:loading.attr="disabled" class="bg-indigo-600 hover:bg-indigo-500 text-white font-bold py-2 px-5 rounded-lg shadow-md border border-indigo-400 transition-all duration-200 transform hover:scale-105 active:scale-95 text-sm uppercase tracking-wider">
                                            Kup
                                        </button>
                                    </div>
                                </div>
                            @empty
                                <div class="col-span-full text-center py-12 bg-black/20 rounded-2xl border border-white/5 backdrop-blur-sm">
                                    <span class="text-4xl mb-4 block opacity-50">🕸️</span>
                                    <p class="text-purple-300/70 font-medium">Półki świecą pustkami. Wiedźma poszła zbierać zioła.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

            @elseif($activeTab === 'crafting')
                <div class="space-y-6 animate-[fade-in_0.4s_ease-out]">
                    <div class="text-center mb-8">
                        <h3 class="text-3xl font-bold text-emerald-300 medieval-font mb-2 drop-shadow-md">Kocioł Alchemiczny</h3>
                        <p class="text-emerald-200/70">Wybierz przepis i rzuć składniki. Resztą zajmie się magia.</p>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        @forelse($recipes as $recipe)
                            <div class="relative bg-emerald-950/30 border border-emerald-500/30 rounded-2xl p-6 shadow-lg backdrop-blur-md transition-all duration-300 hover:bg-emerald-900/40 hover:border-emerald-500/60 hover:shadow-[0_0_20px_rgba(16,185,129,0.15)] flex flex-col justify-between">
                                <div>
                                    <div class="flex items-center gap-4 mb-5 border-b border-emerald-500/20 pb-4">
                                        <div class="bg-emerald-900/60 p-3 rounded-xl border border-emerald-400/30 text-3xl shadow-inner relative overflow-hidden group">
                                            <div class="absolute inset-0 bg-emerald-400/20 translate-y-full group-hover:translate-y-0 transition-transform duration-500"></div>
                                            ⚗️
                                        </div>
                                        <h4 class="text-xl font-bold text-emerald-100 medieval-font tracking-wide">{{ $recipe['result_name'] }}</h4>
                                    </div>
                                    
                                    <div class="mb-5">
                                        <h5 class="text-xs text-emerald-400/70 uppercase tracking-widest font-bold mb-3">Wymagane Składniki:</h5>
                                        <div class="space-y-2">
                                            @foreach($recipe['ingredients'] as $ing)
                                                <div class="flex items-center justify-between bg-black/20 p-2 rounded-lg border {{ $ing['ok'] ? 'border-emerald-500/30' : 'border-red-500/30' }}">
                                                    <span class="text-sm font-medium {{ $ing['ok'] ? 'text-emerald-200' : 'text-red-300' }}">{{ $ing['name'] }}</span>
                                                    <div class="flex items-center gap-2">
                                                        <span class="text-xs font-mono {{ $ing['ok'] ? 'text-emerald-400' : 'text-red-400' }} bg-black/40 px-2 py-0.5 rounded">
                                                            {{ $ing['owned'] }} / {{ $ing['required'] }}
                                                        </span>
                                                        @if($ing['ok'])
                                                            <span class="text-emerald-400">✓</span>
                                                        @else
                                                            <span class="text-red-400">✗</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="flex items-center justify-between mt-4 bg-black/30 p-3 rounded-xl border border-white/5">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs text-emerald-400/70 uppercase tracking-widest font-bold">Koszt:</span>
                                        <span class="text-amber-300 font-bold bg-amber-900/30 px-2 py-1 rounded border border-amber-500/30 flex items-center gap-1 text-sm shadow-inner">
                                            💰 {{ $recipe['gold_cost'] }}
                                        </span>
                                    </div>
                                    
                                    <button wire:click="craftPotion('{{ $recipe['id'] }}')" 
                                        wire:loading.attr="disabled" 
                                        @if(!$recipe['can_craft']) disabled @endif
                                        class="relative overflow-hidden font-bold py-2.5 px-6 rounded-lg shadow-lg border transition-all duration-300 medieval-font tracking-wide uppercase text-sm
                                        {{ $recipe['can_craft'] 
                                            ? 'bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white border-emerald-400 hover:shadow-[0_0_15px_rgba(16,185,129,0.5)] transform hover:-translate-y-0.5' 
                                            : 'bg-slate-800 text-slate-400 border-slate-600 cursor-not-allowed opacity-70' }}">
                                        <span wire:loading.remove wire:target="craftPotion('{{ $recipe['id'] }}')">
                                            Uwarz
                                        </span>
                                        <span wire:loading wire:target="craftPotion('{{ $recipe['id'] }}')" class="flex items-center gap-2">
                                            <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                            Bulgocze...
                                        </span>
                                    </button>
                                </div>
                            </div>
                        @empty
                            <div class="col-span-full text-center py-12 bg-black/20 rounded-2xl border border-white/5 backdrop-blur-sm">
                                <span class="text-4xl mb-4 block opacity-50">📜</span>
                                <p class="text-emerald-300/70 font-medium">Brak znanych receptur. Wiedźma zapomniała przepisów.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            @endif
        </div>
        
    </div>
</div>
