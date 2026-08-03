<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="bg-black">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Berserk Rush') }}</title>

    <!-- Favicon & Touch Icons -->
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">

    <!-- Add to Home Screen (standalone launch experience) -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="{{ config('app.name', 'Berserk Rush') }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="theme-color" content="#000000">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700;900&family=Noto+Color+Emoji&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script>
        if (typeof window.smartTooltip === 'undefined') {
            window.smartTooltip = function() {
                const getStyle = () => {
                    const isMobile = window.innerWidth < 768;
                    return {
                        position: 'fixed',
                        top: '50%',
                        left: '50%',
                        transform: 'translate(-50%, -50%)',
                        margin: '0',
                        width: isMobile ? 'calc(100vw - 1.5rem)' : 'auto',
                        maxWidth: 'min(calc(100vw - 1.5rem), 780px)',
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
                        // Bezpośrednie najechanie myszką nie otwiera już automatycznie tooltipu.
                    },
                    closeTooltip(event) {
                        // Zjechanie myszką nie zamyka automatycznie tooltipu.
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
            };
        }
    </script>

    <style>
        /* ===== Toast Animations ===== */
        .toast-enter {
            animation: toastSlideIn 0.35s cubic-bezier(0.21, 1.02, 0.73, 1) forwards;
        }
        .toast-leave {
            animation: toastSlideOut 0.3s cubic-bezier(0.55, 0.085, 0.68, 0.53) forwards;
        }
        @keyframes toastSlideIn {
            from {
                opacity: 0;
                transform: translateX(100%) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateX(0) scale(1);
            }
        }
        @keyframes toastSlideOut {
            from {
                opacity: 1;
                transform: translateX(0) scale(1);
            }
            to {
                opacity: 0;
                transform: translateX(100%) scale(0.95);
            }
        }

        /* Toast progress bar drain animation */
        .toast-progress {
            animation: toastDrain var(--toast-duration, 4s) linear forwards;
        }
        @keyframes toastDrain {
            from { width: 100%; }
            to { width: 0%; }
        }

        /* ===== Custom Thin Dark Fantasy Scrollbar ===== */
        .custom-scrollbar {
            scrollbar-width: thin;
            scrollbar-color: #d97706 rgba(12, 10, 9, 0.6);
        }
        .custom-scrollbar::-webkit-scrollbar {
            width: 5px;
            height: 5px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: rgba(12, 10, 9, 0.6);
            border-radius: 9999px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: linear-gradient(to bottom, #78350f, #d97706, #78350f);
            border-radius: 9999px;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(to bottom, #b45309, #f59e0b, #b45309);
        }
    </style>
    <script>
        window.visitedLocations = window.visitedLocations || new Set();

        window.normalizeLocationUrl = function(url) {
            if (!url) return '';
            try {
                const parsed = new URL(url, window.location.origin);
                return parsed.pathname;
            } catch (e) {
                return String(url).split('?')[0].split('#')[0];
            }
        };

        window.isLocationCached = function(url) {
            if (!url) return false;
            const normalized = window.normalizeLocationUrl(url);
            return window.visitedLocations.has(normalized);
        };

        if (window.location.pathname) {
            window.visitedLocations.add(window.normalizeLocationUrl(window.location.pathname));
        }

        document.addEventListener('livewire:navigated', () => {
            if (window.location.pathname) {
                window.visitedLocations.add(window.normalizeLocationUrl(window.location.pathname));
            }
        });

        document.addEventListener('pointerdown', function unlock() {
            let audio = new Audio("data:audio/wav;base64,UklGRigAAABXQVZFZm10IBIAAAABAAEARKwAAIhYAQACABAAAABkYXRhAgAAAAEA");
            audio.play().catch(() => {});
            document.removeEventListener('pointerdown', unlock);
        }, { once: true });
    </script>
</head>

<body class="font-sans antialiased bg-black text-slate-100">
    {{-- ===== Global Location Transition Overlay ===== --}}
    <div x-data="{ leaving: false, text: 'Podróż...', icon: 'fa-solid fa-archway' }"
         x-on:location-leave.window="
             let targetUrl = $event.detail?.url || $event.detail?.targetUrl;
             if (targetUrl && window.isLocationCached && window.isLocationCached(targetUrl)) {
                 leaving = false;
             } else {
                 leaving = true;
                 text = $event.detail?.text || 'Podróż...';
                 icon = $event.detail?.icon || 'fa-solid fa-archway';
             }
         "
         x-on:livewire:navigated.window="leaving = false"
         x-show="leaving"
         x-transition:enter="transition ease-in-out duration-500"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         class="fixed inset-0 z-[9999] flex flex-col items-center justify-center bg-black"
         style="display: none;">
         
         <div class="inline-block relative w-24 h-24 mb-8">
             <div class="absolute inset-0 rounded-full border-4 border-amber-900/30 border-t-amber-500 animate-spin"></div>
             <div class="absolute inset-2 rounded-full border-4 border-amber-800/20 border-t-amber-400 animate-spin" style="animation-direction: reverse; animation-duration: 1.5s;"></div>
             <div class="absolute inset-4 rounded-full border-4 border-amber-700/10 border-t-amber-300 animate-spin" style="animation-duration: 2s;"></div>
             <div class="absolute inset-0 flex items-center justify-center text-3xl text-amber-400">
                 <template x-if="icon && icon.includes('fa-')">
                     <i :class="icon"></i>
                 </template>
                 <template x-if="!icon || !icon.includes('fa-')">
                     <span x-text="icon || '🏰'"></span>
                 </template>
             </div>
         </div>
         
         <h2 class="text-3xl font-bold text-amber-500 drop-shadow-[0_0_10px_rgba(245,158,11,0.5)] animate-pulse" style="font-family: 'Cinzel', serif;" x-text="text">
         </h2>
    </div>

    {{-- ===== Global Audio Player ===== --}}
    <div x-data="{
        sounds: {
            unequip: 3,
            equip: 2,
            sell: 2,
            buy: 1,
            levelup: 1,
            'upgrade-success': 1,
            'upgrade-fail': 1,
            'enchant-success': 1,
            'enchant-fail': 1,
            shop: 1,
            tab: 1,
            stat: 1,
            combat: 1,
            profile: 1,
            hover: 1,
            victory: 1,
            defeat: 1,
            hit: 1,
            crit: 1,
            dodge: 1,
            error: 1
        },
        combatTypes: ['combat', 'victory', 'defeat', 'hit', 'crit', 'dodge'],
        volumes: {
            sfx: parseInt(localStorage.getItem('berserk_sfx_volume') ?? '50') / 100,
            levelup: parseInt(localStorage.getItem('berserk_levelup_volume') ?? '50') / 100,
            combat: parseInt(localStorage.getItem('berserk_combat_volume') ?? '50') / 100
        },
        categoryFor(type) {
            if (type === 'levelup') return 'levelup';
            if (this.combatTypes.includes(type)) return 'combat';
            return 'sfx';
        },
        activeSounds: {},
        playAudio(type) {
            if (!this.sounds[type]) return;

            if (this.activeSounds[type]) {
                this.activeSounds[type].pause();
                this.activeSounds[type].currentTime = 0;
            }

            let maxVariants = this.sounds[type];
            let variant = Math.floor(Math.random() * maxVariants) + 1;
            let audio = new Audio('/storage/sound/' + type + '-' + variant + '.mp3');
            audio.volume = this.volumes[this.categoryFor(type)];

            this.activeSounds[type] = audio;
            audio.play().catch(e => {
                if (e.name !== 'AbortError') {
                    console.log('Audio play failed: ', e);
                }
            });
        }
    }"
         @play-audio.window="playAudio($event.detail.type)"
         @settings-volume-changed.window="volumes[$event.detail.category] = $event.detail.value / 100"></div>

    {{-- ===== Toast Notification System ===== --}}
    <div
        x-data="{
            toasts: [],
            counter: 0,
            addToast(type, message, duration = 4000) {
                const id = ++this.counter;
                this.toasts.push({ id, type, message, duration, leaving: false });
                setTimeout(() => this.removeToast(id), duration);

                if (type === 'error') {
                    window.dispatchEvent(new CustomEvent('play-audio', { detail: { type: 'error' } }));
                }
            },
            removeToast(id) {
                const toast = this.toasts.find(t => t.id === id);
                if (toast) {
                    toast.leaving = true;
                    setTimeout(() => {
                        this.toasts = this.toasts.filter(t => t.id !== id);
                    }, 300);
                }
            }
        }"
        x-on:notify.window="
            if ($event.detail.category === 'quest_achievement' && localStorage.getItem('berserk_notify_quest_achievement') === 'false') return;
            addToast($event.detail.type || 'info', $event.detail.message, $event.detail.duration || 4000)
        "
        class="fixed top-4 right-4 z-[9998] flex flex-col gap-3 pointer-events-none max-w-sm w-full"
        style="font-family: 'Cinzel', serif;"
    >
        <template x-for="toast in toasts" :key="toast.id">
            <div
                :class="toast.leaving ? 'toast-leave' : 'toast-enter'"
                class="pointer-events-auto relative overflow-hidden rounded-lg border shadow-2xl backdrop-blur-md px-4 py-3 flex items-start gap-3 cursor-pointer"
                :style="
                    toast.type === 'success' ? 'background: linear-gradient(135deg, rgba(5,46,22,0.95), rgba(20,83,45,0.95)); border-color: rgba(34,197,94,0.5);' :
                    toast.type === 'error' ? 'background: linear-gradient(135deg, rgba(69,10,10,0.95), rgba(127,29,29,0.95)); border-color: rgba(239,68,68,0.5);' :
                    'background: linear-gradient(135deg, rgba(23,37,84,0.95), rgba(30,58,138,0.95)); border-color: rgba(96,165,250,0.5);'
                "
                @click="removeToast(toast.id)"
            >
                {{-- Icon --}}
                <div class="text-xl shrink-0 mt-0.5">
                    <span x-show="toast.type === 'success'"><i class="fa-solid fa-circle-check text-green-400"></i></span>
                    <span x-show="toast.type === 'error'"><i class="fa-solid fa-circle-xmark text-red-400"></i></span>
                    <span x-show="toast.type !== 'success' && toast.type !== 'error'"><i class="fa-solid fa-circle-info text-blue-400"></i></span>
                </div>

                {{-- Message --}}
                <div class="flex-1 min-w-0">
                    <p
                        class="text-sm font-semibold leading-snug break-words"
                        :class="
                            toast.type === 'success' ? 'text-green-200' :
                            toast.type === 'error' ? 'text-red-200' :
                            'text-blue-200'
                        "
                        x-html="toast.message"
                    ></p>
                </div>

                {{-- Close button --}}
                <button
                    @click.stop="removeToast(toast.id)"
                    class="shrink-0 text-white/40 hover:text-white/80 transition-colors text-xs leading-none mt-0.5"
                ><i class="fa-solid fa-xmark"></i></button>

                {{-- Progress bar --}}
                <div
                    class="absolute bottom-0 left-0 h-0.5 toast-progress"
                    :class="
                        toast.type === 'success' ? 'bg-green-400/60' :
                        toast.type === 'error' ? 'bg-red-400/60' :
                        'bg-blue-400/60'
                    "
                    :style="'--toast-duration: ' + toast.duration + 'ms'"
                ></div>
            </div>
        </template>
    </div>

    {{-- ===== Not Enough Gems Modal ===== --}}
    <div x-data="{ open: false }" 
         @not-enough-gems.window="open = true; window.dispatchEvent(new CustomEvent('play-audio', { detail: { type: 'error' } }));"
         x-show="open" 
         style="display: none; font-family: 'Cinzel', serif;"
         class="fixed inset-0 z-[9999] flex items-center justify-center p-4">
         
         {{-- Backdrop --}}
         <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" @click="open = false"></div>
         
         {{-- Modal content --}}
         <div class="relative bg-gradient-to-b from-slate-900 to-black border-2 border-amber-600 rounded-xl shadow-[0_0_30px_rgba(217,119,6,0.5)] p-8 max-w-md w-full text-center"
              x-transition:enter="transition ease-out duration-300"
              x-transition:enter-start="opacity-0 scale-90"
              x-transition:enter-end="opacity-100 scale-100"
              x-transition:leave="transition ease-in duration-200"
              x-transition:leave-start="opacity-100 scale-100"
              x-transition:leave-end="opacity-0 scale-90">
              
             <div class="text-5xl mb-4 text-purple-400 drop-shadow-[0_0_15px_rgba(168,85,247,0.6)]"><i class="fa-solid fa-gem"></i></div>
             <h2 class="text-2xl font-bold text-amber-300 mb-2">Brak Gemów</h2>
             <p class="text-amber-100/80 mb-8">Nie masz wystarczającej ilości Gemów, aby wykonać tę akcję. Czy chcesz doładować swoje konto w Sklepie Premium?</p>
             
             <div class="flex flex-col sm:flex-row gap-4 justify-center">
                 <button @click="open = false" class="px-6 py-2 border border-stone-500 text-stone-300 hover:bg-stone-800 hover:text-white rounded-lg transition-colors font-bold uppercase tracking-wider">
                     Anuluj
                 </button>
                 <a href="{{ route('itemshop') }}" class="px-6 py-2 bg-gradient-to-r from-amber-600 to-yellow-600 hover:from-amber-500 hover:to-yellow-500 text-white font-bold rounded-lg transition-all shadow-lg shadow-amber-500/30 uppercase tracking-wider text-center block" wire:navigate>
                     Tak, Doładuj Gemy
                 </a>
             </div>
         </div>
    </div>

    <div class="min-h-screen bg-slate-950 text-amber-100 pb-16 lg:pb-0">
        <x-flash-messages />
        @auth
            @if (session('active_character'))
                <div class="lg:flex lg:flex-row min-h-screen w-full">
                    <x-desktop-nav />
                    <div class="flex-1 min-w-0 flex flex-col min-h-screen">
                        @livewire('global.weekend-event-banner')
                        @livewire('global.active-buffs')

                        <!-- Page Heading -->
                        @if (isset($header))
                            <header class="bg-stone-900 border-b border-amber-900/60 shadow">
                                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                                    {{ $header }}
                                </div>
                            </header>
                        @endif

                        <!-- Page Content -->
                        <main class="flex-1 min-w-0">
                            {{ $slot }}
                        </main>
                    </div>
                </div>
            @else
                @livewire('global.active-buffs')

                <!-- Page Heading -->
                @if (isset($header))
                    <header class="bg-white shadow">
                        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endif

                <!-- Page Content -->
                <main class="flex-1 min-w-0">
                    {{ $slot }}
                </main>
            @endif
        @else
            @livewire('global.active-buffs')

            <!-- Page Heading -->
            @if (isset($header))
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <!-- Page Content -->
            <main class="flex-1 min-w-0">
                {{ $slot }}
            </main>
        @endauth
    </div>

    @auth
        @if (session('active_character'))
            @livewire('global.reward-infobox')
            @livewire('global.level-up-modal')
            @livewire('global.global-chat-component')
            @livewire('global.suggestion-modal')
            @livewire('global.discord-link-modal')
            <x-mobile-nav />
        @endif

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const activeCharId = "{{ session('active_character') ?? '' }}";
                if (activeCharId) {
                    localStorage.setItem('berserk_active_char', activeCharId);
                }

                window.addEventListener('storage', (e) => {
                    if (e.key === 'berserk_active_char') {
                        if (activeCharId && e.newValue && e.newValue !== activeCharId) {
                            window.location.href = "{{ route('homepage') }}";
                        }
                    }
                });
            });

            document.addEventListener('livewire:init', () => {
                if (window.Livewire) {
                    window.Livewire.hook('request', ({ fail }) => {
                        fail(({ status, preventDefault }) => {
                            if (status === 503) {
                                preventDefault();
                                window.location.reload();
                            }
                        });
                    });
                }
            });
        </script>
    @endauth
</body>

</html>
