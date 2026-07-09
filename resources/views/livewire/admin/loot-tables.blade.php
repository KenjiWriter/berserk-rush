<div class="min-h-screen bg-gray-900 text-gray-100 p-8">
    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-amber-500">🎁 Zarządzanie Tabelami Łupów</h1>
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
                    {{ $editingId ? 'Edytuj Tabelę' : 'Dodaj Nową Tabelę' }}
                </h2>

                <form wire:submit.prevent="save">
                    <div class="mb-4">
                        <label class="block text-gray-400 text-sm font-bold mb-2">Nazwa Tabeli</label>
                        <input type="text" wire:model="name" class="shadow appearance-none border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white leading-tight focus:outline-none focus:border-amber-500">
                        @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-6">
                        <label class="block text-gray-400 text-sm font-bold mb-2">Opis</label>
                        <textarea wire:model="description" class="shadow border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white focus:outline-none focus:border-amber-500"></textarea>
                        @error('description') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex justify-between">
                        <button type="submit" class="bg-amber-600 hover:bg-amber-500 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition">
                            Zapisz
                        </button>
                        @if($editingId)
                            <button type="button" wire:click="$set('editingId', null); $reset(['name', 'description'])" class="bg-gray-600 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded transition">
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
                                <th class="p-3 text-gray-400 font-bold uppercase text-sm">ID</th>
                                <th class="p-3 text-gray-400 font-bold uppercase text-sm">Nazwa</th>
                                <th class="p-3 text-gray-400 font-bold uppercase text-sm">Opis</th>
                                <th class="p-3 text-gray-400 font-bold uppercase text-sm">Ilość Wpisów</th>
                                <th class="p-3 text-gray-400 font-bold uppercase text-sm text-right">Akcje</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($lootTables as $lt)
                                <tr class="border-b border-gray-700 hover:bg-gray-700/50 transition">
                                    <td class="p-3 text-gray-300">{{ $lt->id }}</td>
                                    <td class="p-3 text-white font-bold">{{ $lt->name }}</td>
                                    <td class="p-3 text-gray-400 text-sm">{{ $lt->description }}</td>
                                    <td class="p-3 text-blue-400">{{ $lt->entries_count }} wpisów</td>
                                    <td class="p-3 text-right">
                                        <button wire:click="edit({{ $lt->id }})" class="text-blue-400 hover:text-blue-300 mr-3">Edytuj</button>
                                        <button wire:click="delete({{ $lt->id }})" class="text-red-400 hover:text-red-300" onclick="confirm('Na pewno usunąć?') || event.stopImmediatePropagation()">Usuń</button>
                                    </td>
                                </tr>
                            @endforeach
                            @if($lootTables->isEmpty())
                                <tr>
                                    <td colspan="5" class="p-6 text-center text-gray-500">Brak tabel łupów.</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
