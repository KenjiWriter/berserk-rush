<div class="min-h-screen bg-gradient-to-b from-slate-900 via-slate-800 to-slate-900 text-amber-100 p-4 md:p-8">
    {{-- Header --}}
    <div class="max-w-4xl mx-auto flex items-center justify-between mb-8 bg-gradient-to-r from-red-900/40 to-slate-800/40 border border-red-800/50 rounded-xl p-4 shadow-2xl backdrop-blur-sm">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 bg-gradient-to-br from-red-600 to-red-900 rounded-lg border-2 border-red-500 shadow-[0_0_15px_rgba(220,38,38,0.5)] flex items-center justify-center text-3xl">
                🚩
            </div>
            <div>
                <h1 class="text-3xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-red-400 to-amber-300" style="font-family: 'Cinzel', serif;">Gildie</h1>
                <p class="text-red-300/80 text-sm">Zjednoczcie siły i walczcie razem.</p>
            </div>
        </div>

        <button wire:click="goTo('hub')" @click="$dispatch('location-leave')" class="bg-slate-700 hover:bg-slate-600 text-amber-100 px-4 py-2 rounded shadow transition">
            Powrót do Miasta
        </button>
    </div>

    <div class="max-w-4xl mx-auto">
        @if($viewMode === 'list')
            {{-- LIST OF GUILDS --}}
            <div class="bg-slate-800/80 border border-slate-700 rounded-xl p-6 shadow-2xl backdrop-blur-sm">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold text-amber-200">Dostępne Gildie</h2>
                    <button wire:click="setViewMode('create')" class="bg-gradient-to-r from-red-700 to-red-900 hover:from-red-600 hover:to-red-800 text-white px-4 py-2 rounded-lg shadow font-bold text-sm">
                        + Załóż Gildię
                    </button>
                </div>

                <div class="mb-4 flex gap-2">
                    <input type="text" wire:model.live.debounce.300ms="searchQuery" placeholder="Szukaj gildii..." class="bg-slate-900 border border-slate-600 rounded px-3 py-2 text-sm text-amber-100 flex-1 focus:outline-none focus:border-red-500">
                </div>

                @error('join')
                    <div class="bg-red-900/50 border border-red-500 text-red-200 px-4 py-2 rounded mb-4 text-sm">{{ $message }}</div>
                @enderror

                <div class="space-y-3">
                    @forelse($this->guilds as $guild)
                        <div class="flex items-center justify-between bg-slate-900/50 border border-slate-700 rounded-lg p-4 hover:border-red-800/50 transition">
                            <div>
                                <h3 class="text-lg font-bold text-amber-400">{{ $guild->name }} <span class="text-xs text-slate-400 bg-slate-800 px-2 py-0.5 rounded ml-2">Lvl {{ $guild->level }}</span></h3>
                                <p class="text-xs text-slate-400 mt-1">
                                    {{ $guild->title ?? 'Brak tytułu' }} •
                                    Min. Poziom: <span class="text-amber-200">{{ $guild->min_level }}</span> •
                                    Członkowie: <span class="text-amber-200">{{ $guild->members_count }}/{{ $guild->getMaxMembers() }}</span>
                                </p>
                            </div>
                            <div>
                                @if($guild->is_public)
                                    <button wire:click="joinGuild('{{ $guild->id }}')"
                                        wire:loading.attr="disabled" wire:target="joinGuild('{{ $guild->id }}')"
                                        wire:loading.class="opacity-50 cursor-not-allowed" wire:target="joinGuild('{{ $guild->id }}')"
                                        class="bg-green-700/80 hover:bg-green-600/80 border border-green-500 text-white px-3 py-1.5 rounded text-sm transition flex items-center justify-center">
                                        <span wire:loading.remove wire:target="joinGuild('{{ $guild->id }}')">Dołącz</span>
                                        <span wire:loading wire:target="joinGuild('{{ $guild->id }}')">
                                            <svg class="animate-spin inline-block h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                        </span>
                                    </button>
                                @else
                                    <span class="text-red-400 text-xs italic">Na zaproszenie</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8 text-slate-500 italic">Brak gildii. Bądź pierwszym który ją założy!</div>
                    @endforelse
                </div>
            </div>

        @elseif($viewMode === 'create')
            {{-- CREATE GUILD --}}
            <div class="bg-slate-800/80 border border-slate-700 rounded-xl p-6 shadow-2xl backdrop-blur-sm max-w-lg mx-auto">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold text-amber-200">Załóż nową Gildię</h2>
                    <button wire:click="setViewMode('list')" class="text-slate-400 hover:text-white text-sm">
                        Anuluj
                    </button>
                </div>

                @error('create')
                    <div class="bg-red-900/50 border border-red-500 text-red-200 px-4 py-2 rounded mb-4 text-sm">{{ $message }}</div>
                @enderror

                <div class="space-y-4">
                    <div>
                        <label class="block text-amber-500 text-xs font-bold mb-1 uppercase tracking-wider">Nazwa Gildii *</label>
                        <input type="text" wire:model="newGuildName" class="w-full bg-slate-900 border border-slate-600 rounded px-3 py-2 text-sm text-amber-100 focus:outline-none focus:border-red-500">
                        @error('newGuildName') <span class="text-red-400 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-amber-500 text-xs font-bold mb-1 uppercase tracking-wider">Tytuł / Krótki Opis</label>
                        <input type="text" wire:model="newGuildTitle" class="w-full bg-slate-900 border border-slate-600 rounded px-3 py-2 text-sm text-amber-100 focus:outline-none focus:border-red-500">
                        @error('newGuildTitle') <span class="text-red-400 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div class="flex gap-4">
                        <div class="flex-1">
                            <label class="block text-amber-500 text-xs font-bold mb-1 uppercase tracking-wider">Wymagany Poziom</label>
                            <input type="number" wire:model="newGuildMinLevel" min="1" max="100" class="w-full bg-slate-900 border border-slate-600 rounded px-3 py-2 text-sm text-amber-100 focus:outline-none focus:border-red-500">
                            @error('newGuildMinLevel') <span class="text-red-400 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div class="flex-1">
                            <label class="block text-amber-500 text-xs font-bold mb-1 uppercase tracking-wider">Widoczność</label>
                            <select wire:model="newGuildIsPublic" class="w-full bg-slate-900 border border-slate-600 rounded px-3 py-2 text-sm text-amber-100 focus:outline-none focus:border-red-500">
                                <option value="1">Publiczna (Każdy dołącza)</option>
                                <option value="0">Zamknięta (Na zaproszenie)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="mt-8 border-t border-slate-700 pt-6">
                    <div class="flex items-center justify-between bg-slate-900/50 rounded-lg p-3 border border-red-900/30 mb-4">
                        <span class="text-sm text-slate-300">Koszt założenia:</span>
                        <span class="text-cyan-400 font-bold flex items-center gap-1">150 <span class="text-lg">💎</span></span>
                    </div>

                    <button wire:click="createGuild"
                        wire:loading.attr="disabled" wire:target="createGuild"
                        wire:loading.class="opacity-50 cursor-not-allowed" wire:target="createGuild"
                        class="w-full bg-gradient-to-r from-red-700 to-red-900 hover:from-red-600 hover:to-red-800 text-white py-3 rounded-lg font-bold shadow-lg transition flex items-center justify-center gap-2">
                        <span wire:loading.remove wire:target="createGuild"><span>🚩</span> Utwórz Gildię</span>
                        <span wire:loading wire:target="createGuild"><svg class="animate-spin inline-block h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> Tworzenie...</span>
                    </button>
                </div>
            </div>

        @elseif($viewMode === 'panel')
            {{-- GUILD PANEL --}}
            @php $guild = $character->guild; @endphp
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                {{-- Left col: Info --}}
                <div class="md:col-span-1 space-y-6">
                    <div class="bg-slate-800/80 border border-slate-700 rounded-xl p-6 shadow-2xl backdrop-blur-sm">
                        <h2 class="text-2xl font-bold text-amber-400 mb-1" style="font-family: 'Cinzel', serif;">{{ $guild->name }}</h2>
                        <p class="text-sm text-slate-400 italic mb-4">{{ $guild->title ?? 'Brak tytułu' }}</p>

                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between border-b border-slate-700 pb-1">
                                <span class="text-slate-400">Poziom:</span>
                                <span class="text-amber-200 font-bold">{{ $guild->level }}</span>
                            </div>
                            <div class="flex justify-between border-b border-slate-700 pb-1">
                                <span class="text-slate-400">Wym. Poziom:</span>
                                <span class="text-amber-200">{{ $guild->min_level }}</span>
                            </div>
                            <div class="flex justify-between border-b border-slate-700 pb-1">
                                <span class="text-slate-400">Członkowie:</span>
                                <span class="text-amber-200">{{ $guild->members()->count() }} / {{ $guild->getMaxMembers() }}</span>
                            </div>
                            <div class="flex justify-between border-b border-slate-700 pb-1">
                                <span class="text-slate-400">Złoto Skarbiec:</span>
                                <span class="text-yellow-400">{{ number_format($guild->gold) }} / {{ number_format($guild->getMaxGold()) }}</span>
                            </div>
                            <div class="flex justify-between pb-1">
                                <span class="text-slate-400">Diamenty:</span>
                                <span class="text-cyan-400">{{ number_format($guild->gems) }} / {{ number_format($guild->getMaxGems()) }}</span>
                            </div>
                        </div>

                        <div class="mt-4 bg-slate-900 rounded p-2 text-center text-xs text-slate-400">
                            Postęp EXP do kolejnego poziomu:
                            <div class="w-full bg-slate-700 h-2 mt-1 rounded overflow-hidden">
                                @php
                                    $req = $guild->getRequiredXpForNextLevel();
                                    $pct = $req ? min(100, ($guild->xp / $req) * 100) : 100;
                                @endphp
                                <div class="bg-amber-500 h-full" style="width: {{ $pct }}%"></div>
                            </div>
                            <div class="mt-1">{{ number_format($guild->xp) }} / {{ $req ? number_format($req) : 'MAX' }}</div>
                        </div>
                    </div>

                    <button wire:click="leaveGuild"
                        wire:loading.attr="disabled" wire:target="leaveGuild"
                        wire:loading.class="opacity-50 cursor-not-allowed" wire:target="leaveGuild"
                        class="w-full bg-red-900/50 hover:bg-red-800/80 border border-red-700 text-red-200 py-2 rounded-lg text-sm font-bold transition">
                        <span wire:loading.remove wire:target="leaveGuild">Opuść Gildię</span>
                        <span wire:loading wire:target="leaveGuild"><svg class="animate-spin inline-block h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> Opuszczanie...</span>
                    </button>
                </div>

                {{-- Right col: Members & Bonuses --}}
                <div class="md:col-span-2 space-y-6">
                    @php $myMember = $guild->members->where('character_id', $character->id)->first(); @endphp
                    <div class="bg-slate-800/80 border border-slate-700 rounded-xl p-6 shadow-2xl backdrop-blur-sm">
                        <div class="flex gap-4 border-b border-slate-700 pb-2 mb-4">
                            <button wire:click="setPanelTab('members')" class="text-lg font-bold {{ $panelTab === 'members' ? 'text-amber-200 underline decoration-amber-500' : 'text-slate-400 hover:text-amber-300' }}">Członkowie Gildii</button>
                            <span class="text-slate-600">|</span>
                            <button wire:click="setPanelTab('wars')" class="text-lg font-bold {{ $panelTab === 'wars' ? 'text-amber-200 underline decoration-amber-500' : 'text-slate-400 hover:text-amber-300' }}">Wojny Gildii</button>
                            @if($myMember && $myMember->role === 'leader')
                                <span class="text-slate-600">|</span>
                                <button wire:click="setPanelTab('logs')" class="text-lg font-bold {{ $panelTab === 'logs' ? 'text-amber-200 underline decoration-amber-500' : 'text-slate-400 hover:text-amber-300' }}">Logi Gildii (Tylko Lider)</button>
                            @endif
                        </div>
                        
                        @if($panelTab === 'members')
                            <div class="mb-2">
                                @error('roster')
                                    <div class="bg-red-900/50 border border-red-500 text-red-200 px-4 py-2 rounded mb-4 text-sm">{{ $message }}</div>
                                @enderror
                                <div class="text-xs text-slate-400 mb-2">Drużyna wojenna: {{ count($guild->war_team ?? []) }}/5</div>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-sm text-slate-300">
                                    <thead class="text-xs uppercase bg-slate-900/50 text-slate-500">
                                        <tr>
                                            <th class="px-4 py-2">Gracz</th>
                                            <th class="px-4 py-2">Rola</th>
                                            <th class="px-4 py-2">Poz.</th>
                                            @if($myMember && $myMember->role === 'leader')
                                                <th class="px-4 py-2 text-right">Akcje</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $warTeam = $guild->war_team ?? []; @endphp
                                        @foreach($guild->members()->with('character')->get() as $member)
                                            @php $inWar = in_array($member->character_id, $warTeam); @endphp
                                            <tr class="border-b border-slate-700/50 hover:bg-slate-700/30">
                                                <td class="px-4 py-2 font-bold text-amber-400">
                                                    {{ $member->character->name }}
                                                    @if($inWar)
                                                        <span class="text-red-500 ml-1" title="W drużynie wojennej">⚔️</span>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-2 capitalize text-amber-600">{{ $member->role }}</td>
                                                <td class="px-4 py-2">{{ $member->character->level }}</td>
                                                @if($myMember && $myMember->role === 'leader')
                                                    <td class="px-4 py-2 text-right">
                                                        <button wire:click="toggleWarRoster('{{ $member->character_id }}')"
                                                                wire:loading.attr="disabled"
                                                                class="text-xs border px-2 py-1 rounded transition {{ $inWar ? 'border-red-600 text-red-400 hover:bg-red-900/30' : 'border-slate-500 text-slate-400 hover:bg-slate-700' }}">
                                                            {{ $inWar ? 'Wycofaj z wojny' : 'Dodaj do wojny' }}
                                                        </button>
                                                    </td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @elseif($panelTab === 'logs')
                            <div class="overflow-x-auto max-h-96 overflow-y-auto">
                                <table class="w-full text-left text-sm text-slate-300">
                                    <thead class="text-xs uppercase bg-slate-900/50 text-slate-500 sticky top-0">
                                        <tr>
                                            <th class="px-4 py-2">Data</th>
                                            <th class="px-4 py-2">Gracz</th>
                                            <th class="px-4 py-2">Akcja</th>
                                            <th class="px-4 py-2">Wartość</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($this->logs as $log)
                                            <tr class="border-b border-slate-700/50 hover:bg-slate-700/30">
                                                <td class="px-4 py-2 text-xs text-slate-500">{{ $log->created_at->format('Y-m-d H:i') }}</td>
                                                <td class="px-4 py-2 font-bold text-amber-400">{{ $log->character->name }}</td>
                                                <td class="px-4 py-2">
                                                    @if($log->action === 'donate_exp') <span class="text-emerald-400">Dotacja EXP</span>
                                                    @elseif($log->action === 'donate_gold') <span class="text-yellow-400">Wpłata Złota</span>
                                                    @elseif($log->action === 'donate_gems') <span class="text-cyan-400">Wpłata Diamentów</span>
                                                    @else {{ $log->action }} @endif
                                                </td>
                                                <td class="px-4 py-2 font-mono text-amber-200">+{{ number_format($log->amount) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="px-4 py-4 text-center text-slate-500 italic">Brak zapisów w logach.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        @elseif($panelTab === 'wars')
                            <div class="space-y-4">
                                @forelse($this->wars as $war)
                                    <div class="bg-slate-900/50 border border-slate-700 rounded-lg p-4">
                                        <div class="flex justify-between items-center mb-3">
                                            <div class="flex items-center gap-4">
                                                <span class="font-bold {{ $war->challenger_guild_id === $guild->id ? 'text-blue-400' : 'text-red-400' }}">
                                                    {{ $war->challengerGuild->name }}
                                                </span>
                                                <span class="text-slate-500 font-bold text-sm">VS</span>
                                                <span class="font-bold {{ $war->defender_guild_id === $guild->id ? 'text-blue-400' : 'text-red-400' }}">
                                                    {{ $war->defenderGuild->name }}
                                                </span>
                                            </div>
                                            <div>
                                                @if($war->status === 'finished')
                                                    @if($war->winner_guild_id === $guild->id)
                                                        <span class="text-green-400 font-bold border border-green-500/50 bg-green-900/30 px-2 py-1 rounded text-xs">Zwycięstwo</span>
                                                    @else
                                                        <span class="text-red-400 font-bold border border-red-500/50 bg-red-900/30 px-2 py-1 rounded text-xs">Porażka</span>
                                                    @endif
                                                @else
                                                    <span class="text-amber-400 font-bold border border-amber-500/50 bg-amber-900/30 px-2 py-1 rounded text-xs">W trakcie</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="text-xs text-slate-400 mb-3 border-b border-slate-700/50 pb-2">
                                            Data: {{ $war->created_at->format('Y-m-d H:i') }}
                                        </div>
                                        
                                        @if($war->fights->count() > 0)
                                            <div class="grid grid-cols-5 gap-2">
                                                @foreach($war->fights as $fight)
                                                    @php 
                                                        $amIChallenger = $war->challenger_guild_id === $guild->id;
                                                        $myCharId = $amIChallenger ? $fight->challenger_character_id : $fight->defender_character_id;
                                                        $won = $fight->winner_character_id === $myCharId;
                                                    @endphp
                                                    <a href="{{ route('city.arena.combat.gvg', ['character' => $character, 'gvgId' => $fight->id]) }}" wire:navigate class="block border {{ $won ? 'border-green-600/50 bg-green-900/20 hover:bg-green-800/40' : 'border-red-600/50 bg-red-900/20 hover:bg-red-800/40' }} rounded p-2 text-center transition">
                                                        <div class="text-[10px] text-slate-400 mb-1">Runda {{ $fight->fight_order }}</div>
                                                        <div class="text-sm font-bold {{ $won ? 'text-green-400' : 'text-red-400' }} mb-1">{{ $won ? 'W' : 'P' }}</div>
                                                        <div class="text-xs text-amber-200 truncate">▶ Obejrzyj</div>
                                                    </a>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="text-xs text-slate-500 italic text-center">Brak rozegranych walk.</div>
                                        @endif
                                    </div>
                                @empty
                                    <div class="text-center py-8 text-slate-500 italic">Twoja gildia nie brała jeszcze udziału w żadnych wojnach.</div>
                                @endforelse
                            </div>
                        @endif
                    </div>

                    <div class="bg-slate-800/80 border border-slate-700 rounded-xl p-6 shadow-2xl backdrop-blur-sm">
                        <h3 class="text-lg font-bold text-amber-200 border-b border-slate-700 pb-2 mb-4">Bonusy Pasywne</h3>
                        
                        @error('upgrade')
                            <div class="bg-red-900/50 border border-red-500 text-red-200 px-4 py-2 rounded mb-4 text-sm">{{ $message }}</div>
                        @enderror

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="bg-slate-900 p-4 rounded-lg border border-slate-700 flex flex-col justify-between">
                                <div>
                                    <div class="flex justify-between items-start mb-2">
                                        <div class="text-emerald-400 font-bold text-lg">Bonus EXP: +{{ $guild->bonus_xp_level }}%</div>
                                        <span class="text-xs text-slate-500 bg-slate-800 px-2 py-1 rounded">Poziom {{ $guild->bonus_xp_level }}/20</span>
                                    </div>
                                    <div class="text-xs text-slate-400 mb-4">Zwiększa otrzymywane doświadczenie ze wszystkich źródeł walki dla członków gildii.</div>
                                </div>
                                @if($myMember && $myMember->role === 'leader')
                                    @if($guild->bonus_xp_level < 20)
                                        @php
                                            $costGold = (int)(10000 * pow($guild->bonus_xp_level + 1, 1.5));
                                            $costGems = (int)(100 * pow($guild->bonus_xp_level + 1, 1.2));
                                        @endphp
                                        <div class="flex gap-2">
                                            <button wire:click="upgradeBonus('xp', 'gold')"
                                                wire:loading.attr="disabled" wire:target="upgradeBonus"
                                                wire:loading.class="opacity-50 cursor-not-allowed" wire:target="upgradeBonus"
                                                class="flex-1 bg-slate-800 hover:bg-slate-700 border border-slate-600 rounded p-2 transition flex flex-col items-center justify-center">
                                                <span wire:loading.remove wire:target="upgradeBonus('xp', 'gold')" class="flex flex-col items-center">
                                                    <span class="text-xs text-amber-200 font-bold mb-1">Ulepsz (+1%)</span>
                                                    <span class="text-[10px] {{ $guild->gold >= $costGold ? 'text-yellow-400' : 'text-red-400' }}">{{ number_format($costGold) }} Złota</span>
                                                </span>
                                                <span wire:loading wire:target="upgradeBonus('xp', 'gold')">
                                                    <svg class="animate-spin h-5 w-5 text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                                </span>
                                            </button>
                                            <button wire:click="upgradeBonus('xp', 'gems')"
                                                wire:loading.attr="disabled" wire:target="upgradeBonus"
                                                wire:loading.class="opacity-50 cursor-not-allowed" wire:target="upgradeBonus"
                                                class="flex-1 bg-slate-800 hover:bg-slate-700 border border-slate-600 rounded p-2 transition flex flex-col items-center justify-center">
                                                <span wire:loading.remove wire:target="upgradeBonus('xp', 'gems')" class="flex flex-col items-center">
                                                    <span class="text-xs text-amber-200 font-bold mb-1">Ulepsz (+1%)</span>
                                                    <span class="text-[10px] {{ $guild->gems >= $costGems ? 'text-cyan-400' : 'text-red-400' }}">{{ number_format($costGems) }} 💎</span>
                                                </span>
                                                <span wire:loading wire:target="upgradeBonus('xp', 'gems')">
                                                    <svg class="animate-spin h-5 w-5 text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                                </span>
                                            </button>
                                        </div>
                                    @else
                                        <div class="w-full bg-emerald-900/50 border border-emerald-700 text-emerald-400 rounded p-2 text-center text-xs font-bold">Maksymalny Poziom</div>
                                    @endif
                                @endif
                            </div>

                            <div class="bg-slate-900 p-4 rounded-lg border border-slate-700 flex flex-col justify-between">
                                <div>
                                    <div class="flex justify-between items-start mb-2">
                                        <div class="text-yellow-400 font-bold text-lg">Bonus Złota: +{{ $guild->bonus_gold_level }}%</div>
                                        <span class="text-xs text-slate-500 bg-slate-800 px-2 py-1 rounded">Poziom {{ $guild->bonus_gold_level }}/20</span>
                                    </div>
                                    <div class="text-xs text-slate-400 mb-4">Zwiększa ilość zdobywanego złota podczas walk dla wszystkich członków gildii.</div>
                                </div>
                                @if($myMember && $myMember->role === 'leader')
                                    @if($guild->bonus_gold_level < 20)
                                        @php
                                            $costGold = (int)(10000 * pow($guild->bonus_gold_level + 1, 1.5));
                                            $costGems = (int)(100 * pow($guild->bonus_gold_level + 1, 1.2));
                                        @endphp
                                        <div class="flex gap-2">
                                            <button wire:click="upgradeBonus('gold', 'gold')"
                                                wire:loading.attr="disabled" wire:target="upgradeBonus"
                                                wire:loading.class="opacity-50 cursor-not-allowed" wire:target="upgradeBonus"
                                                class="flex-1 bg-slate-800 hover:bg-slate-700 border border-slate-600 rounded p-2 transition flex flex-col items-center justify-center">
                                                <span wire:loading.remove wire:target="upgradeBonus('gold', 'gold')" class="flex flex-col items-center">
                                                    <span class="text-xs text-amber-200 font-bold mb-1">Ulepsz (+1%)</span>
                                                    <span class="text-[10px] {{ $guild->gold >= $costGold ? 'text-yellow-400' : 'text-red-400' }}">{{ number_format($costGold) }} Złota</span>
                                                </span>
                                                <span wire:loading wire:target="upgradeBonus('gold', 'gold')">
                                                    <svg class="animate-spin h-5 w-5 text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                                </span>
                                            </button>
                                            <button wire:click="upgradeBonus('gold', 'gems')"
                                                wire:loading.attr="disabled" wire:target="upgradeBonus"
                                                wire:loading.class="opacity-50 cursor-not-allowed" wire:target="upgradeBonus"
                                                class="flex-1 bg-slate-800 hover:bg-slate-700 border border-slate-600 rounded p-2 transition flex flex-col items-center justify-center">
                                                <span wire:loading.remove wire:target="upgradeBonus('gold', 'gems')" class="flex flex-col items-center">
                                                    <span class="text-xs text-amber-200 font-bold mb-1">Ulepsz (+1%)</span>
                                                    <span class="text-[10px] {{ $guild->gems >= $costGems ? 'text-cyan-400' : 'text-red-400' }}">{{ number_format($costGems) }} 💎</span>
                                                </span>
                                                <span wire:loading wire:target="upgradeBonus('gold', 'gems')">
                                                    <svg class="animate-spin h-5 w-5 text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                                </span>
                                            </button>
                                        </div>
                                    @else
                                        <div class="w-full bg-yellow-900/50 border border-yellow-700 text-yellow-400 rounded p-2 text-center text-xs font-bold">Maksymalny Poziom</div>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
