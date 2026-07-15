<div class="fixed inset-0 z-[100] flex items-center justify-center bg-black/80 backdrop-blur-sm">
    <div class="w-full max-w-md p-1 bg-gradient-to-b from-amber-700 to-amber-900 rounded-lg shadow-2xl">
        <div class="bg-slate-900 border border-amber-900/50 p-6 rounded text-amber-100">
            
            <div class="text-center mb-6">
                <h2 class="text-2xl font-bold text-amber-500 font-serif tracking-wider mb-2">WITAJ BOHATERZE</h2>
                <div class="h-1 w-32 mx-auto bg-gradient-to-r from-transparent via-amber-600 to-transparent"></div>
                <p class="mt-4 text-sm text-slate-400">
                    Twoje konto zostało utworzone pomyślnie. Wybierz nazwę, pod którą inni gracze będą Cię znali w świecie gry.
                </p>
            </div>

            <form wire:submit.prevent="save" class="space-y-4">
                
                <div>
                    <label class="block text-sm font-medium text-amber-500/80 mb-1 font-serif uppercase tracking-wider">
                        E-mail
                    </label>
                    <div class="relative">
                        <input type="text" disabled value="{{ Auth::user()->email ?? '' }}" 
                               class="w-full bg-slate-950 border border-slate-700 text-slate-500 rounded p-2 focus:outline-none cursor-not-allowed">
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                            <svg class="h-5 w-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-xs text-slate-500 mt-1">E-mail zweryfikowany przez dostawcę</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-amber-500/80 mb-1 font-serif uppercase tracking-wider">
                        Nazwa bohatera
                    </label>
                    <input type="text" wire:model="name" required
                           class="w-full bg-slate-800 border @error('name') border-red-500 @else border-amber-900/50 @enderror text-amber-100 rounded p-2 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 outline-none transition-colors">
                    @error('name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="pt-4">
                    <button type="submit" 
                            class="w-full relative overflow-hidden group bg-gradient-to-b from-amber-600 to-amber-800 text-amber-100 font-bold py-3 px-4 rounded shadow-[0_0_15px_rgba(217,119,6,0.3)] hover:shadow-[0_0_25px_rgba(217,119,6,0.5)] transition-all duration-300">
                        <span class="relative z-10 font-serif tracking-widest uppercase">Rozpocznij Grę</span>
                        <div class="absolute inset-0 h-full w-0 bg-white/20 group-hover:w-full transition-all duration-300 ease-out"></div>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
