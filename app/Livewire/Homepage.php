<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

class Homepage extends Component
{
    // Deletion Modal State
    public bool $showDeleteModal = false;
    public ?string $characterToDeleteId = null;
    public ?string $characterToDeleteName = null;
    public string $deleteCharacterNameInput = '';
    public string $deleteCodeInput = '';
    public ?string $deleteErrorMessage = null;

    public function mount()
    {
        $refCode = request()->query('ref');
        if ($refCode && !session('referral_code')) {
            session(['referral_code' => $refCode]);
        }
    }

    #[On('user-logged-in')]
    #[On('tutorial-completed')]
    public function refreshAfterLogin()
    {
        // This will trigger a re-render to show the logged-in state or updated tutorial state
    }

    public function render()
    {
        $onlinePlayers = \App\Models\User::where('last_active_at', '>=', now()->subMinutes(5))->count();
        $onlinePlayers24h = \App\Models\User::where('last_active_at', '>=', now()->subDay())->count();
        $totalAccounts = \App\Models\User::count();
        $totalCharacters = \App\Infrastructure\Persistence\Character::count();
        $serverOnline = !app()->isDownForMaintenance();

        $topCharacters = \App\Infrastructure\Persistence\Character::with('guild')
            ->orderByDesc('level')
            // Przy tym samym poziomie wygrywa TEN, kto osiągnął go WCZEŚNIEJ.
            // NULLS FIRST = postaci sprzed wdrożenia tej funkcji traktowane jako "najstarsze"
            ->orderByRaw('max_level_reached_at ASC NULLS FIRST')
            ->limit(10)
            ->get()
            ->map(function ($c) {
                return [
                    'name'  => $c->name,
                    'level' => $c->level,
                    'guild' => $c->guild ? $c->guild->name : '-',
                ];
            });

        $topGuilds = \App\Models\Guild::withCount('characters')
            ->withAvg('characters', 'level')
            ->orderByDesc('level')
            ->orderByDesc('xp')
            ->limit(10)
            ->get()
            ->map(function ($g) {
                return [
                    'name' => $g->name,
                    'members' => $g->characters_count,
                    'avgLevel' => round($g->characters_avg_level ?? 0),
                ];
            });

        // Fetch real news from database
        $adminMessages = \App\Infrastructure\Persistence\News::orderBy('published_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($n) {
                return [
                    'title' => $n->title,
                    'content' => self::formatNewsMarkdown($n->content),
                    'date' => $n->published_at ? $n->published_at->format('Y-m-d') : $n->created_at->format('Y-m-d')
                ];
            });

        // Mock data for rankings and admin messages
        $mockData = [
            'onlinePlayers' => $onlinePlayers,
            'onlinePlayers24h' => $onlinePlayers24h,
            'totalAccounts' => $totalAccounts,
            'totalCharacters' => $totalCharacters,
            'serverOnline' => $serverOnline,
            'topCharacters' => $topCharacters,
            'topGuilds' => $topGuilds,
            'adminMessages' => $adminMessages
        ];

        // Get real character data if user is authenticated
        if (Auth::check()) {
            $mockData['myCharacters'] = Auth::user()->getCharacterSlots();
            $mockData['canCreateCharacter'] = !Auth::user()->hasMaxCharacters();
        }

        $mockData['galleryImages'] = \App\Infrastructure\Persistence\GalleryImage::where('is_active', true)->orderBy('order')->get();

        return view('livewire.homepage', $mockData);
    }

    public static function formatNewsMarkdown(?string $content): string
    {
        if (empty($content)) {
            return '';
        }

        // Clean invalid UTF-8 byte sequences to prevent CommonMark UnexpectedEncodingException
        $clean = mb_convert_encoding($content, 'UTF-8', 'UTF-8');

        // Normalize unicode bullet points at the start of lines to Markdown list hyphens (- )
        $clean = preg_replace('/^[ \t]*[\x{2022}\x{2023}\x{2219}]\s*/um', '- ', $clean);

        try {
            return \Illuminate\Support\Str::markdown($clean);
        } catch (\Throwable $e) {
            return nl2br(e($clean));
        }
    }

    public function openDeleteModal(string $characterId): void
    {
        if (!Auth::check()) {
            return;
        }

        $character = Auth::user()->characters()->where('id', $characterId)->first();
        if (!$character) {
            return;
        }

        $this->characterToDeleteId = $character->id;
        $this->characterToDeleteName = $character->name;
        $this->deleteCharacterNameInput = '';
        $this->deleteCodeInput = '';
        $this->deleteErrorMessage = null;
        $this->showDeleteModal = true;
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->characterToDeleteId = null;
        $this->characterToDeleteName = null;
        $this->deleteCharacterNameInput = '';
        $this->deleteCodeInput = '';
        $this->deleteErrorMessage = null;
    }

    public function confirmDeleteCharacter(): void
    {
        if (!Auth::check()) {
            return;
        }

        $user = Auth::user();
        $character = $user->characters()->where('id', $this->characterToDeleteId)->first();

        if (!$character) {
            $this->deleteErrorMessage = 'Nie znaleziono wybranej postaci.';
            return;
        }

        if (trim($this->deleteCharacterNameInput) !== $character->name) {
            $this->deleteErrorMessage = 'Wpisana nazwa postaci jest nieprawidłowa. Musi dokładnie zgadzać się z "' . $character->name . '".';
            return;
        }

        $providedCode = trim($this->deleteCodeInput);
        if (mb_strlen($providedCode) < 7) {
            $this->deleteErrorMessage = 'Kod usunięcia postaci musi mieć co najmniej 7 znaków.';
            return;
        }

        if (empty($user->deletion_code)) {
            $user->update(['deletion_code' => $providedCode]);
        } elseif ($providedCode !== $user->deletion_code) {
            $this->deleteErrorMessage = 'Wprowadzono nieprawidłowy kod usunięcia postaci.';
            return;
        }

        $characterName = $character->name;

        if (session('active_character') === $character->id) {
            session()->forget('active_character');
        }

        $character->delete();

        $this->closeDeleteModal();
        session()->flash('message', "Postać \"{$characterName}\" została bezpowrotnie usunięta.");
    }
}
