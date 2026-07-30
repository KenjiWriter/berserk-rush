<div x-data="{
    showModal: @entangle('isOpen'),
    isSpinning: @entangle('isSpinning'),
    isFinished: @entangle('isFinished'),
    translates: [0, 0, 0],
    itemWidth: 144, // 132px width + 12px gap

    handleSpin(data) {
        let payload = data;
        if (data && data.payload) payload = data.payload;
        if (Array.isArray(payload)) payload = payload[0];
        if (!payload) return;

        this.translates = [0, 0, 0];
        
        setTimeout(() => {
            this.spinAllReels(payload);
        }, 100);
    },

    spinAllReels(payload) {
        let spins = payload.spins || [payload];

        spins.forEach((spin, idx) => {
            let viewportEl = this.$refs['viewport' + idx] || this.$refs.viewport0 || this.$refs.viewport;
            let containerWidth = viewportEl ? viewportEl.offsetWidth : 600;
            let targetIdx = spin.winning_index !== undefined ? spin.winning_index : 28;

            let itemCardWidth = 128; // w-32 = 8rem = 128px
            let gap = 12;            // gap-3 = 0.75rem = 12px
            let paddingLeft = 8;     // px-2 = 0.5rem = 8px
            let stride = itemCardWidth + gap; // 140px

            let randomOffset = Math.floor(Math.random() * 40) - 20; // +/- 20px variation within the card
            let targetCenter = paddingLeft + (targetIdx * stride) + (itemCardWidth / 2); // targetIdx * 140 + 72
            let targetPos = targetCenter - (containerWidth / 2) + randomOffset;

            this.translates[idx] = targetPos;
        });

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
    },

    init() {
        this.$wire.on('start-case-spin', (data) => {
            this.handleSpin(data);
        });
        if (typeof Livewire !== 'undefined') {
            Livewire.on('start-case-spin', (data) => {
                this.handleSpin(data);
            });
        }
    }
}" @start-case-spin.window="handleSpin($event.detail)">

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
                 class="relative w-full max-w-3xl bg-slate-900 border-2 border-amber-500/80 rounded-2xl shadow-[0_0_60px_rgba(245,158,11,0.25)] p-6 sm:p-8 flex flex-col items-center overflow-hidden my-auto max-h-[92vh] overflow-y-auto">

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
                <div class="text-center mb-5">
                    <h3 class="text-xs uppercase tracking-widest text-amber-400/80 font-bold mb-1">
                        Otwieranie {{ ($chestData['count'] ?? 1) > 1 ? ($chestData['count'] . 'x Skrzyń Na Raz') : 'Skrzyni' }}
                    </h3>
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

                @if($chestData && (isset($chestData['spins']) || isset($chestData['roulette_items'])))
                    @php
                        $spinsList = $chestData['spins'] ?? [[
                            'spin_index' => 0,
                            'winning_item' => $chestData['winning_item'] ?? null,
                            'winning_index' => $chestData['winning_index'] ?? 28,
                            'roulette_items' => $chestData['roulette_items'] ?? []
                        ]];
                    @endphp

                    {{-- MULTI-REEL STACKED CONTAINER --}}
                    <div class="w-full max-w-2xl space-y-4 mb-4">
                        @foreach($spinsList as $sIdx => $sData)
                            <div class="relative w-full">
                                {{-- Spin Label if multi --}}
                                @if(count($spinsList) > 1)
                                    <div class="text-[11px] font-extrabold text-amber-300 uppercase tracking-widest mb-1 flex items-center gap-1.5">
                                        <i class="fa-solid fa-cube text-amber-400"></i> Losowanie #{{ $sIdx + 1 }}
                                    </div>
                                @endif

                                {{-- Top & Bottom Center Markers --}}
                                <div class="absolute -top-2.5 left-1/2 -translate-x-1/2 z-30 text-amber-400 text-xl filter drop-shadow-[0_0_8px_rgba(245,158,11,0.9)] animate-pulse">
                                    ▼
                                </div>
                                <div class="absolute -bottom-2.5 left-1/2 -translate-x-1/2 z-30 text-amber-400 text-xl filter drop-shadow-[0_0_8px_rgba(245,158,11,0.9)] animate-pulse">
                                    ▲
                                </div>
                                <div class="absolute top-0 bottom-0 left-1/2 -translate-x-1/2 w-1 bg-amber-400/80 z-20 shadow-[0_0_12px_#f59e0b] pointer-events-none"></div>

                                {{-- Viewport Box --}}
                                <div x-ref="viewport{{ $sIdx }}" class="w-full h-36 bg-slate-950 rounded-xl border-2 border-amber-600/60 shadow-inner overflow-hidden relative">
                                    
                                    {{-- Vignette Gradients --}}
                                    <div class="absolute inset-y-0 left-0 w-20 bg-gradient-to-r from-slate-950 via-slate-950/80 to-transparent z-10 pointer-events-none"></div>
                                    <div class="absolute inset-y-0 right-0 w-20 bg-gradient-to-l from-slate-950 via-slate-950/80 to-transparent z-10 pointer-events-none"></div>

                                    {{-- Moving Reel Strip --}}
                                    <div class="flex gap-3 py-3 px-2 absolute top-0 bottom-0 left-0 items-center transition-transform"
                                         :style="`transform: translateX(-${translates[{{ $sIdx }}] || 0}px); transition-duration: ${isSpinning ? '6000ms' : '0ms'}; transition-timing-function: cubic-bezier(0.12, 0.8, 0.15, 1.0);`">
                                        
                                        @foreach($sData['roulette_items'] as $item)
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

                                            <div class="w-32 h-28 flex-shrink-0 rounded-xl border-2 {{ $rarityBg }} p-2 flex flex-col items-center justify-between relative shadow-lg">
                                                <div class="absolute top-1 right-1 px-1.5 py-0.2 rounded text-[10px] font-black bg-black/70 text-amber-300 border border-amber-500/30">
                                                    x{{ $item['quantity'] }}
                                                </div>

                                                <div class="w-12 h-12 mt-1 flex items-center justify-center">
                                                    <img src="{{ asset('assets/items/' . $item['icon']) }}" 
                                                         alt="{{ $item['name'] }}" 
                                                         class="w-full h-full object-contain filter drop-shadow-md">
                                                </div>

                                                <div class="w-full text-center">
                                                    <span class="text-[10px] font-bold text-slate-200 line-clamp-1 block truncate">
                                                        {{ $item['name'] }}
                                                    </span>
                                                    <span class="text-[8px] uppercase font-bold tracking-wider px-1 py-0.2 rounded border {{ $rarityBadge }} inline-block">
                                                        {{ $item['rarity'] }}
                                                    </span>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- WINNING REWARDS RESULT SHOWCASE --}}
                    @if($isFinished)
                        <div x-transition:enter="transition ease-out duration-500"
                             x-transition:enter-start="opacity-0 scale-90 translate-y-4"
                             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                             class="w-full max-w-xl bg-gradient-to-b from-amber-950/90 via-slate-900 to-slate-950 border-2 border-amber-500/80 rounded-2xl p-5 text-center shadow-[0_0_40px_rgba(245,158,11,0.3)] mb-4">
                            
                            <div class="text-xs uppercase tracking-widest text-amber-400 font-bold mb-3 flex items-center justify-center gap-2">
                                <i class="fa-solid fa-trophy text-amber-400"></i>
                                <span>{{ count($spinsList) > 1 ? '🎉 Wygrane Łupy (' . count($spinsList) . 'x)!' : '🎉 Wygrany Łup!' }}</span>
                            </div>

                            <div class="grid {{ count($spinsList) > 1 ? 'grid-cols-2 sm:grid-cols-3' : 'grid-cols-1' }} gap-3 justify-center mb-3">
                                @foreach($spinsList as $sData)
                                    @php $wItem = $sData['winning_item']; @endphp
                                    @if($wItem)
                                        <div class="bg-slate-950/90 border border-amber-500/50 rounded-xl p-3 flex flex-col items-center shadow-lg">
                                            <div class="w-16 h-16 relative flex items-center justify-center bg-slate-900 rounded-xl border border-amber-400/60 mb-2">
                                                <img src="{{ asset('assets/items/' . $wItem['icon']) }}" 
                                                     alt="{{ $wItem['name'] }}" 
                                                     class="w-12 h-12 object-contain animate-bounce">
                                                <span class="absolute -top-1.5 -right-1.5 px-1.5 py-0.5 rounded text-[10px] font-black bg-black text-amber-300 border border-amber-400">
                                                    x{{ $wItem['quantity'] }}
                                                </span>
                                            </div>
                                            <span class="text-xs font-bold text-amber-100 truncate w-full block">{{ $wItem['name'] }}</span>
                                            <span class="text-[10px] font-extrabold text-amber-400">x{{ $wItem['quantity'] }}</span>
                                        </div>
                                    @endif
                                @endforeach
                            </div>

                            <p class="text-xs text-slate-400">
                                Przekazano automatycznie do Twojego magazynu materiałów!
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
