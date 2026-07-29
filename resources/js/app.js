import './bootstrap';

window.lastClickCoords = { x: window.innerWidth / 2, y: window.innerHeight / 2 };

document.addEventListener('click', (e) => {
    // Odrobinę nad kursorem myszy zgodnie z uwagą (Y - 10)
    window.lastClickCoords = { 
        x: e.clientX, 
        y: e.clientY - 10 
    };
});

function createSmartTooltip() {
    return {
        showInfo: false,
        timeout: null,
        tooltipStyle: {},
        init() {
            this.$watch('showInfo', (value) => {
                if (value) {
                    this.updatePosition();
                }
            });
        },
        updatePosition() {
            if (!this.showInfo) return;
            this.$nextTick(() => {
                const triggerEl = this.$el;
                const tooltipEl = this.$refs.tooltipContainer || this.$el.querySelector('[data-tooltip-container]');
                if (!triggerEl || !tooltipEl) return;

                const triggerRect = triggerEl.getBoundingClientRect();
                const tooltipRect = tooltipEl.getBoundingClientRect();
                if (!triggerRect.width || !tooltipRect.width) return;

                // Mobile screens (< 640px) use modal overlay or standard layout
                if (window.innerWidth < 640) {
                    this.tooltipStyle = {};
                    return;
                }

                // Pozycjonowanie liczone względem viewportu (position: fixed), a nie
                // rodzica (position: absolute) - tooltip jest teleportowany do <body>
                // (x-teleport="body"), więc offsety liczone względem trigger elementu
                // nie mają odniesienia do jego rzeczywistego kontekstu pozycjonowania.
                const minMargin = 12;
                const triggerCenter = triggerRect.left + triggerRect.width / 2;
                let left = triggerCenter - (tooltipRect.width / 2);
                left = Math.max(minMargin, Math.min(left, window.innerWidth - minMargin - tooltipRect.width));

                const style = {
                    position: 'fixed',
                    left: left + 'px',
                    transform: 'none',
                    margin: '0',
                };

                if (triggerRect.top < tooltipRect.height + 16) {
                    style.top = (triggerRect.bottom + 8) + 'px';
                    style.bottom = 'auto';
                } else {
                    style.top = (triggerRect.top - tooltipRect.height - 8) + 'px';
                    style.bottom = 'auto';
                }

                this.tooltipStyle = style;
            });
        },
        openTooltip() {
            clearTimeout(this.timeout);
            this.showInfo = true;
            this.updatePosition();
        },
        closeTooltip() {
            clearTimeout(this.timeout);
            this.timeout = setTimeout(() => {
                this.showInfo = false;
            }, 250);
        },
        toggleTooltip() {
            clearTimeout(this.timeout);
            this.showInfo = !this.showInfo;
            if (this.showInfo) {
                this.updatePosition();
            }
        }
    };
}

window.smartTooltip = createSmartTooltip;

if (window.Alpine) {
    window.Alpine.data('smartTooltip', createSmartTooltip);
}

document.addEventListener('alpine:init', () => {
    if (window.Alpine) {
        window.Alpine.data('smartTooltip', createSmartTooltip);
    }
});

