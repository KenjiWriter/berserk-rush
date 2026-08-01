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
        _onCloseAllTooltips: null,
        init() {
            this._onCloseAllTooltips = () => {
                clearTimeout(this.timeout);
                this.showInfo = false;
            };
            window.addEventListener('close-all-tooltips', this._onCloseAllTooltips);

            this.$watch('showInfo', (value) => {
                if (value) {
                    this.updatePosition();
                }
            });
        },
        destroy() {
            if (this._onCloseAllTooltips) {
                window.removeEventListener('close-all-tooltips', this._onCloseAllTooltips);
            }
        },
        updatePosition() {
            if (!this.showInfo) return;
            this.$nextTick(() => {
                const triggerEl = this.$el;
                const tooltipEl = this.$refs.tooltipContainer || (this.$el && this.$el.querySelector ? this.$el.querySelector('[data-tooltip-container]') : null);
                if (!triggerEl || !tooltipEl) return;

                const triggerRect = triggerEl.getBoundingClientRect();
                const targetBoxEl = tooltipEl.firstElementChild || tooltipEl;
                const tooltipRect = targetBoxEl.getBoundingClientRect();

                if (!triggerRect.width || !tooltipRect.width) return;

                // Mobile screens (< 640px) use fixed center modal layout
                if (window.innerWidth < 640) {
                    this.tooltipStyle = {
                        position: 'fixed',
                        top: '50%',
                        left: '50%',
                        transform: 'translate(-50%, -50%)',
                        margin: '0',
                        width: 'calc(100vw - 1.5rem)',
                        maxWidth: '380px',
                        maxHeight: '85vh',
                        overflowY: 'auto',
                        zIndex: '10000',
                    };
                    return;
                }

                const minMargin = 12;
                const triggerCenter = triggerRect.left + triggerRect.width / 2;
                let left = triggerCenter - (tooltipRect.width / 2);
                left = Math.max(minMargin, Math.min(left, window.innerWidth - minMargin - tooltipRect.width));

                let top = triggerRect.top - tooltipRect.height - 8;
                if (top < minMargin) {
                    top = triggerRect.bottom + 8;
                }
                if (top + tooltipRect.height > window.innerHeight - minMargin) {
                    top = Math.max(minMargin, window.innerHeight - tooltipRect.height - minMargin);
                }

                const style = {
                    position: 'fixed',
                    left: left + 'px',
                    top: top + 'px',
                    bottom: 'auto',
                    transform: 'none',
                    margin: '0',
                    maxHeight: 'calc(100vh - 24px)',
                    overflowY: 'auto',
                    zIndex: '10000',
                };

                this.tooltipStyle = style;
            });
        },
        openTooltip() {
            if (window.innerWidth < 640) return;
            window.dispatchEvent(new CustomEvent('close-all-tooltips'));
            clearTimeout(this.timeout);
            this.showInfo = true;
            this.updatePosition();
        },
        closeTooltip(event) {
            if (window.innerWidth < 640) return;
            if (event && event.relatedTarget) {
                const tooltipEl = this.$refs.tooltipContainer;
                const movingIntoTooltip = tooltipEl && tooltipEl.contains(event.relatedTarget);
                const movingIntoTrigger = this.$el && this.$el.contains(event.relatedTarget);
                const movingIntoSubTooltip = event.relatedTarget.closest && event.relatedTarget.closest('[data-item-tooltip]');
                if (movingIntoTooltip || movingIntoTrigger || movingIntoSubTooltip) {
                    return;
                }
            }

            clearTimeout(this.timeout);
            this.timeout = setTimeout(() => {
                this.showInfo = false;
            }, 120);
        },
        toggleTooltip() {
            clearTimeout(this.timeout);
            if (!this.showInfo) {
                window.dispatchEvent(new CustomEvent('close-all-tooltips'));
                this.showInfo = true;
                this.updatePosition();
            } else {
                this.showInfo = false;
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

