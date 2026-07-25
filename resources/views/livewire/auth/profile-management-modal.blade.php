<div>
    {{-- Manage Profile Button --}}
    <button wire:click="openModal"
        class="w-full sm:w-auto bg-gradient-to-r from-amber-600 via-amber-700 to-amber-800 hover:from-amber-500 hover:to-amber-700 text-amber-100 font-bold py-2 px-4 rounded-lg transition-all duration-200 transform hover:scale-105 shadow-lg text-center border-2 border-amber-500 medieval-font text-sm flex items-center justify-center gap-2">
        <i class="fa-solid fa-user-gear text-amber-300"></i> Zarządzaj profilem
    </button>

    {{-- Modal --}}
    @if ($showModal)
        @teleport('body')
            <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                {{-- Background overlay --}}
                <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm transition-opacity" wire:click="closeModal"></div>

                    {{-- Centering wrapper --}}
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                    {{-- Modal panel --}}
                    <div
                        class="inline-block align-bottom bg-gradient-to-br from-amber-50 via-amber-100 to-amber-50 rounded-lg border-4 border-amber-700 text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full relative z-10">
                        
                        {{-- Decorative corners --}}
                        <div class="absolute top-0 left-0 w-6 h-6 bg-amber-800 transform rotate-45 -translate-x-3 -translate-y-3 z-20"></div>
                        <div class="absolute top-0 right-0 w-6 h-6 bg-amber-800 transform rotate-45 translate-x-3 -translate-y-3 z-20"></div>
                        <div class="absolute bottom-0 left-0 w-6 h-6 bg-amber-800 transform rotate-45 -translate-x-3 translate-y-3 z-20"></div>
                        <div class="absolute bottom-0 right-0 w-6 h-6 bg-amber-800 transform rotate-45 translate-x-3 translate-y-3 z-20"></div>

                        <div class="relative px-6 pt-5 pb-6 z-10">
                            {{-- Header --}}
                            <div class="flex items-center justify-between mb-5 border-b-2 border-amber-700/40 pb-3">
                                <h3 class="text-2xl font-bold text-amber-950 medieval-font flex items-center gap-2" id="modal-title">
                                    <i class="fa-solid fa-user-gear text-amber-700"></i> Zarządzanie Profilem
                                </h3>
                                <button wire:click="closeModal" class="text-amber-800 hover:text-amber-950 transition-colors">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>

                            {{-- Navigation Tabs --}}
                            <div class="flex border-b border-amber-300 mb-5 gap-2">
                                <button wire:click="setTab('password')"
                                    class="py-2 px-4 font-bold text-sm rounded-t-lg transition-colors medieval-font flex items-center gap-2 {{ $activeTab === 'password' ? 'bg-amber-700 text-amber-100 shadow' : 'bg-amber-200/60 text-amber-900 hover:bg-amber-300/80' }}">
                                    <i class="fa-solid fa-key"></i> Zmień Hasło
                                </button>
                                <button wire:click="setTab('delete')"
                                    class="py-2 px-4 font-bold text-sm rounded-t-lg transition-colors medieval-font flex items-center gap-2 {{ $activeTab === 'delete' ? 'bg-red-800 text-red-100 shadow' : 'bg-red-200/60 text-red-900 hover:bg-red-300/80' }}">
                                    <i class="fa-solid fa-user-slash"></i> Usuń Konto
                                </button>
                            </div>

                            {{-- Flash Messages --}}
                            @if ($successMessage)
                                <div class="mb-4 p-3 bg-emerald-100 border border-emerald-500 text-emerald-900 rounded text-sm font-semibold flex items-center gap-2">
                                    <i class="fa-solid fa-circle-check text-emerald-600 text-lg"></i>
                                    {{ $successMessage }}
                                </div>
                            @endif

                            @if ($errorMessage)
                                <div class="mb-4 p-3 bg-red-100 border border-red-500 text-red-900 rounded text-sm font-semibold flex items-center gap-2">
                                    <i class="fa-solid fa-circle-exclamation text-red-600 text-lg"></i>
                                    {{ $errorMessage }}
                                </div>
                            @endif

                            {{-- Tab 1: Password Change --}}
                            @if ($activeTab === 'password')
                                <form wire:submit.prevent="updatePassword" class="space-y-4">
                                    @if (!empty(Auth::user()->password))
                                        <div>
                                            <label class="block text-xs font-bold uppercase tracking-wider text-amber-900 mb-1">
                                                Obecne Hasło
                                            </label>
                                            <input type="password" wire:model.defer="current_password"
                                                class="w-full px-3 py-2 bg-amber-50 border border-amber-400 rounded focus:ring-2 focus:ring-amber-600 focus:border-amber-600 text-amber-950 font-medium"
                                                placeholder="••••••••">
                                            @error('current_password')
                                                <p class="mt-1 text-xs text-red-600 font-bold">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    @endif

                                    <div>
                                        <label class="block text-xs font-bold uppercase tracking-wider text-amber-900 mb-1">
                                            Nowe Hasło
                                        </label>
                                        <input type="password" wire:model.defer="password"
                                            class="w-full px-3 py-2 bg-amber-50 border border-amber-400 rounded focus:ring-2 focus:ring-amber-600 focus:border-amber-600 text-amber-950 font-medium"
                                            placeholder="••••••••">
                                        @error('password')
                                            <p class="mt-1 text-xs text-red-600 font-bold">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold uppercase tracking-wider text-amber-900 mb-1">
                                            Powtórz Nowe Hasło
                                        </label>
                                        <input type="password" wire:model.defer="password_confirmation"
                                            class="w-full px-3 py-2 bg-amber-50 border border-amber-400 rounded focus:ring-2 focus:ring-amber-600 focus:border-amber-600 text-amber-950 font-medium"
                                            placeholder="••••••••">
                                    </div>

                                    <div class="pt-3 flex justify-end gap-3">
                                        <button type="button" wire:click="closeModal"
                                            class="px-4 py-2 bg-slate-600 hover:bg-slate-700 text-white rounded font-bold text-sm transition-all">
                                            Anuluj
                                        </button>
                                        <button type="submit"
                                            class="px-5 py-2 bg-gradient-to-r from-amber-600 to-amber-700 hover:from-amber-700 hover:to-amber-800 text-white font-bold rounded shadow border border-amber-500 medieval-font text-sm flex items-center gap-2">
                                            <i class="fa-solid fa-floppy-disk"></i> Zapisz Hasło
                                        </button>
                                    </div>
                                </form>
                            @endif

                            {{-- Tab 2: Delete Account --}}
                            @if ($activeTab === 'delete')
                                <form wire:submit.prevent="deleteAccount" class="space-y-4">
                                    <div class="p-4 bg-red-100/80 border-2 border-red-400 rounded-lg text-red-950 text-xs leading-relaxed space-y-2">
                                        <p class="font-bold text-sm text-red-900 flex items-center gap-1.5">
                                            <i class="fa-solid fa-triangle-exclamation text-red-600 text-base"></i> Ostrzeżenie!
                                        </p>
                                        <p>
                                            Usunięcie konta jest nieodwracalne. Wszystkie Twoje postacie, postęp w grze, ekwipunek i diamenty zostaną bezpowrotnie skasowane z serwera.
                                        </p>
                                    </div>

                                    @if (!empty(Auth::user()->password))
                                        <div>
                                            <label class="block text-xs font-bold uppercase tracking-wider text-red-900 mb-1">
                                                Wprowadź hasło, aby potwierdzić usunięcie konta
                                            </label>
                                            <input type="password" wire:model.defer="delete_password"
                                                class="w-full px-3 py-2 bg-amber-50 border border-red-400 rounded focus:ring-2 focus:ring-red-600 text-red-950 font-medium"
                                                placeholder="••••••••">
                                            @error('delete_password')
                                                <p class="mt-1 text-xs text-red-600 font-bold">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    @endif

                                    <div class="pt-3 flex justify-end gap-3">
                                        <button type="button" wire:click="closeModal"
                                            class="px-4 py-2 bg-slate-600 hover:bg-slate-700 text-white rounded font-bold text-sm transition-all">
                                            Anuluj
                                        </button>
                                        <button type="submit"
                                            class="px-5 py-2 bg-gradient-to-r from-red-700 to-red-800 hover:from-red-800 hover:to-red-900 text-white font-bold rounded shadow border border-red-600 medieval-font text-sm flex items-center gap-2">
                                            <i class="fa-solid fa-trash-can"></i> Trwale Usuń Konto
                                        </button>
                                    </div>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endteleport
    @endif
</div>
