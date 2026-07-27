<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Przerwa Techniczna - Berserk Rush</title>
    
    <!-- Favicons -->
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

    <!-- Google Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;700;900&family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Tailwind CSS (CDN for standalone reliability during maintenance) -->
    <script src="https://cdn.tailwindcss.com"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        cinzel: ['Cinzel', 'serif'],
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        berserk: {
                            gold: '#f59e0b',
                            goldLight: '#fbbf24',
                            dark: '#0a0a0c',
                            card: '#121218',
                            border: '#2a2420',
                            crimson: '#dc2626',
                        }
                    }
                }
            }
        }
    </script>

    <style>
        body {
            background-color: #060608;
            color: #e2e8f0;
            font-family: 'Inter', sans-serif;
            overflow-x: hidden;
        }

        /* Gothic Ember Background Glows */
        .glow-radial {
            background: radial-gradient(circle at 50% 30%, rgba(220, 38, 38, 0.18) 0%, rgba(217, 119, 6, 0.08) 40%, rgba(6, 6, 8, 0) 70%);
        }

        .amber-glow-box {
            box-shadow: 0 0 50px -10px rgba(217, 119, 6, 0.25), 0 0 20px -5px rgba(220, 38, 38, 0.2);
        }

        .gold-gradient-text {
            background: linear-gradient(135deg, #fef08a 0%, #f59e0b 50%, #b45309 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Floating embers animation */
        @keyframes floatEmber {
            0% {
                transform: translateY(0) translateX(0) scale(0.8);
                opacity: 0;
            }
            20% {
                opacity: 0.8;
            }
            80% {
                opacity: 0.6;
            }
            100% {
                transform: translateY(-100vh) translateX(50px) scale(1.2);
                opacity: 0;
            }
        }

        .ember {
            position: absolute;
            background: radial-gradient(circle, #f59e0b 0%, rgba(220, 38, 38, 0.8) 60%, transparent 100%);
            border-radius: 50%;
            pointer-events: none;
            animation: floatEmber linear infinite;
        }

        /* Pulse glow animation for anvil/shield */
        @keyframes pulseGlow {
            0%, 100% {
                filter: drop-shadow(0 0 15px rgba(245, 158, 11, 0.4)) drop-shadow(0 0 35px rgba(220, 38, 38, 0.3));
                transform: scale(1);
            }
            50% {
                filter: drop-shadow(0 0 30px rgba(245, 158, 11, 0.8)) drop-shadow(0 0 60px rgba(220, 38, 38, 0.6));
                transform: scale(1.03);
            }
        }

        .animate-forge-glow {
            animation: pulseGlow 3.5s ease-in-out infinite;
        }

        /* Shimmer animation on progress line */
        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        .animate-shimmer {
            animation: shimmer 2.5s infinite;
        }
    </style>
</head>
<div class="min-h-screen relative flex flex-col items-center justify-between p-4 sm:p-6 md:p-8 overflow-hidden select-none glow-radial">
    
    <!-- Background Embers Canvas Container -->
    <div id="embers-container" class="absolute inset-0 pointer-events-none overflow-hidden z-0"></div>

    <!-- Header Logo -->
    <header class="z-10 pt-4 md:pt-8 text-center">
        <div class="inline-flex items-center gap-3 px-4 py-2 rounded-full bg-black/40 border border-amber-500/20 backdrop-blur-md mb-2">
            <i class="fa-solid fa-shield-halved text-amber-500 text-sm"></i>
            <span class="font-cinzel text-xs md:text-sm font-bold tracking-widest text-amber-200 uppercase">Berserk Rush</span>
        </div>
    </header>

    <!-- Main Content Card -->
    <main class="z-10 my-auto w-full max-w-2xl text-center">
        <div class="relative bg-gradient-to-b from-[#16161e]/90 via-[#101017]/95 to-[#0b0b10]/95 border border-amber-500/30 rounded-2xl p-6 sm:p-10 backdrop-blur-xl amber-glow-box overflow-hidden">
            
            <!-- Top Decorative Sword Accent Lines -->
            <div class="absolute top-0 inset-x-0 h-1 bg-gradient-to-r from-transparent via-amber-500 to-transparent opacity-80"></div>
            
            <!-- Animated Icon / Forge Emblem -->
            <div class="relative mb-6 inline-block">
                <div class="w-24 h-24 sm:w-28 sm:h-28 rounded-full bg-gradient-to-b from-amber-500/20 to-red-950/40 border border-amber-500/40 flex items-center justify-center mx-auto animate-forge-glow relative">
                    <i class="fa-solid fa-hammer text-4xl sm:text-5xl text-amber-400"></i>
                    
                    <!-- Pulsing status ring -->
                    <span class="absolute -top-1 -right-1 flex h-5 w-5">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-5 w-5 bg-amber-500 border-2 border-[#101017]"></span>
                    </span>
                </div>
            </div>

            <!-- Maintenance Status Badge -->
            <div class="mb-4">
                <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-xs font-semibold tracking-wide uppercase bg-amber-950/60 text-amber-300 border border-amber-700/50 shadow-inner">
                    <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                    Prace Konserwacyjne &bull; Przebudowa
                </span>
            </div>

            <!-- Title -->
            <h1 class="font-cinzel text-2xl sm:text-4xl font-extrabold gold-gradient-text tracking-wide mb-4">
                Berserk Rush w trakcie przebudowy
            </h1>

            <!-- Description -->
            <p class="text-slate-300 text-sm sm:text-base leading-relaxed max-w-lg mx-auto mb-6">
                Władcy i Wojownicy! Nasza kuźnia pracuje na najwyższych obrotach. Przeprowadzamy prace modernizacyjne i przygotowujemy dla Was nowe ulepszenia oraz aktualizacje gry.
            </p>

            <!-- Custom Message output if provided by artisan down --message="..." -->
            @if(isset($exception) && $exception->getMessage())
                <div class="mb-6 p-4 rounded-xl bg-amber-950/40 border border-amber-500/30 text-amber-200 text-sm italic">
                    <i class="fa-solid fa-scroll mr-2 text-amber-400"></i>
                    "{{ $exception->getMessage() }}"
                </div>
            @endif

            <!-- Progress / Status Bar Visual -->
            <div class="w-full max-w-md mx-auto mb-8 bg-black/60 border border-amber-900/40 rounded-full h-3 p-0.5 relative overflow-hidden">
                <div class="bg-gradient-to-r from-red-600 via-amber-500 to-amber-400 h-full rounded-full w-3/4 relative overflow-hidden">
                    <div class="absolute inset-0 bg-white/20 animate-shimmer"></div>
                </div>
            </div>

            <!-- Auto-refresh Controls & Action Buttons -->
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <button type="button" id="refresh-btn" onclick="reloadPage()" class="group w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-gradient-to-r from-amber-600 via-amber-500 to-amber-600 hover:from-amber-500 hover:to-amber-400 active:scale-95 text-black font-extrabold text-sm tracking-wider uppercase transition-all shadow-lg shadow-amber-600/30 cursor-pointer">
                    <i id="refresh-icon" class="fa-solid fa-rotate-right transition-transform group-hover:rotate-180"></i>
                    <span id="refresh-text">Odśwież stronę</span>
                </button>
            </div>

            <!-- Auto-reload hint timer -->
            <div class="mt-6 text-xs text-slate-400 flex items-center justify-center gap-2">
                <i class="fa-regular fa-clock text-amber-500/80"></i>
                Strona odświeży się automatycznie za <span id="countdown" class="font-bold text-amber-400">60</span>s
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="z-10 pb-4 text-center text-xs text-slate-400">
        <p>&copy; {{ date('Y') }} <span class="text-amber-400 font-cinzel">Berserk Rush</span>. Dziękujemy za cierpliwość!</p>
    </footer>

    <!-- Embers Generator Script & Countdown -->
    <script>
        // Reload function that bypasses cache
        function reloadPage() {
            const icon = document.getElementById('refresh-icon');
            const text = document.getElementById('refresh-text');
            if (icon) icon.classList.add('fa-spin');
            if (text) text.innerText = 'Odświeżanie...';
            
            // Hard reload with cache buster
            window.location.href = window.location.origin + window.location.pathname + '?r=' + Date.now();
        }

        // Embers animation generator
        (function createEmbers() {
            const container = document.getElementById('embers-container');
            const count = 35;
            for (let i = 0; i < count; i++) {
                const ember = document.createElement('div');
                ember.className = 'ember';
                const size = Math.random() * 4 + 2;
                ember.style.width = size + 'px';
                ember.style.height = size + 'px';
                ember.style.left = Math.random() * 100 + '%';
                ember.style.bottom = '-10px';
                ember.style.animationDuration = (Math.random() * 6 + 4) + 's';
                ember.style.animationDelay = (Math.random() * 5) + 's';
                container.appendChild(ember);
            }
        })();

        // Countdown timer for automatic page refresh
        let seconds = 60;
        const countdownEl = document.getElementById('countdown');
        const timer = setInterval(() => {
            seconds--;
            if (countdownEl) countdownEl.innerText = seconds;
            if (seconds <= 0) {
                clearInterval(timer);
                reloadPage();
            }
        }, 1000);
    </script>
</div>
</html>
