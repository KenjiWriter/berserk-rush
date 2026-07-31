<div x-data="{ open: @entangle('isOpen'), copied: false }"
     x-show="open"
     x-on:keydown.escape.window="$wire.closeModal()"
     style="display: none; font-family: 'Cinzel', serif;"
     class="fixed inset-0 z-[9999] flex items-center justify-center p-4">

    {{-- Backdrop overlay --}}
    <div class="absolute inset-0 bg-stone-950/85 backdrop-blur-md transition-opacity duration-300"
         @click="$wire.closeModal()"></div>

    {{-- Modal Container --}}
    <div class="relative bg-gradient-to-b from-stone-900 via-stone-950 to-black border-2 border-indigo-500/80 rounded-2xl shadow-[0_0_40px_rgba(88,101,242,0.35)] p-6 sm:p-8 max-w-md w-full text-stone-100 transform transition-all duration-300 overflow-hidden"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95 translate-y-4"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 scale-95 translate-y-4">

        {{-- Decorative corner glow --}}
        <div class="absolute top-0 right-0 w-24 h-24 bg-[radial-gradient(ellipse_at_top_right,_var(--tw-gradient-stops))] from-indigo-500/20 via-transparent to-transparent pointer-events-none"></div>

        {{-- Close Button --}}
        <button @click="$wire.closeModal()"
                class="absolute top-4 right-4 w-8 h-8 rounded-full bg-stone-900 border border-indigo-700/60 text-indigo-300 hover:text-indigo-100 hover:border-indigo-400 flex items-center justify-center transition-all duration-200 hover:scale-110 z-10 cursor-pointer">
            <i class="fa-solid fa-xmark"></i>
        </button>

        {{-- Modal Header --}}
        <div class="flex items-center gap-3 mb-6 border-b border-indigo-900/60 pb-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-800 border border-indigo-300 shadow-[0_0_15px_rgba(88,101,242,0.5)] flex items-center justify-center text-white text-2xl shrink-0">
                <i class="fa-brands fa-discord"></i>
            </div>
            <div>
                <h2 class="text-xl font-black text-indigo-300 tracking-wide uppercase drop-shadow">Połącz z Discordem</h2>
                <p class="text-xs text-indigo-200/70 font-sans mt-0.5">Twój jednorazowy kod do wpisania na Discordzie</p>
            </div>
        </div>

        {{-- Code display --}}
        <div class="font-sans space-y-5">
            <div class="bg-stone-950 border-2 border-dashed border-indigo-500/60 rounded-xl py-5 px-4 flex items-center justify-between gap-3">
                <span class="text-3xl sm:text-4xl font-mono font-black tracking-[0.3em] text-indigo-200 select-all">{{ $code }}</span>

                <button type="button"
                        @click="navigator.clipboard.writeText('{{ $code }}'); copied = true; setTimeout(() => copied = false, 2000)"
                        class="shrink-0 px-3 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold uppercase tracking-wide transition-colors cursor-pointer">
                    <span x-show="!copied"><i class="fa-regular fa-copy mr-1"></i> Kopiuj</span>
                    <span x-show="copied" x-cloak><i class="fa-solid fa-check mr-1"></i> Skopiowano!</span>
                </button>
            </div>

            <div class="text-sm text-stone-300 space-y-2 leading-relaxed">
                <p>Na kanale <span class="font-bold text-indigo-300">#in-game-chat</span> na Twoim serwerze Discord wpisz:</p>
                <p class="bg-stone-900/80 border border-stone-700 rounded-lg px-3 py-2 font-mono text-indigo-200">!link {{ $code }}</p>
                <p class="text-xs text-stone-400">Kod jest ważny przez <span class="font-bold">10 minut</span> i można go użyć tylko raz. Jeśli wygaśnie, wpisz <span class="font-mono">/discord</span> na czacie ponownie, żeby dostać nowy.</p>
            </div>

            <button @click="$wire.closeModal()"
                    class="w-full py-2.5 rounded-lg bg-stone-800 hover:bg-stone-700 border border-stone-600 text-stone-200 text-sm font-bold uppercase tracking-wide transition-colors cursor-pointer">
                Zamknij
            </button>
        </div>
    </div>
</div>
