<div class="min-h-screen bg-gradient-to-b from-slate-900 via-stone-900 to-slate-900 text-amber-100 relative overflow-hidden">
    {{-- Dark overlay for readability --}}
    <div class="absolute inset-0 bg-gradient-to-b from-slate-900/80 via-slate-800/80 to-slate-900/80 z-0"></div>

    <div class="relative container mx-auto px-4 py-8 min-h-screen z-10 max-w-5xl">
        {{-- Header --}}
        <div class="flex items-center justify-between mb-8">
            <div class="flex items-center space-x-4">
                <div class="w-16 h-16 bg-gradient-to-br from-blue-700 to-blue-900 rounded-full border-2 border-blue-400 flex items-center justify-center text-3xl shadow-lg shadow-blue-900/50">
                    ✉️
                </div>
                <div>
                    <h1 class="text-4xl font-bold text-blue-400 medieval-font drop-shadow-md">Poczta</h1>
                    <p class="text-blue-200/70">Wiadomości systemowe, powiadomienia z marketu i załączniki</p>
                </div>
            </div>
            
            <button wire:click="backToCity" @click="$dispatch('location-leave')"
                class="bg-gradient-to-r from-slate-600 to-slate-700 hover:from-slate-700 hover:to-slate-800 text-amber-200 font-bold py-3 px-6 rounded-lg transition-all duration-200 shadow-lg border border-slate-500 flex items-center">
                🏠 Powrót do miasta
            </button>
        </div>

        {{-- Tabs --}}
        <div class="flex space-x-2 border-b border-blue-900/50 mb-6">
            <button wire:click="switchTab('unclaimed')" 
                class="px-6 py-3 font-semibold rounded-t-lg transition-colors {{ $activeTab === 'unclaimed' ? 'bg-blue-900/80 text-blue-300 border-t border-l border-r border-blue-700' : 'bg-slate-800/50 text-slate-400 hover:text-blue-200 hover:bg-slate-800' }}">
                📥 Nowe i Nieodebrane
                @php
                    $unread = \App\Infrastructure\Persistence\Mail::where('to_character_id', $character->id)->where('claimed', false)->count();
                @endphp
                @if($unread > 0)
                    <span class="ml-2 bg-red-500 text-white text-xs px-2 py-0.5 rounded-full">{{ $unread }}</span>
                @endif
            </button>
            <button wire:click="switchTab('all')" 
                class="px-6 py-3 font-semibold rounded-t-lg transition-colors {{ $activeTab === 'all' ? 'bg-blue-900/80 text-blue-300 border-t border-l border-r border-blue-700' : 'bg-slate-800/50 text-slate-400 hover:text-blue-200 hover:bg-slate-800' }}">
                🗃️ Wszystkie
            </button>
        </div>

        {{-- Mail List --}}
        <div class="bg-slate-800/60 border border-slate-700 p-6 rounded-lg shadow-xl backdrop-blur-sm min-h-[500px]">
            @if(count($mails) > 0)
                <div class="space-y-4">
                    @foreach($mails as $mail)
                        <div class="border {{ $mail->claimed ? 'border-slate-700 bg-slate-900/50' : 'border-blue-600 bg-slate-800' }} rounded-lg p-5 flex flex-col sm:flex-row gap-4 transition-colors hover:border-blue-500/50">
                            
                            {{-- Icon --}}
                            <div class="flex-shrink-0 flex items-start pt-1">
                                <div class="w-12 h-12 rounded-full flex items-center justify-center text-2xl
                                    {{ $mail->claimed ? 'bg-slate-800 text-slate-500' : 'bg-blue-900/50 text-amber-400 border border-blue-500/50' }}">
                                    @if($mail->hasAttachments() && !$mail->claimed)
                                        🎁
                                    @elseif($mail->claimed)
                                        📭
                                    @else
                                        ✉️
                                    @endif
                                </div>
                            </div>
                            
                            {{-- Content --}}
                            <div class="flex-grow">
                                <div class="flex justify-between items-start mb-2">
                                    <h3 class="text-xl font-bold {{ $mail->claimed ? 'text-slate-400' : 'text-blue-300' }}">{{ $mail->subject ?: '(Brak tematu)' }}</h3>
                                    <span class="text-xs text-slate-500">{{ $mail->created_at->format('Y-m-d H:i') }} ({{ $mail->created_at->diffForHumans() }})</span>
                                </div>
                                
                                <div class="text-sm text-slate-300 mb-4 bg-slate-900/40 p-3 rounded border border-slate-700/50">
                                    {!! nl2br(e($mail->body)) !!}
                                </div>
                                
                                @if($mail->hasAttachments())
                                    <div class="mt-4 pt-3 border-t border-slate-700">
                                        <div class="text-xs text-slate-400 mb-2 uppercase tracking-wide">Załączniki:</div>
                                        <div class="flex flex-wrap gap-3">
                                            @foreach($mail->attachments as $attachment)
                                                @if(isset($attachment['type']) && $attachment['type'] === 'item' && isset($attachment['id']))
                                                    @php
                                                        $item = \App\Infrastructure\Persistence\ItemInstance::find($attachment['id']);
                                                    @endphp
                                                    @if($item)
                                                        <div class="flex items-center space-x-2 bg-slate-900/80 border border-slate-600 rounded p-2 pr-3">
                                                            <div class="text-lg">
                                                                @if($item->template->slot === 'weapon') ⚔️
                                                                @elseif($item->template->slot === 'head') 🪖
                                                                @elseif($item->template->slot === 'chest') 🛡️
                                                                @elseif($item->template->slot === 'legs') 👖
                                                                @elseif($item->template->slot === 'boots') 👢
                                                                @else 📦
                                                                @endif
                                                            </div>
                                                            <div class="text-sm font-semibold 
                                                                @if($item->rarity === 'common') text-slate-300
                                                                @elseif($item->rarity === 'uncommon') text-green-400
                                                                @elseif($item->rarity === 'rare') text-blue-400
                                                                @elseif($item->rarity === 'epic') text-purple-400
                                                                @elseif($item->rarity === 'legendary') text-orange-400
                                                                @endif
                                                            ">
                                                                {{ $item->template->name }} 
                                                                @if($item->level > 1) <span class="text-xs opacity-70">+{{ $item->level - 1 }}</span> @endif
                                                            </div>
                                                        </div>
                                                    @else
                                                        <div class="flex items-center space-x-2 bg-slate-900 border border-red-900/50 rounded p-2 text-red-400/50 text-sm">
                                                            <span>❓ Nieznany przedmiot</span>
                                                        </div>
                                                    @endif
                                                @elseif(isset($attachment['type']) && in_array($attachment['type'], ['gold', 'gems']) && isset($attachment['qty']))
                                                    <div class="flex items-center space-x-2 bg-slate-900/80 border border-slate-600 rounded p-2 pr-3">
                                                        <div class="text-lg">
                                                            {{ $attachment['type'] === 'gold' ? '💰' : '💎' }}
                                                        </div>
                                                        <div class="text-sm font-bold {{ $attachment['type'] === 'gold' ? 'text-yellow-400' : 'text-purple-400' }}">
                                                            +{{ number_format($attachment['qty']) }} {{ $attachment['type'] === 'gold' ? 'Złota' : 'Klejnotów' }}
                                                        </div>
                                                    </div>
                                                @elseif(isset($attachment['type']) && $attachment['type'] === 'guild_invite')
                                                    @php $guild = \App\Models\Guild::find($attachment['guild_id'] ?? null); @endphp
                                                    <div class="flex items-center space-x-2 bg-slate-900/80 border border-slate-600 rounded p-2 pr-3">
                                                        <div class="text-lg">🛡️</div>
                                                        <div class="text-sm font-bold text-emerald-400">
                                                            Zaproszenie od: {{ $guild ? $guild->name : 'Nieznana Gildia' }}
                                                        </div>
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                            
                            {{-- Actions --}}
                            <div class="flex flex-col sm:justify-center items-end sm:items-center gap-2 border-t sm:border-t-0 sm:border-l border-slate-700 pt-3 sm:pt-0 sm:pl-4 min-w-[120px]">
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
                                            wire:loading.class="opacity-50 cursor-not-allowed" wire:target="claimMail('{{ $mail->id }}')"
                                            class="w-full mb-1 bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-500 hover:to-emerald-600 text-white font-bold py-1.5 px-3 rounded shadow flex items-center justify-center transition-all text-sm">
                                            <span wire:loading.remove wire:target="claimMail('{{ $mail->id }}')">✅ Przyjmij</span>
                                            <span wire:loading wire:target="claimMail('{{ $mail->id }}')"><svg class="animate-spin inline-block h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg></span>
                                        </button>
                                        <button wire:click="declineGuildInvite('{{ $mail->id }}')" 
                                            wire:loading.attr="disabled" wire:target="declineGuildInvite('{{ $mail->id }}')"
                                            wire:loading.class="opacity-50 cursor-not-allowed" wire:target="declineGuildInvite('{{ $mail->id }}')"
                                            class="w-full bg-gradient-to-r from-red-600 to-red-700 hover:from-red-500 hover:to-red-600 text-white font-bold py-1.5 px-3 rounded shadow flex items-center justify-center transition-all text-sm">
                                            <span wire:loading.remove wire:target="declineGuildInvite('{{ $mail->id }}')">❌ Odrzuć</span>
                                            <span wire:loading wire:target="declineGuildInvite('{{ $mail->id }}')"><svg class="animate-spin inline-block h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg></span>
                                        </button>
                                    @else
                                        <button wire:click="claimMail('{{ $mail->id }}')" 
                                            wire:loading.attr="disabled" wire:target="claimMail('{{ $mail->id }}')"
                                            wire:loading.class="opacity-50 cursor-not-allowed" wire:target="claimMail('{{ $mail->id }}')"
                                            class="w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-500 hover:to-blue-600 text-white font-bold py-2 px-4 rounded shadow-lg flex items-center justify-center transition-all">
                                            <span wire:loading.remove wire:target="claimMail('{{ $mail->id }}')">
                                                @if($mail->hasAttachments())
                                                    🎁 Odbierz
                                                @else
                                                    ✓ Przeczytane
                                                @endif
                                            </span>
                                            <span wire:loading wire:target="claimMail('{{ $mail->id }}')"><svg class="animate-spin inline-block h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg></span>
                                        </button>
                                    @endif
                                @else
                                    <div class="text-sm text-green-500/70 font-semibold mb-2">
                                        ✓ Odebrane
                                    </div>
                                    <button wire:click="deleteMail('{{ $mail->id }}')" onclick="confirm('Czy na pewno chcesz usunąć tę wiadomość?') || event.stopImmediatePropagation()"
                                        wire:loading.attr="disabled" wire:target="deleteMail('{{ $mail->id }}')"
                                        wire:loading.class="opacity-50 cursor-not-allowed" wire:target="deleteMail('{{ $mail->id }}')"
                                        class="w-full bg-red-900/30 hover:bg-red-800 text-red-300 hover:text-red-100 font-medium py-1 px-3 rounded border border-red-800 transition-colors text-xs">
                                        🗑️ Usuń
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
                
                <div class="mt-6">
                    {{ $mails->links() }}
                </div>
            @else
                <div class="flex flex-col items-center justify-center h-full py-16 text-slate-500">
                    <div class="text-6xl mb-4 opacity-50">📭</div>
                    <h3 class="text-xl font-medium text-slate-400 mb-2">Brak wiadomości</h3>
                    <p>Twoja skrzynka pocztowa jest pusta.</p>
                </div>
            @endif
        </div>
    </div>
</div>
