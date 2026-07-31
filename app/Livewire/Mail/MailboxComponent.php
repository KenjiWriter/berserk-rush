<?php

namespace App\Livewire\Mail;

use App\Application\Mail\Actions\ClaimMailAction;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\Mail;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;

class MailboxComponent extends Component
{
    use WithPagination;

    public $characterId;
    public $activeTab = 'unclaimed'; // 'unclaimed' or 'all'

    public function mount(Character $character)
    {
        $this->characterId = $character->id;
    }

    public function getCharacterProperty()
    {
        return Character::find($this->characterId);
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }
    
    public function backToCity()
    {
        return redirect()->route('city.hub', $this->character);
    }

    public function claimMail(string $mailId, ClaimMailAction $action)
    {
        $character = $this->character;
        $mail = Mail::find($mailId);

        if (!$mail) {
            $this->dispatch('notify', message: 'Wiadomość nie została znaleziona.', type: 'error');
            return;
        }

        $result = $action->execute($character, $mail);

        if ($result->isError()) {
            $this->dispatch('notify', message: $result->getErrorMessage(), type: 'error');
            return;
        }

        $this->dispatch('notify', message: 'Wiadomość/załączniki zostały pomyślnie odebrane!', type: 'success');
        $this->dispatch('character-updated');
    }
    
    public function deleteMail(string $mailId)
    {
        $mail = Mail::find($mailId);
        
        if (!$mail || $mail->to_character_id !== $this->characterId) {
            $this->dispatch('notify', message: 'Nie możesz usunąć tej wiadomości.', type: 'error');
            return;
        }
        
        $hasValuableAttachments = false;
        if (!empty($mail->attachments)) {
            foreach($mail->attachments as $att) {
                if (!in_array(($att['type'] ?? ''), ['guild_invite', 'guild_war_challenge'])) {
                    $hasValuableAttachments = true;
                    break;
                }
            }
        }
        
        if (!$mail->claimed && $hasValuableAttachments) {
            $this->dispatch('notify', message: 'Musisz najpierw odebrać załączniki.', type: 'error');
            return;
        }
        
        $mail->delete();
        $this->dispatch('notify', message: 'Wiadomość została usunięta.', type: 'success');
    }

    public function declineGuildInvite(string $mailId)
    {
        $mail = Mail::find($mailId);
        if (!$mail || $mail->to_character_id !== $this->characterId || $mail->claimed) {
            return;
        }

        $guildId = null;
        if (!empty($mail->attachments)) {
            foreach($mail->attachments as $att) {
                if (($att['type'] ?? '') === 'guild_invite') {
                    $guildId = $att['guild_id'] ?? null;
                    break;
                }
            }
        }

        if ($guildId) {
            \App\Models\GuildLog::create([
                'guild_id' => $guildId,
                'character_id' => $this->characterId,
                'action' => 'invite_declined',
                'amount' => 0,
            ]);
            $this->dispatch('notify', message: 'Zaproszenie odrzucone.', type: 'info');
        }

        $mail->delete();
    }

    public function acceptGuildWar(string $mailId, \App\Application\Guilds\GuildWarService $warService)
    {
        $mail = Mail::find($mailId);
        if (!$mail || $mail->to_character_id !== $this->characterId || $mail->claimed) {
            return;
        }

        $warId = null;
        if (!empty($mail->attachments)) {
            foreach ($mail->attachments as $att) {
                if (($att['type'] ?? '') === 'guild_war_challenge') {
                    $warId = $att['guild_war_id'] ?? null;
                    break;
                }
            }
        }

        if (!$warId) {
            $this->dispatch('notify', message: 'Nie odnaleziono ID wyzwania wojennego.', type: 'error');
            return;
        }

        $war = \App\Infrastructure\Persistence\GuildWar::find($warId);
        if (!$war) {
            $mail->update(['claimed' => true]);
            $this->dispatch('notify', message: 'Wyzwanie wojenne wygasło lub zostało już usunięte.', type: 'error');
            return;
        }

        $character = $this->character;
        if (!$character->guild_id) {
            $this->dispatch('notify', message: 'Nie należysz do żadnej gildii.', type: 'error');
            return;
        }

        $myGuild = \App\Models\Guild::find($character->guild_id);
        $myMember = \App\Models\GuildMember::where('character_id', $character->id)->first();

        if (!$myMember || $myMember->role !== 'leader') {
            $this->dispatch('notify', message: 'Tylko lider gildii może odpowiedzieć na wyzwanie wojenne.', type: 'error');
            return;
        }

        if (!$myGuild->hasWarTeam()) {
            $this->dispatch('notify', message: 'Ustaw drużynę wojenną (5 członków) w panelu gildii przed akceptacją.', type: 'error');
            return;
        }

        $result = $warService->acceptWar($war, $myGuild);
        if (!$result->isOk()) {
            $this->dispatch('notify', message: $result->getErrorMessage(), type: 'error');
            return;
        }

        \App\Jobs\ProcessGuildWarJob::dispatchSync($war->id);

        $mail->update(['claimed' => true]);

        $war->refresh();
        if ($war->status === 'finished') {
            $won = $war->winner_guild_id === $myGuild->id;
            $this->dispatch('notify', message: $won
                ? 'Wyzwanie zaakceptowane - Twoja gildia zwyciężyła w starciu!'
                : 'Wyzwanie zaakceptowane - Twoja gildia przegrała starcie.', type: $won ? 'success' : 'error');
        } else {
            $this->dispatch('notify', message: 'Wyzwanie zostało zaakceptowane!', type: 'success');
        }

        $this->dispatch('character-updated');
    }

