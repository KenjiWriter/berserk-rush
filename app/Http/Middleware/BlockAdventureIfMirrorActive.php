<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Infrastructure\Persistence\Character;

class BlockAdventureIfMirrorActive
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $charParam = $request->route('character');
            $character = null;

            if ($charParam) {
                $character = $charParam instanceof Character ? $charParam : Character::find($charParam);
            }

            if (!$character && session('active_character')) {
                $character = Character::find(session('active_character'));
            }

            if ($character && $character->hasActiveMirror()) {
                if ($request->has('world_boss')) {
                    return $next($request);
                }

                session()->flash('error', 'Lustro jest aktywne! Zwykłe Mapy są zablokowane podczas trwania lustra.');
                return redirect()->route('city.adventure', ['character' => $character, 'tab' => 'dungeons']);
            }
        }

        return $next($request);
    }
}
