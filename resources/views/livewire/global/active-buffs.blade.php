<div>
    {{-- Stack pozioma ikon buffów – tylko ikony z kółkowym promieniem odliczania --}}
    <div class="fixed top-4 right-4 z-50 flex flex-col items-end gap-1.5 pointer-events-none" wire:ignore.self>
        @foreach($buffs as $buff)
            <div x-data="{
                    expiresAt: {{ $buff['expires_at_timestamp'] }},
                    totalMs: 0,
                    timeLeft: 0,
                    interval: null,
                    showTip: false,
                    init() {
                        this.totalMs = Math.max(1, this.expiresAt - new Date().getTime());
                        this.updateTime();
                        this.interval = setInterval(() => { this.updateTime(); }, 1000);
                    },
                    updateTime() {
                        const now = new Date().getTime();
                        this.timeLeft = Math.max(0, this.expiresAt - now);
                        if (this.timeLeft <= 0) clearInterval(this.interval);
                    },
                    formatTime() {
                        if (this.timeLeft <= 0) return '0:00';
                        const s = Math.floor(this.timeLeft / 1000);
                        if (s >= 3600) {
                            const h = Math.floor(s / 3600);
                            const m = Math.floor((s % 3600) / 60).toString().padStart(2, '0');
                            return h + ':' + m + ':00';
                        }
                        const m = Math.floor(s / 60).toString().padStart(2, '0');
                        const sec = (s % 60).toString().padStart(2, '0');
                        return m + ':' + sec;
                    },
                    progress() {
                        if (this.totalMs <= 0) return 0;
                        return Math.max(0, Math.min(1, this.timeLeft / this.totalMs));
                    }
                 }"
                 x-show="timeLeft > 0"
                 x-transition
                 @mouseenter="showTip = true"
                 @mouseleave="showTip = false"
                 class="relative pointer-events-auto group">

                {{-- Ikona z kółkowym odliczaniem --}}
                <div class="relative w-10 h-10 cursor-default select-none">
                    {{-- SVG progress ring --}}
                    <svg class="w-10 h-10 -rotate-90 absolute inset-0" viewBox="0 0 40 40">
                        {{-- Track --}}
                        <circle cx="20" cy="20" r="16"
                                fill="rgba(30,58,138,0.75)"
                                stroke="rgba(59,130,246,0.25)"
                                stroke-width="2"/>
                        {{-- Progress arc --}}
                        <circle cx="20" cy="20" r="16"
                                fill="none"
                                stroke="#60a5fa"
                                stroke-width="2.5"
                                stroke-linecap="round"
                                stroke-dasharray="100.5"
                                :stroke-dashoffset="100.5 * (1 - progress())"
                                style="transition: stroke-dashoffset 1s linear;"/>
                    </svg>
                    {{-- Lightning bolt icon --}}
                    <div class="absolute inset-0 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-300" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                </div>

                {{-- Tooltip po najechaniu --}}
                <div x-show="showTip"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 translate-x-2"
                     x-transition:enter-end="opacity-100 translate-x-0"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100 translate-x-0"
                     x-transition:leave-end="opacity-0 translate-x-2"
                     class="absolute right-12 top-1/2 -translate-y-1/2 z-[9999] bg-gray-900/95 border border-blue-500/60 rounded-xl shadow-2xl p-3 min-w-[180px] max-w-[240px] pointer-events-none backdrop-blur-sm"
                     style="display: none;">
                    {{-- Nazwa --}}
                    <p class="text-blue-300 font-bold text-xs uppercase tracking-wider leading-tight mb-1">
                        {{ $buff['name'] }}
                    </p>
                    {{-- Odliczanie --}}
                    <p class="text-white font-mono text-sm font-bold mb-2" x-text="formatTime()"></p>
                    {{-- Efekty --}}
                    @if(!empty($buff['effects']) && is_array($buff['effects']))
                        <div class="flex flex-col gap-0.5 border-t border-blue-900/60 pt-2">
                            @foreach($buff['effects'] as $key => $value)
                                <div class="flex items-center justify-between gap-2 text-[11px]">
                                    <span class="text-gray-400 capitalize">{{ str_replace('_', ' ', $key) }}</span>
                                    <span class="text-green-400 font-bold">
                                        @if(is_numeric($value) && $value > 0)+{{ $value }}@else{{ $value }}@endif
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

            </div>
        @endforeach
    </div>
</div>
