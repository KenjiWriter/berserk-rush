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
        
        if (!$mail->claimed && !empty($mail->attachments)) {
            $this->dispatch('notify', message: 'Musisz najpierw odebrać załączniki.', type: 'error');
            return;
        }
        
        $mail->delete();
        $this->dispatch('notify', message: 'Wiadomość została usunięta.', type: 'success');
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
        ])->layout('layouts.app');
    }
}
