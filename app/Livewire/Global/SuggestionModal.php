<?php

namespace App\Livewire\Global;

use App\Infrastructure\Persistence\Suggestion;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class SuggestionModal extends Component
{
    public bool $isOpen = false;
    public string $category = 'sugestia';
    public string $content = '';

    #[On('open-suggestion-modal')]
    public function openModal(): void
    {
        $this->resetValidation();
        $this->reset(['content']);
        $this->category = 'sugestia';
        $this->isOpen = true;
    }

    public function closeModal(): void
    {
        $this->isOpen = false;
    }

    public function submit(): void
    {
        $this->validate([
            'category' => 'required|string|in:sugestia,błąd,inne',
            'content'  => 'required|string|min:5|max:2000',
        ], [
            'content.required' => 'Wpisz treść sugestii.',
            'content.min'      => 'Sugestia musi mieć co najmniej 5 znaków.',
            'content.max'      => 'Sugestia nie może przekraczać 2000 znaków.',
        ]);

        Suggestion::create([
            'user_id'      => Auth::id(),
            'character_id' => session('active_character'),
            'category'     => $this->category,
            'content'      => trim($this->content),
            'status'       => 'new',
        ]);

        $this->isOpen = false;
        $this->reset(['content']);

        $this->dispatch('notify', 
            type: 'success', 
            message: 'Dziękujemy! Twoja sugestia została pomyślnie przesłana do administracji.'
        );
        $this->dispatch('play-audio', type: 'buy');
    }

    public function render()
    {
        return view('livewire.global.suggestion-modal');
    }
}
