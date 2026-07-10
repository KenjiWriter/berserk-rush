<div class="min-h-screen bg-gray-900 text-gray-100 p-8">
    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-amber-500">🏰 Zarządzanie Dungeonami</h1>
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
                    {{ $editingId ? 'Edytuj Dungeon' : 'Dodaj Nowy Dungeon' }}
                </h2>

                <form wire:submit.prevent="save">
                    <div class="mb-4">
                        <label class="block text-gray-400 text-sm font-bold mb-2">Nazwa</label>
                        <input type="text" wire:model="name" class="shadow appearance-none border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white leading-tight focus:outline-none focus:border-amber-500">
                        @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-400 text-sm font-bold mb-2">Min. Poziom</label>
                        <input type="number" wire:model="min_level" class="shadow appearance-none border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white leading-tight focus:outline-none focus:border-amber-500">
                        @error('min_level') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-400 text-sm font-bold mb-2">Klucz wejścia (Opcjonalnie)</label>
                        <select wire:model="entry_item_template_id" class="shadow border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white focus:outline-none focus:border-amber-500">
                            <option value="">-- Brak --</option>
                            @foreach($itemTemplates as $item)
                                <option value="{{ $item->id }}">{{ $item->name }}</option>
                            @endforeach
                        </select>
                        @error('entry_item_template_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <!-- Etapy -->
                    <div class="mb-6">
                        <label class="block text-gray-400 text-sm font-bold mb-2">Etapy</label>
                        <div class="space-y-2">
                            @foreach($stages as $index => $stage)
                                <div class="flex items-center gap-2">
                                    <span class="text-gray-500 text-sm font-mono w-6 text-center">{{ $index + 1 }}.</span>
                                    <select wire:model="stages.{{ $index }}.monster_id" class="shadow border border-gray-600 rounded flex-1 py-2 px-3 bg-gray-700 text-white focus:outline-none focus:border-amber-500">
                                        <option value="">-- Wybierz Potwora --</option>
                                        @foreach($monsters as $monster)
                                            <option value="{{ $monster->id }}">{{ $monster->name }} (Lv. {{ $monster->level }})</option>
                                        @endforeach
                                    </select>
                                    <button type="button" wire:click="removeStage({{ $index }})" class="text-red-400 hover:text-red-300 text-sm font-bold px-2 py-1">✕</button>
                                </div>
                                @error("stages.{$index}.monster_id") <span class="text-red-500 text-xs ml-8">{{ $message }}</span> @enderror
                            @endforeach
                        </div>
                        <button type="button" wire:click="addStage" class="mt-2 text-sm text-amber-400 hover:text-amber-300 font-bold">
                            + Dodaj Etap
                        </button>
                    </div>

                    <div class="flex justify-between">
                        <button type="submit" class="bg-amber-600 hover:bg-amber-500 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition">
                            Zapisz
                        </button>
                        @if($editingId)
                            <button type="button" wire:click="$set('editingId', null); $reset(['name', 'min_level', 'entry_item_template_id', 'stages'])" class="bg-gray-600 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded transition">
                                Anuluj
                            </button>
                        @endif
                    </div>
                </form>
            </div>

            <!-- Lista Dungeonów -->
            <div class="lg:col-span-2">
                <div class="bg-gray-800 rounded-lg shadow-lg border border-gray-700 overflow-hidden">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-900 border-b border-gray-700">
                                <th class="p-3 text-gray-400 font-bold uppercase text-sm">Nazwa</th>
                                <th class="p-3 text-gray-400 font-bold uppercase text-sm">Min. Poziom</th>
                                <th class="p-3 text-gray-400 font-bold uppercase text-sm">Klucz wejścia</th>
                                <th class="p-3 text-gray-400 font-bold uppercase text-sm">Etapy</th>
                                <th class="p-3 text-gray-400 font-bold uppercase text-sm text-right">Akcje</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($dungeons as $dungeon)
                                <tr class="border-b border-gray-700 hover:bg-gray-700/50 transition">
                                    <td class="p-3 text-white font-bold">{{ $dungeon->name }}</td>
                                    <td class="p-3 text-yellow-500">{{ $dungeon->min_level }}</td>
                                    <td class="p-3 text-gray-300">{{ $dungeon->entryItemTemplate?->name ?? 'Brak' }}</td>
                                    <td class="p-3 text-gray-400 text-xs">
                                        @foreach($dungeon->stages as $stage)
                                            <span class="inline-block bg-gray-700 border border-gray-600 rounded px-2 py-0.5 mr-1 mb-1">
                                                {{ $stage->stage_order }}. {{ $stage->monster?->name ?? '?' }}
                                            </span>
                                        @endforeach
                                        @if($dungeon->stages->isEmpty())
                                            <span class="text-gray-500">Brak etapów</span>
                                        @endif
                                    </td>
                                    <td class="p-3 text-right">
                                        <button wire:click="edit('{{ $dungeon->id }}')" class="text-blue-400 hover:text-blue-300 mr-3">Edytuj</button>
                                        <button wire:click="delete('{{ $dungeon->id }}')" class="text-red-400 hover:text-red-300" onclick="confirm('Na pewno usunąć?') || event.stopImmediatePropagation()">Usuń</button>
                                    </td>
                                </tr>
                            @endforeach
                            @if($dungeons->isEmpty())
                                <tr>
                                    <td colspan="5" class="p-6 text-center text-gray-500">Brak dungeonów w bazie danych.</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
