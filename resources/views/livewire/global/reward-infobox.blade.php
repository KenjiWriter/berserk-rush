<div
    x-data="rewardInfobox()"
    @stats-updated.window="handleAnimation($event.detail)"
    @toggle-reward-infobox.window="toggleBox()"
    style="font-family: 'Cinzel', serif;"
>
    <!-- latające ikony kontener -->
    <template x-for="anim in animations" :key="anim.id">
        <div class="fixed z-[10000] pointer-events-none transition-all duration-700 ease-in-out font-bold text-xl flex items-center gap-1"
             :style="`left: ${anim.x}px; top: ${anim.y}px; opacity: ${anim.opacity}; transform: scale(${anim.scale}); text-shadow: 0 0 10px rgba(0,0,0,0.8);`"
             :class="anim.type === 'gold' ? 'text-yellow-400' : (anim.type === 'gem' ? 'text-purple-400' : 'text-blue-400')">
             <span x-text="anim.amount > 0 ? '+' + anim.amount : ''"></span>
             <span x-show="anim.type === 'gold'">🪙</span>
             <span x-show="anim.type === 'gem'">💎</span>
             <span x-show="anim.type === 'xp'">✨</span>
        </div>
    </template>

    <!-- infobox -->
    <div class="fixed top-24 left-0 z-[9999]"
         x-cloak
         x-show="showBox"
         x-transition:enter="transition transform ease-out duration-500"
         x-transition:enter-start="-translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition transform ease-in duration-500"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="-translate-x-full"
         style="width: 320px; display: none;">
        <div class="bg-gradient-to-b from-stone-900 to-black border-2 border-l-0 border-amber-700/80 rounded-r-2xl shadow-[5px_5px_15px_rgba(0,0,0,0.8)] p-4 text-stone-200 relative overflow-hidden">
            <!-- Tło ozdobne -->
            <div class="absolute top-0 right-0 w-32 h-32 bg-amber-600/10 rounded-full blur-3xl"></div>
            
            <div class="flex items-start gap-4 relative z-10">
                <!-- Avatar -->
                <div class="relative w-16 h-16 shrink-0 border-2 border-amber-600 rounded-lg overflow-hidden shadow-inner">
                    <img :src="stats.avatar_image ? '/img/avatars/' + stats.avatar_image + '.png' : '/img/avatars/plate.png'" 
                         @@error="$el.src='/img/avatars/plate.png'"
                         alt="Avatar" class="w-full h-full object-cover">
                    <div class="absolute bottom-0 left-0 right-0 bg-black/70 text-center text-[10px] font-bold text-amber-400 border-t border-amber-600">
                        Lvl <span x-text="stats.level"></span>
                    </div>
                </div>
                
                <!-- Info -->
                <div class="flex-1 min-w-0">
                    <div class="text-xs text-amber-500 font-bold uppercase tracking-widest truncate" x-text="stats.title"></div>
                    <div class="text-lg font-bold text-stone-100 truncate shadow-black drop-shadow-md" x-text="stats.nickname"></div>
                    
                    <!-- Pasek XP -->
                    <div class="mt-2">
                        <div class="flex justify-between text-[10px] text-stone-400 mb-0.5 font-sans">
                            <span>EXP</span>
                            <span><span x-text="stats.experience"></span> / <span x-text="stats.experience_required"></span></span>
                        </div>
                        <div class="w-full h-1.5 bg-stone-800 rounded-full overflow-hidden border border-stone-700">
                            <div class="h-full bg-gradient-to-r from-blue-600 to-blue-400 transition-all duration-1000 relative"
                                 :style="`width: ${Math.min(100, (stats.experience / Math.max(1, stats.experience_required)) * 100)}%`">
                                 <div class="absolute inset-0 bg-white/20 w-full animate-[shimmer_2s_infinite]"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Złoto i Gemy -->
            <div class="mt-4 grid grid-cols-2 gap-2 relative z-10">
                <div class="bg-stone-900/80 border border-amber-900/50 rounded p-2 flex items-center justify-between shadow-inner">
                    <span class="text-sm">🪙</span>
                    <span class="font-bold text-yellow-500 text-sm drop-shadow-md transition-all duration-300" 
                          :class="animatingGold ? 'scale-125 text-yellow-300' : ''" 
                          x-text="displayGold"></span>
                </div>
                <div class="bg-stone-900/80 border border-purple-900/50 rounded p-2 flex items-center justify-between shadow-inner">
                    <span class="text-sm">💎</span>
                    <span class="font-bold text-purple-400 text-sm drop-shadow-md transition-all duration-300"
                          :class="animatingGems ? 'scale-125 text-purple-300' : ''" 
                          x-text="displayGems"></span>
                </div>
            </div>
            
            <button @click="showBox = false" class="absolute top-2 right-2 text-stone-500 hover:text-amber-500 transition-colors">
                ✕
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('rewardInfobox', () => ({
        showBox: false,
        timeoutId: null,
        stats: @json($characterStats),
        displayGold: 0,
        displayGems: 0,
        animatingGold: false,
        animatingGems: false,
        animations: [],
        animId: 0,

        init() {
            this.displayGold = this.stats.gold;
            this.displayGems = this.stats.gems;
        },

        async toggleBox() {
            this.showBox = !this.showBox;
            if (this.showBox) {
                if (typeof $wire !== 'undefined' && $wire.loadCharacterStats) {
                    try {
                        let freshStats = await $wire.loadCharacterStats();
                        if (freshStats) {
                            this.stats = freshStats;
                            this.displayGold = freshStats.gold;
                            this.displayGems = freshStats.gems;
                        }
                    } catch (e) {}
                }
                this.resetHideTimeout();
            }
        },

        handleAnimation(eventDetail) {
            let data = Array.isArray(eventDetail) ? eventDetail[0] : eventDetail;
            if (!data) return;

            if (data.gold !== undefined) {
                this.stats.gold = Number(data.gold);
                this.displayGold = Number(data.gold);
            }
            if (data.gems !== undefined) {
                this.stats.gems = Number(data.gems);
                this.displayGems = Number(data.gems);
            }

            // Optymistyczna aktualizacja lokalnych wartości
            if (data.newStats) {
                this.stats = data.newStats;
                this.displayGold = this.stats.gold;
                this.displayGems = this.stats.gems;
            } else {
                if (data.goldAdded) this.stats.gold += Number(data.goldAdded);
                if (data.goldDeducted) this.stats.gold -= Number(data.goldDeducted);
                if (data.xpAdded) this.stats.experience += Number(data.xpAdded);
                if (data.gemsAdded) this.stats.gems += Number(data.gemsAdded);
                
                this.displayGold = this.stats.gold;
                this.displayGems = this.stats.gems;

                // Prosty mechanizm levelowania na frontendzie
                let leveledUpTo = null;
                while (this.stats.experience >= this.stats.experience_required) {
                    this.stats.experience -= this.stats.experience_required;
                    this.stats.level++;
                    this.stats.experience_required = Math.round(50 * Math.pow(1.25, this.stats.level - 1));
                    leveledUpTo = this.stats.level;
                }
                
                if (leveledUpTo) {
                    // Odpal dźwięk i pokaż modal z awansem
                    window.dispatchEvent(new CustomEvent('play-audio', { detail: { type: 'levelup' } }));
                    Livewire.dispatch('open-level-up-modal', { level: leveledUpTo });
                }
            }
            
            if (!this.stats.experience_required) {
                this.stats = { ...this.stats, experience_required: 1, experience: 0 };
            }

            if (typeof $wire !== 'undefined' && $wire.loadCharacterStats) {
                $wire.loadCharacterStats().then(freshStats => {
                    if (freshStats) {
                        this.stats = freshStats;
                        this.displayGold = freshStats.gold;
                        this.displayGems = freshStats.gems;
                    }
                }).catch(() => {});
            }
            
            this.showBox = true;
            this.resetHideTimeout();

            const coords = window.lastClickCoords || { x: window.innerWidth / 2, y: window.innerHeight / 2 };
            
            if (data.goldAdded > 0) this.spawnAnimation('gold', data.goldAdded, coords);
            if (data.gemsAdded > 0) this.spawnAnimation('gem', data.gemsAdded, coords);
            if (data.xpAdded > 0) this.spawnAnimation('xp', data.xpAdded, coords);

            setTimeout(() => {
                if (data.goldAdded > 0) {
                    this.animatingGold = true;
                    this.animateValue('displayGold', this.displayGold, this.stats.gold, 500);
                    setTimeout(() => this.animatingGold = false, 500);
                }
                
                if (data.gemsAdded > 0) {
                    this.animatingGems = true;
                    this.animateValue('displayGems', this.displayGems, this.stats.gems, 500);
                    setTimeout(() => this.animatingGems = false, 500);
                }
            }, 700);
        },

        spawnAnimation(type, amount, startCoords) {
            const id = this.animId++;
            // Start position (w miejscu kliknięcia)
            let anim = {
                id: id,
                type: type,
                amount: amount,
                x: startCoords.x,
                y: startCoords.y,
                opacity: 1,
                scale: 1,
            };
            
            // Odstęp między ikonami jeśli lecą naraz (żeby na siebie nie nachodziły)
            if (type === 'gem') anim.x += 40;
            if (type === 'xp') anim.x -= 40;

            this.animations.push(anim);

            // Klatka po klatce odpalamy CSS transition do miejsca docelowego (Infobox lewy górny róg ekranu)
            setTimeout(() => {
                const index = this.animations.findIndex(a => a.id === id);
                if (index !== -1) {
                    // Miejsce docelowe - okolice infoboxa z lewej strony
                    let targetY = 120; // 96px top + padding
                    let targetX = 50; 
                    
                    if (type === 'gold') { targetY = 220; targetX = 30; }
                    if (type === 'gem') { targetY = 220; targetX = 180; }
                    if (type === 'xp') { targetY = 160; targetX = 150; }
                    
                    this.animations[index].x = targetX;
                    this.animations[index].y = targetY;
                    this.animations[index].opacity = 0; // zanika na końcu
                    this.animations[index].scale = 0.5;
                }
            }, 50);

            // Usunięcie po zakończeniu animacji (700ms)
            setTimeout(() => {
                this.animations = this.animations.filter(a => a.id !== id);
                if(type === 'gold') window.dispatchEvent(new CustomEvent('play-audio', { detail: { type: 'sell' } }));
            }, 750);
        },

        animateValue(prop, start, end, duration) {
            let startTimestamp = null;
            const step = (timestamp) => {
                if (!startTimestamp) startTimestamp = timestamp;
                const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                this[prop] = Math.floor(progress * (end - start) + start);
                if (progress < 1) {
                    window.requestAnimationFrame(step);
                } else {
                    this[prop] = end;
                }
            };
            window.requestAnimationFrame(step);
        },

        resetHideTimeout() {
            if (this.timeoutId) clearTimeout(this.timeoutId);
            // Chowa się po 4 sekundach od ostatniego zdarzenia (od momentu jak dolecą)
            this.timeoutId = setTimeout(() => {
                this.showBox = false;
            }, 5500);
        }
    }));
});
</script>
