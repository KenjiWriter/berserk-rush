<div>
    @if($show)
        <div class="fixed inset-0 z-[10000] flex items-center justify-center bg-black/80 backdrop-blur-sm"
             x-data="{
                 visible: false,
                 progress: 100,
                 duration: 5000,
                 startTime: null,
                 animFrame: null,
                 initModal() {
                     setTimeout(() => this.visible = true, 50);
                     this.startCountdown();
                 },
                 startCountdown() {
                     this.progress = 100;
                     this.startTime = Date.now();
                     const tick = () => {
                         const elapsed = Date.now() - this.startTime;
                         const remaining = Math.max(0, this.duration - elapsed);
                         this.progress = (remaining / this.duration) * 100;
                         if (remaining > 0) {
                             this.animFrame = requestAnimationFrame(tick);
                         } else {
                             this.closeModal();
                         }
                     };
                     this.animFrame = requestAnimationFrame(tick);
                 },
                 closeModal() {
                     if (this.animFrame) {
                         cancelAnimationFrame(this.animFrame);
                         this.animFrame = null;
                     }
                     $wire.close();
                 },
                 destroy() {
                     if (this.animFrame) {
                         cancelAnimationFrame(this.animFrame);
                     }
                 }
             }"
             x-init="initModal()"
             x-show="visible"
             x-transition:enter="transition ease-out duration-500 transform"
             x-transition:enter-start="opacity-0 scale-50"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-300 transform"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-90"
        >
            <!-- Promienie chwały -->
            <div class="absolute inset-0 overflow-hidden pointer-events-none flex items-center justify-center">
                <div class="w-[200vw] h-[200vw] animate-[spin_20s_linear_infinite] opacity-40 mix-blend-screen" 
                     style="background: conic-gradient(
                         transparent 0deg 15deg, rgba(245,158,11,0.6) 15deg 30deg, 
                         transparent 30deg 45deg, rgba(245,158,11,0.6) 45deg 60deg, 
                         transparent 60deg 75deg, rgba(245,158,11,0.6) 75deg 90deg, 
                         transparent 90deg 105deg, rgba(245,158,11,0.6) 105deg 120deg, 
                         transparent 120deg 135deg, rgba(245,158,11,0.6) 135deg 150deg, 
                         transparent 150deg 165deg, rgba(245,158,11,0.6) 165deg 180deg, 
                         transparent 180deg 195deg, rgba(245,158,11,0.6) 195deg 210deg, 
                         transparent 210deg 225deg, rgba(245,158,11,0.6) 225deg 240deg, 
                         transparent 240deg 255deg, rgba(245,158,11,0.6) 255deg 270deg, 
                         transparent 270deg 285deg, rgba(245,158,11,0.6) 285deg 300deg, 
                         transparent 300deg 315deg, rgba(245,158,11,0.6) 315deg 330deg, 
                         transparent 330deg 345deg, rgba(245,158,11,0.6) 345deg 360deg
                     ); radial-gradient(circle, rgba(0,0,0,0) 0%, rgba(0,0,0,1) 70%)">
                </div>
            </div>

            <div class="bg-gradient-to-b from-stone-900 to-black border-4 border-amber-600 rounded-2xl p-8 max-w-md w-full text-center shadow-[0_0_50px_rgba(245,158,11,0.4)] relative z-10 mx-4">
                
                <!-- Ikona / Wstążka -->
                <div class="absolute -top-12 left-1/2 transform -translate-x-1/2">
                    <div class="w-24 h-24 bg-gradient-to-br from-yellow-400 to-amber-600 rounded-full border-4 border-stone-900 flex items-center justify-center shadow-lg shadow-amber-500/50">
                        <span class="text-4xl">🌟</span>
                    </div>
                </div>

                <div class="mt-10 mb-6">
                    <h2 class="text-amber-500 font-bold uppercase tracking-widest text-sm mb-1" style="font-family: 'Cinzel', serif;">Awans!</h2>
                    <h3 class="text-4xl font-bold text-white drop-shadow-md" style="font-family: 'Cinzel', serif;">Poziom {{ $newLevel }}</h3>
                </div>

                <div class="text-stone-300 mb-8 px-4">
                    <p class="mb-4">Gratulacje bohaterze! Z każdym dniem stajesz się potężniejszy. Kto wie, jakie wyzwania jeszcze na Ciebie czekają?</p>
                    
                    @if(count($unlockedMaps) > 0)
                        <div class="bg-green-900/40 border border-green-600/50 rounded-lg p-4 mt-6 animate-[pulse_2s_infinite]">
                            <h4 class="text-green-400 font-bold mb-2 flex items-center justify-center gap-2">
                                <span>🗺️</span> Odblokowano nowe mapy:
                            </h4>
                            <ul class="text-green-200 text-sm font-bold flex flex-col gap-1">
                                @foreach($unlockedMaps as $map)
                                    <li>{{ $map->name }} (Lvl {{ $map->level_min }})</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>

                <button @click="closeModal()" 
                        class="relative overflow-hidden bg-stone-950 hover:bg-stone-900 border-2 border-amber-500/70 text-white font-bold py-3.5 px-8 rounded-xl shadow-[0_0_20px_rgba(245,158,11,0.3)] transform transition-all hover:scale-[1.02] active:scale-95 w-full uppercase tracking-wider text-sm group cursor-pointer">
                    <!-- Progress Bar Background -->
                    <div class="absolute inset-y-0 left-0 bg-gradient-to-r from-amber-600 via-amber-500 to-yellow-500 transition-none pointer-events-none opacity-90 group-hover:opacity-100"
                         :style="`width: ${progress}%`">
                    </div>

                    <!-- Inner Glow Line at progress tip -->
                    <div class="absolute inset-y-0 w-1 bg-white/80 shadow-[0_0_8px_#fff] pointer-events-none transition-none"
                         :style="`left: calc(${progress}% - 2px)`"
                         x-show="progress > 0 && progress < 100">
                    </div>

                    <!-- Button Content -->
                    <span class="relative z-10 flex items-center justify-center gap-2 drop-shadow-[0_2px_4px_rgba(0,0,0,0.9)] font-extrabold text-white text-base">
                        <span>Kontynuuj</span>
                        <span class="text-xs text-amber-200 font-mono bg-black/40 px-2 py-0.5 rounded-full border border-amber-400/30" 
                              x-text="`(${Math.ceil((progress / 100) * 5)}s)`"></span>
                    </span>
                </button>
            </div>
        </div>
    @endif
</div>
