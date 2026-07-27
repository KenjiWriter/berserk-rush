<div class="min-h-screen bg-gray-900 text-gray-100 p-8">
    <div class="max-w-7xl mx-auto space-y-8">
        {{-- Header & Navigation --}}
        <div class="flex justify-between items-center border-b border-gray-800 pb-4">
            <div>
                <h1 class="text-3xl font-bold text-amber-500 flex items-center gap-3">
                    <i class="fa-solid fa-calendar-star text-amber-400"></i>
                    Zarządzanie Eventami Weekendowymi
                </h1>
                <p class="text-gray-400 text-sm mt-1">Konfiguracja automatycznego losowania eventów co weekend oraz ręczne aktywacje mnożników.</p>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="text-gray-400 hover:text-white underline flex items-center gap-2">
                <i class="fa-solid fa-arrow-left"></i> Powrót do panelu
            </a>
        </div>

        {{-- Session Flash Messages --}}
        @if (session()->has('message'))
            <div class="bg-emerald-900/80 border border-emerald-500 text-emerald-200 p-4 rounded-lg shadow-lg flex items-center gap-3">
                <i class="fa-solid fa-circle-check text-xl text-emerald-400"></i>
                <span class="font-bold">{{ session('message') }}</span>
            </div>
        @endif

        @if (session()->has('error'))
            <div class="bg-red-900/80 border border-red-500 text-red-200 p-4 rounded-lg shadow-lg flex items-center gap-3">
                <i class="fa-solid fa-triangle-exclamation text-xl text-red-400"></i>
                <span class="font-bold">{{ session('error') }}</span>
            </div>
        @endif

        {{-- Status Bar / Currently Active Event Card --}}
        <div class="bg-gray-800 rounded-xl p-6 shadow-xl border border-gray-700 relative overflow-hidden">
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <span class="text-xs font-bold uppercase tracking-widest px-3 py-1 rounded-full border {{ $activeEvent ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40' : 'bg-gray-700 text-gray-400 border-gray-600' }}">
                            @if($activeEvent)
                                <i class="fa-solid fa-tower-broadcast text-emerald-400 animate-pulse mr-1"></i> Aktywny Event
                            @else
                                <i class="fa-solid fa-pause text-gray-400 mr-1"></i> Brak Aktywnego Eventu
                            @endif
                        </span>
                        <span class="text-xs text-gray-400 font-mono">
                            Tryb: <strong class="text-amber-400 uppercase">{{ $currentMode }}</strong>
                            @if($isWeekend)
                                <span class="ml-2 text-emerald-400 font-bold"><i class="fa-solid fa-calendar-check"></i> Dzisiaj jest weekend</span>
                            @else
                                <span class="ml-2 text-gray-500"><i class="fa-solid fa-calendar-day"></i> Dzień roboczy</span>
                            @endif
                        </span>
                    </div>

                    @if($activeEvent)
                        <h2 class="text-2xl font-extrabold text-white flex items-center gap-3">
                            <i class="{{ $activeEvent['icon'] }} {{ $activeEvent['color'] }}"></i>
                            {{ $activeEvent['name'] }}
                        </h2>
                        <p class="text-gray-300 mt-1 max-w-2xl text-sm">{{ $activeEvent['description'] }}</p>

                        @if($activeEvent['ends_at'])
                            <div class="mt-3 text-xs font-mono text-amber-300 flex items-center gap-2">
                                <i class="fa-solid fa-clock"></i>
                                <span>Wygasa: <strong>{{ \Carbon\Carbon::parse($activeEvent['ends_at'])->format('Y-m-d H:i:s') }}</strong></span>
                                <span>({{ \Carbon\Carbon::parse($activeEvent['ends_at'])->diffForHumans() }})</span>
                            </div>
                        @endif
                    @else
                        <h2 class="text-xl font-bold text-gray-400">Brak aktywnego eventu globalnego</h2>
                        <p class="text-gray-500 text-sm mt-1">Wybierz jeden z dostępnych eventów poniżej, aby aktywować go ręcznie, lub włącz tryb automatycznego losowania w weekendy.</p>
                    @endif
                </div>

                {{-- Global Controls --}}
                <div class="flex flex-wrap gap-3 shrink-0">
                    <button wire:click="activateAutoMode" 
                            class="px-4 py-2 bg-gradient-to-r from-blue-700 to-indigo-700 hover:from-blue-600 hover:to-indigo-600 text-white text-xs font-bold rounded-lg transition shadow flex items-center gap-2">
                        <i class="fa-solid fa-rotate"></i> Tryb Auto Weekend
                    </button>
                    
                    <button wire:click="activateForceAutoMode" 
                            class="px-4 py-2 bg-gradient-to-r from-purple-700 to-indigo-800 hover:from-purple-600 hover:to-indigo-700 text-white text-xs font-bold rounded-lg transition shadow flex items-center gap-2">
                        <i class="fa-solid fa-dice"></i> Wymuś Auto Event Teraz
                    </button>

                    <button wire:click="disableAllEvents" 
                            onclick="confirm('Na pewno wyłączyć wszystkie eventy?') || event.stopImmediatePropagation()"
                            class="px-4 py-2 bg-red-900/60 hover:bg-red-800 text-red-200 border border-red-700 text-xs font-bold rounded-lg transition shadow flex items-center gap-2">
                        <i class="fa-solid fa-power-off"></i> Wyłącz Eventy
                    </button>
                </div>
            </div>
        </div>

        {{-- Event Selection Grid --}}
        <div>
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-amber-400 flex items-center gap-2">
                    <i class="fa-solid fa-list-check"></i> Dostępne Eventy Weekendowe (1 z 5)
                </h2>

                <div class="flex items-center gap-3">
                    <label class="text-xs text-gray-400 font-bold">Czas trwania aktywacji:</label>
                    <select wire:model.live="durationHours" class="bg-gray-800 border border-gray-700 rounded text-xs text-white px-3 py-1 focus:border-amber-500">
                        <option value="12">12 godzin</option>
                        <option value="24">24 godziny (1 dzień)</option>
                        <option value="48">48 godzin (Cały Weekend)</option>
                        <option value="72">72 godziny (3 dni)</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($availableEvents as $key => $event)
                    @php
                        $isActiveThis = $activeEvent && $activeEvent['key'] === $key;
                    @endphp
                    <div class="bg-gray-800 rounded-xl p-5 border {{ $isActiveThis ? 'border-amber-500 shadow-[0_0_20px_rgba(245,158,11,0.3)] bg-gradient-to-b from-gray-800 to-amber-950/30' : 'border-gray-700 hover:border-gray-600' }} transition-all flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <div class="w-10 h-10 rounded-lg bg-gray-900 border border-gray-700 flex items-center justify-center text-xl shrink-0">
                                    <i class="{{ $event['icon'] }} {{ $event['color'] }}"></i>
                                </div>
                                <span class="text-xs font-mono font-bold px-2.5 py-0.5 rounded-full border {{ $event['badge_bg'] }}">
                                    Mnożnik: x{{ $event['multiplier'] }}
                                </span>
                            </div>

                            <h3 class="text-lg font-bold text-white mb-1">{{ $event['name'] }}</h3>
                            <p class="text-gray-400 text-xs leading-relaxed mb-4">{{ $event['description'] }}</p>
                        </div>

                        <div class="pt-3 border-t border-gray-700/60 flex items-center justify-between">
                            @if($isActiveThis)
                                <span class="text-xs font-bold text-amber-400 flex items-center gap-1.5">
                                    <i class="fa-solid fa-circle-check"></i> Włączony
                                </span>
                            @else
                                <span class="text-xs text-gray-500 font-mono">Nieaktywny</span>
                            @endif

                            <button wire:click="activateManualEvent('{{ $key }}')" 
                                    class="px-3 py-1.5 {{ $isActiveThis ? 'bg-amber-600 hover:bg-amber-500 text-stone-950 font-black' : 'bg-gray-700 hover:bg-amber-600 hover:text-stone-950 text-white font-bold' }} text-xs rounded transition flex items-center gap-1.5">
                                <i class="fa-solid fa-play"></i> Uruchom Ręcznie
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
