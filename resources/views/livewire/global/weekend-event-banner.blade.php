<div>
    @if($activeEvent)
        <div x-data="{ 
                dismissed: sessionStorage.getItem('event_banner_dismissed_' + '{{ $activeEvent['key'] }}') === 'true',
                endsAt: {{ $activeEvent['ends_at_timestamp'] ?? 0 }},
                timeLeftStr: '',
                interval: null,
                init() {
                    if (this.endsAt > 0) {
                        this.updateCountdown();
                        this.interval = setInterval(() => this.updateCountdown(), 1000);
                    }
                },
                updateCountdown() {
                    const now = new Date().getTime();
                    const diff = this.endsAt - now;
                    if (diff <= 0) {
                        this.timeLeftStr = 'Koniec';
                        if (this.interval) clearInterval(this.interval);
                        return;
                    }
                    const hours = Math.floor(diff / (1000 * 60 * 60));
                    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                    const seconds = Math.floor((diff % (1000 * 60)) / 1000);
                    this.timeLeftStr = `${hours}h ${minutes}m ${seconds}s`;
                },
                dismiss() {
                    this.dismissed = true;
                    sessionStorage.setItem('event_banner_dismissed_' + '{{ $activeEvent['key'] }}', 'true');
                }
             }"
             x-show="!dismissed"
             x-transition:leave="transition ease-in duration-300 transform"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-full"
             class="w-full bg-gradient-to-r {{ $activeEvent['bg_gradient'] }} border-b-2 {{ $activeEvent['border_color'] }} shadow-[0_4px_20px_rgba(0,0,0,0.6)] relative z-30 transition-all duration-300 select-none"
             style="font-family: 'Cinzel', serif;">
            
            <div class="max-w-7xl mx-auto px-4 py-2 flex items-center justify-between gap-4">
                {{-- Event Left Info --}}
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-8 h-8 rounded-lg bg-stone-950/80 border {{ $activeEvent['border_color'] }} flex items-center justify-center text-lg shrink-0 shadow-[0_0_10px_rgba(245,158,11,0.3)]">
                        <i class="{{ $activeEvent['icon'] }} {{ $activeEvent['color'] }} animate-pulse"></i>
                    </div>

                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-[10px] font-black uppercase tracking-widest px-2 py-0.5 rounded border {{ $activeEvent['badge_bg'] }}">
                                <i class="fa-solid fa-bolt text-amber-400 mr-1"></i> Event Weekendowy
                            </span>
                            <h4 class="text-xs sm:text-sm font-extrabold text-amber-100 truncate tracking-wide">
                                {{ $activeEvent['name'] }}
                            </h4>
                        </div>
                        <p class="text-[11px] text-stone-300 font-sans truncate hidden sm:block">
                            {{ $activeEvent['description'] }}
                        </p>
                    </div>
                </div>

                {{-- Event Right Side: Timer, Multiplier Badge & Close Button --}}
                <div class="flex items-center gap-3 shrink-0">
                    <div class="text-right hidden xs:block">
                        <div class="text-[10px] text-amber-300/80 font-bold uppercase tracking-wider">Mnożnik</div>
                        <div class="text-xs font-black {{ $activeEvent['color'] }} font-mono">
                            x{{ $activeEvent['multiplier'] }}
                        </div>
                    </div>

                    <template x-if="endsAt > 0">
                        <div class="bg-stone-950/80 border border-amber-500/40 px-2.5 py-1 rounded-lg text-center shadow-inner">
                            <div class="text-[9px] text-stone-400 font-bold uppercase tracking-widest">Do końca</div>
                            <div class="text-xs font-mono font-bold text-amber-400" x-text="timeLeftStr"></div>
                        </div>
                    </template>

                    {{-- Close / Dismiss Button --}}
                    <button @click="dismiss()" 
                            class="w-7 h-7 rounded-lg bg-stone-950/80 hover:bg-stone-800 border border-stone-700/80 text-stone-400 hover:text-white flex items-center justify-center text-xs transition-colors shrink-0 cursor-pointer ml-1"
                            title="Zamknij powiadomienie">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
