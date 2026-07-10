<div class="min-h-screen bg-gradient-to-b from-slate-900 via-stone-900 to-slate-900 text-amber-100 relative overflow-hidden">
    {{-- Dark overlay for readability --}}
    <div class="absolute inset-0 bg-gradient-to-b from-slate-900/80 via-slate-800/80 to-slate-900/80 z-0"></div>

    <div class="relative container mx-auto px-4 py-8 min-h-screen z-10 max-w-7xl">
        {{-- Header --}}
        <div class="flex items-center justify-between mb-8">
            <div class="flex items-center space-x-4">
                <div class="w-16 h-16 bg-gradient-to-br from-amber-600 to-amber-800 rounded-full border-2 border-amber-400 flex items-center justify-center text-3xl shadow-lg shadow-amber-900/50">
                    ⚖️
                </div>
                <div>
                    <h1 class="text-4xl font-bold text-amber-500 medieval-font drop-shadow-md">Targowisko</h1>
                    <p class="text-amber-200/70">Kupuj i sprzedawaj przedmioty z innymi graczami</p>
                </div>
            </div>
            
            <div class="flex items-center space-x-6">
                <div class="flex items-center space-x-4 bg-slate-800/80 p-3 rounded-lg border border-slate-700">
                    <div class="text-right">
                        <div class="text-xs text-slate-400 uppercase tracking-wider">Twoje Złoto</div>
                        <div class="text-xl font-bold text-yellow-400 flex items-center justify-end">
                            {{ number_format($character->gold) }} <span class="text-sm ml-1">💰</span>
                        </div>
                    </div>
                    <div class="w-px h-8 bg-slate-600"></div>
                    <div class="text-right">
                        <div class="text-xs text-slate-400 uppercase tracking-wider">Twoje Klejnoty</div>
                        <div class="text-xl font-bold text-purple-400 flex items-center justify-end">
                            {{ number_format($character->gems) }} <span class="text-sm ml-1">💎</span>
                        </div>
                    </div>
                </div>

                <button wire:click="backToCity"
                    class="bg-gradient-to-r from-slate-600 to-slate-700 hover:from-slate-700 hover:to-slate-800 text-amber-200 font-bold py-3 px-6 rounded-lg transition-all duration-200 shadow-lg border border-slate-500 flex items-center">
                    🏠 Powrót do miasta
                </button>
            </div>
        </div>

        {{-- Tabs --}}
        <div class="flex space-x-2 border-b border-amber-900/50 mb-6">
            <button wire:click="switchTab('buy')" 
                class="px-6 py-3 font-semibold rounded-t-lg transition-colors {{ $activeTab === 'buy' ? 'bg-amber-900/80 text-amber-300 border-t border-l border-r border-amber-700' : 'bg-slate-800/50 text-slate-400 hover:text-amber-200 hover:bg-slate-800' }}">
                🛒 Kupuj
            </button>
            <button wire:click="switchTab('my_listings')" 
                class="px-6 py-3 font-semibold rounded-t-lg transition-colors {{ $activeTab === 'my_listings' ? 'bg-amber-900/80 text-amber-300 border-t border-l border-r border-amber-700' : 'bg-slate-800/50 text-slate-400 hover:text-amber-200 hover:bg-slate-800' }}">
                📦 Moje oferty
            </button>
        </div>

        @if($activeTab === 'buy')
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                {{-- Filters Sidebar --}}
                <div class="lg:col-span-1 space-y-6">
                    <div class="bg-slate-800/60 border border-slate-700 p-5 rounded-lg shadow-xl backdrop-blur-sm">
                        <h3 class="text-lg font-bold text-amber-400 mb-4 border-b border-slate-600 pb-2">Filtry</h3>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm text-slate-400 mb-1">Szukaj nazwy</label>
                                <input type="text" wire:model.live.debounce.300ms="search" class="w-full bg-slate-900 border border-slate-600 rounded p-2 text-amber-100 placeholder-slate-500 focus:border-amber-500 focus:ring-1 focus:ring-amber-500" placeholder="Wpisz nazwę...">
                            </div>

                            <div>
                                <label class="block text-sm text-slate-400 mb-1">Rzadkość</label>
                                <select wire:model.live="rarity" class="w-full bg-slate-900 border border-slate-600 rounded p-2 text-amber-100">
                                    <option value="">Wszystkie</option>
                                    <option value="common">Zwykły (Common)</option>
                                    <option value="uncommon">Niecodzienny (Uncommon)</option>
                                    <option value="rare">Rzadki (Rare)</option>
                                    <option value="epic">Epicki (Epic)</option>
                                    <option value="legendary">Legendarny (Legendary)</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm text-slate-400 mb-1">Typ (Slot)</label>
                                <select wire:model.live="slot" class="w-full bg-slate-900 border border-slate-600 rounded p-2 text-amber-100">
                                    <option value="">Wszystkie</option>
                                    <option value="weapon">Broń</option>
                                    <option value="head">Głowa</option>
                                    <option value="chest">Zbroja</option>
                                    <option value="legs">Nogi</option>
                                    <option value="boots">Buty</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm text-slate-400 mb-1">Waluta</label>
                                <select wire:model.live="currency" class="w-full bg-slate-900 border border-slate-600 rounded p-2 text-amber-100">
                                    <option value="">Obojętnie</option>
                                    <option value="gold">Tylko za złoto</option>
                                    <option value="gems">Tylko za klejnoty</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm text-slate-400 mb-1">Sortowanie</label>
                                <div class="flex space-x-2">
                                    <select wire:model.live="sortBy" class="w-2/3 bg-slate-900 border border-slate-600 rounded p-2 text-amber-100 text-sm">
                                        <option value="created_at">Najnowsze</option>
                                        <option value="price">Cena</option>
                                        <option value="expires_at">Wygasające</option>
                                    </select>
                                    <select wire:model.live="sortDir" class="w-1/3 bg-slate-900 border border-slate-600 rounded p-2 text-amber-100 text-sm">
                                        <option value="desc">Malejąco</option>
                                        <option value="asc">Rosnąco</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Listings Grid --}}
                <div class="lg:col-span-3">
                    @if(count($listings) > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                            @foreach($listings as $listing)
                                <div class="bg-slate-800/80 border border-slate-600 rounded-lg p-4 flex flex-col justify-between shadow-lg hover:shadow-xl hover:border-amber-600/50 transition-all">
                                    <div class="flex items-start space-x-3 mb-3">
                                        {{-- Item Icon placeholder based on slot/rarity --}}
                                        <div class="w-12 h-12 rounded border-2 border-slate-500 flex items-center justify-center shrink-0 bg-slate-900 text-2xl
                                            @if($listing->item->rarity === 'common') border-slate-400 shadow-[0_0_8px_rgba(148,163,184,0.3)]
                                            @elseif($listing->item->rarity === 'uncommon') border-green-400 shadow-[0_0_8px_rgba(74,222,128,0.3)]
                                            @elseif($listing->item->rarity === 'rare') border-blue-400 shadow-[0_0_8px_rgba(96,165,250,0.3)]
                                            @elseif($listing->item->rarity === 'epic') border-purple-400 shadow-[0_0_8px_rgba(192,132,252,0.3)]
                                            @elseif($listing->item->rarity === 'legendary') border-orange-400 shadow-[0_0_8px_rgba(251,146,60,0.5)]
                                            @endif
                                        ">
                                            @if($listing->item->template->slot === 'weapon') ⚔️
                                            @elseif($listing->item->template->slot === 'head') 🪖
                                            @elseif($listing->item->template->slot === 'chest') 🛡️
                                            @elseif($listing->item->template->slot === 'legs') 👖
                                            @elseif($listing->item->template->slot === 'boots') 👢
                                            @else 📦
                                            @endif
                                        </div>
                                        
                                        <div>
                                            <h4 class="font-bold text-amber-200 leading-tight">
                                                {{ $listing->item->template->name }}
                                                @if($listing->item->level > 1) <span class="text-xs text-amber-500 ml-1">+{{ $listing->item->level - 1 }}</span> @endif
                                            </h4>
                                            <div class="text-xs text-slate-400 flex gap-2 mt-1">
                                                <span>Poz: {{ $listing->item->template->level_requirement }}</span>
                                                <span>{{ ucfirst($listing->item->rarity) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    {{-- Item stats preview (simplified) --}}
                                    <div class="text-xs text-slate-300 bg-slate-900/50 p-2 rounded mb-3 grid grid-cols-2 gap-1">
                                        @foreach($listing->item->template->base_stats as $stat => $val)
                                            @if($val > 0)
                                                <div class="flex justify-between">
                                                    <span class="text-slate-400">{{ ucfirst(str_replace('_', ' ', $stat)) }}</span>
                                                    <span class="text-green-400">+{{ $listing->item->getTotalStats()[$stat] ?? $val }}</span>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                    
                                    <div class="flex flex-col mt-auto pt-2 border-t border-slate-700">
                                        <div class="flex justify-between items-center mb-2">
                                            <span class="text-xs text-slate-400">Sprzedawca: <span class="text-amber-100/70">{{ $listing->seller->name }}</span></span>
                                            <span class="text-[10px] text-slate-500">{{ $listing->expires_at->diffForHumans() }}</span>
                                        </div>
                                        
                                        <button wire:click="buyItem('{{ $listing->id }}')" 
                                            class="w-full flex items-center justify-center font-bold py-2 px-4 rounded transition-all duration-200 
                                            @if($listing->currency === 'gold' && $character->gold >= $listing->price) bg-gradient-to-r from-amber-600 to-amber-700 hover:from-amber-500 hover:to-amber-600 text-white
                                            @elseif($listing->currency === 'gems' && $character->gems >= $listing->price) bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-500 hover:to-purple-600 text-white
                                            @else bg-slate-700 text-slate-400 cursor-not-allowed opacity-70 @endif
                                        ">
                                            <span class="mr-2">Kup za</span> 
                                            <span class="{{ $listing->currency === 'gold' ? 'text-yellow-300' : 'text-purple-300' }} text-lg">
                                                {{ number_format($listing->price) }} {{ $listing->currency === 'gold' ? '💰' : '💎' }}
                                            </span>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        {{ $listings->links() }}
                    @else
                        <div class="bg-slate-800/40 border border-slate-700 rounded-lg p-12 text-center text-slate-400">
                            <div class="text-5xl mb-4 opacity-50">🕸️</div>
                            <h3 class="text-xl font-medium text-amber-200 mb-2">Brak wyników</h3>
                            <p>Nie znaleziono ofert spełniających podane kryteria.</p>
                            <button wire:click="$set('search', ''); $set('rarity', ''); $set('currency', ''); $set('slot', '');" class="mt-4 px-4 py-2 bg-slate-700 hover:bg-slate-600 rounded text-amber-100 transition">Wyczyść filtry</button>
                        </div>
                    @endif
                </div>
            </div>
            
        @else
            {{-- My Listings Tab --}}
            <div class="bg-slate-800/60 border border-slate-700 p-6 rounded-lg shadow-xl backdrop-blur-sm min-h-[400px]">
                
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold text-amber-400">Moje aktywne oferty</h3>
                    <p class="text-sm text-slate-400">Pamiętaj, że oferty wygasają po wyznaczonym czasie. Pobierane jest 5% prowizji przy sprzedaży.</p>
                </div>
                
                @if(count($myListings) > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-900/50 border-b border-slate-700 text-slate-400 uppercase text-xs">
                                    <th class="p-3 font-semibold">Przedmiot</th>
                                    <th class="p-3 font-semibold text-center">Status</th>
                                    <th class="p-3 font-semibold text-right">Cena</th>
                                    <th class="p-3 font-semibold text-right">Wystawiono/Wygasa</th>
                                    <th class="p-3 font-semibold text-center">Akcja</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($myListings as $listing)
                                    <tr class="border-b border-slate-700/50 hover:bg-slate-800/80 transition-colors">
                                        <td class="p-3">
                                            <div class="flex items-center space-x-3">
                                                <div class="text-2xl">
                                                    @if($listing->item->template->slot === 'weapon') ⚔️
                                                    @elseif($listing->item->template->slot === 'head') 🪖
                                                    @elseif($listing->item->template->slot === 'chest') 🛡️
                                                    @elseif($listing->item->template->slot === 'legs') 👖
                                                    @elseif($listing->item->template->slot === 'boots') 👢
                                                    @else 📦
                                                    @endif
                                                </div>
                                                <div>
                                                    <div class="font-bold text-amber-200 
                                                        @if($listing->item->rarity === 'uncommon') text-green-300
                                                        @elseif($listing->item->rarity === 'rare') text-blue-300
                                                        @elseif($listing->item->rarity === 'epic') text-purple-300
                                                        @elseif($listing->item->rarity === 'legendary') text-orange-300
                                                        @endif
                                                    ">
                                                        {{ $listing->item->template->name }}
                                                        @if($listing->item->level > 1) <span class="text-xs opacity-70 ml-1">+{{ $listing->item->level - 1 }}</span> @endif
                                                    </div>
                                                    <div class="text-xs text-slate-400">Poz: {{ $listing->item->template->level_requirement }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="p-3 text-center">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                {{ $listing->status === 'active' ? 'bg-green-100 text-green-800' : 
                                                  ($listing->status === 'sold' ? 'bg-blue-100 text-blue-800' : 'bg-slate-100 text-slate-800') }}">
                                                {{ $listing->statusLabel() }}
                                            </span>
                                        </td>
                                        <td class="p-3 text-right font-semibold">
                                            {{ number_format($listing->price) }} 
                                            <span class="{{ $listing->currency === 'gold' ? 'text-yellow-400' : 'text-purple-400' }}">{{ $listing->currency === 'gold' ? '💰' : '💎' }}</span>
                                        </td>
                                        <td class="p-3 text-right text-sm">
                                            <div class="text-slate-300">{{ $listing->created_at->format('Y-m-d H:i') }}</div>
                                            <div class="text-xs text-amber-500/80">{{ $listing->expires_at->diffForHumans() }}</div>
                                        </td>
                                        <td class="p-3 text-center">
                                            @if($listing->status === 'active')
                                                <button wire:click="cancelListing('{{ $listing->id }}')" onclick="confirm('Czy na pewno chcesz anulować tę ofertę? Opłata za wystawienie nie zostanie zwrócona.') || event.stopImmediatePropagation()" class="px-3 py-1 bg-red-900/50 hover:bg-red-800 text-red-200 border border-red-700 rounded transition-colors text-sm">
                                                    Anuluj
                                                </button>
                                            @else
                                                <span class="text-slate-500">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-4">
                        {{ $myListings->links() }}
                    </div>
                @else
                    <div class="text-center py-12 text-slate-400">
                        <div class="text-4xl mb-4 opacity-50">💨</div>
                        <p>Nie masz obecnie żadnych przedmiotów wystawionych na sprzedaż.</p>
                        <p class="text-sm mt-2">Aby wystawić przedmiot, przejdź do swojego plecaka i wybierz opcję "Wystaw na market" przy wybranym przedmiocie.</p>
                    </div>
                @endif
                
            </div>
        @endif
    </div>
</div>
