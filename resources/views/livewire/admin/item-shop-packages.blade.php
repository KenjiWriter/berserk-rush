<div class="min-h-screen bg-gray-900 text-gray-100 p-8">
    <div class="max-w-7xl mx-auto">
        
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-amber-500">💎 Pakiety Item Shop</h1>
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
                    {{ $editingPackageId ? 'Edytuj pakiet' : 'Dodaj nowy pakiet' }}
                </h2>

                <form wire:submit.prevent="save">
                    <div class="mb-4">
                        <label class="block text-gray-400 text-sm font-bold mb-2">Nazwa Pakietu</label>
                        <input type="text" wire:model="name" class="shadow appearance-none border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white leading-tight focus:outline-none focus:border-amber-500">
                        @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-400 text-sm font-bold mb-2">Ilość Gemów</label>
                        <input type="number" wire:model="gem_amount" class="shadow appearance-none border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white leading-tight focus:outline-none focus:border-amber-500">
                        @error('gem_amount') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-400 text-sm font-bold mb-2">Cena (w walucie, np. 10.00)</label>
                        <input type="number" step="0.01" wire:model="price" class="shadow appearance-none border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white leading-tight focus:outline-none focus:border-amber-500">
                        @error('price') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-400 text-sm font-bold mb-2">Waluta</label>
                        <input type="text" wire:model="currency" class="shadow appearance-none border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white leading-tight focus:outline-none focus:border-amber-500" placeholder="PLN">
                        @error('currency') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    
                    <div class="flex items-center mb-6">
                        <input type="checkbox" wire:model="is_active" id="is_active" class="rounded bg-gray-700 border-gray-600 text-amber-500 focus:ring-amber-500 mr-2 leading-tight">
                        <label for="is_active" class="text-gray-400 text-sm font-bold">Pakiet Aktywny (widoczny w sklepie)</label>
                    </div>

                    <div class="flex justify-between">
                        <button type="submit" class="bg-amber-600 hover:bg-amber-500 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition">
                            {{ $editingPackageId ? 'Zapisz' : 'Dodaj' }}
                        </button>
                        @if($editingPackageId)
                            <button type="button" wire:click="reset(['name', 'gem_amount', 'price', 'currency', 'is_active', 'editingPackageId'])" class="bg-gray-600 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded transition">
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
                            <tr class="bg-gray-900 border-b border-gray-700">
                                <th class="p-3 text-gray-400 font-bold uppercase text-sm">Nazwa</th>
                                <th class="p-3 text-gray-400 font-bold uppercase text-sm">Gemy</th>
                                <th class="p-3 text-gray-400 font-bold uppercase text-sm">Cena</th>
                                <th class="p-3 text-gray-400 font-bold uppercase text-sm">Aktywny</th>
                                <th class="p-3 text-gray-400 font-bold uppercase text-sm text-right">Akcje</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($packages as $pkg)
                                <tr class="border-b border-gray-700 hover:bg-gray-700/50 transition">
                                    <td class="p-3 text-white font-bold text-amber-500">{{ $pkg->name }}</td>
                                    <td class="p-3 text-yellow-400 font-bold">💎 {{ $pkg->gem_amount }}</td>
                                    <td class="p-3 text-gray-300">{{ number_format($pkg->price_in_cents / 100, 2) }} {{ $pkg->currency }}</td>
                                    <td class="p-3">
                                        @if($pkg->is_active)
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-900 text-green-300 border border-green-700">Tak</span>
                                        @else
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-900 text-red-300 border border-red-700">Nie</span>
                                        @endif
                                    </td>
                                    <td class="p-3 text-right">
                                        <button wire:click="edit('{{ $pkg->id }}')" class="text-blue-400 hover:text-blue-300 mr-3">Edytuj</button>
                                        <button wire:click="delete('{{ $pkg->id }}')" class="text-red-400 hover:text-red-300" onclick="confirm('Na pewno usunąć?') || event.stopImmediatePropagation()">Usuń</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>
