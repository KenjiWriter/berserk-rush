<div class="p-6 text-gray-100">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-amber-500">Zarządzanie Przepisami (Crafting)</h2>
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
            <h3 class="text-xl font-bold mb-4 text-blue-300">{{ $editingId ? 'Edytuj Przepis' : 'Nowy Przepis' }}</h3>
            
            <form wire:submit.prevent="save">
                <div class="mb-4">
                    <label class="block text-sm font-bold mb-1">Przedmiot Wynikowy</label>
                    <select wire:model="result_item_template_id" class="w-full bg-gray-900 border border-gray-600 rounded p-2 text-white" required>
                        <option value="">Wybierz przedmiot...</option>
                        @foreach($templates as $tpl)
                            <option value="{{ $tpl->id }}">{{ $tpl->name }} ({{ $tpl->type }})</option>
                        @endforeach
                    </select>
                    @error('result_item_template_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-bold mb-1">Koszt Złota</label>
                    <input type="number" wire:model="gold_cost" class="w-full bg-gray-900 border border-gray-600 rounded p-2 text-white" min="0" required>
                    @error('gold_cost') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-bold mb-2 text-amber-400">Składniki (Materiały)</label>
                    
                    @foreach($ingredients as $index => $ingredient)
                        <div class="flex gap-2 mb-2 items-center bg-gray-900 p-2 rounded border border-gray-700">
                            <div class="flex-grow">
                                <select wire:model="ingredients.{{ $index }}.template_id" class="w-full bg-gray-800 border border-gray-600 rounded p-1 text-sm text-white" required>
                                    <option value="">Wybierz materiał...</option>
                                    @foreach($templates->where('type', 'material') as $mat)
                                        <option value="{{ $mat->id }}">{{ $mat->name }}</option>
                                    @endforeach
                                    <option disabled>--- Inne ---</option>
                                    @foreach($templates->where('type', '!=', 'material') as $mat)
                                        <option value="{{ $mat->id }}">{{ $mat->name }}</option>
                                    @endforeach
                                </select>
                                @error('ingredients.'.$index.'.template_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                            <div class="w-24">
                                <input type="number" wire:model="ingredients.{{ $index }}.quantity" class="w-full bg-gray-800 border border-gray-600 rounded p-1 text-sm text-white" min="1" placeholder="Ilość" required>
                            </div>
                            <button type="button" wire:click="removeIngredient({{ $index }})" class="bg-red-600 hover:bg-red-500 text-white p-1 px-2 rounded font-bold text-sm">X</button>
                        </div>
                    @endforeach

                    <button type="button" wire:click="addIngredient" class="mt-2 w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-1 px-2 rounded text-sm transition">
                        + Dodaj składnik
                    </button>
                    @error('ingredients') <span class="text-red-500 text-xs block mt-1">{{ $message }}</span> @enderror
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
            @foreach($recipes as $recipe)
                <div class="bg-gray-800 p-4 rounded shadow border border-gray-700 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <div class="font-bold text-lg text-amber-400">
                            Wynik: <span class="text-white">{{ $recipe->resultItemTemplate->name ?? 'Nieznany ('.$recipe->result_item_template_id.')' }}</span>
                        </div>
                        <div class="text-sm text-yellow-500 mb-2">Koszt w złocie: 🪙 {{ $recipe->gold_cost }}</div>
                        
                        <div class="text-sm text-gray-400">
                            <strong>Składniki:</strong>
                            <ul class="list-disc pl-5 mt-1">
                                @foreach($recipe->ingredients as $ing)
                                    @php
                                        $ingTpl = $templates->firstWhere('id', $ing['template_id']);
                                    @endphp
                                    <li>{{ $ingTpl ? $ingTpl->name : $ing['template_id'] }} x{{ $ing['quantity'] }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    
                    <div class="flex gap-2 w-full sm:w-auto">
                        <button wire:click="edit('{{ $recipe->id }}')" class="flex-1 sm:flex-none bg-blue-600 hover:bg-blue-500 px-3 py-1 rounded font-bold text-sm transition">Edytuj</button>
                        <button wire:click="delete('{{ $recipe->id }}')" class="flex-1 sm:flex-none bg-red-600 hover:bg-red-500 px-3 py-1 rounded font-bold text-sm transition" onclick="return confirm('Na pewno?')">Usuń</button>
                    </div>
                </div>
            @endforeach

            @if($recipes->isEmpty())
                <div class="text-gray-500 italic text-center p-8 bg-gray-800 rounded border border-gray-700">
                    Brak utworzonych przepisów.
                </div>
            @endif
        </div>
    </div>
</div>
