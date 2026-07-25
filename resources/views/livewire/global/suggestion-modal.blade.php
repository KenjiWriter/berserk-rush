<div x-data="{ open: @entangle('isOpen') }"
     x-show="open"
     x-on:keydown.escape.window="$wire.closeModal()"
     style="display: none; font-family: 'Cinzel', serif;"
     class="fixed inset-0 z-[9999] flex items-center justify-center p-4">

    {{-- Backdrop overlay --}}
    <div class="absolute inset-0 bg-stone-950/85 backdrop-blur-md transition-opacity duration-300"
         @click="$wire.closeModal()"></div>

    {{-- Modal Container --}}
    <div class="relative bg-gradient-to-b from-stone-900 via-stone-950 to-black border-2 border-amber-600/80 rounded-2xl shadow-[0_0_40px_rgba(245,158,11,0.35)] p-6 sm:p-8 max-w-lg w-full text-stone-100 transform transition-all duration-300 border-t-amber-400 overflow-hidden"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95 translate-y-4"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 scale-95 translate-y-4">

        {{-- Gothic Decorative Corner Trims --}}
        <div class="absolute top-0 right-0 w-24 h-24 bg-[radial-gradient(ellipse_at_top_right,_var(--tw-gradient-stops))] from-amber-500/20 via-transparent to-transparent pointer-events-none"></div>
        <div class="absolute bottom-0 left-0 w-24 h-24 bg-[radial-gradient(ellipse_at_bottom_left,_var(--tw-gradient-stops))] from-amber-500/10 via-transparent to-transparent pointer-events-none"></div>

        {{-- Close Button --}}
        <button @click="$wire.closeModal()"
                class="absolute top-4 right-4 w-8 h-8 rounded-full bg-stone-900 border border-amber-700/60 text-amber-400 hover:text-amber-200 hover:border-amber-400 flex items-center justify-center transition-all duration-200 hover:scale-110 z-10 cursor-pointer">
            <i class="fa-solid fa-xmark"></i>
        </button>

        {{-- Modal Header --}}
        <div class="flex items-center gap-3 mb-6 border-b border-amber-900/60 pb-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-amber-600 to-amber-900 border border-amber-400 shadow-[0_0_15px_rgba(245,158,11,0.5)] flex items-center justify-center text-stone-950 text-2xl shrink-0">
                <i class="fa-solid fa-lightbulb"></i>
            </div>
            <div>
                <h2 class="text-xl font-black text-amber-300 tracking-wide uppercase drop-shadow">Zgłoś Sugestię / Uwagę</h2>
                <p class="text-xs text-amber-200/70 font-sans mt-0.5">Podziel się z nami swoim pomysłem lub zgłoś uwagę do gry.</p>
            </div>
        </div>

        {{-- Form Content --}}
        <form wire:submit.prevent="submit" class="space-y-5 font-sans">
            {{-- Category Selection --}}
            <div>
                <label class="block text-xs font-bold text-amber-400 uppercase tracking-wider mb-2 font-serif">Kategoria zgłoszenia</label>
                <div class="grid grid-cols-3 gap-2">
                    <button type="button"
                            wire:click="$set('category', 'sugestia')"
                            class="py-2.5 px-3 rounded-lg border text-xs font-bold transition-all flex flex-col sm:flex-row items-center justify-center gap-1.5 cursor-pointer {{ $category === 'sugestia' ? 'bg-amber-600/30 border-amber-400 text-amber-200 shadow-[0_0_10px_rgba(245,158,11,0.3)]' : 'bg-stone-900/80 border-stone-700 text-stone-400 hover:border-stone-500' }}">
                        <span>💡</span>
                        <span>Sugestia</span>
                    </button>

                    <button type="button"
                            wire:click="$set('category', 'błąd')"
                            class="py-2.5 px-3 rounded-lg border text-xs font-bold transition-all flex flex-col sm:flex-row items-center justify-center gap-1.5 cursor-pointer {{ $category === 'błąd' ? 'bg-red-950/50 border-red-500 text-red-200 shadow-[0_0_10px_rgba(239,68,68,0.3)]' : 'bg-stone-900/80 border-stone-700 text-stone-400 hover:border-stone-500' }}">
                        <span>🐛</span>
                        <span>Błąd w grze</span>
                    </button>

                    <button type="button"
                            wire:click="$set('category', 'inne')"
                            class="py-2.5 px-3 rounded-lg border text-xs font-bold transition-all flex flex-col sm:flex-row items-center justify-center gap-1.5 cursor-pointer {{ $category === 'inne' ? 'bg-indigo-950/50 border-indigo-500 text-indigo-200 shadow-[0_0_10px_rgba(99,102,241,0.3)]' : 'bg-stone-900/80 border-stone-700 text-stone-400 hover:border-stone-500' }}">
                        <span>💬</span>
                        <span>Inne uwagi</span>
                    </button>
                </div>
            </div>

            {{-- Textarea Content --}}
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label for="suggestion-content" class="block text-xs font-bold text-amber-400 uppercase tracking-wider font-serif">Treść zgłoszenia</label>
                    <span class="text-[11px] text-stone-400 font-mono">{{ strlen($content) }}/2000</span>
                </div>

                <textarea id="suggestion-content"
                          wire:model.live="content"
                          rows="5"
                          placeholder="Opisz dokładnie swoją sugestię lub zauważony błąd. Każda uwaga jest dla nas cenna!"
                          class="w-full bg-stone-900/90 border-2 border-stone-700 focus:border-amber-500 rounded-xl p-3.5 text-sm text-stone-100 placeholder-stone-500 focus:outline-none focus:ring-2 focus:ring-amber-500/20 transition-all custom-scrollbar resize-none"></textarea>

                @error('content')
                    <p class="text-xs text-red-400 font-semibold mt-1.5 flex items-center gap-1">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>

            {{-- Action Buttons --}}
            <div class="flex items-center justify-end gap-3 pt-3 border-t border-stone-800 font-serif">
                <button type="button"
                        wire:click="closeModal"
                        class="px-5 py-2.5 rounded-xl border border-stone-600 text-stone-300 hover:bg-stone-800 hover:text-stone-100 transition-colors font-bold uppercase tracking-wider text-xs cursor-pointer">
                    Anuluj
                </button>

                <button type="submit"
                        wire:loading.attr="disabled"
                        class="px-6 py-2.5 bg-gradient-to-r from-amber-600 via-amber-500 to-yellow-600 hover:from-amber-500 hover:to-yellow-500 text-stone-950 font-black rounded-xl transition-all shadow-[0_0_15px_rgba(245,158,11,0.4)] hover:shadow-[0_0_25px_rgba(245,158,11,0.6)] uppercase tracking-wider text-xs flex items-center gap-2 cursor-pointer disabled:opacity-50">
                    <span wire:loading.remove wire:target="submit"><i class="fa-solid fa-paper-plane mr-1"></i> Wyślij Uwaga</span>
                    <span wire:loading wire:target="submit" class="flex items-center gap-2">
                        <i class="fa-solid fa-spinner animate-spin"></i> Wysyłanie...
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>
