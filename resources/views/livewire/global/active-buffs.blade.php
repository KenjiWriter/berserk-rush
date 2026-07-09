<div>
    <div class="fixed top-4 right-4 z-50 flex flex-col gap-2 pointer-events-none">
        @foreach($buffs as $buff)
            <div x-data="buffCountdown({{ $buff['expires_at_timestamp'] }})" 
                 x-show="timeLeft > 0"
                 class="bg-blue-900/80 border border-blue-500 text-white p-2 rounded-lg shadow-lg flex items-center gap-3 backdrop-blur-sm transform transition-all duration-300 pointer-events-auto">
                
                <div class="bg-blue-800 p-2 rounded text-blue-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd" />
                    </svg>
                </div>
                
                <div class="flex flex-col min-w-[120px]">
                    <span class="text-xs font-bold text-blue-300 uppercase tracking-wider">{{ $buff['name'] }}</span>
                    <span class="text-sm font-mono" x-text="formatTime()"></span>
                </div>
                
            </div>
        @endforeach

        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('buffCountdown', (expiresAt) => ({
                    timeLeft: 0,
                    interval: null,
                    init() {
                        this.updateTime();
                        this.interval = setInterval(() => {
                            this.updateTime();
                        }, 1000);
                    },
                    updateTime() {
                        const now = new Date().getTime();
                        this.timeLeft = Math.max(0, expiresAt - now);
                        if (this.timeLeft <= 0) {
                            clearInterval(this.interval);
                            // Optional: trigger Livewire reload to remove the buff completely
                            // $wire.$refresh(); 
                        }
                    },
                    formatTime() {
                        if (this.timeLeft <= 0) return '00:00';
                        const totalSeconds = Math.floor(this.timeLeft / 1000);
                        const m = Math.floor(totalSeconds / 60).toString().padStart(2, '0');
                        const s = (totalSeconds % 60).toString().padStart(2, '0');
                        return `${m}:${s}`;
                    }
                }))
            })
        </script>
    </div>
</div>
