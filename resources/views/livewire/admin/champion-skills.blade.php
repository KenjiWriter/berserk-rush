<div class="min-h-screen bg-gray-900 text-gray-100 p-8">
    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-sky-400 flex items-center gap-2"><i class="fa-solid fa-crown"></i> Zarządzanie Drzewkiem Mistrzostwa</h1>
            <a href="{{ route('admin.dashboard') }}" class="text-gray-400 hover:text-white underline">&larr; Powrót do panelu</a>
        </div>

        @if (session()->has('message'))
            <div class="bg-green-600 text-white p-3 rounded mb-4 shadow">
                {{ session('message') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
            <!-- Formularz -->
            <div class="bg-gray-800 p-6 rounded-lg shadow-lg border border-gray-700 h-fit lg:order-last lg:sticky lg:top-8 max-h-[calc(100vh-4rem)] overflow-y-auto relative">

                <div wire:loading wire:target="edit" class="absolute inset-0 bg-gray-900/80 z-50 flex items-center justify-center rounded-lg backdrop-blur-sm">
                    <div class="text-sky-400 font-bold flex flex-col items-center">
                        <svg class="animate-spin h-10 w-10 mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Wczytywanie danych...
                    </div>
                </div>

                <h2 class="text-xl font-bold mb-4 border-b border-gray-700 pb-2">
                    {{ $editingId ? 'Edytuj Umiejętność' : 'Dodaj Nową Umiejętność' }}
                </h2>

                <form wire:submit.prevent="save">
                    <div class="mb-4">
                        <label class="block text-gray-400 text-sm font-bold mb-2">Klucz (unikalny, bez spacji)</label>
                        <input type="text" wire:model="key" placeholder="np. strength" class="shadow appearance-none border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white leading-tight focus:outline-none focus:border-sky-500">
                        @error('key') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-400 text-sm font-bold mb-2">Nazwa</label>
                        <input type="text" wire:model="name" class="shadow appearance-none border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white leading-tight focus:outline-none focus:border-sky-500">
                        @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-400 text-sm font-bold mb-2">Opis</label>
                        <textarea wire:model="description" class="shadow appearance-none border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white leading-tight focus:outline-none focus:border-sky-500 h-20"></textarea>
                        @error('description') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-400 text-sm font-bold mb-2">Typ Statystyki (gdzie wpięty jest bonus)</label>
                        <select wire:model="stat_type" class="shadow border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white focus:outline-none focus:border-sky-500">
                            @foreach(\App\Livewire\Admin\ChampionSkills::STAT_TYPES as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('stat_type') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex gap-4 mb-4">
                        <div class="w-1/2">
                            <label class="block text-gray-400 text-sm font-bold mb-2">Bonus / Punkt (%)</label>
                            <input type="number" step="0.1" min="0" wire:model="bonus_per_point" class="shadow appearance-none border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white leading-tight focus:outline-none focus:border-sky-500">
                            @error('bonus_per_point') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div class="w-1/2">
                            <label class="block text-gray-400 text-sm font-bold mb-2">Max Punktów</label>
                            <input type="number" min="1" wire:model="max_points" class="shadow appearance-none border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white leading-tight focus:outline-none focus:border-sky-500">
                            @error('max_points') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="flex gap-4 mb-4">
                        <div class="w-1/2">
                            <label class="block text-gray-400 text-sm font-bold mb-2">Ikona (klasa FontAwesome)</label>
                            <input type="text" wire:model="icon" placeholder="fa-solid fa-hand-fist" class="shadow appearance-none border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white leading-tight focus:outline-none focus:border-sky-500">
                            @error('icon') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div class="w-1/2">
                            <label class="block text-gray-400 text-sm font-bold mb-2">Kolejność</label>
                            <input type="number" min="0" wire:model="sort_order" class="shadow appearance-none border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white leading-tight focus:outline-none focus:border-sky-500">
                            @error('sort_order') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    @if($icon)
                        <div class="mb-4 flex items-center gap-2 text-sm text-gray-400">
                            <span>Podgląd:</span>
                            <div class="w-10 h-10 rounded-lg border-2 border-sky-700 bg-sky-950/50 flex items-center justify-center text-sky-300 text-lg">
                                <i class="{{ $icon }}"></i>
                            </div>
                        </div>
                    @endif

                    <div class="flex justify-between mt-6">
                        <button type="submit" class="bg-sky-600 hover:bg-sky-500 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition">
                            Zapisz
                        </button>
                        @if($editingId)
                            <button type="button" wire:click="cancelEdit" class="bg-gray-600 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded transition">
                                Anuluj
                            </button>
                        @endif
                    </div>
                </form>
            </div>

            <!-- Lista Umiejętności -->
            <div class="lg:col-span-2">
                <div class="bg-gray-800 rounded-lg shadow-lg border border-gray-700 overflow-hidden">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-900 border-b border-gray-700">
                                <th class="p-3 text-gray-400 font-bold uppercase text-sm">Umiejętność</th>
                                <th class="p-3 text-gray-400 font-bold uppercase text-sm">Typ Bonusu</th>
                                <th class="p-3 text-gray-400 font-bold uppercase text-sm">Wartość</th>
                                <th class="p-3 text-gray-400 font-bold uppercase text-sm text-right">Akcje</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($skills as $skill)
                                <tr class="border-b border-gray-700 hover:bg-gray-700/50 transition cursor-pointer" wire:click="edit('{{ $skill->id }}')">
                                    <td class="p-3 text-white font-bold">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-lg border-2 border-sky-700 bg-sky-950/50 flex items-center justify-center text-sky-300 shrink-0">
                                                <i class="{{ $skill->icon ?: 'fa-solid fa-star' }}"></i>
                                            </div>
                                            <div>
                                                {{ $skill->name }}
                                                <div class="text-xs text-gray-500">{{ $skill->key }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-3 text-purple-400">{{ \App\Livewire\Admin\ChampionSkills::STAT_TYPES[$skill->stat_type] ?? $skill->stat_type }}</td>
                                    <td class="p-3">
                                        <div class="text-yellow-500">+{{ $skill->bonus_per_point }}% / pkt</div>
                                        <div class="text-xs text-gray-400">Max {{ $skill->max_points }} pkt (+{{ $skill->bonus_per_point * $skill->max_points }}%)</div>
                                    </td>
                                    <td class="p-3 text-right">
                                        <button wire:click.stop="delete('{{ $skill->id }}')" class="text-red-400 hover:text-red-300" onclick="confirm('Na pewno usunąć?') || event.stopImmediatePropagation()">Usuń</button>
                                    </td>
                                </tr>
                            @endforeach
                            @if($skills->isEmpty())
                                <tr>
                                    <td colspan="4" class="p-6 text-center text-gray-500">Brak umiejętności w bazie danych.</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
