<div class="p-6 text-gray-100">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-amber-500">Zarządzanie Zasadami Ulepszeń (Kuźnia)</h2>
        <a href="{{ route('admin.dashboard') }}" class="bg-gray-700 hover:bg-gray-600 px-4 py-2 rounded text-sm font-bold">Wróć do panelu</a>
    </div>

    @if (session()->has('message'))
        <div class="bg-green-600 text-white p-3 rounded mb-4 shadow">
            {{ session('message') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Form -->
        <div class="bg-gray-800 p-6 rounded shadow border border-gray-700 h-max">
            <h3 class="text-xl font-bold mb-4 text-blue-300">{{ $editingId ? 'Edytuj Zasadę' : 'Nowa Zasada' }}</h3>
            
            <form wire:submit.prevent="save">
                <div class="mb-4">
                    <label class="block text-sm font-bold mb-1">Dotyczy (Typ/Slot)</label>
                    <select wire:model="applies_to" class="w-full bg-gray-900 border border-gray-600 rounded p-2 text-white" required>
                        <option value="slot">Slot</option>
                        <option value="type">Typ</option>
                        <option value="template">Szablon przedmiotu (ID)</option>
                        <option value="rarity">Rzadkość</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-bold mb-1">Wartość (np. weapon, armor, legendary)</label>
                    <input type="text" wire:model="applies_value" class="w-full bg-gray-900 border border-gray-600 rounded p-2 text-white" required>
                </div>
                
                <div class="flex gap-4 mb-4">
                    <div class="flex-1">
                        <label class="block text-sm font-bold mb-1">Z poziomu</label>
                        <input type="number" wire:model="from_level" class="w-full bg-gray-900 border border-gray-600 rounded p-2 text-white" min="0" required>
                    </div>
                    <div class="flex-1">
                        <label class="block text-sm font-bold mb-1">Na poziom</label>
                        <input type="number" wire:model="to_level" class="w-full bg-gray-900 border border-gray-600 rounded p-2 text-white" min="1" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-bold mb-1">Szansa na Sukces (0.00 - 1.00)</label>
                    <input type="number" step="0.01" wire:model="success_chance" class="w-full bg-gray-900 border border-gray-600 rounded p-2 text-white" min="0" max="1" required>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-bold mb-1">Przy Porażce (Co się dzieje?)</label>
                    <select wire:model="on_fail" class="w-full bg-gray-900 border border-gray-600 rounded p-2 text-white" required>
                        <option value="nothing">Zniszczenie ulepszaczy (Przedmiot nietknięty)</option>
                        <option value="downgrade">Spadek poziomu ulepszenia (-1)</option>
                        <option value="break">Zniszczenie przedmiotu</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-bold mb-1">Koszt Złota</label>
                    <input type="number" wire:model="gold_cost" class="w-full bg-gray-900 border border-gray-600 rounded p-2 text-white" min="0" required>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-bold mb-2 text-amber-400">Wymagane Materiały (Ulepszacze)</label>
                    
                    @foreach($materials as $index => $ingredient)
                        <div class="flex gap-2 mb-2 items-center bg-gray-900 p-2 rounded border border-gray-700">
                            <div class="flex-grow">
                                <select wire:model="materials.{{ $index }}.template_id" class="w-full bg-gray-800 border border-gray-600 rounded p-1 text-sm text-white" required>
                                    <option value="">Wybierz materiał...</option>
                                    @foreach($templates->where('type', 'material') as $mat)
                                        <option value="{{ $mat->id }}">{{ $mat->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="w-24">
                                <input type="number" wire:model="materials.{{ $index }}.quantity" class="w-full bg-gray-800 border border-gray-600 rounded p-1 text-sm text-white" min="1" placeholder="Ilość" required>
                            </div>
                            <button type="button" wire:click="removeMaterial({{ $index }})" class="bg-red-600 hover:bg-red-500 text-white p-1 px-2 rounded font-bold text-sm">X</button>
                        </div>
                    @endforeach

                    <button type="button" wire:click="addMaterial" class="mt-2 w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-1 px-2 rounded text-sm transition">
                        + Dodaj materiał
                    </button>
                </div>

                <div class="flex gap-2 mt-6">
                    <button type="submit" class="bg-green-600 hover:bg-green-500 text-white font-bold py-2 px-4 rounded w-full transition">
                        Zapisz
                    </button>
                    @if($editingId)
                        <button type="button" wire:click="resetForm" class="bg-gray-600 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded transition">
                            Anuluj
                        </button>
                    @endif
                </div>
            </form>
        </div>

        <!-- List -->
        <div class="lg:col-span-2 space-y-4">
            @foreach($rulesList as $rule)
                <div class="bg-gray-800 p-4 rounded shadow border border-gray-700 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <div class="font-bold text-lg text-amber-400">
                            +{{ $rule->from_level }} -> +{{ $rule->to_level }} <span class="text-sm text-gray-400">({{ $rule->applies_to }} = {{ $rule->applies_value }})</span>
                        </div>
                        <div class="text-sm text-yellow-500 mt-1">Szansa: {{ $rule->success_chance * 100 }}% | Kara: {{ $rule->on_fail }}</div>
                        <div class="text-sm text-yellow-500 mb-2">Koszt: 🪙 {{ $rule->cost['gold'] ?? 0 }}</div>
                        
                        <div class="text-sm text-gray-400">
                            <strong>Materiały:</strong>
                            <ul class="list-disc pl-5 mt-1">
                                @forelse($rule->cost['materials'] ?? [] as $mat)
                                    @php
                                        $matTpl = $templates->firstWhere('id', $mat['template_id']);
                                    @endphp
                                    <li>{{ $matTpl ? $matTpl->name : $mat['template_id'] }} x{{ $mat['quantity'] }}</li>
                                @empty
                                    <li>Brak</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                    
                    <div class="flex gap-2 w-full sm:w-auto">
                        <button wire:click="edit('{{ $rule->id }}')" class="flex-1 sm:flex-none bg-blue-600 hover:bg-blue-500 px-3 py-1 rounded font-bold text-sm transition">Edytuj</button>
                        <button wire:click="delete('{{ $rule->id }}')" class="flex-1 sm:flex-none bg-red-600 hover:bg-red-500 px-3 py-1 rounded font-bold text-sm transition" onclick="return confirm('Na pewno?')">Usuń</button>
                    </div>
                </div>
            @endforeach

            @if($rulesList->isEmpty())
                <div class="text-gray-500 italic text-center p-8 bg-gray-800 rounded border border-gray-700">
                    Brak utworzonych zasad ulepszeń.
                </div>
            @endif
        </div>
    </div>
</div>
