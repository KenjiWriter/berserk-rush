<div class="min-h-screen bg-slate-900 text-slate-300 relative overflow-hidden font-sans"
    x-data="{ show: false }" x-init="setTimeout(() => show = true, 100)">
    
    {{-- Background image --}}
    <div class="absolute inset-0 bg-cover bg-center bg-no-repeat opacity-40 transition-opacity duration-1000"
        :class="show ? 'opacity-40' : 'opacity-0'"
        style="background-image: url('{{ asset('img/homepage-background.png') }}');">
    </div>

    {{-- Dark vignette overlay --}}
    <div class="absolute inset-0 bg-gradient-to-b from-slate-950/80 via-transparent to-slate-950/90"></div>

    {{-- Embers Particles --}}
    <div class="particles-container absolute inset-0 pointer-events-none">
        @for($i = 1; $i <= 12; $i++)
            <div class="ember ember-{{ $i }}"></div>
        @endfor
    </div>

    <div class="relative flex items-center justify-center min-h-screen px-4 py-12">
        <div class="w-full max-w-xl z-10">
            
            {{-- Back to homepage link --}}
            <div class="text-center mb-8" 
                 x-show="show" x-transition:enter="transition ease-out duration-700 delay-100" 
                 x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">
                <a href="{{ route('homepage') }}"
                    class="inline-flex items-center text-slate-400 hover:text-amber-500 transition-colors font-semibold group text-lg">
                    <svg class="w-6 h-6 mr-2 transform group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Opuść Karczmę (Powrót)
                </a>
            </div>

            {{-- Registration form container --}}
            <div x-show="show" x-transition:enter="transition ease-out duration-1000 delay-300"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-8" 
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 class="bg-slate-900/80 border border-slate-700 rounded-lg shadow-2xl backdrop-blur-md relative overflow-hidden shadow-black/50">
                
                {{-- Metallic Corners --}}
                <div class="absolute top-0 left-0 w-6 h-6 border-t-2 border-l-2 border-amber-600/50"></div>
                <div class="absolute top-0 right-0 w-6 h-6 border-t-2 border-r-2 border-amber-600/50"></div>
                <div class="absolute bottom-0 left-0 w-6 h-6 border-b-2 border-l-2 border-amber-600/50"></div>
                <div class="absolute bottom-0 right-0 w-6 h-6 border-b-2 border-r-2 border-amber-600/50"></div>

                {{-- Bloody/Amber Top highlight --}}
                <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-transparent via-red-800 to-transparent opacity-70"></div>

                <div class="relative p-10 md:p-14">
                    {{-- Header --}}
                    <div class="text-center mb-10">
                        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gradient-to-tr from-amber-700 to-amber-500 border-2 border-amber-400 flex items-center justify-center text-amber-950 shadow-lg text-2xl">
                            <i class="fa-solid fa-book-skull"></i>
                        </div>
                        <h1 class="text-4xl md:text-5xl font-bold text-amber-500 medieval-font mb-4 drop-shadow-md">
                            Księga Rejestrów
                        </h1>
                        <p class="text-slate-400 text-base md:text-lg">
                            Zapisz swe miano, by rozpocząć rzeź
                        </p>
                    </div>

                    {{-- Form --}}
                    <form wire:submit="register" class="space-y-7">
                        {{-- Name field --}}
                        <div x-show="show" x-transition:enter="transition ease-out duration-500 delay-500" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">
                            <label for="name" class="block text-sm uppercase tracking-wider font-bold text-slate-400 mb-3 flex items-center gap-2">
                                <i class="fa-solid fa-user text-amber-500"></i> Miano Wojownika
                            </label>
                            <input type="text" id="name" wire:model.live="name"
                                class="w-full px-5 py-4 bg-slate-950/50 border border-slate-700 rounded focus:ring-1 focus:ring-amber-500 focus:border-amber-500 text-amber-100 placeholder-slate-600 transition-all duration-300 text-lg"
                                placeholder="Guts..." autocomplete="name">
                            @error('name')
                                <p class="mt-2 text-sm text-red-500 font-semibold">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Email field --}}
                        <div x-show="show" x-transition:enter="transition ease-out duration-500 delay-600" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">
                            <label for="email" class="block text-sm uppercase tracking-wider font-bold text-slate-400 mb-3 flex items-center gap-2">
                                <i class="fa-solid fa-envelope text-amber-500"></i> Magiczny Zwój (Email)
                            </label>
                            <input type="email" id="email" wire:model.live="email"
                                class="w-full px-5 py-4 bg-slate-950/50 border border-slate-700 rounded focus:ring-1 focus:ring-amber-500 focus:border-amber-500 text-amber-100 placeholder-slate-600 transition-all duration-300 text-lg"
                                placeholder="twoj@email.com" autocomplete="email">
                            @error('email')
                                <p class="mt-2 text-sm text-red-500 font-semibold">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Password field --}}
                        <div x-show="show" x-transition:enter="transition ease-out duration-500 delay-700" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">
                            <label for="password" class="block text-sm uppercase tracking-wider font-bold text-slate-400 mb-3 flex items-center gap-2">
                                <i class="fa-solid fa-lock text-amber-500"></i> Hasło
                            </label>
                            <input type="password" id="password" wire:model.live="password"
                                class="w-full px-5 py-4 bg-slate-950/50 border border-slate-700 rounded focus:ring-1 focus:ring-amber-500 focus:border-amber-500 text-amber-100 placeholder-slate-600 transition-all duration-300 tracking-widest text-lg"
                                placeholder="••••••••" autocomplete="new-password">
                            @error('password')
                                <p class="mt-2 text-sm text-red-500 font-semibold">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Password confirmation field --}}
                        <div x-show="show" x-transition:enter="transition ease-out duration-500 delay-800" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">
                            <label for="password_confirmation" class="block text-sm uppercase tracking-wider font-bold text-slate-400 mb-3 flex items-center gap-2">
                                <i class="fa-solid fa-shield-halved text-amber-500"></i> Potwierdź Hasło
                            </label>
                            <input type="password" id="password_confirmation" wire:model.live="password_confirmation"
                                class="w-full px-5 py-4 bg-slate-950/50 border border-slate-700 rounded focus:ring-1 focus:ring-amber-500 focus:border-amber-500 text-amber-100 placeholder-slate-600 transition-all duration-300 tracking-widest text-lg"
                                placeholder="••••••••" autocomplete="new-password">
                        </div>

                        {{-- Submit button --}}
                        <div x-show="show" x-transition:enter="transition ease-out duration-500 delay-900" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" class="pt-4">
                            <button type="submit"
                                class="w-full relative group overflow-hidden bg-gradient-to-b from-red-800 to-red-950 border border-red-700/50 text-amber-100 font-bold py-5 px-6 rounded transition-all duration-300 transform hover:scale-[1.02] shadow-[0_0_15px_rgba(153,27,27,0.3)] hover:shadow-[0_0_25px_rgba(185,28,28,0.5)] medieval-font text-xl disabled:opacity-50 disabled:cursor-not-allowed"
                                wire:loading.attr="disabled"
                                wire:target="register">
                                
                                {{-- Button shine effect --}}
                                <div class="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/10 to-transparent group-hover:animate-[shine_1.5s_ease-in-out_infinite]"></div>

                                <span wire:loading.remove wire:target="register" class="relative z-10 drop-shadow-md flex items-center justify-center gap-2">
                                    <i class="fa-solid fa-user-plus text-amber-300"></i> Wykuj Swoje Przeznaczenie
                                </span>
                                
                                <span wire:loading wire:target="register" class="relative z-10 flex items-center justify-center gap-2">
                                    <svg class="animate-spin h-6 w-6 text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Kucie losu...
                                </span>
                            </button>
                        </div>

                        {{-- Login link --}}
                        <div class="text-center pt-6" x-show="show" x-transition:enter="transition ease-out duration-500 delay-1000" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                            <p class="text-slate-500 text-base">
                                Znany wojownik?
                                <a href="{{ route('homepage') }}"
                                    class="font-bold text-amber-600 hover:text-amber-400 hover:drop-shadow-[0_0_8px_rgba(251,191,36,0.5)] transition-all">
                                    Wejdź do gry
                                </a>
                            </p>
                        </div>

                        {{-- Social Login section --}}
                        <div class="mt-6 pt-6 border-t border-slate-800" x-show="show" x-transition:enter="transition ease-out duration-500 delay-1100" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                            <div class="relative flex justify-center text-sm uppercase tracking-widest text-slate-600 mb-5 font-bold">
                                <span>Lub użyj paktu z</span>
                            </div>

                            <div class="grid grid-cols-2 gap-5">
                                <a href="{{ route('auth.social.redirect', 'google') }}" 
                                   class="flex items-center justify-center px-6 py-3 bg-slate-900 border border-slate-700 hover:border-slate-500 rounded text-slate-300 hover:text-white transition-all transform hover:-translate-y-0.5 shadow-md">
                                    <svg class="w-6 h-6 mr-3 text-white" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M12.48 10.92v3.28h7.84c-.24 1.84-.853 3.187-1.787 4.133-1.147 1.147-2.933 2.4-6.053 2.4-4.827 0-8.6-3.893-8.6-8.72s3.773-8.72 8.6-8.72c2.6 0 4.507 1.027 5.907 2.347l2.307-2.307C18.747 1.44 16.133 0 12.48 0 5.867 0 .307 5.387.307 12s5.56 12 12.173 12c3.573 0 6.267-1.173 8.373-3.36 2.16-2.16 2.84-5.213 2.84-7.667 0-.76-.053-1.467-.173-2.053H12.48z"/>
                                    </svg>
                                    <span class="font-bold text-base">Google</span>
                                </a>
                                
                                <a href="{{ route('auth.social.redirect', 'facebook') }}" 
                                   class="flex items-center justify-center px-6 py-3 bg-slate-900 border border-slate-700 hover:border-blue-500/50 rounded text-slate-300 hover:text-white transition-all transform hover:-translate-y-0.5 shadow-md">
                                    <svg class="w-6 h-6 mr-3 text-[#1877F2]" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.469h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.469h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                    </svg>
                                    <span class="font-bold text-base">Facebook</span>
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&display=swap');

        .medieval-font {
            font-family: 'Cinzel', serif;
        }

        /* Embers Animation */
        .ember {
            position: absolute;
            bottom: -20px;
            width: 4px;
            height: 4px;
            background: #f59e0b;
            border-radius: 50%;
            box-shadow: 0 0 10px 2px rgba(245, 158, 11, 0.8), 0 0 20px 4px rgba(220, 38, 38, 0.6);
            animation: rise linear infinite;
            opacity: 0;
            filter: blur(1px);
        }

        @keyframes rise {
            0% {
                transform: translateY(0) scale(1) rotate(0deg);
                opacity: 0;
            }
            20% {
                opacity: 0.8;
            }
            80% {
                opacity: 0.5;
            }
            100% {
                transform: translateY(-100vh) scale(0.2) rotate(360deg);
                opacity: 0;
            }
        }

        @keyframes shine {
            100% {
                transform: translateX(100%);
            }
        }

        /* Generated Embers config */
        @for($i = 1; $i <= 12; $i++)
            .ember-{{ $i }} {
                left: {{ rand(5, 95) }}%;
                animation-duration: {{ rand(8, 15) }}s;
                animation-delay: {{ rand(0, 10) }}s;
                width: {{ rand(3, 6) }}px;
                height: {{ rand(3, 6) }}px;
            }
        @endfor
    </style>
</div>
