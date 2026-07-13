<div class="min-h-screen bg-gray-900 text-gray-100 p-8">
    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-amber-500">👑 Zarządzanie Tytułami</h1>
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
                <h2 class="text-xl font-bold mb-4 border-b border-gray-700 pb-2 text-white">
                    {{ $isEditing ? 'Edytuj Tytuł' : 'Dodaj Nowy Tytuł' }}
                </h2>

                <form wire:submit.prevent="save">
                    <div class="mb-4">
                        <label class="block text-gray-400 text-sm font-bold mb-2">Nazwa</label>
                        <input type="text" wire:model="name" class="shadow appearance-none border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white leading-tight focus:outline-none focus:border-amber-500" required>
                        @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-400 text-sm font-bold mb-2">Prefix (wyświetlany na czacie)</label>
                        <input type="text" wire:model="prefix" class="shadow appearance-none border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white leading-tight focus:outline-none focus:border-amber-500" placeholder="np. [Zabójca Smoków]">
                        @error('prefix') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-400 text-sm font-bold mb-2">Opis</label>
                        <textarea wire:model="description" class="shadow appearance-none border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white leading-tight focus:outline-none focus:border-amber-500" rows="3"></textarea>
                        @error('description') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-6">
                        <label class="block text-gray-400 text-sm font-bold mb-2">Bonusy Statystyk</label>
                        
                        @foreach($stats_bonus as $index => $stat)
                            <div class="flex space-x-2 mb-2 items-center">
                                @if(is_array($stat) && isset($stat['key']))
                                    <select wire:model="stats_bonus.{{ $index }}.key" class="shadow border border-gray-600 rounded w-1/2 py-2 px-3 bg-gray-700 text-white focus:outline-none focus:border-amber-500">
                                        @foreach($availableStats as $s)
                                            <option value="{{ $s }}">{{ $s }}</option>
                                        @endforeach
                                    </select>
                                    <input type="number" wire:model="stats_bonus.{{ $index }}.value" class="shadow appearance-none border border-gray-600 rounded w-1/3 py-2 px-3 bg-gray-700 text-white leading-tight focus:outline-none focus:border-amber-500">
                                @else
                                    <span class="text-white">Błąd formatowania, zapisz ponownie statystyki.</span>
                                @endif
                                <button type="button" wire:click="removeStat({{ $index }})" class="bg-red-600 hover:bg-red-500 text-white p-2 rounded">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                            </div>
                        @endforeach
                        <button type="button" wire:click="addStat" class="mt-2 px-4 py-2 bg-green-600 hover:bg-green-500 text-white rounded text-sm transition">Dodaj Bonus</button>
                    </div>

                    <div class="flex justify-between">
                        <button type="submit" class="bg-amber-600 hover:bg-amber-500 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition">
                            Zapisz
                        </button>
                        @if($isEditing)
                            <button type="button" wire:click="create" class="bg-gray-600 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded transition">
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
                                <th class="p-3 text-gray-400 font-bold uppercase text-sm">Tytuł</th>
                                <th class="p-3 text-gray-400 font-bold uppercase text-sm">Prefix</th>
                                <th class="p-3 text-gray-400 font-bold uppercase text-sm">Bonusy</th>
                                <th class="p-3 text-gray-400 font-bold uppercase text-sm text-right">Akcje</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($titles as $title)
                                <tr class="border-b border-gray-700 hover:bg-gray-700/50 transition">
                                    <td class="p-3 text-white">
                                        <div class="font-bold text-yellow-500">{{ $title->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $title->id }}</div>
                                    </td>
                                    <td class="p-3 text-gray-300">
                                        @if($title->prefix)
                                            <span class="px-2 py-1 bg-gray-900 border border-gray-600 text-purple-400 text-xs rounded">{{ $title->prefix }}</span>
                                        @else
                                            <span class="text-xs text-gray-500">Brak</span>
                                        @endif
                                    </td>
                                    <td class="p-3 text-gray-300">
                                        <pre class="text-xs text-amber-300 bg-gray-900 p-1 rounded">{{ json_encode($title->stats_bonus) }}</pre>
                                    </td>
                                    <td class="p-3 text-right">
                                        <button wire:click="edit('{{ $title->id }}')" class="text-blue-400 hover:text-blue-300 mr-3">Edytuj</button>
                                        <button wire:click="delete('{{ $title->id }}')" class="text-red-400 hover:text-red-300" onclick="confirm('Na pewno usunąć?') || event.stopImmediatePropagation()">Usuń</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="p-4 text-center text-gray-500">Brak tytułów w bazie.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
