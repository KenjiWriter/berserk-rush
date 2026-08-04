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
    const getStyle = () => {
        const isMobile = window.innerWidth < 768;
        return {
            position: 'fixed',
            top: '50%',
            left: '50%',
            transform: 'translate(-50%, -50%)',
            margin: '0',
            width: isMobile ? 'calc(100vw - 1.5rem)' : 'auto',
            maxWidth: 'min(calc(100vw - 1.5rem), 960px)',
            maxHeight: isMobile ? '88vh' : 'calc(100vh - 24px)',
            overflowY: 'auto',
            zIndex: '10000',
        };
    };

    return {
        showInfo: false,
        timeout: null,
        tooltipStyle: getStyle(),
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
                        return activeContainers[activeContainers.length - 1];
                    }
                }
                if (activeContainers.length > 0) {
                    return activeContainers[activeContainers.length - 1];
                }
            }
            return document.querySelector('[data-tooltip-container]');
        },
        updatePosition() {
            this.tooltipStyle = getStyle();
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

