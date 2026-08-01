<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

use Illuminate\Support\Facades\DB;

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
        $activePlayers = DB::table('sessions')
            ->whereNotNull('user_id')
            ->where('last_activity', '>=', now()->subMinutes(5)->timestamp)
            ->distinct('user_id')
            ->count('user_id');

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
                    'content' => self::formatNewsMarkdown($n->content),
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
}
