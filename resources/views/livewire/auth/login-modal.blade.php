<div>
    {{-- Login Button --}}
    <button wire:click="openModal"
        class="w-full bg-gradient-to-r from-slate-600 to-slate-700 hover:from-slate-700 hover:to-slate-800 text-amber-200 font-bold py-3 px-4 rounded-lg transition-all duration-200 transform hover:scale-105 shadow-lg text-center border-2 border-amber-600 medieval-font">
        🗝️ Zaloguj się
    </button>

    {{-- Modal - Portal to body to avoid container constraints --}}
    @if ($showModal)
        @teleport('body')
            <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                {{-- Background overlay --}}
                <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-slate-900 bg-opacity-75 transition-opacity" wire:click="closeModal"></div>

                    {{-- Centering wrapper --}}
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                    {{-- Modal panel --}}
                    <div
                        class="inline-block align-bottom bg-gradient-to-br from-amber-50 to-amber-100 rounded-lg border-4 border-amber-700 text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full relative z-10">
                        {{-- Decorative corners --}}
                        <div
                            class="absolute top-0 left-0 w-8 h-8 bg-amber-800 transform rotate-45 -translate-x-4 -translate-y-4 z-20">
                        </div>
                        <div
                            class="absolute top-0 right-0 w-8 h-8 bg-amber-800 transform rotate-45 translate-x-4 -translate-y-4 z-20">
                        </div>
                        <div
                            class="absolute bottom-0 left-0 w-8 h-8 bg-amber-800 transform rotate-45 -translate-x-4 translate-y-4 z-20">
                        </div>
                        <div
                            class="absolute bottom-0 right-0 w-8 h-8 bg-amber-800 transform rotate-45 translate-x-4 translate-y-4 z-20">
                        </div>

                        <div class="relative px-8 pt-6 pb-6 z-10">
                            {{-- Header --}}
                            <div class="flex items-center justify-between mb-6">
                                <h3 class="text-2xl font-bold text-amber-900 medieval-font" id="modal-title">
                                    🗝️ Zaloguj się do gry
                                </h3>
                                <button wire:click="closeModal"
                                    class="text-amber-700 hover:text-amber-900 transition-colors">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>

                            {{-- Form --}}
                            <form wire:submit="login" class="space-y-4">
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
                                        placeholder="••••••••" autocomplete="current-password">
                                    @error('password')
                                        <p class="mt-1 text-sm text-red-600 font-semibold">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Remember me --}}
                                <div class="flex items-center">
                                    <input type="checkbox" id="remember" wire:model="remember"
                                        class="w-4 h-4 text-amber-600 bg-amber-50 border-amber-600 rounded focus:ring-amber-500">
                                    <label for="remember" class="ml-2 text-sm text-amber-800 font-semibold">
                                        Zapamiętaj mnie
                                    </label>
                                </div>

                                {{-- Action buttons --}}
                                <div class="flex flex-col sm:flex-row gap-3 pt-4">
                                    <button type="submit"
                                        class="flex-1 bg-gradient-to-r from-amber-600 to-amber-700 hover:from-amber-700 hover:to-amber-800 text-white font-bold py-3 px-4 rounded-lg transition-all duration-200 transform hover:scale-105 shadow-lg medieval-font">
                                        ⚔️ Wejdź do gry
                                    </button>
                                    <button type="button" wire:click="closeModal"
                                        class="flex-1 bg-gradient-to-r from-slate-500 to-slate-600 hover:from-slate-600 hover:to-slate-700 text-white font-bold py-3 px-4 rounded-lg transition-all duration-200 transform hover:scale-105 shadow-lg">
                                        Anuluj
                                    </button>
                                </div>

                                {{-- Register link --}}
                                <div class="text-center pt-4 border-t-2 border-amber-600">
                                    <p class="text-amber-800">
                                        Nie masz konta?
                                        <a href="{{ route('register') }}"
                                            class="font-bold text-amber-900 hover:text-amber-700 underline transition-colors">
                                            Załóż konto
                                        </a>
                                    </p>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endteleport
    @endif
</div>
