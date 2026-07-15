<div class="min-h-screen bg-gray-900 text-gray-100 p-8">
    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-amber-500">⚒️ Zarządzanie Ulepszeniami (Kuźnia)</h1>
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
                
                <h2 class="text-xl font-bold mb-4 border-b border-gray-700 pb-2 text-amber-400">
                    {{ $editingId ? 'Edytuj Ulepszenie' : 'Dodaj Ulepszenie' }}
                </h2>

                <form wire:submit.prevent="save">
                    <div class="mb-4">
                        <label class="block text-gray-400 text-sm font-bold mb-2">Przedmiot do ulepszenia</label>
                        <select wire:model="template_id" class="shadow border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white focus:outline-none focus:border-amber-500" required>
                            <option value="">-- Wybierz Przedmiot --</option>
                            @foreach($upgradeableItems as $item)
                                <option value="{{ $item->id }}">{{ $item->name }} (Typ: {{ $item->type }})</option>
                            @endforeach
                        </select>
                        @error('template_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-400 text-sm font-bold mb-2">Na jaki poziom? (+1 do +9)</label>
                        <select wire:model="to_level" class="shadow border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white focus:outline-none focus:border-amber-500" required>
                            @for($i = 1; $i <= 9; $i++)
                                <option value="{{ $i }}">+{{ $i }} (z +{{ $i - 1 }})</option>
                            @endfor
                        </select>
                        @error('to_level') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-gray-400 text-sm font-bold mb-2">Szansa na Sukces (0-1)</label>
                            <input type="number" step="0.01" wire:model="success_chance" class="shadow border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white focus:outline-none focus:border-amber-500" min="0" max="1" required>
                            @error('success_chance') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-gray-400 text-sm font-bold mb-2">Koszt Złota</label>
                            <input type="number" wire:model="gold_cost" class="shadow border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white focus:outline-none focus:border-amber-500" min="0" required>
                            @error('gold_cost') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-400 text-sm font-bold mb-2">Przy Porażce (Co się dzieje?)</label>
                        <select wire:model="on_fail" class="shadow border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-white focus:outline-none focus:border-amber-500" required>
                            <option value="nothing">Utrata ulepszaczy (Przedmiot nietknięty)</option>
                            <option value="downgrade">Spadek poziomu ulepszenia (-1)</option>
                            <option value="break">Zniszczenie przedmiotu</option>
                        </select>
                        @error('on_fail') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-6">
                        <label class="block text-amber-400 text-sm font-bold mb-2">Wymagane Materiały (Ulepszacze)</label>
                        
                        @foreach($materials as $index => $ingredient)
                            <div class="flex gap-2 mb-2 items-center bg-gray-900 p-2 rounded border border-gray-700">
                                <div class="flex-grow">
                                    <select wire:model="materials.{{ $index }}.template_id" class="w-full bg-gray-800 border border-gray-600 rounded p-1 text-sm text-white focus:border-amber-500 focus:outline-none" required>
                                        <option value="">Wybierz materiał...</option>
                                        @foreach($templates->where('type', 'material') as $mat)
                                            <option value="{{ $mat->id }}">{{ $mat->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="w-20">
                                    <input type="number" wire:model="materials.{{ $index }}.quantity" class="w-full bg-gray-800 border border-gray-600 rounded p-1 text-sm text-white text-center focus:border-amber-500 focus:outline-none" min="1" placeholder="Ilość" required>
                                </div>
                                <button type="button" wire:click="removeMaterial({{ $index }})" class="bg-red-600 hover:bg-red-500 text-white p-1 px-2 rounded font-bold text-sm transition" title="Usuń">X</button>
                            </div>
                        @endforeach

                        <button type="button" wire:click="addMaterial" class="mt-2 w-full bg-blue-600/30 border border-blue-500/50 hover:bg-blue-600/50 text-blue-200 font-bold py-1.5 px-2 rounded text-sm transition">
                            + Dodaj wymagany materiał
                        </button>
                    </div>

                    <div class="flex justify-between">
                        <button type="submit" class="bg-amber-600 hover:bg-amber-500 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition">
                            Zapisz Ulepszenie
                        </button>
                        @if($editingId)
                            <button type="button" wire:click="resetForm" class="bg-gray-600 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded transition">
                                Anuluj
                            </button>
                        @endif
                    </div>
                </form>
            </div>

            <!-- Lista Ulepszeń -->
            <div class="lg:col-span-2">
                <div class="bg-gray-800 rounded-lg shadow-lg border border-gray-700 overflow-hidden">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-900 border-b border-gray-700">
                                <th class="p-3 text-gray-400 font-bold uppercase text-sm">Przedmiot</th>
                                <th class="p-3 text-gray-400 font-bold uppercase text-sm text-center">Poziom</th>
                                <th class="p-3 text-gray-400 font-bold uppercase text-sm text-center">Szansa</th>
                                <th class="p-3 text-gray-400 font-bold uppercase text-sm">Koszt / Materiały</th>
                                <th class="p-3 text-gray-400 font-bold uppercase text-sm text-right">Akcje</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rulesList->groupBy('applies_value') as $templateId => $itemRules)
                                @php 
                                    $itemTemplate = $upgradeableItems->firstWhere('id', $templateId);
                                @endphp
                                <!-- Nagłówek grupy przedmiotu -->
                                <tr class="bg-gray-700/50 border-b border-gray-700">
                                    <td colspan="5" class="p-3 font-bold text-amber-500">
                                        <div class="flex items-center gap-3">
                                            @if($itemTemplate && $itemTemplate->icon)
                                                <img src="{{ route('assets.items', ['filename' => $itemTemplate->icon]) }}" class="w-8 h-8 object-contain drop-shadow-md bg-gray-900 rounded p-1" alt="">
                                            @else
                                                <div class="w-8 h-8 bg-gray-900 rounded"></div>
                                            @endif
                                            {{ $itemTemplate ? $itemTemplate->name : $templateId }}
                                        </div>
                                    </td>
                                </tr>
                                
                                @foreach($itemRules as $rule)
                                    <tr class="border-b border-gray-700 hover:bg-gray-700/30 transition cursor-pointer" wire:click="edit('{{ $rule->id }}')">
                                        <td class="p-3 pl-8 text-gray-400 text-sm">
                                            Kara: 
                                            @if($rule->on_fail === 'nothing') <span class="text-gray-400">Brak</span>
                                            @elseif($rule->on_fail === 'downgrade') <span class="text-orange-400">Spadek poziomu</span>
                                            @else <span class="text-red-500">Zniszczenie</span> @endif
                                        </td>
                                        <td class="p-3 text-center text-white font-bold">
                                            +{{ $rule->from_level }} <span class="text-gray-500 text-xs mx-1">-></span> <span class="text-green-400">+{{ $rule->to_level }}</span>
                                        </td>
                                        <td class="p-3 text-center text-yellow-500 font-bold">
                                            {{ $rule->success_chance * 100 }}%
                                        </td>
                                        <td class="p-3">
                                            <div class="text-yellow-500 text-xs mb-1">🪙 {{ $rule->cost['gold'] ?? 0 }} Złota</div>
                                            <div class="flex flex-wrap gap-1">
                                                @forelse($rule->cost['materials'] ?? [] as $mat)
                                                    @php
                                                        $matTpl = $templates->firstWhere('id', $mat['template_id']);
                                                    @endphp
                                                    <span class="bg-gray-900 text-gray-300 text-[10px] px-1.5 py-0.5 rounded border border-gray-600">
                                                        {{ $matTpl ? $matTpl->name : $mat['template_id'] }} <span class="text-amber-500">x{{ $mat['quantity'] }}</span>
                                                    </span>
                                                @empty
                                                    <span class="text-gray-500 text-xs">Brak materiałów</span>
                                                @endforelse
                                            </div>
                                        </td>
                                        <td class="p-3 text-right">
                                            <button wire:click.stop="delete('{{ $rule->id }}')" class="text-red-400 hover:text-red-300 text-sm" onclick="confirm('Na pewno usunąć to ulepszenie?') || event.stopImmediatePropagation()">Usuń</button>
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                            @if($rulesList->isEmpty())
                                <tr>
                                    <td colspan="5" class="p-6 text-center text-gray-500">Brak zdefiniowanych zasad ulepszeń. Zdefiniuj nową zasadę w panelu obok.</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
