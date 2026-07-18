<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

class Homepage extends Component
{
    #[On('user-logged-in')]
    #[On('tutorial-completed')]
    public function refreshAfterLogin()
    {
        // This will trigger a re-render to show the logged-in state or updated tutorial state
    }

    public function render()
    {
        $activePlayers = \App\Infrastructure\Persistence\Character::where('updated_at', '>=', now()->subHour())->count();

        $topCharacters = \App\Infrastructure\Persistence\Character::with('guild')
            ->orderByDesc('level')
            ->orderByDesc('xp')
            ->limit(10)
            ->get()
            ->map(function ($c) {
                return [
                    'name' => $c->name,
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
                    'content' => $n->content,
                    'date' => $n->published_at ? $n->published_at->format('Y-m-d') : $n->created_at->format('Y-m-d')
                ];
            });

        // Mock data for rankings and admin messages
        $mockData = [
            'activePlayers' => $activePlayers,
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
}
