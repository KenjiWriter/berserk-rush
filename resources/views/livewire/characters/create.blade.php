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
        <div class="w-full max-w-2xl">
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

            {{-- Character creation form --}}
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
                    <div class="text-center mb-8">
                        <h1 class="text-3xl font-bold text-amber-900 medieval-font mb-2">
                            ⚔️ Stwórz Nowego Wojownika
                        </h1>
                        <p class="text-amber-800 font-semibold">
                            Określ atrybuty swojej postaci i rozpocznij przygodę
                        </p>
                    </div>

                    {{-- Form errors --}}
                    @error('form')
                        <div class="mb-6 p-4 bg-red-100 border-2 border-red-600 rounded-lg">
                            <p class="text-red-800 font-semibold">{{ $message }}</p>
                        </div>
                    @enderror

                    @error('stats')
                        <div class="mb-6 p-4 bg-red-100 border-2 border-red-600 rounded-lg">
                            <p class="text-red-800 font-semibold">{{ $message }}</p>
                        </div>
                    @enderror

                    {{-- Form --}}
                    <form wire:submit="createCharacter" class="space-y-6">
                        {{-- Character name --}}
                        <div>
                            <label for="name" class="block text-lg font-bold text-amber-900 mb-3 medieval-font">
                                Nazwa Postaci
                            </label>
                            <input type="text" id="name" wire:model.live="name"
                                class="w-full px-4 py-3 text-lg border-2 border-amber-600 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 bg-amber-50 text-amber-900 placeholder-amber-600"
                                placeholder="WielkiWojownik123" maxlength="16">
                            @error('name')
                                <p class="mt-2 text-sm text-red-600 font-semibold">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-sm text-amber-700">3-16 znaków, tylko litery, cyfry i podkreślenia</p>
                        </div>

                        {{-- Avatar selection carousel --}}
                        @if (!empty($availableAvatars))
                            <div>
                                <label class="block text-lg font-bold text-amber-900 mb-4 medieval-font">
                                    Wybierz Avatar
                                </label>

                                <div class="bg-amber-100/80 border-2 border-amber-600 rounded-lg p-6">
                                    {{-- Selected avatar display --}}
                                    <div class="text-center mb-6">
                                        <div class="inline-block relative">
                                            <div
                                                class="w-32 h-32 mx-auto border-4 border-amber-700 rounded-full overflow-hidden bg-gradient-to-b from-amber-200 to-amber-300 shadow-lg">
                                                @if ($avatar && isset($availableAvatars[$avatar]))
                                                    <img src="{{ $availableAvatars[$avatar] }}"
                                                        alt="Avatar {{ $avatar }}"
                                                        class="w-full h-full object-cover">
                                                @else
                                                    <div
                                                        class="w-full h-full flex items-center justify-center text-4xl text-amber-700">
                                                        👤
                                                    </div>
                                                @endif
                                            </div>
                                            <div
                                                class="absolute -bottom-2 left-1/2 transform -translate-x-1/2 bg-amber-800 text-amber-100 px-3 py-1 rounded-full text-sm font-bold">
                                                {{ $avatar ? ucfirst($avatar) : 'Wybierz' }}
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Avatar carousel --}}
                                    <div class="relative">
                                        <div class="flex items-center justify-center space-x-4 overflow-x-auto pb-2">
                                            @foreach ($availableAvatars as $avatarKey => $avatarUrl)
                                                <button type="button" wire:click="selectAvatar('{{ $avatarKey }}')"
                                                    class="flex-shrink-0 relative group">
                                                    <div
                                                        class="w-20 h-20 border-2 rounded-full overflow-hidden transition-all duration-200 transform hover:scale-110 {{ $avatar === $avatarKey ? 'border-amber-700 ring-4 ring-amber-500 shadow-lg' : 'border-amber-500 hover:border-amber-600' }}">
                                                        <img src="{{ $avatarUrl }}"
                                                            alt="Avatar {{ $avatarKey }}"
                                                            class="w-full h-full object-cover">
                                                    </div>

                                                    {{-- Selection indicator --}}
                                                    @if ($avatar === $avatarKey)
                                                        <div
                                                            class="absolute -top-1 -right-1 w-6 h-6 bg-green-600 rounded-full border-2 border-white flex items-center justify-center">
                                                            <svg class="w-3 h-3 text-white" fill="currentColor"
                                                                viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd"
                                                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                                    clip-rule="evenodd"></path>
                                                            </svg>
                                                        </div>
                                                    @endif

                                                    {{-- Hover tooltip --}}
                                                    <div
                                                        class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 bg-slate-800 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap">
                                                        {{ ucfirst($avatarKey) }}
                                                    </div>
                                                </button>
                                            @endforeach
                                        </div>

                                        {{-- Navigation hint --}}
                                        <p class="text-center text-amber-700 text-sm mt-3 font-semibold">
                                            Kliknij avatar aby go wybrać
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Attribute allocation --}}
                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-bold text-amber-900 medieval-font">Atrybuty Postaci</h3>
                                <div class="text-right">
                                    <div
                                        class="text-2xl font-bold {{ $this->getRemainingPoints() > 0 ? 'text-green-700' : 'text-amber-900' }}">
                                        {{ $this->getRemainingPoints() }}
                                    </div>
                                    <div class="text-sm text-amber-700">punktów do rozdania</div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                {{-- Strength --}}
                                <div class="bg-amber-100/80 border-2 border-amber-600 rounded-lg p-4">
                                    <div class="flex items-center justify-between mb-2">
                                        <div>
                                            <h4 class="font-bold text-amber-900">💪 Siła (STR)</h4>
                                            <p class="text-xs text-amber-700">Obrażenia fizyczne, bonus HP</p>
                                        </div>
                                        <div class="text-2xl font-bold text-amber-900">{{ $str }}</div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <button type="button" wire:click="decrementStat('str')"
                                            class="w-8 h-8 bg-red-600 hover:bg-red-700 text-white rounded font-bold"
                                            {{ $str <= 0 ? 'disabled' : '' }}>
                                            -
                                        </button>
                                        <div class="flex-1 bg-amber-200 rounded-full h-2">
                                            <div class="bg-red-600 h-2 rounded-full transition-all duration-300"
                                                style="width: {{ ($str / 10) * 100 }}%"></div>
                                        </div>
                                        <button type="button" wire:click="incrementStat('str')"
                                            class="w-8 h-8 bg-green-600 hover:bg-green-700 text-white rounded font-bold"
                                            {{ $str >= 10 || $this->getRemainingPoints() <= 0 ? 'disabled' : '' }}>
                                            +
                                        </button>
                                    </div>
                                </div>

                                {{-- Intelligence --}}
                                <div class="bg-amber-100/80 border-2 border-amber-600 rounded-lg p-4">
                                    <div class="flex items-center justify-between mb-2">
                                        <div>
                                            <h4 class="font-bold text-amber-900">🧠 Inteligencja (INT)</h4>
                                            <p class="text-xs text-amber-700">Obrażenia magiczne, mana</p>
                                        </div>
                                        <div class="text-2xl font-bold text-amber-900">{{ $int }}</div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <button type="button" wire:click="decrementStat('int')"
                                            class="w-8 h-8 bg-red-600 hover:bg-red-700 text-white rounded font-bold"
                                            {{ $int <= 0 ? 'disabled' : '' }}>
                                            -
                                        </button>
                                        <div class="flex-1 bg-amber-200 rounded-full h-2">
                                            <div class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                                                style="width: {{ ($int / 10) * 100 }}%"></div>
                                        </div>
                                        <button type="button" wire:click="incrementStat('int')"
                                            class="w-8 h-8 bg-green-600 hover:bg-green-700 text-white rounded font-bold"
                                            {{ $int >= 10 || $this->getRemainingPoints() <= 0 ? 'disabled' : '' }}>
                                            +
                                        </button>
                                    </div>
                                </div>

                                {{-- Vitality --}}
                                <div class="bg-amber-100/80 border-2 border-amber-600 rounded-lg p-4">
                                    <div class="flex items-center justify-between mb-2">
                                        <div>
                                            <h4 class="font-bold text-amber-900">❤️ Witalność (VIT)</h4>
                                            <p class="text-xs text-amber-700">Maksymalne HP, obrona</p>
                                        </div>
                                        <div class="text-2xl font-bold text-amber-900">{{ $vit }}</div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <button type="button" wire:click="decrementStat('vit')"
                                            class="w-8 h-8 bg-red-600 hover:bg-red-700 text-white rounded font-bold"
                                            {{ $vit <= 0 ? 'disabled' : '' }}>
                                            -
                                        </button>
                                        <div class="flex-1 bg-amber-200 rounded-full h-2">
                                            <div class="bg-green-600 h-2 rounded-full transition-all duration-300"
                                                style="width: {{ ($vit / 10) * 100 }}%"></div>
                                        </div>
                                        <button type="button" wire:click="incrementStat('vit')"
                                            class="w-8 h-8 bg-green-600 hover:bg-green-700 text-white rounded font-bold"
                                            {{ $vit >= 10 || $this->getRemainingPoints() <= 0 ? 'disabled' : '' }}>
                                            +
                                        </button>
                                    </div>
                                </div>

                                {{-- Agility --}}
                                <div class="bg-amber-100/80 border-2 border-amber-600 rounded-lg p-4">
                                    <div class="flex items-center justify-between mb-2">
                                        <div>
                                            <h4 class="font-bold text-amber-900">🏃 Zręczność (AGI)</h4>
                                            <p class="text-xs text-amber-700">Obrażenia dystansowe, unik, krytyki</p>
                                        </div>
                                        <div class="text-2xl font-bold text-amber-900">{{ $agi }}</div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <button type="button" wire:click="decrementStat('agi')"
                                            class="w-8 h-8 bg-red-600 hover:bg-red-700 text-white rounded font-bold"
                                            {{ $agi <= 0 ? 'disabled' : '' }}>
                                            -
                                        </button>
                                        <div class="flex-1 bg-amber-200 rounded-full h-2">
                                            <div class="bg-yellow-600 h-2 rounded-full transition-all duration-300"
                                                style="width: {{ ($agi / 10) * 100 }}%"></div>
                                        </div>
                                        <button type="button" wire:click="incrementStat('agi')"
                                            class="w-8 h-8 bg-green-600 hover:bg-green-700 text-white rounded font-bold"
                                            {{ $agi >= 10 || $this->getRemainingPoints() <= 0 ? 'disabled' : '' }}>
                                            +
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Submit button --}}
                        <button type="submit"
                            class="w-full bg-gradient-to-r from-amber-600 to-amber-700 hover:from-amber-700 hover:to-amber-800 text-white font-bold py-4 px-4 rounded-lg transition-all duration-200 transform hover:scale-105 shadow-lg medieval-font text-lg"
                            {{ $this->getRemainingPoints() !== 0 ? 'disabled' : '' }}>
                            🛡️ Stwórz Wojownika
                        </button>
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

        button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Custom scrollbar for avatar carousel */
        .overflow-x-auto::-webkit-scrollbar {
            height: 6px;
        }

        .overflow-x-auto::-webkit-scrollbar-track {
            background: rgba(251, 191, 36, 0.2);
            border-radius: 3px;
        }

        .overflow-x-auto::-webkit-scrollbar-thumb {
            background: rgba(180, 83, 9, 0.8);
            border-radius: 3px;
        }

        .overflow-x-auto::-webkit-scrollbar-thumb:hover {
            background: rgba(180, 83, 9, 1);
        }
    </style>
</div>
