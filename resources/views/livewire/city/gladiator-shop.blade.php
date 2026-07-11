<div class="min-h-screen bg-[url('/public/img/backgrounds/city.jpg')] bg-cover bg-center bg-fixed">
    <div class="min-h-screen bg-stone-900/80 backdrop-blur-sm py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <div class="flex justify-between items-center mb-8 bg-stone-800/90 p-4 rounded-xl border-2 border-amber-700/50 shadow-2xl">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 bg-gradient-to-br from-amber-600 to-amber-900 rounded-full border-2 border-amber-400 flex items-center justify-center shadow-lg">
                        <span class="text-3xl">🏛️</span>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-amber-500 medieval-font drop-shadow-md">Sklep Gladiatora</h1>
                        <p class="text-amber-200/80 font-medium">Wymień żetony na unikalne nagrody</p>
                    </div>
                </div>

                <div class="flex items-center gap-6">
                    <div class="bg-stone-900/80 px-4 py-2 rounded-lg border border-amber-700/30">
                        <div class="text-xs text-amber-400/80 uppercase font-bold tracking-wider mb-1">Twoje Żetony</div>
                        <div class="text-2xl font-bold text-amber-300 flex items-center gap-2">
                            {{ $character->arena_tokens }}
                            <span class="text-xl">🎫</span>
                        </div>
                    </div>
                </div>
                <button wire:click="backToArena" @click="travelingTo = 'Arena'" 
                    class="relative rounded-lg px-6 py-2 shadow-lg overflow-hidden group">
                    <img src="{{ asset('img/avatars/plate.png') }}" class="absolute inset-0 w-full h-full object-cover rounded-lg">
                    <div class="absolute inset-0 bg-amber-900/40 group-hover:bg-amber-800/40 transition-colors rounded-lg"></div>
                    <span class="relative text-amber-100 font-bold medieval-font drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)]">⬅️ Powrót do Areny</span>
                </button>
            </div>

            <!-- Shop Items Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach($merchantItems as $item)
                    <div class="bg-stone-800/90 border border-amber-900/50 rounded-xl p-5 shadow-xl flex flex-col relative overflow-hidden group hover:border-amber-500/50 transition-colors">
                        <!-- Decorative corners -->
                        <div class="absolute top-0 left-0 w-4 h-4 border-t-2 border-l-2 border-amber-700/50 rounded-tl"></div>
                        <div class="absolute top-0 right-0 w-4 h-4 border-t-2 border-r-2 border-amber-700/50 rounded-tr"></div>
                        
                        <div class="flex items-center gap-3 mb-4">
                            <img src="{{ asset('img/items/' . $item->template->type . '.png') }}" class="w-12 h-12 rounded bg-amber-900/50 p-1 border border-amber-700">
                            <div>
                                <h4 class="font-bold text-lg text-amber-100">{{ $item->template->name }}</h4>
                                <div class="text-xs text-amber-500 uppercase">{{ $item->template->type }}</div>
                            </div>
                        </div>

                        <div class="flex-grow p-4 bg-gray-900/40 mb-4 rounded border border-amber-900/30">
                            <p class="text-sm text-gray-400 mb-2">Opis: {{ $item->template->description }}</p>
                            @if($item->template->health_bonus > 0)<div class="text-xs text-green-400">+{{ $item->template->health_bonus }} HP</div>@endif
                            @if($item->template->attack_min > 0)<div class="text-xs text-red-400">Obrażenia: {{ $item->template->attack_min }} - {{ $item->template->attack_max }}</div>@endif
                            @if($item->template->defense > 0)<div class="text-xs text-blue-400">Obrona: {{ $item->template->defense }}</div>@endif
                            <div class="mt-2 text-sm">
                                <span class="text-yellow-400 font-bold">Wymagany poziom: {{ $item->required_level }}</span>
                            </div>
                        </div>

                        <div class="mt-auto pt-4 border-t border-amber-800/50">
                            <button wire:click="buyItem({{ $item->id }})" 
                                @if($character->arena_tokens < $item->price) disabled @endif
                                class="w-full relative overflow-hidden rounded py-2 font-bold transition-all
                                {{ $character->arena_tokens >= $item->price 
                                    ? 'bg-gradient-to-r from-amber-700 to-amber-600 text-amber-100 hover:from-amber-600 hover:to-amber-500 shadow-[0_0_15px_rgba(217,119,6,0.4)]' 
                                    : 'bg-stone-700 text-stone-400 cursor-not-allowed opacity-70' }}">
                                <div class="flex items-center justify-center gap-2">
                                    <span>Kup za {{ $item->price }}</span>
                                    <span>🎫</span>
                                </div>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
            
            @if($merchantItems->isEmpty())
                <div class="bg-stone-800/80 border border-stone-700 rounded-xl p-8 text-center text-stone-400">
                    Niestety, gladiator nie ma obecnie nic na sprzedaż. Wróć później!
                </div>
            @endif
        </div>
    </div>
</div>
