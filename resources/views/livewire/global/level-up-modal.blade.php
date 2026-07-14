<div>
    @if($show)
        <div class="fixed inset-0 z-[10000] flex items-center justify-center bg-black/80 backdrop-blur-sm"
             x-data="{ visible: false }"
             x-init="setTimeout(() => visible = true, 50)"
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

                <button wire:click="close" class="bg-gradient-to-r from-amber-600 to-amber-700 hover:from-amber-500 hover:to-amber-600 text-white font-bold py-3 px-8 rounded-xl shadow-lg transform transition-all hover:scale-105 active:scale-95 w-full uppercase tracking-wider text-sm">
                    Kontynuuj
                </button>
            </div>
        </div>
    @endif
</div>