    public function declineGuildWar(string $mailId, \App\Application\Guilds\GuildWarService $warService)
    {
        $mail = Mail::find($mailId);
        if (!$mail || $mail->to_character_id !== $this->characterId || $mail->claimed) {
            return;
        }

        $warId = null;
        if (!empty($mail->attachments)) {
            foreach ($mail->attachments as $att) {
                if (($att['type'] ?? '') === 'guild_war_challenge') {
                    $warId = $att['guild_war_id'] ?? null;
                    break;
                }
            }
        }

        if ($warId) {
            $war = \App\Infrastructure\Persistence\GuildWar::find($warId);
            if ($war) {
                $character = $this->character;
                $myGuild = \App\Models\Guild::find($character->guild_id ?? null);
                if ($myGuild) {
                    $warService->declineWar($war, $myGuild);
                }
            }
        }

        $mail->update(['claimed' => true]);
        $this->dispatch('notify', message: 'Wyzwanie wojenne zostało odrzucone.', type: 'info');
    }

    public function claimAll(ClaimMailAction $action)
    {
        $character = $this->character;
        $unclaimedMails = Mail::where('to_character_id', $character->id)
            ->where('claimed', false)
            ->get();

        if ($unclaimedMails->isEmpty()) {
            $this->dispatch('notify', message: 'Brak nieodebranych wiadomości.', type: 'info');
            return;
        }

        $claimedCount = 0;
        foreach ($unclaimedMails as $mail) {
            // Check if special interactive action mail (guild invite or war challenge) - skip auto claim
            $isSpecialMail = false;
            if (!empty($mail->attachments)) {
                foreach ($mail->attachments as $att) {
                    if (in_array(($att['type'] ?? ''), ['guild_invite', 'guild_war_challenge'])) {
                        $isSpecialMail = true;
                        break;
                    }
                }
            }

            if ($isSpecialMail) {
                continue;
            }

            $result = $action->execute($character, $mail);
            if ($result->isSuccess()) {
                $claimedCount++;
            }
        }

        if ($claimedCount > 0) {
            $this->dispatch('notify', message: "Odebrano załączniki z {$claimedCount} wiadomości!", type: 'success');
            $this->dispatch('character-updated');
        } else {
            $this->dispatch('notify', message: 'Nie odebrano żadnych załączników.', type: 'info');
        }
    }

    public function deleteAllClaimed()
    {
        $deleted = Mail::where('to_character_id', $this->characterId)
            ->where('claimed', true)
            ->delete();

        if ($deleted > 0) {
            $this->dispatch('notify', message: "Usunięto {$deleted} odebranych wiadomości.", type: 'success');
        } else {
            $this->dispatch('notify', message: 'Brak odebranych wiadomości do usunięcia.', type: 'info');
        }
    }

    public function render()
    {
        $character = $this->character;
        
        $query = Mail::where('to_character_id', $character->id);
        
        if ($this->activeTab === 'unclaimed') {
            $query->where('claimed', false);
        }
        
        $mails = $query->orderBy('created_at', 'desc')->paginate(10);

        return view('livewire.mail.mailbox', [
            'character' => $character,
            'mails' => $mails,
        ]);
    }
}

