<div class="min-h-screen text-amber-100 relative overflow-hidden select-none"
     style="background: radial-gradient(circle at 50% 0%, #1c1917 0%, #0c0a09 60%, #050505 100%); font-family: 'Cinzel', serif;">
    
    {{-- Ambient Glow Overlay --}}
    <div class="absolute top-0 inset-x-0 h-64 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-amber-600/15 via-transparent to-transparent pointer-events-none"></div>

    <div class="relative container mx-auto px-4 py-6 sm:py-8 min-h-screen z-10 max-w-6xl">
        
        {{-- Header Section --}}
        <div class="flex flex-col md:flex-row items-center justify-between gap-4 mb-8 border-b-2 border-amber-900/60 pb-6 bg-gradient-to-b from-stone-950/80 to-transparent p-4 sm:p-6 rounded-2xl shadow-xl">
            <div class="flex items-center space-x-4">
                <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl bg-gradient-to-b from-amber-700 via-amber-900 to-stone-950 border-2 border-amber-400 flex items-center justify-center text-2xl sm:text-3xl text-amber-300 shadow-[0_0_20px_rgba(245,158,11,0.5)] shrink-0">
                    <i class="fa-solid fa-envelope-open-text"></i>
                </div>
                <div>
                    <h1 class="text-2xl sm:text-4xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-yellow-200 via-amber-400 to-amber-600 drop-shadow-md">KRÓLEWSKA POCZTA</h1>
                    <p class="text-xs sm:text-sm text-amber-300/70 font-sans tracking-wide">Wiadomości systemowe, powiadomienia z targowiska oraz załączniki i nagrody</p>
                </div>
            </div>
            
            <div class="flex flex-wrap items-center gap-4">
                {{-- Wallet Display --}}
                <div class="flex items-center gap-3 bg-stone-950/90 px-4 py-2 rounded-xl border-2 border-amber-800/80 shadow-[inset_0_2px_4px_rgba(0,0,0,0.8)]">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-coins text-amber-400 text-base"></i>
                        <div class="text-right">
                            <span class="text-[9px] text-amber-500 font-extrabold uppercase tracking-wider block leading-none font-sans">ZŁOTO</span>
                            <span class="font-extrabold text-yellow-300 text-sm sm:text-base drop-shadow">{{ number_format($character->gold) }}</span>
                        </div>
                    </div>
                    <div class="w-px h-7 bg-amber-900/60"></div>
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-gem text-purple-400 text-base"></i>
                        <div class="text-right">
                            <span class="text-[9px] text-purple-400 font-extrabold uppercase tracking-wider block leading-none font-sans">KLEJNOTY</span>
                            <span class="font-extrabold text-purple-300 text-sm sm:text-base drop-shadow">{{ number_format(auth()->user()->gems) }}</span>
                        </div>
                    </div>
                </div>

                {{-- Back Button --}}
                <button wire:click="backToCity" @click="$dispatch('location-leave', { text: 'Powrót do Miasta...', icon: 'fa-solid fa-archway', url: '{{ route('city.hub', $character->id) }}' }); $dispatch('play-audio', { type: 'tab' })"
                    class="px-4 py-2.5 min-h-[44px] rounded-xl bg-gradient-to-b from-slate-800 via-slate-900 to-stone-950 text-amber-200 font-extrabold text-xs uppercase tracking-widest border-2 border-slate-700 hover:border-amber-500 hover:text-yellow-100 shadow-[inset_0_1px_0_rgba(255,255,255,0.1),0_4px_10px_rgba(0,0,0,0.8)] transition-all duration-200 flex items-center justify-center gap-2 group cursor-pointer w-full sm:w-auto">
                    <i class="fa-solid fa-archway text-amber-400 group-hover:scale-110 transition-transform"></i>
                    <span>Powrót do Miasta</span>
                </button>
            </div>
        </div>

        {{-- Navigation Tabs & Mass Action Bar --}}
        @php
            $unreadCount = \App\Infrastructure\Persistence\Mail::where('to_character_id', $character->id)->where('claimed', false)->count();
            $claimedCount = \App\Infrastructure\Persistence\Mail::where('to_character_id', $character->id)->where('claimed', true)->count();
        @endphp

        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4 border-b-2 border-amber-900/60 mb-6 pb-2 sm:pb-0">
            <div class="flex space-x-2 sm:space-x-3">
                <button wire:click="switchTab('unclaimed')" 
                        @click="$dispatch('play-audio', { type: 'tab' })"
                        @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                        class="px-4 sm:px-6 py-3 min-h-[44px] font-extrabold text-xs tracking-widest uppercase rounded-t-xl transition-all duration-200 border-t-2 border-x-2 flex items-center justify-center gap-2.5 cursor-pointer {{ $activeTab === 'unclaimed' ? 'bg-gradient-to-b from-amber-900/90 via-stone-900 to-stone-950 text-yellow-300 border-amber-500 shadow-[0_-5px_15px_rgba(245,158,11,0.2)]' : 'bg-stone-950/60 text-stone-400 border-stone-800 hover:text-amber-200 hover:bg-stone-900' }}">
                    <i class="fa-solid fa-inbox {{ $activeTab === 'unclaimed' ? 'text-amber-400' : 'text-stone-500' }}"></i>
                    <span>Nieodebrane</span>
                    @if($unreadCount > 0)
                        <span class="ml-1 bg-amber-500 text-stone-950 font-extrabold text-[10px] px-2 py-0.5 rounded-full font-sans shadow">{{ $unreadCount }}</span>
                    @endif
                </button>
                
                <button wire:click="switchTab('all')" 
                        @click="$dispatch('play-audio', { type: 'tab' })"
                        @mouseenter="$dispatch('play-audio', { type: 'hover' })"
                        class="px-4 sm:px-6 py-3 min-h-[44px] font-extrabold text-xs tracking-widest uppercase rounded-t-xl transition-all duration-200 border-t-2 border-x-2 flex items-center justify-center gap-2.5 cursor-pointer {{ $activeTab === 'all' ? 'bg-gradient-to-b from-amber-900/90 via-stone-900 to-stone-950 text-yellow-300 border-amber-500 shadow-[0_-5px_15px_rgba(245,158,11,0.2)]' : 'bg-stone-950/60 text-stone-400 border-stone-800 hover:text-amber-200 hover:bg-stone-900' }}">
                    <i class="fa-solid fa-box-archive {{ $activeTab === 'all' ? 'text-amber-400' : 'text-stone-500' }}"></i>
                    <span>Wszystkie</span>
                </button>
            </div>

            {{-- Quick Action Buttons --}}
            <div class="flex items-center gap-2 pb-2 sm:pb-0">
                @if($unreadCount > 0)
                    <button wire:click="claimAll"
                            wire:loading.attr="disabled"
                            @click="$dispatch('play-audio', { type: 'button' })"
                            class="px-3.5 py-2 rounded-xl bg-gradient-to-b from-amber-600 via-amber-700 to-amber-900 hover:from-amber-500 hover:to-amber-800 text-yellow-100 font-extrabold text-[11px] uppercase tracking-wider border border-amber-400/80 shadow-[0_2px_10px_rgba(245,158,11,0.3)] transition-all flex items-center gap-2 cursor-pointer">
                        <i class="fa-solid fa-boxes-packing text-yellow-300"></i>
                        <span>Odbierz Wszystkie</span>
                    </button>
                @endif

                @if($claimedCount > 0)
                    <button wire:click="deleteAllClaimed"
                            onclick="confirm('Czy na pewno chcesz usunąć wszystkie odczytane wiadomości?') || event.stopImmediatePropagation()"
                            wire:loading.attr="disabled"
                            class="px-3 py-2 rounded-xl bg-stone-900 hover:bg-red-950/80 text-stone-400 hover:text-red-200 font-extrabold text-[11px] uppercase tracking-wider border border-stone-800 hover:border-red-800 transition-all flex items-center gap-1.5 cursor-pointer">
                        <i class="fa-solid fa-broom text-stone-400 group-hover:text-red-300"></i>
                        <span>Wyczyść Odebrane</span>
                    </button>
                @endif
            </div>
        </div>

        {{-- Mail List Container --}}
        <div class="bg-stone-950/90 border-2 border-amber-800/80 p-4 sm:p-6 rounded-2xl shadow-2xl backdrop-blur-sm relative overflow-hidden min-h-[500px]">
            <div class="absolute top-0 right-0 w-64 h-64 bg-amber-600/5 rounded-full blur-3xl pointer-events-none"></div>

            @if(count($mails) > 0)
                <div class="space-y-4 relative z-10">
                    @foreach($mails as $mail)
                        <div class="relative rounded-2xl p-4 sm:p-5 flex flex-col md:flex-row gap-4 transition-all duration-200 overflow-hidden
                            {{ $mail->claimed 
                                ? 'bg-stone-950/40 border border-stone-800/80 text-stone-400 opacity-80 hover:opacity-100' 
                                : 'bg-gradient-to-r from-stone-900/95 via-stone-950 to-stone-900 border-2 border-amber-600/70 hover:border-amber-400 shadow-[0_4px_20px_rgba(0,0,0,0.6)] border-l-4 border-l-amber-400' }}">
                            
                            {{-- Status Icon Badge --}}
                            <div class="flex-shrink-0 flex items-start pt-1">
                                <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl flex items-center justify-center text-xl sm:text-2xl shadow-md transition-transform duration-200
                                    {{ $mail->claimed 
                                        ? 'bg-stone-900 border border-stone-800 text-stone-600' 
                                        : ($mail->hasAttachments() 
                                            ? 'bg-gradient-to-b from-amber-600 via-amber-800 to-amber-950 border-2 border-amber-400 text-yellow-300 shadow-[0_0_15px_rgba(245,158,11,0.4)] animate-pulse' 
                                            : 'bg-gradient-to-b from-amber-800/80 to-stone-950 border border-amber-500/60 text-amber-300') }}">
                                    @if($mail->hasAttachments() && !$mail->claimed)
                                        <i class="fa-solid fa-gift"></i>
                                    @elseif($mail->claimed)
                                        <i class="fa-solid fa-envelope-open"></i>
                                    @else
                                        <i class="fa-solid fa-envelope"></i>
                                    @endif
                                </div>
                            </div>
                            
                            {{-- Main Mail Content --}}
                            <div class="flex-grow min-w-0">
                                <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                                    <div class="flex items-center gap-2">
                                        <h3 class="text-base sm:text-lg font-extrabold tracking-wide {{ $mail->claimed ? 'text-stone-400' : 'text-yellow-200' }}">
                                            {{ $mail->subject ?: '(Brak tematu)' }}
                                        </h3>
                                        @if(!$mail->claimed)
                                            <span class="bg-amber-950/80 border border-amber-600/60 text-amber-300 text-[10px] font-extrabold uppercase px-2 py-0.5 rounded tracking-wider font-sans flex items-center gap-1">
                                                <i class="fa-solid fa-circle text-[6px] text-amber-400 animate-ping"></i>
                                                <span>Nowa</span>
                                            </span>
                                        @endif
                                    </div>

                                    <span class="text-[11px] text-stone-500 font-sans flex items-center gap-1.5 shrink-0">
                                        <i class="fa-solid fa-clock text-amber-600/70"></i>
                                        <span>{{ $mail->created_at->format('Y-m-d H:i') }}</span>
                                        <span class="text-stone-600">({{ $mail->created_at->diffForHumans() }})</span>
                                    </span>
                                </div>
                                
                                {{-- Mail Body --}}
                                <div class="text-xs sm:text-sm text-stone-300/90 font-sans leading-relaxed bg-stone-900/80 p-3.5 rounded-xl border border-stone-800/80 shadow-inner">
                                    {!! nl2br(e($mail->body)) !!}
                                </div>
                                
                                {{-- Attachments Section --}}
                                @if($mail->hasAttachments())
                                    <div class="mt-3 pt-3 border-t border-amber-900/40">
                                        <div class="text-[10px] font-extrabold text-amber-500 uppercase tracking-widest mb-2 flex items-center gap-1.5 font-sans">
                                            <i class="fa-solid fa-paperclip"></i>
                                            <span>Załączniki</span>
                                        </div>
                                        
                                        <div class="flex flex-wrap gap-2.5">
                                            @foreach($mail->attachments as $attachment)
                                                @if(isset($attachment['type']) && $attachment['type'] === 'item' && isset($attachment['id']))
                                                    @php
                                                        $item = \App\Infrastructure\Persistence\ItemInstance::find($attachment['id']);
                                                    @endphp
                                                    @if($item)
                                                        <div class="flex items-center gap-2.5 bg-stone-950 border-2 rounded-xl p-2 pr-3.5 shadow-md transition-all
                                                            @if($item->rarity === 'common') border-stone-700 text-stone-300
                                                            @elseif($item->rarity === 'uncommon') border-emerald-600/80 text-emerald-400 bg-emerald-950/20
                                                            @elseif($item->rarity === 'rare') border-blue-500/80 text-blue-400 bg-blue-950/20
                                                            @elseif($item->rarity === 'epic') border-purple-500/80 text-purple-400 bg-purple-950/20
                                                            @elseif($item->rarity === 'legendary') border-amber-500/80 text-amber-400 bg-amber-950/30 shadow-[0_0_10px_rgba(245,158,11,0.2)]
                                                            @else border-stone-800 text-stone-300
                                                            @endif
                                                        ">
                                                            <div class="w-9 h-9 rounded-lg bg-stone-900 border border-stone-700/60 flex items-center justify-center shrink-0 p-1">
                                                                @if($item->template && $item->template->icon)
                                                                    <img src="{{ route('assets.items', ['filename' => $item->template->icon]) }}" class="w-full h-full object-contain drop-shadow" alt="{{ $item->template->name }}">
                                                                @else
                                                                    @if(($item->template->slot ?? '') === 'weapon') <i class="fa-solid fa-shield-halved text-amber-400 text-sm"></i>
                                                                    @elseif(($item->template->slot ?? '') === 'head') <i class="fa-solid fa-hat-wizard text-amber-400 text-sm"></i>
                                                                    @elseif(($item->template->slot ?? '') === 'chest') <i class="fa-solid fa-shield-cat text-amber-400 text-sm"></i>
                                                                    @elseif(($item->template->slot ?? '') === 'legs') <i class="fa-solid fa-socks text-amber-400 text-sm"></i>
                                                                    @elseif(($item->template->slot ?? '') === 'boots') <i class="fa-solid fa-shoe-prints text-amber-400 text-sm"></i>
                                                                    @else <i class="fa-solid fa-box text-amber-400 text-sm"></i>
                                                                    @endif
                                                                @endif
                                                            </div>
                                                            
                                                            <div class="text-xs font-bold font-sans flex items-center gap-2">
                                                                <span>{{ $item->template ? $item->template->name : 'Nieznany przedmiot' }}</span>
                                                                @if(($item->stack_size ?? 1) > 1)
                                                                    <span class="text-[10px] font-extrabold text-amber-400 bg-amber-950 border border-amber-600/60 px-1.5 py-0.5 rounded">x{{ $item->stack_size }}</span>
                                                                @endif
                                                                @if(($item->level ?? 1) > 1)
                                                                    <span class="text-[10px] text-amber-300 font-extrabold opacity-80">+{{ $item->level - 1 }}</span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @else
                                                        <div class="flex items-center gap-2 bg-stone-950 border border-red-900/60 rounded-xl p-2 px-3 text-red-400/80 text-xs font-sans">
                                                            <i class="fa-solid fa-circle-question"></i>
                                                            <span>Przedmiot wygasł lub zmieniono ID</span>
                                                        </div>
                                                    @endif
                                                @elseif(isset($attachment['type']) && in_array($attachment['type'], ['gold', 'gems']) && isset($attachment['qty']))
                                                    <div class="flex items-center gap-2.5 bg-stone-950 border-2 border-stone-800 rounded-xl p-2 pr-3.5 shadow-md">
                                                        <div class="w-8 h-8 rounded-lg bg-stone-900 flex items-center justify-center text-sm">
                                                            @if($attachment['type'] === 'gold')
                                                                <i class="fa-solid fa-coins text-amber-400"></i>
                                                            @else
                                                                <i class="fa-solid fa-gem text-purple-400"></i>
                                                            @endif
                                                        </div>
                                                        <div class="text-xs font-extrabold font-sans {{ $attachment['type'] === 'gold' ? 'text-yellow-300' : 'text-purple-300' }}">
                                                            +{{ number_format($attachment['qty']) }} {{ $attachment['type'] === 'gold' ? 'Złota' : 'Klejnotów' }}
                                                        </div>
                                                    </div>
                                                @elseif(isset($attachment['type']) && $attachment['type'] === 'guild_invite')
                                                    @php $guild = \App\Models\Guild::find($attachment['guild_id'] ?? null); @endphp
                                                    <div class="flex items-center gap-2.5 bg-stone-950 border-2 border-emerald-800/80 rounded-xl p-2 pr-3.5 shadow-md">
                                                        <i class="fa-solid fa-shield-halved text-emerald-400 text-base"></i>
                                                        <div class="text-xs font-bold text-emerald-300 font-sans">
                                                            Zaproszenie od gildii: <span class="underline underline-offset-2">{{ $guild ? $guild->name : 'Nieznana Gildia' }}</span>
                                                        </div>
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                            
                            {{-- Action Column --}}
                            <div class="flex flex-row md:flex-col justify-end md:justify-center items-center gap-2 border-t md:border-t-0 md:border-l border-stone-800 pt-3 md:pt-0 md:pl-4 min-w-[130px] shrink-0">
                                @if(!$mail->claimed)
                                    @php
                                        $isGuildInvite = false;
                                        if (!empty($mail->attachments)) {
                                            foreach($mail->attachments as $att) {
                                                if (($att['type'] ?? '') === 'guild_invite') {
                                                    $isGuildInvite = true;
                                                    break;
                                                }
                                            }
                                        }
                                    @endphp
                                    
                                    @if($isGuildInvite)
                                        <button wire:click="claimMail('{{ $mail->id }}')" 
                                            wire:loading.attr="disabled" wire:target="claimMail('{{ $mail->id }}')"
                                            class="w-full bg-gradient-to-b from-emerald-600 via-emerald-700 to-emerald-900 hover:from-emerald-500 hover:to-emerald-800 text-emerald-100 font-extrabold py-2 px-3 rounded-xl border border-emerald-400/80 shadow-md flex items-center justify-center gap-1.5 transition-all text-xs cursor-pointer">
                                            <span wire:loading.remove wire:target="claimMail('{{ $mail->id }}')">
                                                <i class="fa-solid fa-check"></i> Przyjmij
                                            </span>
                                            <span wire:loading wire:target="claimMail('{{ $mail->id }}')">
                                                <i class="fa-solid fa-spinner animate-spin"></i>
                                            </span>
                                        </button>

                                        <button wire:click="declineGuildInvite('{{ $mail->id }}')" 
                                            wire:loading.attr="disabled" wire:target="declineGuildInvite('{{ $mail->id }}')"
                                            class="w-full bg-gradient-to-b from-red-900 via-stone-900 to-stone-950 hover:from-red-800 hover:to-stone-900 text-red-300 font-bold py-1.5 px-3 rounded-xl border border-red-800/80 flex items-center justify-center gap-1.5 transition-all text-xs cursor-pointer">
                                            <span wire:loading.remove wire:target="declineGuildInvite('{{ $mail->id }}')">
                                                <i class="fa-solid fa-xmark"></i> Odrzuć
                                            </span>
                                            <span wire:loading wire:target="declineGuildInvite('{{ $mail->id }}')">
                                                <i class="fa-solid fa-spinner animate-spin"></i>
                                            </span>
                                        </button>
                                    @else
                                        <button wire:click="claimMail('{{ $mail->id }}')" 
                                            wire:loading.attr="disabled" wire:target="claimMail('{{ $mail->id }}')"
                                            @click="$dispatch('play-audio', { type: 'button' })"
                                            class="w-full bg-gradient-to-b from-amber-600 via-amber-700 to-amber-900 hover:from-amber-500 hover:to-amber-800 text-yellow-100 font-extrabold py-2.5 px-4 rounded-xl border border-amber-400/80 shadow-[0_2px_10px_rgba(245,158,11,0.3)] flex items-center justify-center gap-2 transition-all text-xs uppercase tracking-wider cursor-pointer">
                                            <span wire:loading.remove wire:target="claimMail('{{ $mail->id }}')">
                                                @if($mail->hasAttachments())
                                                    <i class="fa-solid fa-hand-holding-hand text-amber-300"></i> Odbierz
                                                @else
                                                    <i class="fa-solid fa-check-double text-amber-300"></i> Przeczytane
                                                @endif
                                            </span>
                                            <span wire:loading wire:target="claimMail('{{ $mail->id }}')">
                                                <i class="fa-solid fa-spinner animate-spin"></i>
                                            </span>
                                        </button>
                                    @endif
                                @else
                                    <div class="text-xs text-emerald-400 font-extrabold flex items-center gap-1.5 bg-emerald-950/40 border border-emerald-800/60 px-3 py-1.5 rounded-lg mb-1 font-sans">
                                        <i class="fa-solid fa-circle-check text-emerald-500"></i>
                                        <span>Odebrane</span>
                                    </div>
                                    <button wire:click="deleteMail('{{ $mail->id }}')" 
                                        onclick="confirm('Czy na pewno chcesz usunąć tę wiadomość?') || event.stopImmediatePropagation()"
                                        wire:loading.attr="disabled" wire:target="deleteMail('{{ $mail->id }}')"
                                        class="w-full bg-stone-900 hover:bg-red-950/80 text-stone-400 hover:text-red-300 font-bold py-1.5 px-3 rounded-xl border border-stone-800 hover:border-red-800 transition-all text-[11px] flex items-center justify-center gap-1.5 cursor-pointer">
                                        <i class="fa-solid fa-trash-can"></i>
                                        <span>Usuń</span>
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
                
                <div class="mt-6 relative z-10">
                    {{ $mails->links() }}
                </div>
            @else
                <div class="flex flex-col items-center justify-center h-full py-20 text-stone-500 relative z-10">
                    <div class="w-20 h-20 rounded-full bg-stone-900/80 border border-stone-800 flex items-center justify-center text-4xl text-amber-900/40 mb-4 shadow-inner">
                        <i class="fa-solid fa-inbox"></i>
                    </div>
                    <h3 class="text-xl font-extrabold text-amber-200/60 mb-1">Brak wiadomości</h3>
                    <p class="text-xs text-stone-500 font-sans">Twoja skrzynka pocztowa jest obecnie pusta.</p>
                </div>
            @endif
        </div>
    </div>
</div>
