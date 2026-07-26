import './bootstrap';

window.lastClickCoords = { x: window.innerWidth / 2, y: window.innerHeight / 2 };

document.addEventListener('click', (e) => {
    // Odrobinę nad kursorem myszy zgodnie z uwagą (Y - 10)
    window.lastClickCoords = { 
        x: e.clientX, 
        y: e.clientY - 10 
    };
});

window.smartTooltip = function() {
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

                const style = {};
                const triggerCenter = triggerRect.left + triggerRect.width / 2;
                const halfWidth = tooltipRect.width / 2;
                const minMargin = 12; // Minimum margin from viewport edges in px

                // Horizontal position clamping
                if (triggerCenter - halfWidth < minMargin) {
                    // Left edge would overflow screen -> shift right to keep minMargin from left edge
                    const leftOffset = minMargin - triggerRect.left;
                    style.left = leftOffset + 'px';
                    style.transform = 'none';
                } else if (triggerCenter + halfWidth > window.innerWidth - minMargin) {
                    // Right edge would overflow screen -> shift left to keep minMargin from right edge
                    const leftOffset = (window.innerWidth - minMargin) - triggerRect.left - tooltipRect.width;
                    style.left = leftOffset + 'px';
                    style.transform = 'none';
                } else {
                    style.left = '50%';
                    style.transform = 'translateX(-50%)';
                }

                // Vertical positioning (below if not enough room above)
                if (triggerRect.top < tooltipRect.height + 16) {
                    style.top = '100%';
                    style.bottom = 'auto';
                    style.marginTop = '8px';
                    style.marginBottom = '0';
                } else {
                    style.bottom = '100%';
                    style.top = 'auto';
                    style.marginBottom = '8px';
                    style.marginTop = '0';
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
};

