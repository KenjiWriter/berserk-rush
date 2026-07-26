<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\Map;

class TrackCharacterActivity
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (Auth::check()) {
            $user = Auth::user();

            // Throttled user last_active_at update (every 30 seconds)
            if (!$user->last_active_at || $user->last_active_at->diffInSeconds(now()) > 30) {
                $user->last_active_at = now();
                $user->saveQuietly();
            }

            $activeCharId = session('active_character');
            if (!$activeCharId) {
                $charParam = $request->route('character');
                if ($charParam) {
                    $activeCharId = $charParam instanceof Character ? $charParam->id : $charParam;
                    if ($activeCharId) {
                        session(['active_character' => $activeCharId]);
                    }
                }
            }

            if ($activeCharId) {
                $character = Character::find($activeCharId);
                if ($character) {
                    $location = $this->resolveLocation($request);
                    $needsSave = false;

                    if ($character->current_location !== $location) {
                        $character->current_location = $location;
                        $needsSave = true;
                    }

                    if (!$character->last_active_at || $character->last_active_at->diffInSeconds(now()) > 30) {
                        $character->last_active_at = now();
                        $needsSave = true;
                    }

                    if ($needsSave) {
                        $character->saveQuietly();
                    }
                }
            }
        }

        return $response;
    }

    private function resolveLocation(Request $request): string
    {
        $routeName = $request->route() ? $request->route()->getName() : '';

        return match ($routeName) {
            'city.hub' => 'Miasto (Centrum)',
            'city.profile' => 'Profil postaci',
            'city.armorsmith' => 'Zbrojmistrz',
            'city.weaponsmith' => 'Brońmistrz',
            'city.witch' => 'Wiedźma',
            'city.wizard' => 'Czarodziej',
            'city.warlock' => 'Czarnoksiężnik',
            'city.market' => 'Targowisko',
            'city.mailbox' => 'Poczta',
            'city.guild' => 'Gildia',
            'city.adventure' => 'Strefa przygód',
            'adventure.map' => $this->getMapLocation($request),
            'city.dungeon.run' => 'Lochy',
            'city.pets' => 'Chowańce',
            'city.arena' => 'Arena PvP',
            'city.arena.combat.pvp' => 'Walka na Arenie',
            'city.arena.combat.gvg' => 'Walka GvG',
            'city.gladiator' => 'Sklep gladiatora',
            'city.quests' => 'Zadania',
            'itemshop' => 'Item Shop',
            'homepage' => 'Strona Główna',
            default => 'W grze',
        };
    }

    private function getMapLocation(Request $request): string
    {
        $map = $request->route('map');
        if ($map instanceof Map) {
            return "Mapa: {$map->name}";
        } elseif (is_string($map)) {
            $foundMap = Map::find($map);
            if ($foundMap) {
                return "Mapa: {$foundMap->name}";
            }
        }
        return "Mapa przygód";
    }
}
