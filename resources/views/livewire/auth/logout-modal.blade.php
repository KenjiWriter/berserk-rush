<div>
    {{-- Logout Button --}}
    <button wire:click="openModal"
        class="w-full bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-bold py-2 px-4 rounded-lg transition-all duration-200 transform hover:scale-105 shadow-lg text-center border-2 border-red-500 medieval-font text-sm">
        🚪 Wyloguj się
    </button>

    {{-- Modal --}}
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
                        class="inline-block align-bottom bg-gradient-to-br from-amber-50 to-amber-100 rounded-lg border-4 border-amber-700 text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full relative z-10">
                        {{-- Decorative corners --}}
                        <div
                            class="absolute top-0 left-0 w-6 h-6 bg-amber-800 transform rotate-45 -translate-x-3 -translate-y-3 z-20">
                        </div>
                        <div
                            class="absolute top-0 right-0 w-6 h-6 bg-amber-800 transform rotate-45 translate-x-3 -translate-y-3 z-20">
                        </div>
                        <div
                            class="absolute bottom-0 left-0 w-6 h-6 bg-amber-800 transform rotate-45 -translate-x-3 translate-y-3 z-20">
                        </div>
                        <div
                            class="absolute bottom-0 right-0 w-6 h-6 bg-amber-800 transform rotate-45 translate-x-3 translate-y-3 z-20">
                        </div>

                        <div class="relative px-6 pt-5 pb-4 z-10">
                            {{-- Header --}}
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-xl font-bold text-amber-900 medieval-font" id="modal-title">
                                    🚪 Wylogowanie
                                </h3>
                                <button wire:click="closeModal"
                                    class="text-amber-700 hover:text-amber-900 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>

                            {{-- Content --}}
                            <div class="mb-6">
                                <p class="text-amber-800 text-center">
                                    Czy na pewno chcesz opuścić świat Berserk Rush?
                                </p>
                            </div>

                            {{-- Action buttons --}}
                            <div class="flex flex-col sm:flex-row gap-3">
                                <button wire:click="logout"
                                    class="flex-1 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-bold py-3 px-4 rounded-lg transition-all duration-200 transform hover:scale-105 shadow-lg medieval-font">
                                    🚪 Tak, wyloguj
                                </button>
                                <button wire:click="closeModal"
                                    class="flex-1 bg-gradient-to-r from-slate-500 to-slate-600 hover:from-slate-600 hover:to-slate-700 text-white font-bold py-3 px-4 rounded-lg transition-all duration-200 transform hover:scale-105 shadow-lg">
                                    Anuluj
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endteleport
    @endif
</div>
