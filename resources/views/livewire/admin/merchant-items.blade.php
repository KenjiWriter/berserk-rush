<div class="min-h-screen bg-gray-900 text-gray-100 p-8">
    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-amber-500">🏬 Asortyment Handlarzy</h1>
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
                    {{ $editingId ? 'Edytuj Asortyment' : 'Dodaj Przedmiot do Sklepu' }}
                </h2>

                <form wire:submit.prevent="save">
                    <div class="mb-4">
                        <label class="block text-gray-400 text-sm font-bold mb-2">Handlarz</label>
                        <select wire:model="merchant_id" class="shadow border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white focus:outline-none focus:border-amber-500">
                            <option value="armorsmith">Zbrojmistrz</option>
                            <option value="weaponsmith">Brońmistrz</option>
                            <option value="witch">Wiedźma</option>
                        </select>
                        @error('merchant_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-400 text-sm font-bold mb-2">Szablon Przedmiotu</label>
                        <select wire:model="item_template_id" class="shadow border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white focus:outline-none focus:border-amber-500">
                            <option value="">-- Wybierz przedmiot --</option>
                            @foreach($templates as $tpl)
                                <option value="{{ $tpl->id }}">[{{ $tpl->type }}] {{ $tpl->name }} (Lvl {{ $tpl->level_requirement }})</option>
                            @endforeach
                        </select>
                        @error('item_template_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-400 text-sm font-bold mb-2">Wymagany Level Postaci (w sklepie)</label>
                        <input type="number" wire:model="required_level" class="shadow appearance-none border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white leading-tight focus:outline-none focus:border-amber-500">
                        <p class="text-xs text-gray-500 mt-1">Gracz nie zobaczy go przed tym levelem.</p>
                        @error('required_level') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-4 p-3 border border-gray-700 rounded bg-gray-900">
                        <label class="flex items-center space-x-2 text-gray-300 font-bold mb-2 cursor-pointer">
                            <input type="checkbox" wire:model.live="is_limited" class="rounded bg-gray-700 border-gray-600 text-amber-500 focus:ring-amber-500">
                            <span>Edycja Limitowana (Ograniczona pula sztuk)</span>
                        </label>
                        @if($is_limited)
                            <div class="mt-2">
                                <label class="block text-gray-400 text-sm mb-1">Maksymalna liczba sztuk na cały serwer</label>
                                <input type="number" wire:model="max_quantity" placeholder="np. 1000" class="shadow appearance-none border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white leading-tight focus:outline-none focus:border-amber-500">
                                @error('max_quantity') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                        @endif
                    </div>

                    <div class="flex justify-between">
                        <button type="submit" class="bg-amber-600 hover:bg-amber-500 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition">
                            Zapisz
                        </button>
                        @if($editingId)
                            <button type="button" wire:click="$set('editingId', null); $reset(['item_template_id', 'required_level', 'is_limited', 'max_quantity'])" class="bg-gray-600 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded transition">
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
                                <th class="p-3 text-gray-400 font-bold uppercase text-sm">Handlarz / Przedmiot</th>
                                <th class="p-3 text-gray-400 font-bold uppercase text-sm text-center">Wym. Level</th>
                                <th class="p-3 text-gray-400 font-bold uppercase text-sm text-center">Status Puli</th>
                                <th class="p-3 text-gray-400 font-bold uppercase text-sm text-right">Akcje</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($merchantItems as $item)
                                <tr class="border-b border-gray-700 hover:bg-gray-700/50 transition">
                                    <td class="p-3">
                                        <div class="text-xs text-blue-400 font-bold uppercase">{{ $item->merchant_id }}</div>
                                        <div class="text-white font-bold">{{ $item->template->name }}</div>
                                    </td>
                                    <td class="p-3 text-center text-gray-300">{{ $item->required_level }}</td>
                                    <td class="p-3 text-center">
                                        @if($item->is_limited)
                                            <div class="text-xs font-bold {{ $item->sold_quantity >= $item->max_quantity ? 'text-red-500' : 'text-amber-500' }}">
                                                {{ $item->sold_quantity }} / {{ $item->max_quantity }}
                                            </div>
                                            @if($item->sold_quantity >= $item->max_quantity)
                                                <span class="text-[10px] text-red-500 uppercase">Wyprzedane</span>
                                            @endif
                                        @else
                                            <span class="text-xs text-green-500">Bez limitu</span>
                                        @endif
                                    </td>
                                    <td class="p-3 text-right">
                                        <button wire:click="edit({{ $item->id }})" class="text-blue-400 hover:text-blue-300 mr-3">Edytuj</button>
                                        <button wire:click="delete({{ $item->id }})" class="text-red-400 hover:text-red-300" onclick="confirm('Na pewno usunąć z oferty?') || event.stopImmediatePropagation()">Usuń</button>
                                    </td>
                                </tr>
                            @endforeach
                            @if($merchantItems->isEmpty())
                                <tr>
                                    <td colspan="4" class="p-6 text-center text-gray-500">Brak przedmiotów przypisanych do handlarzy.</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
