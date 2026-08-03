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
    const isMobile = window.innerWidth < 768;
    const defaultStyle = isMobile ? {
        position: 'fixed',
        top: '50%',
        left: '50%',
        transform: 'translate(-50%, -50%)',
        margin: '0',
        width: 'calc(100vw - 1.5rem)',
        maxWidth: '420px',
        maxHeight: '85vh',
        overflowY: 'auto',
        zIndex: '10000',
    } : {
        position: 'fixed',
        top: '50%',
        left: '50%',
        transform: 'translate(-50%, -50%)',
        margin: '0',
        maxHeight: 'calc(100vh - 24px)',
        overflowY: 'auto',
        zIndex: '10000',
    };

    return {
        showInfo: false,
        timeout: null,
        tooltipStyle: { ...defaultStyle },
        _onCloseAllTooltips: null,
        _onWindowClick: null,
        init() {
            this._onCloseAllTooltips = () => {
                clearTimeout(this.timeout);
                this.showInfo = false;
            };
            window.addEventListener('close-all-tooltips', this._onCloseAllTooltips);

            this._onWindowClick = (e) => {
                if (!this.showInfo) return;
                const tooltipEl = this.getTooltipElement();
                const isInsideTooltip = tooltipEl && tooltipEl.contains(e.target);
                const isInsideTrigger = this.$el && this.$el.contains(e.target);
                if (!isInsideTooltip && !isInsideTrigger) {
                    this.showInfo = false;
                }
            };

            this.$watch('showInfo', (value) => {
                if (value) {
                    this.updatePosition();
                    setTimeout(() => {
                        window.addEventListener('pointerdown', this._onWindowClick);
                    }, 50);
                } else {
                    window.removeEventListener('pointerdown', this._onWindowClick);
                }
            });
        },
        destroy() {
            if (this._onCloseAllTooltips) {
                window.removeEventListener('close-all-tooltips', this._onCloseAllTooltips);
            }
            if (this._onWindowClick) {
                window.removeEventListener('pointerdown', this._onWindowClick);
            }
        },
        getTooltipElement() {
            if (this.$refs && this.$refs.tooltipContainer) {
                return this.$refs.tooltipContainer;
            }
            if (this.$el) {
                if (this.$el.querySelector) {
                    const internal = this.$el.querySelector('[data-tooltip-container]');
                    if (internal) return internal;
                }
                const activeContainers = document.body.querySelectorAll('[data-tooltip-container]');
                for (let i = 0; i < activeContainers.length; i++) {
                    if (activeContainers[i].offsetParent !== null || window.getComputedStyle(activeContainers[i]).display !== 'none') {
                        return activeContainers[i];
                    }
                }
                if (activeContainers.length > 0) {
                    return activeContainers[activeContainers.length - 1];
                }
            }
            return document.querySelector('[data-tooltip-container]');
        },
        updatePosition() {
            if (!this.showInfo) return;

            // Mobile/tablet screens (< 768px) use fixed center modal layout immediately
            if (window.innerWidth < 768) {
                this.tooltipStyle = {
                    position: 'fixed',
                    top: '50%',
                    left: '50%',
                    transform: 'translate(-50%, -50%)',
                    margin: '0',
                    width: 'calc(100vw - 1.5rem)',
                    maxWidth: '420px',
                    maxHeight: '85vh',
                    overflowY: 'auto',
                    zIndex: '10000',
                };
                return;
            }

            const attemptPositioning = (retryCount = 0) => {
                if (!this.showInfo) return;
                const triggerEl = this.$el;
                const tooltipEl = this.getTooltipElement();
                if (!triggerEl || !tooltipEl) return;

                const triggerRect = triggerEl.getBoundingClientRect();
                const targetBoxEl = tooltipEl.firstElementChild || tooltipEl;
                const tooltipRect = targetBoxEl.getBoundingClientRect();

                if ((!triggerRect.width || !tooltipRect.width) && retryCount < 3) {
                    requestAnimationFrame(() => attemptPositioning(retryCount + 1));
                    return;
                }

                if (triggerRect.width && tooltipRect.width) {
                    this._applyDesktopStyle(triggerRect, tooltipRect);
                }
            };

            this.$nextTick(() => attemptPositioning(0));
        },
        _applyDesktopStyle(triggerRect, tooltipRect) {
            const minMargin = 12;
            const desktopNav = document.querySelector('aside');
            const navRight = (desktopNav && window.getComputedStyle(desktopNav).display !== 'none')
                ? desktopNav.getBoundingClientRect().right
                : 0;
            const mainEl = this.$el && this.$el.closest ? this.$el.closest('main') : null;
            const mainLeft = mainEl ? mainEl.getBoundingClientRect().left : 0;
            const containerLeft = Math.max(navRight, mainLeft);

            const triggerCenter = triggerRect.left + triggerRect.width / 2;
            let left = triggerCenter - (tooltipRect.width / 2);

            left = Math.max(containerLeft + minMargin, Math.min(left, window.innerWidth - minMargin - tooltipRect.width));

            let top = triggerRect.top - tooltipRect.height - 8;
            if (top < minMargin) {
                top = triggerRect.bottom + 8;
            }
            if (top + tooltipRect.height > window.innerHeight - minMargin) {
                top = Math.max(minMargin, window.innerHeight - tooltipRect.height - minMargin);
            }

            this.tooltipStyle = {
                position: 'fixed',
                left: left + 'px',
                top: top + 'px',
                bottom: 'auto',
                transform: 'none',
                margin: '0',
                maxWidth: `calc(100vw - ${containerLeft + minMargin * 2}px)`,
                maxHeight: 'calc(100vh - 24px)',
                overflowY: 'auto',
                zIndex: '10000',
            };
        },
        openTooltip() {
            // Bezpośrednie najechanie myszką nie otwiera już automatycznie tooltipu,
            // aby zapobiec zasłanianiu widoku i skakaniu okienek. Tooltip otwiera się po kliknięciu.
        },
        closeTooltip(event) {
            // Zjechanie myszką nie zamyka automatycznie tooltipa otwartego kliknięciem.
            // Zamknięcie następuje po kliknięciu poza przedmiot lub kliknięciu innego przedmiotu.
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

