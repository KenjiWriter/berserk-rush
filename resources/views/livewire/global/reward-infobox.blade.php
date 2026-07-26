<div
    x-data="rewardInfobox()"
    @stats-updated.window="handleAnimation($event.detail)"
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
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('rewardInfobox', () => ({
        animations: [],
        animId: 0,

        handleAnimation(eventDetail) {
            let data = Array.isArray(eventDetail) ? eventDetail[0] : eventDetail;
            if (!data) return;

            const coords = window.lastClickCoords || { x: window.innerWidth / 2, y: window.innerHeight / 2 };
            
            if (data.goldAdded > 0) this.spawnAnimation('gold', data.goldAdded, coords);
            if (data.gemsAdded > 0) this.spawnAnimation('gem', data.gemsAdded, coords);
            if (data.xpAdded > 0) this.spawnAnimation('xp', data.xpAdded, coords);
        },

        spawnAnimation(type, amount, startCoords) {
            const id = this.animId++;
            let anim = {
                id: id,
                type: type,
                amount: amount,
                x: startCoords.x,
                y: startCoords.y,
                opacity: 1,
                scale: 1,
            };
            
            if (type === 'gem') anim.x += 40;
            if (type === 'xp') anim.x -= 40;

            this.animations.push(anim);

            setTimeout(() => {
                const index = this.animations.findIndex(a => a.id === id);
                if (index !== -1) {
                    this.animations[index].y = Math.max(20, startCoords.y - 80);
                    this.animations[index].opacity = 0;
                    this.animations[index].scale = 0.8;
                }
            }, 50);

            setTimeout(() => {
                this.animations = this.animations.filter(a => a.id !== id);
                if(type === 'gold') window.dispatchEvent(new CustomEvent('play-audio', { detail: { type: 'sell' } }));
            }, 750);
        }
    }));
});
</script>
