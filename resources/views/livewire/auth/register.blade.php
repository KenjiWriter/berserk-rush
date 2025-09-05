<div
    class="min-h-screen bg-gradient-to-b from-slate-800/90 via-slate-700/90 to-slate-800/90 text-amber-100 relative overflow-hidden">
    {{-- Background image --}}
    <div class="absolute inset-0 bg-cover bg-center bg-no-repeat"
        style="background-image: url('{{ asset('img/homepage-background.png') }}');">
    </div>

    {{-- Dark overlay for readability --}}
    <div class="absolute inset-0 bg-gradient-to-b from-slate-900/60 via-slate-800/70 to-slate-900/60"></div>

    {{-- Floating particles --}}
    <div class="particles-container absolute inset-0 pointer-events-none">
        <div class="particle particle-1"></div>
        <div class="particle particle-2"></div>
        <div class="particle particle-3"></div>
        <div class="particle particle-4"></div>
        <div class="particle particle-5"></div>
    </div>

    <div class="relative flex items-center justify-center min-h-screen px-4 py-8">
        <div class="w-full max-w-md">
            {{-- Back to homepage link --}}
            <div class="text-center mb-6">
                <a href="{{ route('homepage') }}"
                    class="inline-flex items-center text-amber-300 hover:text-amber-100 transition-colors font-semibold">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Powrót do gry
                </a>
            </div>

            {{-- Registration form --}}
            <div
                class="bg-gradient-to-br from-amber-50/95 to-amber-100/95 border-4 border-amber-700 rounded-lg shadow-2xl backdrop-blur-sm relative overflow-hidden">
                {{-- Decorative corners --}}
                <div
                    class="absolute top-0 left-0 w-8 h-8 bg-amber-800 transform rotate-45 -translate-x-4 -translate-y-4">
                </div>
                <div
                    class="absolute top-0 right-0 w-8 h-8 bg-amber-800 transform rotate-45 translate-x-4 -translate-y-4">
                </div>
                <div
                    class="absolute bottom-0 left-0 w-8 h-8 bg-amber-800 transform rotate-45 -translate-x-4 translate-y-4">
                </div>
                <div
                    class="absolute bottom-0 right-0 w-8 h-8 bg-amber-800 transform rotate-45 translate-x-4 translate-y-4">
                </div>

                <div class="relative p-8">
                    {{-- Header --}}
                    <div class="text-center mb-6">
                        <h1 class="text-3xl font-bold text-amber-900 medieval-font mb-2">
                            ⚔️ Dołącz do Berserk Rush
                        </h1>
                        <p class="text-amber-800 font-semibold">
                            Stwórz konto i rozpocznij przygodę
                        </p>
                    </div>

                    {{-- Form --}}
                    <form wire:submit="register" class="space-y-4">
                        {{-- Name field --}}
                        <div>
                            <label for="name" class="block text-sm font-bold text-amber-900 mb-2">
                                Nazwa użytkownika
                            </label>
                            <input type="text" id="name" wire:model.live="name"
                                class="w-full px-4 py-3 border-2 border-amber-600 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 bg-amber-50 text-amber-900 placeholder-amber-600"
                                placeholder="TwojaSkrytobójcza" autocomplete="name">
                            @error('name')
                                <p class="mt-1 text-sm text-red-600 font-semibold">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Email field --}}
                        <div>
                            <label for="email" class="block text-sm font-bold text-amber-900 mb-2">
                                Email
                            </label>
                            <input type="email" id="email" wire:model.live="email"
                                class="w-full px-4 py-3 border-2 border-amber-600 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 bg-amber-50 text-amber-900 placeholder-amber-600"
                                placeholder="twoj@email.com" autocomplete="email">
                            @error('email')
                                <p class="mt-1 text-sm text-red-600 font-semibold">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Password field --}}
                        <div>
                            <label for="password" class="block text-sm font-bold text-amber-900 mb-2">
                                Hasło
                            </label>
                            <input type="password" id="password" wire:model.live="password"
                                class="w-full px-4 py-3 border-2 border-amber-600 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 bg-amber-50 text-amber-900 placeholder-amber-600"
                                placeholder="••••••••" autocomplete="new-password">
                            @error('password')
                                <p class="mt-1 text-sm text-red-600 font-semibold">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Password confirmation field --}}
                        <div>
                            <label for="password_confirmation" class="block text-sm font-bold text-amber-900 mb-2">
                                Potwierdź hasło
                            </label>
                            <input type="password" id="password_confirmation" wire:model.live="password_confirmation"
                                class="w-full px-4 py-3 border-2 border-amber-600 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 bg-amber-50 text-amber-900 placeholder-amber-600"
                                placeholder="••••••••" autocomplete="new-password">
                        </div>

                        {{-- Submit button --}}
                        <button type="submit"
                            class="w-full bg-gradient-to-r from-amber-600 to-amber-700 hover:from-amber-700 hover:to-amber-800 text-white font-bold py-4 px-4 rounded-lg transition-all duration-200 transform hover:scale-105 shadow-lg medieval-font text-lg">
                            🛡️ Stwórz konto wojownika
                        </button>

                        {{-- Login link --}}
                        <div class="text-center pt-4 border-t-2 border-amber-600">
                            <p class="text-amber-800">
                                Masz już konto?
                                <a href="{{ route('homepage') }}"
                                    class="font-bold text-amber-900 hover:text-amber-700 underline transition-colors">
                                    Zaloguj się
                                </a>
                            </p>
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

        /* Floating particles */
        .particle {
            position: absolute;
            background: radial-gradient(circle, rgba(251, 191, 36, 0.8) 0%, rgba(251, 191, 36, 0.4) 50%, transparent 100%);
            border-radius: 50%;
            pointer-events: none;
            animation: float 15s infinite linear;
        }

        .particle-1 {
            width: 4px;
            height: 4px;
            left: 10%;
            animation-delay: 0s;
            animation-duration: 20s;
        }

        .particle-2 {
            width: 6px;
            height: 6px;
            left: 30%;
            animation-delay: 5s;
            animation-duration: 18s;
        }

        .particle-3 {
            width: 3px;
            height: 3px;
            left: 50%;
            animation-delay: 10s;
            animation-duration: 22s;
        }

        .particle-4 {
            width: 5px;
            height: 5px;
            left: 70%;
            animation-delay: 15s;
            animation-duration: 16s;
        }

        .particle-5 {
            width: 4px;
            height: 4px;
            left: 90%;
            animation-delay: 20s;
            animation-duration: 19s;
        }

        @keyframes float {
            0% {
                transform: translateY(100vh) translateX(0px) rotate(0deg);
                opacity: 0;
            }

            10% {
                opacity: 1;
            }

            90% {
                opacity: 1;
            }

            100% {
                transform: translateY(-100px) translateX(100px) rotate(360deg);
                opacity: 0;
            }
        }
    </style>
</div>
