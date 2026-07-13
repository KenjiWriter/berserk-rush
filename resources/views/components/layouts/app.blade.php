<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&display=swap" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

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
    </style>
    <script>
        document.addEventListener('pointerdown', function unlock() {
            let audio = new Audio("data:audio/wav;base64,UklGRigAAABXQVZFZm10IBIAAAABAAEARKwAAIhYAQACABAAAABkYXRhAgAAAAEA");
            audio.play().catch(() => {});
            document.removeEventListener('pointerdown', unlock);
        }, { once: true });
    </script>
</head>

<body class="font-sans antialiased">
    {{-- ===== Global Location Transition Overlay ===== --}}
    <div x-data="{ leaving: false }"
         @location-leave.window="leaving = true"
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
             <div class="absolute inset-0 flex items-center justify-center text-3xl">🏰</div>
         </div>
         
         <h2 class="text-3xl font-bold text-amber-500 drop-shadow-[0_0_10px_rgba(245,158,11,0.5)] animate-pulse" style="font-family: 'Cinzel', serif;">
             Podróż do Miasta...
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
            audio.volume = 0.5;
            
            this.activeSounds[type] = audio;
            audio.play().catch(e => console.log('Audio play failed: ', e));
        }
    }" @play-audio.window="playAudio($event.detail.type)"></div>

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
        x-on:notify.window="addToast($event.detail.type || 'info', $event.detail.message, $event.detail.duration || 4000)"
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
                    <span x-show="toast.type === 'success'">✅</span>
                    <span x-show="toast.type === 'error'">❌</span>
                    <span x-show="toast.type !== 'success' && toast.type !== 'error'">ℹ️</span>
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
                        x-text="toast.message"
                    ></p>
                </div>

                {{-- Close button --}}
                <button
                    @click.stop="removeToast(toast.id)"
                    class="shrink-0 text-white/40 hover:text-white/80 transition-colors text-xs leading-none mt-0.5"
                >✕</button>

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

    <div class="min-h-screen bg-gray-100 pb-16 lg:pb-0">

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
        <main>
            {{ $slot }}
        </main>
    </div>

    @auth
        @if (session('active_character'))
            @livewire('global.global-chat-component')
            <x-mobile-nav />
        @endif
    @endauth
</body>

</html>
