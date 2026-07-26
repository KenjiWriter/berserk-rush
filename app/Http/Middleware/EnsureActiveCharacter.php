<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Infrastructure\Persistence\Character;

class EnsureActiveCharacter
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $charParam = $request->route('character');
            $requestedCharId = null;

            if ($charParam) {
                $requestedCharId = $charParam instanceof Character ? $charParam->id : $charParam;
            }

            if ($requestedCharId) {
                // Verify character ownership
                $character = Character::find($requestedCharId);
                if (!$character || $character->user_id !== Auth::id()) {
                    abort(403, 'Nie masz dostępu do tej postaci.');
                }

                $activeCharId = session('active_character');

                // If no active character is set yet, activate this one
                if (!$activeCharId) {
                    session(['active_character' => $character->id]);
                } elseif ($activeCharId !== $character->id) {
                    // Active character mismatch (another character is active in session)
                    $activeChar = Character::find($activeCharId);
                    $activeName = $activeChar ? $activeChar->name : 'inną postać';

                    session()->flash('error', "Nie możesz grać postacią \"{$character->name}\", ponieważ masz już aktywną postać \"{$activeName}\" w innym oknie/karcie. Najpierw opuść grę tamtą postacią.");

                    return redirect()->route('homepage');
                }
            }
        }

        return $next($request);
    }
}
