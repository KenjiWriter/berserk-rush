<div class="min-h-screen bg-gray-900 text-gray-100 p-8">
    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-amber-500">Galeria - Zarządzanie</h1>
            <a href="{{ route('admin.dashboard') }}" class="text-gray-400 hover:text-white underline">
                &larr; Wróć do panelu
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            {{-- Upload Form --}}
            <div class="bg-gray-800 p-6 rounded-lg shadow-lg border border-gray-700 h-fit">
                <h2 class="text-xl font-bold mb-4 text-amber-400">Dodaj nowe zdjęcie</h2>
                
                <form wire:submit="save" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-1">Tytuł</label>
                        <input type="text" wire:model="title" class="w-full bg-gray-700 border-gray-600 rounded text-white px-3 py-2" placeholder="Krótki opis" required>
                        @error('title') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-1">Zdjęcie</label>
                        <input type="file" wire:model="photo" class="w-full text-sm text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-amber-600 file:text-white hover:file:bg-amber-500 cursor-pointer" accept="image/*" required>
                        @error('photo') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    
                    @if ($photo)
                        <div class="mt-4">
                            <p class="text-sm text-gray-400 mb-2">Podgląd:</p>
                            <img src="{{ $photo->temporaryUrl() }}" class="w-full h-auto rounded border border-gray-600">
                        </div>
                    @endif

                    <div class="pt-4">
                        <button type="submit" class="w-full bg-amber-600 hover:bg-amber-500 text-white font-bold py-2 px-4 rounded transition-colors" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="save">Dodaj do galerii</span>
                            <span wire:loading wire:target="save">Przesyłanie...</span>
                        </button>
                    </div>
                </form>
            </div>

            {{-- Gallery List --}}
            <div class="md:col-span-2 space-y-4">
                <h2 class="text-xl font-bold mb-4 text-amber-400">Aktualne zdjęcia ({{ $images->count() }})</h2>
                
                @if($images->isEmpty())
                    <div class="bg-gray-800 p-8 rounded-lg border border-gray-700 text-center text-gray-400">
                        Brak zdjęć w galerii. Dodaj pierwsze zdjęcie, aby pokazać graczom jak wygląda gra!
                    </div>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @foreach($images as $image)
                            <div class="bg-gray-800 rounded-lg border {{ $image->is_active ? 'border-gray-700' : 'border-red-900 opacity-60' }} overflow-hidden group">
                                <div class="relative h-48">
                                    <img src="{{ asset($image->image_path) }}" alt="{{ $image->title }}" class="w-full h-full object-cover">
                                    @if(!$image->is_active)
                                        <div class="absolute inset-0 bg-red-900/50 flex items-center justify-center">
                                            <span class="bg-red-900 text-white px-2 py-1 rounded text-sm font-bold">UKRYTE</span>
                                        </div>
                                    @endif
                                </div>
                                <div class="p-4">
                                    <h3 class="font-bold text-lg mb-2 truncate" title="{{ $image->title }}">{{ $image->title }}</h3>
                                    <div class="flex justify-between items-center mt-4">
                                        <button wire:click="toggleActive('{{ $image->id }}')" class="text-sm px-3 py-1 rounded border {{ $image->is_active ? 'border-yellow-600 text-yellow-500 hover:bg-yellow-600 hover:text-white' : 'border-green-600 text-green-500 hover:bg-green-600 hover:text-white' }} transition-colors">
                                            {{ $image->is_active ? 'Ukryj' : 'Pokaż' }}
                                        </button>
                                        
                                        <button wire:click="deleteImage('{{ $image->id }}')" wire:confirm="Czy na pewno chcesz usunąć to zdjęcie?" class="text-sm px-3 py-1 rounded border border-red-600 text-red-500 hover:bg-red-600 hover:text-white transition-colors">
                                            Usuń
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
