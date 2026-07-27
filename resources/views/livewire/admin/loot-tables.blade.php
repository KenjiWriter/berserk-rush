<div class="min-h-screen bg-gray-900 text-gray-100 p-8">
    <div class="max-w-7xl mx-auto space-y-8">
        <div class="flex justify-between items-center border-b border-gray-800 pb-4">
            <h1 class="text-3xl font-bold text-amber-500 flex items-center gap-3">
                <i class="fa-solid fa-gift"></i> Zarządzanie Tabelami Łupów
            </h1>
            <a href="{{ route('admin.dashboard') }}" class="text-gray-400 hover:text-white underline flex items-center gap-1.5 text-sm font-semibold">
                <i class="fa-solid fa-arrow-left"></i> Powrót do panelu
            </a>
        </div>

        @if (session()->has('message'))
            <div class="bg-emerald-600/90 text-white p-4 rounded-xl shadow-lg flex items-center justify-between border border-emerald-500">
                <div class="flex items-center gap-2 font-bold">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>{{ session('message') }}</span>
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Formularz Tabeli -->
            <div class="bg-gray-800 p-6 rounded-xl shadow-lg border border-gray-700 h-fit">
                <h2 class="text-xl font-bold mb-4 border-b border-gray-700 pb-2 flex items-center gap-2 text-amber-400">
                    <i class="fa-solid {{ $editingId ? 'fa-pen-to-square' : 'fa-plus' }}"></i>
                    {{ $editingId ? 'Edytuj Tabelę' : 'Dodaj Nową Tabelę' }}
                </h2>

                <form wire:submit.prevent="save" class="space-y-4">
                    <div>
                        <label class="block text-gray-400 text-sm font-bold mb-1">Nazwa Tabeli</label>
                        <input type="text" wire:model="name" placeholder="np. forest_common" class="shadow appearance-none border border-gray-600 rounded-lg w-full py-2.5 px-3 bg-gray-700 text-white leading-tight focus:outline-none focus:border-amber-500">
                        @error('name') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-gray-400 text-sm font-bold mb-1">Opis</label>
                        <textarea wire:model="description" rows="3" placeholder="Opis tabeli łupów..." class="shadow border border-gray-600 rounded-lg w-full py-2.5 px-3 bg-gray-700 text-white focus:outline-none focus:border-amber-500"></textarea>
                        @error('description') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <button type="submit" class="bg-amber-600 hover:bg-amber-500 text-white font-bold py-2.5 px-5 rounded-lg focus:outline-none transition flex items-center gap-2 shadow">
                            <i class="fa-solid fa-floppy-disk"></i> Zapisz
                        </button>
                        @if($editingId)
                            <button type="button" wire:click="$set('editingId', null); $reset(['name', 'description'])" class="bg-gray-600 hover:bg-gray-500 text-white font-bold py-2.5 px-4 rounded-lg transition">
                                Anuluj
                            </button>
                        @endif
                    </div>
                </form>
            </div>

            <!-- Lista Tabel -->
            <div class="lg:col-span-2">
                <div class="bg-gray-800 rounded-xl shadow-lg border border-gray-700 overflow-hidden">
                    <div class="p-4 bg-gray-900/50 border-b border-gray-700 flex justify-between items-center">
                        <h3 class="font-bold text-gray-300 flex items-center gap-2">
                            <i class="fa-solid fa-list"></i> Tabele Łupów ({{ $lootTables->count() }})
                        </h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-900/80 border-b border-gray-700 text-gray-400 uppercase text-xs font-extrabold tracking-wider">
                                    <th class="p-3.5">ID</th>
                                    <th class="p-3.5">Nazwa</th>
                                    <th class="p-3.5">Opis</th>
                                    <th class="p-3.5">Ilość Wpisów</th>
                                    <th class="p-3.5 text-right">Akcje</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-700/60">
                                @foreach($lootTables as $lt)
                                    <tr class="hover:bg-gray-700/50 transition {{ $selectedTableId === $lt->id ? 'bg-amber-950/40 border-l-4 border-l-amber-500' : '' }}">
                                        <td class="p-3.5 text-gray-400 font-mono text-sm">{{ $lt->id }}</td>
                                        <td class="p-3.5 text-white font-bold">{{ $lt->name }}</td>
                                        <td class="p-3.5 text-gray-400 text-sm max-w-xs truncate">{{ $lt->description ?? '-' }}</td>
                                        <td class="p-3.5">
                                            <button wire:click="selectTable({{ $lt->id }})" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-blue-950/80 border border-blue-700/60 text-blue-300 hover:bg-blue-900 hover:text-white transition text-xs font-bold shadow-sm">
                                                <i class="fa-solid fa-cubes"></i> {{ $lt->entries_count }} wpisów
                                            </button>
                                        </td>
                                        <td class="p-3.5 text-right space-x-2">
                                            <button wire:click="selectTable({{ $lt->id }})" class="text-amber-400 hover:text-amber-300 font-bold text-xs bg-amber-950/60 border border-amber-700/50 px-2.5 py-1.5 rounded-md transition">
                                                <i class="fa-solid fa-list-check mr-1"></i> Zarządzaj Dropem
                                            </button>
                                            <button wire:click="edit({{ $lt->id }})" class="text-blue-400 hover:text-blue-300 font-bold text-xs px-2 py-1">
                                                Edytuj
                                            </button>
                                            <button wire:click="delete({{ $lt->id }})" class="text-red-400 hover:text-red-300 font-bold text-xs px-2 py-1" onclick="confirm('Na pewno usunąć tę tabelę łupów?') || event.stopImmediatePropagation()">
                                                Usuń
                                            </button>
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

        {{-- Sekcja Zarządzania Wpisami Dropu dla Wybranej Tabeli --}}
        @if($selectedTable)
            <div class="bg-gray-800 rounded-xl shadow-2xl border-2 border-amber-600/60 overflow-hidden transition-all duration-300">
                <div class="bg-gradient-to-r from-amber-950 via-gray-900 to-gray-900 p-5 border-b border-amber-600/40 flex justify-between items-center">
                    <div>
                        <div class="text-xs uppercase font-extrabold text-amber-500 tracking-wider">Zarządzanie Dropem (Wpisami)</div>
                        <h2 class="text-2xl font-bold text-white flex items-center gap-2 mt-0.5">
                            <i class="fa-solid fa-boxes-packing text-amber-400"></i> {{ $selectedTable->name }}
                        </h2>
                    </div>
                    <button wire:click="closeEntries" class="bg-gray-700 hover:bg-gray-600 text-gray-200 hover:text-white px-3 py-1.5 rounded-lg text-sm font-bold transition flex items-center gap-1.5">
                        <i class="fa-solid fa-xmark"></i> Zamknij
                    </button>
                </div>

                <div class="p-6 grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Formularz Dodawania/Edycji Wpisu Dropu -->
                    <div class="bg-gray-900/80 p-5 rounded-xl border border-gray-700 h-fit space-y-4">
                        <h4 class="font-bold text-amber-400 border-b border-gray-700 pb-2 flex items-center justify-between">
                            <span>{{ $editingEntryId ? 'Edytuj Wpis Dropu' : 'Dodaj Przedmiot/Nagrodę do Dropu' }}</span>
                            @if($editingEntryId)
                                <button type="button" wire:click="resetEntryForm" class="text-xs text-gray-400 hover:text-white underline">Wyczyść</button>
                            @endif
                        </h4>

                        <form wire:submit.prevent="saveEntry" class="space-y-4">
                            <div>
                                <label class="block text-gray-400 text-xs font-bold mb-1">Typ Nagrody</label>
                                <select wire:model.live="entryRewardType" class="shadow border border-gray-600 rounded-lg w-full py-2 px-3 bg-gray-800 text-white focus:outline-none focus:border-amber-500 text-sm">
                                    <option value="item">Przedmiot (Equipment / Item)</option>
                                    <option value="material">Materiał (Crafting Material)</option>
                                    <option value="gold">Złoto (Gold)</option>
                                    <option value="exp">Punkty Doświadczenia (EXP)</option>
                                    <option value="gems">Gemy / Klejnoty</option>
                                </select>
                                @error('entryRewardType') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            @if(in_array($entryRewardType, ['item', 'material']))
                                <div>
                                    <label class="block text-gray-400 text-xs font-bold mb-1">Wybierz Przedmiot/Materiał</label>
                                    <select wire:model="entryRefUlid" class="shadow border border-gray-600 rounded-lg w-full py-2 px-3 bg-gray-800 text-white focus:outline-none focus:border-amber-500 text-sm">
                                        <option value="">-- Wybierz szablon z bazy --</option>
                                        @foreach($itemTemplates as $tpl)
                                            <option value="{{ $tpl->id }}">
                                                [{{ strtoupper($tpl->type) }}] {{ $tpl->name }} ({{ $tpl->id }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('entryRefUlid') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            @endif

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-gray-400 text-xs font-bold mb-1">Min Ilość</label>
                                    <input type="number" wire:model="entryMinQty" min="1" class="shadow border border-gray-600 rounded-lg w-full py-2 px-3 bg-gray-800 text-white focus:outline-none focus:border-amber-500 text-sm">
                                    @error('entryMinQty') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-gray-400 text-xs font-bold mb-1">Max Ilość</label>
                                    <input type="number" wire:model="entryMaxQty" min="1" class="shadow border border-gray-600 rounded-lg w-full py-2 px-3 bg-gray-800 text-white focus:outline-none focus:border-amber-500 text-sm">
                                    @error('entryMaxQty') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div>
                                <label class="block text-gray-400 text-xs font-bold mb-1">
                                    Waga / Losowość (Weight)
                                    <span class="text-gray-500 font-normal ml-1">(wyższa waga = większa szansa)</span>
                                </label>
                                <input type="number" wire:model="entryWeight" min="1" class="shadow border border-gray-600 rounded-lg w-full py-2 px-3 bg-gray-800 text-white focus:outline-none focus:border-amber-500 text-sm">
                                @error('entryWeight') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div class="flex items-center gap-2 pt-2">
                                <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-2.5 px-4 rounded-lg transition flex items-center justify-center gap-2 text-sm shadow">
                                    <i class="fa-solid fa-plus-circle"></i>
                                    {{ $editingEntryId ? 'Zapisz Wpis' : 'Dodaj do Dropu' }}
                                </button>
                                @if($editingEntryId)
                                    <button type="button" wire:click="resetEntryForm" class="bg-gray-700 hover:bg-gray-600 text-white font-bold py-2.5 px-3 rounded-lg text-sm transition">
                                        Anuluj
                                    </button>
                                @endif
                            </div>
                        </form>
                    </div>

                    <!-- Lista Wpisów Dropu dla Wybranej Tabeli -->
                    <div class="lg:col-span-2">
                        <div class="bg-gray-900/80 rounded-xl border border-gray-700 overflow-hidden">
                            <div class="p-3.5 bg-gray-900 border-b border-gray-700 flex justify-between items-center">
                                <h4 class="font-bold text-gray-200 text-sm flex items-center gap-2">
                                    <i class="fa-solid fa-table-list text-amber-500"></i> Wpisy w tabeli ({{ $entries->count() }})
                                </h4>
                                <div class="text-xs text-gray-400 font-mono">
                                    Suma Wag: <span class="text-amber-400 font-bold">{{ number_format($totalWeight) }}</span>
                                </div>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="bg-gray-950 border-b border-gray-800 text-gray-400 uppercase text-[11px] font-extrabold tracking-wider">
                                            <th class="p-3">Typ</th>
                                            <th class="p-3">Przedmiot / Referencja</th>
                                            <th class="p-3 text-center">Ilość</th>
                                            <th class="p-3 text-center">Waga</th>
                                            <th class="p-3 text-center">Szansa %</th>
                                            <th class="p-3 text-right">Akcje</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-800">
                                        @foreach($entries as $entry)
                                            @php
                                                $chance = $totalWeight > 0 ? number_format(($entry->weight / $totalWeight) * 100, 2) : 0;
                                            @endphp
                                            <tr class="hover:bg-gray-800/60 transition {{ $editingEntryId === $entry->id ? 'bg-amber-950/30' : '' }}">
                                                <td class="p-3">
                                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-xs font-bold
                                                        {{ $entry->reward_type === 'gold' ? 'bg-yellow-950 text-yellow-400 border border-yellow-700/50' : '' }}
                                                        {{ $entry->reward_type === 'exp' ? 'bg-purple-950 text-purple-400 border border-purple-700/50' : '' }}
                                                        {{ $entry->reward_type === 'gems' ? 'bg-blue-950 text-blue-400 border border-blue-700/50' : '' }}
                                                        {{ in_array($entry->reward_type, ['item', 'material']) ? 'bg-emerald-950 text-emerald-400 border border-emerald-700/50' : '' }}
                                                    ">
                                                        @if($entry->reward_type === 'gold') <i class="fa-solid fa-coins"></i> Złoto
                                                        @elseif($entry->reward_type === 'exp') <i class="fa-solid fa-star"></i> EXP
                                                        @elseif($entry->reward_type === 'gems') <i class="fa-solid fa-gem"></i> Gemy
                                                        @elseif($entry->reward_type === 'material') <i class="fa-solid fa-hammer"></i> Materiał
                                                        @else <i class="fa-solid fa-shield-halved"></i> Przedmiot
                                                        @endif
                                                    </span>
                                                </td>

                                                <td class="p-3 text-sm">
                                                    @if(in_array($entry->reward_type, ['item', 'material']) && $entry->itemTemplate)
                                                        <div class="flex items-center gap-2">
                                                            @if($entry->itemTemplate->icon)
                                                                <img src="{{ asset('assets/items/' . $entry->itemTemplate->icon) }}" class="w-7 h-7 object-contain bg-gray-800 rounded p-0.5 border border-gray-700" alt="icon">
                                                            @endif
                                                            <div>
                                                                <div class="font-bold text-white">{{ $entry->itemTemplate->name }}</div>
                                                                <div class="text-[10px] text-gray-500 font-mono">{{ $entry->ref_ulid }}</div>
                                                            </div>
                                                        </div>
                                                    @elseif(in_array($entry->reward_type, ['item', 'material']))
                                                        <span class="text-amber-400 font-mono text-xs">{{ $entry->ref_ulid ?? 'Brak referencji' }}</span>
                                                    @else
                                                        <span class="text-gray-400 italic text-xs">Bezpośredni zasób ({{ strtoupper($entry->reward_type) }})</span>
                                                    @endif
                                                </td>

                                                <td class="p-3 text-center text-sm font-bold text-gray-200">
                                                    @if($entry->min_qty == $entry->max_qty)
                                                        {{ $entry->min_qty }}
                                                    @else
                                                        {{ $entry->min_qty }} - {{ $entry->max_qty }}
                                                    @endif
                                                </td>

                                                <td class="p-3 text-center font-bold text-amber-400 text-sm">
                                                    {{ $entry->weight }}
                                                </td>

                                                <td class="p-3 text-center">
                                                    <span class="inline-block font-mono text-xs font-bold px-2 py-0.5 rounded bg-gray-800 text-sky-300 border border-sky-900">
                                                        {{ $chance }}%
                                                    </span>
                                                </td>

                                                <td class="p-3 text-right space-x-1">
                                                    <button wire:click="editEntry({{ $entry->id }})" class="text-blue-400 hover:text-blue-300 p-1 font-bold text-xs">
                                                        Edytuj
                                                    </button>
                                                    <button wire:click="deleteEntry({{ $entry->id }})" class="text-red-400 hover:text-red-300 p-1 font-bold text-xs" onclick="confirm('Usunąć ten wpis z dropu?') || event.stopImmediatePropagation()">
                                                        Usuń
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                        @if($entries->isEmpty())
                                            <tr>
                                                <td colspan="6" class="p-8 text-center text-gray-500">
                                                    Ta tabela nie posiada jeszcze żadnych wpisów dropu. Dodaj pierwszy wpis po lewej stronie!
                                                </td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
