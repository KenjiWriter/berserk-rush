<div class="min-h-screen bg-gray-900 text-gray-100 p-8">
    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-amber-500">🐉 Zarządzanie Zwierzakami (Pety)</h1>
            <a href="{{ route('admin.dashboard') }}" class="text-gray-400 hover:text-white underline">&larr; Powrót do panelu</a>
        </div>

        @if (session()->has('message'))
            <div class="bg-green-600 text-white p-3 rounded mb-4 shadow">
                {{ session('message') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Formularz -->
            <div class="bg-gray-800 p-6 rounded-lg shadow-lg border border-gray-700 h-fit">
                <h2 class="text-xl font-bold mb-4 border-b border-gray-700 pb-2">
                    {{ $editingId ? 'Edytuj Zwierzaka' : 'Dodaj Nowego Zwierzaka' }}
                </h2>

                <form wire:submit.prevent="save">
                    <div class="mb-4">
                        <label class="block text-gray-400 text-sm font-bold mb-2">Nazwa</label>
                        <input type="text" wire:model="name" class="shadow appearance-none border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white leading-tight focus:outline-none focus:border-amber-500">
                        @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-400 text-sm font-bold mb-2">Rzadkość</label>
                        <select wire:model="rarity" class="shadow border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white focus:outline-none focus:border-amber-500">
                            <option value="common">Common</option>
                            <option value="uncommon">Uncommon</option>
                            <option value="rare">Rare</option>
                            <option value="epic">Epic</option>
                            <option value="legendary">Legendary</option>
                        </select>
                        @error('rarity') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-400 text-sm font-bold mb-2">Ikona (np. 🐺)</label>
                        <input type="text" wire:model="icon" class="shadow appearance-none border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white leading-tight focus:outline-none focus:border-amber-500">
                        @error('icon') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <h3 class="text-lg font-bold mb-2 text-gray-300">Bazowe Atrybuty</h3>
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div>
                            <label class="block text-gray-400 text-sm font-bold mb-2">Siła (STR)</label>
                            <input type="number" wire:model="str" class="shadow border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white focus:outline-none focus:border-amber-500">
                        </div>
                        <div>
                            <label class="block text-gray-400 text-sm font-bold mb-2">Zręczność (AGI)</label>
                            <input type="number" wire:model="agi" class="shadow border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white focus:outline-none focus:border-amber-500">
                        </div>
                        <div>
                            <label class="block text-gray-400 text-sm font-bold mb-2">Inteligencja (INT)</label>
                            <input type="number" wire:model="int" class="shadow border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white focus:outline-none focus:border-amber-500">
                        </div>
                        <div>
                            <label class="block text-gray-400 text-sm font-bold mb-2">Witalność (VIT)</label>
                            <input type="number" wire:model="vit" class="shadow border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white focus:outline-none focus:border-amber-500">
                        </div>
                    </div>

                    <div class="flex justify-between">
                        <button type="submit" class="bg-amber-600 hover:bg-amber-500 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition">
                            Zapisz
                        </button>
                        @if($editingId)
                            <button type="button" wire:click="$set('editingId', null); $reset(['name', 'rarity', 'icon', 'str', 'agi', 'int', 'vit'])" class="bg-gray-600 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded transition">
                                Anuluj
                            </button>
                        @endif
                    </div>
                </form>
            </div>

            <!-- Lista -->
            <div class="lg:col-span-2">
                <div class="bg-gray-800 rounded-lg shadow-lg border border-gray-700 overflow-hidden">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-700 border-b border-gray-600">
                                <th class="py-3 px-4 text-gray-300 font-bold uppercase text-sm">Ikona & Nazwa</th>
                                <th class="py-3 px-4 text-gray-300 font-bold uppercase text-sm">Rzadkość</th>
                                <th class="py-3 px-4 text-gray-300 font-bold uppercase text-sm">Statystyki</th>
                                <th class="py-3 px-4 text-gray-300 font-bold uppercase text-sm text-right">Akcje</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($templates as $pet)
                                <tr class="border-b border-gray-700 hover:bg-gray-750 transition">
                                    <td class="py-3 px-4">
                                        <div class="flex items-center gap-2">
                                            <span class="text-2xl">{{ $pet->icon }}</span>
                                            <span class="font-bold text-white">{{ $pet->name }}</span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="px-2 py-1 rounded text-xs font-bold
                                            @if($pet->rarity == 'common') bg-gray-500 text-white
                                            @elseif($pet->rarity == 'uncommon') bg-green-500 text-white
                                            @elseif($pet->rarity == 'rare') bg-blue-500 text-white
                                            @elseif($pet->rarity == 'epic') bg-purple-500 text-white
                                            @elseif($pet->rarity == 'legendary') bg-orange-500 text-white
                                            @endif">
                                            {{ ucfirst($pet->rarity) }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-sm text-gray-400">
                                        STR: {{ $pet->base_stats['str'] ?? 0 }} | 
                                        AGI: {{ $pet->base_stats['agi'] ?? 0 }} | 
                                        INT: {{ $pet->base_stats['int'] ?? 0 }} | 
                                        VIT: {{ $pet->base_stats['vit'] ?? 0 }}
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        <button wire:click="edit('{{ $pet->id }}')" class="text-blue-400 hover:text-blue-300 mr-3">Edytuj</button>
                                        <button wire:click="delete('{{ $pet->id }}')" onclick="confirm('Na pewno?') || event.stopImmediatePropagation()" class="text-red-400 hover:text-red-300">Usuń</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-8 text-center text-gray-500">
                                        Brak szablonów zwierzaków. Dodaj pierwszego!
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
