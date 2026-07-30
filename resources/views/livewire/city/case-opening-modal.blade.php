<div x-data="{
    showModal: @entangle('isOpen'),
    isSpinning: @entangle('isSpinning'),
    isFinished: @entangle('isFinished'),
    translateX: 0,
    itemWidth: 144, // 132px width + 12px gap
    targetIndex: 28,

    init() {
        this.$wire.on('start-case-spin', (data) => {
            let payload = Array.isArray(data) ? data[0] : data;
            if (!payload || !payload.roulette_items) return;

            this.targetIndex = payload.winning_index || 28;
            this.translateX = 0;
            
            // Allow DOM render then trigger animation
            setTimeout(() => {
                this.spinReel();
            }, 100);
        });
    },

    spinReel() {
        let containerWidth = this.$refs.viewport ? this.$refs.viewport.offsetWidth : 600;
        let randomOffset = Math.floor(Math.random() * 40) - 20; // +/- 20px variation inside winning card
        let targetPos = (this.targetIndex * this.itemWidth) - (containerWidth / 2) + (this.itemWidth / 2) + randomOffset;
        
        this.translateX = targetPos;

        // Play tick sound loop during spin
        let startTime = Date.now();
        let duration = 6000;

        let tickInterval = setInterval(() => {
            let elapsed = Date.now() - startTime;
            if (elapsed >= duration) {
                clearInterval(tickInterval);
                setTimeout(() => {
                    this.$wire.call('onSpinCompleted');
                }, 300);
            }
        }, 120);
    }
}">

    <template x-teleport="body">
        <div x-show="showModal" 
             style="display: none;"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="fixed inset-0 z-[250] flex items-center justify-center p-4 bg-slate-950/90 backdrop-blur-md overflow-y-auto">

            <div @click.outside="if(!isSpinning) $wire.call('closeModal')"
                 class="relative w-full max-w-3xl bg-slate-900 border-2 border-amber-500/80 rounded-2xl shadow-[0_0_60px_rgba(245,158,11,0.25)] p-6 sm:p-8 flex flex-col items-center overflow-hidden my-auto">

                {{-- Gold Corners --}}
                <div class="absolute top-0 left-0 w-8 h-8 border-t-4 border-l-4 border-amber-400 rounded-tl-xl pointer-events-none"></div>
                <div class="absolute top-0 right-0 w-8 h-8 border-t-4 border-r-4 border-amber-400 rounded-tr-xl pointer-events-none"></div>
                <div class="absolute bottom-0 left-0 w-8 h-8 border-b-4 border-l-4 border-amber-400 rounded-bl-xl pointer-events-none"></div>
                <div class="absolute bottom-0 right-0 w-8 h-8 border-b-4 border-r-4 border-amber-400 rounded-br-xl pointer-events-none"></div>

                {{-- Close Button --}}
                <button x-show="!isSpinning" 
                        @click="$wire.call('closeModal')" 
                        class="absolute top-4 right-4 text-slate-400 hover:text-amber-200 text-3xl font-bold transition-colors">
                    &times;
                </button>

                {{-- Header --}}
                <div class="text-center mb-6">
                    <h3 class="text-xs uppercase tracking-widest text-amber-400/80 font-bold mb-1">Otwieranie Skrzyni</h3>
                    <h2 class="text-2xl sm:text-3xl font-black text-amber-100 medieval-font tracking-wide drop-shadow-md flex items-center justify-center gap-3">
                        <i class="fa-solid fa-box-open text-amber-400"></i>
                        <span>{{ $chestData['chest_template']['name'] ?? 'Skrzynia Łupów' }}</span>
                    </h2>
                </div>

                {{-- Error Alert --}}
                @if($errorMessage)
                    <div class="w-full mb-4 p-4 bg-red-950/90 border border-red-500/60 rounded-xl text-center">
                        <p class="text-red-200 font-bold text-sm flex items-center justify-center gap-2">
                            <i class="fa-solid fa-triangle-exclamation text-red-400"></i>
                            {{ $errorMessage }}
                        </p>
                    </div>
                @endif

                @if($chestData && isset($chestData['roulette_items']))
                    {{-- ROULETTE VIEWPORT CONTAINER --}}
                    <div class="relative w-full max-w-2xl mb-6">
                        
                        {{-- Top & Bottom Center Selection Markers --}}
                        <div class="absolute -top-3 left-1/2 -translate-x-1/2 z-30 text-amber-400 text-2xl filter drop-shadow-[0_0_8px_rgba(245,158,11,0.9)] animate-pulse">
                            ▼
                        </div>
                        <div class="absolute -bottom-3 left-1/2 -translate-x-1/2 z-30 text-amber-400 text-2xl filter drop-shadow-[0_0_8px_rgba(245,158,11,0.9)] animate-pulse">
                            ▲
                        </div>

                        {{-- Vertical Center Target Line --}}
                        <div class="absolute top-0 bottom-0 left-1/2 -translate-x-1/2 w-1 bg-amber-400/80 z-20 shadow-[0_0_12px_#f59e0b] pointer-events-none"></div>

                        {{-- Viewport Outer Box --}}
                        <div x-ref="viewport" class="w-full h-44 bg-slate-950 rounded-xl border-2 border-amber-600/60 shadow-inner overflow-hidden relative">
                            
                            {{-- Vignette Glass Gradients --}}
                            <div class="absolute inset-y-0 left-0 w-24 bg-gradient-to-r from-slate-950 via-slate-950/80 to-transparent z-10 pointer-events-none"></div>
                            <div class="absolute inset-y-0 right-0 w-24 bg-gradient-to-l from-slate-950 via-slate-950/80 to-transparent z-10 pointer-events-none"></div>

                            {{-- Moving Reel Strip --}}
                            <div class="flex gap-3 py-4 px-2 absolute top-0 bottom-0 left-0 items-center transition-transform"
                                 :style="`transform: translateX(-${translateX}px); transition-duration: ${isSpinning ? '6000ms' : '0ms'}; transition-timing-function: cubic-bezier(0.12, 0.8, 0.15, 1.0);`">
                                
                                @foreach($chestData['roulette_items'] as $index => $item)
                                    @php
                                        $rarityBg = match($item['rarity']) {
                                            'epic' => 'bg-gradient-to-b from-purple-950 via-slate-900 to-slate-950 border-purple-500/80 shadow-[0_0_15px_rgba(168,85,247,0.3)]',
                                            'rare' => 'bg-gradient-to-b from-blue-950 via-slate-900 to-slate-950 border-blue-500/80 shadow-[0_0_15px_rgba(59,130,246,0.3)]',
                                            'uncommon' => 'bg-gradient-to-b from-emerald-950 via-slate-900 to-slate-950 border-emerald-500/80 shadow-[0_0_15px_rgba(16,185,129,0.3)]',
                                            default => 'bg-gradient-to-b from-slate-800 via-slate-900 to-slate-950 border-slate-600/80',
                                        };
                                        $rarityBadge = match($item['rarity']) {
                                            'epic' => 'text-purple-400 border-purple-500/40 bg-purple-950/60',
                                            'rare' => 'text-blue-400 border-blue-500/40 bg-blue-950/60',
                                            'uncommon' => 'text-emerald-400 border-emerald-500/40 bg-emerald-950/60',
                                            default => 'text-slate-400 border-slate-600/40 bg-slate-900/60',
                                        };
                                    @endphp

                                    <div class="w-32 h-36 flex-shrink-0 rounded-xl border-2 {{ $rarityBg }} p-2.5 flex flex-col items-center justify-between relative shadow-lg group">
                                        
                                        {{-- Quantity Badge --}}
                                        <div class="absolute top-1.5 right-1.5 px-1.5 py-0.5 rounded text-[10px] font-black bg-black/70 text-amber-300 border border-amber-500/30">
                                            x{{ $item['quantity'] }}
                                        </div>

                                        {{-- Icon --}}
                                        <div class="w-16 h-16 mt-2 flex items-center justify-center">
                                            <img src="{{ asset('assets/items/' . $item['icon']) }}" 
                                                 alt="{{ $item['name'] }}" 
                                                 class="w-full h-full object-contain filter drop-shadow-md">
                                        </div>

                                        {{-- Name --}}
                                        <div class="w-full text-center">
                                            <span class="text-[11px] font-bold text-slate-200 line-clamp-1 block truncate">
                                                {{ $item['name'] }}
                                            </span>
                                            <span class="text-[9px] uppercase font-bold tracking-wider px-1.5 py-0.2 rounded border {{ $rarityBadge }} inline-block mt-0.5">
                                                {{ $item['rarity'] }}
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- WINNING REWARD ANNOUNCEMENT BOX (Shown after spin finishes) --}}
                    @if($isFinished && isset($chestData['winning_item']))
                        <div x-transition:enter="transition ease-out duration-500"
                             x-transition:enter-start="opacity-0 scale-90 translate-y-4"
                             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                             class="w-full max-w-md bg-gradient-to-b from-amber-950/90 via-slate-900 to-slate-950 border-2 border-amber-500/80 rounded-2xl p-5 text-center shadow-[0_0_40px_rgba(245,158,11,0.3)] mb-4">
                            
                            <div class="text-xs uppercase tracking-widest text-amber-400 font-bold mb-1">🎉 Wygrany Łup!</div>
                            
                            <div class="w-24 h-24 mx-auto my-3 relative flex items-center justify-center bg-slate-950 rounded-2xl border-2 border-amber-400 shadow-2xl">
                                <img src="{{ asset('assets/items/' . $chestData['winning_item']['icon']) }}" 
                                     alt="{{ $chestData['winning_item']['name'] }}" 
                                     class="w-20 h-20 object-contain animate-bounce">
                            </div>

                            <h3 class="text-xl font-black text-amber-100 medieval-font mb-1">
                                {{ $chestData['winning_item']['name'] }}
                            </h3>
                            <p class="text-sm font-bold text-amber-300 mb-2">
                                Ilość: <span class="text-white font-extrabold text-base">x{{ $chestData['winning_item']['quantity'] }}</span>
                            </p>
                            <p class="text-xs text-slate-400">
                                Nagroda została automatycznie przekazana do Twojego magazynu materiałów!
                            </p>
                        </div>
                    @endif
                @endif

                {{-- Action Buttons --}}
                <div class="flex items-center justify-center gap-4 w-full max-w-md mt-2">
                    @if($isFinished)
                        <button @click="$wire.call('closeModal')" 
                                class="px-6 py-3 bg-gradient-to-r from-amber-700 to-amber-600 hover:from-amber-600 hover:to-amber-500 text-white font-bold rounded-xl shadow-lg border border-amber-400 medieval-font text-sm transition-all transform hover:-translate-y-0.5">
                            <i class="fa-solid fa-check mr-1.5"></i> Odbierz i Zamknij
                        </button>
                    @elseif(!$isSpinning)
                        <button @click="$wire.call('closeModal')" 
                                class="px-6 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold rounded-xl border border-slate-600 medieval-font text-xs transition-colors">
                            Zamknij
                        </button>
                    @endif
                </div>

            </div>
        </div>
    </template>
</div>
