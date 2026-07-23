<div class="min-h-screen relative overflow-hidden">
    <div class="absolute inset-0 bg-cover bg-center bg-no-repeat" style="background-image: url('{{ asset('img/maps/shadow-mountains.png') }}');"></div>
    <div class="absolute inset-0 bg-black/50"></div>
    <div class="relative min-h-screen py-8 px-4 sm:px-6 lg:px-8">
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
            <div class="bg-gray-900/60 rounded-xl border border-gray-700/50 p-6 flex flex-col mt-4">
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8 gap-4">
                    @foreach($merchantItems as $item)
                        <div class="relative" x-data="{ showInfo: false, timeout: null }" 
                             :class="{ 'z-50': showInfo, 'z-10': !showInfo }"
                             @mouseenter="clearTimeout(timeout); showInfo = true"
                             @mouseleave="timeout = setTimeout(() => showInfo = false, 300)"
                             @click="clearTimeout(timeout); showInfo = !showInfo">
                             
                            <div class="bg-black/80 border border-gray-600 hover:border-amber-400 rounded-lg p-3 flex flex-col items-center text-center cursor-pointer transition-all h-full">
                                @if($item->template->icon)
                                    <div class="w-12 h-12 mb-2">
                                        <img src="{{ route('assets.items', ['filename' => $item->template->icon]) }}" class="w-full h-full object-contain" alt="{{ $item->template->name }}">
                                    </div>
                                @endif
                                <h4 class="font-bold text-sm text-blue-300 line-clamp-2 leading-tight">{{ $item->template->name }}</h4>
                                <div class="mt-auto pt-2 text-yellow-400 text-sm font-bold flex items-center justify-center gap-1">
                                    <span>{{ $item->price }}</span>
                                    <span class="text-xs">🎫</span>
                                </div>
                            </div>

                            <!-- Infobox Sklepu -->
                            <div x-show="showInfo" x-transition.opacity 
                                 class="absolute z-[100] top-full left-1/2 -translate-x-1/2 mt-2 w-auto pointer-events-auto">
                                <x-item-tooltip :item="$item" :equippedItem="$equipped[$item->template->slot ?? ''] ?? null">
                                    <x-slot:actions>
                                        <button wire:click.stop="buyItem('{{ $item->id }}')" wire:loading.attr="disabled" 
                                            {{ $character->arena_tokens < $item->price ? 'disabled' : '' }}
                                            class="w-full bg-amber-700 hover:bg-amber-600 text-white font-bold py-2 rounded shadow transition flex items-center justify-center gap-2 {{ $character->arena_tokens < $item->price ? 'opacity-50 cursor-not-allowed' : '' }}">
                                            <span>Kup za {{ $item->price }} 🎫</span>
                                        </button>
                                    </x-slot:actions>
                                </x-item-tooltip>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            
            @if($merchantItems->isEmpty())
                <div class="bg-stone-800/80 border border-stone-700 rounded-xl p-8 text-center text-stone-400">
                    Niestety, gladiator nie ma obecnie nic na sprzedaż. Wróć później!
                </div>
            @endif
        </div>
    </div>
</div>
