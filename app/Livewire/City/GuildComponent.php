<?php

namespace App\Livewire\City;

use App\Infrastructure\Persistence\Character;
use App\Models\Guild;
use App\Models\GuildMember;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Illuminate\Database\Eloquent\Collection;

class GuildComponent extends Component
{
    public Character $character;

    public string $viewMode = 'list'; // 'list', 'create', 'panel'
    public string $panelTab = 'members'; // 'members', 'logs', 'wars'
    
    // Create form
    public string $newGuildName = '';
    public string $newGuildTitle = '';
    public int $newGuildMinLevel = 1;
    public bool $newGuildIsPublic = false;

    // List search
    public string $searchQuery = '';

    public function mount(Character $character): void
    {
        if (Auth::user()->id !== $character->user_id) {
            abort(403);
        }

        $this->character = $character;
        session(['active_character' => $character->id]);

        $this->refreshState();
    }

    #[\Livewire\Attributes\On('refresh-guild')]
    public function refreshState(): void
    {
        $this->character->refresh();
        if ($this->character->guild_id) {
            $this->viewMode = 'panel';
        } else {
            $this->viewMode = 'list';
        }
    }

    public function goTo(string $route): void
    {
        $this->redirect(route('city.'.$route, $this->character), navigate: true);
    }

    public function getGuildsProperty()
    {
        $query = Guild::query();
        if ($this->searchQuery) {
            $query->where('name', 'like', '%' . $this->searchQuery . '%');
        }
        return $query->withCount('members')->orderByDesc('level')->get();
    }

    public function setViewMode(string $mode): void
    {
        $this->viewMode = $mode;
    }

    public function setPanelTab(string $tab): void
    {
        $this->panelTab = $tab;
    }

    public function getLogsProperty()
    {
        if ($this->viewMode !== 'panel' || !$this->character->guild_id) {
            return collect();
        }

        $member = GuildMember::where('character_id', $this->character->id)->first();
        if (!$member || $member->role !== 'leader') {
            return collect();
        }

        return \App\Models\GuildLog::with('character')
            ->where('guild_id', $this->character->guild_id)
            ->latest()
            ->take(50)
            ->get();
    }

    public function getWarsProperty()
    {
        if ($this->viewMode !== 'panel' || !$this->character->guild_id) {
            return collect();
        }

        return \App\Infrastructure\Persistence\GuildWar::with(['fights', 'challengerGuild', 'defenderGuild'])
            ->where('challenger_guild_id', $this->character->guild_id)
            ->orWhere('defender_guild_id', $this->character->guild_id)
            ->orderByDesc('created_at')
            ->take(10)
            ->get();
    }

    public function createGuild(): void
    {
        if ($this->character->guild_id) {
            $this->addError('create', 'Jesteś już w gildii.');
            return;
        }

        if ($this->character->user->gems < 150) {
            $this->dispatch('not-enough-gems');
            return;
        }

        $this->validate([
            'newGuildName' => 'required|string|min:3|max:30|unique:guilds,name',
            'newGuildTitle' => 'nullable|string|max:50',
            'newGuildMinLevel' => 'required|integer|min:1|max:100',
        ], [
            'newGuildName.required' => 'Nazwa jest wymagana.',
            'newGuildName.min' => 'Nazwa musi mieć min 3 znaki.',
            'newGuildName.max' => 'Nazwa może mieć max 30 znaków.',
            'newGuildName.unique' => 'Ta nazwa jest już zajęta.',
            'newGuildMinLevel.min' => 'Minimalny poziom to 1.',
        ]);

        DB::transaction(function () {
            // Odebranie gemsów
            $this->character->user->gems -= 150;
            $this->character->user->save();

            $guild = Guild::create([
                'name' => $this->newGuildName,
                'title' => $this->newGuildTitle,
                'min_level' => $this->newGuildMinLevel,
                'is_public' => $this->newGuildIsPublic,
            ]);

            GuildMember::create([
                'guild_id' => $guild->id,
                'character_id' => $this->character->id,
                'role' => 'leader',
            ]);

            $this->character->guild_id = $guild->id;
            $this->character->save();
        });

        $this->refreshState();
    }

    public function leaveGuild(): void
    {
        if (!$this->character->guild_id) return;

        $member = GuildMember::where('character_id', $this->character->id)->first();
        if ($member && $member->role === 'leader') {
            $guild = Guild::find($this->character->guild_id);
            if ($guild->members()->count() > 1) {
                $this->addError('leave', 'Musisz najpierw oddać przywództwo innemu graczowi.');
                return;
            }
        }

        DB::transaction(function () use ($member) {
            if ($member && $member->role === 'leader') {
                $guild = Guild::find($this->character->guild_id);
                if ($guild->members()->count() === 1) {
                    $guild->delete(); // Cascade zniszczy membersów
                }
            } else if ($member) {
                $member->delete();
            }

            $this->character->guild_id = null;
            $this->character->save();
        });

        $this->refreshState();
    }

    public function joinGuild(string $guildId): void
    {
        if ($this->character->guild_id) {
            $this->addError('join', 'Jesteś już w gildii.');
            return;
        }

        $guild = Guild::withCount('members')->find($guildId);
        if (!$guild) return;

        if ($guild->members_count >= $guild->getMaxMembers()) {
            $this->addError('join', 'Ta gildia jest pełna.');
            return;
        }

        if ($this->character->level < $guild->min_level) {
            $this->addError('join', 'Twój poziom jest zbyt niski aby dołączyć do tej gildii.');
            return;
        }

        if (!$guild->is_public) {
            $this->addError('join', 'Ta gildia przyjmuje tylko na zaproszenie.');
            return;
        }

        DB::transaction(function () use ($guild) {
            GuildMember::create([
                'guild_id' => $guild->id,
                'character_id' => $this->character->id,
                'role' => 'member',
            ]);

            $this->character->guild_id = $guild->id;
            $this->character->save();
        });

        $this->refreshState();
    }

    public function upgradeBonus(string $type, string $currency): void
    {
        if (!$this->character->guild_id) return;
        $guild = Guild::find($this->character->guild_id);
        if (!$guild) return;

        $member = GuildMember::where('character_id', $this->character->id)->first();
        if (!$member || $member->role !== 'leader') {
            $this->addError('upgrade', 'Tylko lider może ulepszać bonusy.');
            return;
        }

        if ($type === 'xp') {
            $currentLvl = $guild->bonus_xp_level;
            if ($currentLvl >= 20) {
                $this->addError('upgrade', 'Osiągnięto maksymalny poziom tego bonusu.');
                return;
            }
            $costGold = (int)(10000 * pow($currentLvl + 1, 1.5));
            $costGems = (int)(100 * pow($currentLvl + 1, 1.2));

            if ($currency === 'gold' && $guild->gold < $costGold) {
                $this->addError('upgrade', 'Gildia nie posiada wystarczającego złota w skarbcu.');
                return;
            }
            if ($currency === 'gems' && $guild->gems < $costGems) {
                $this->addError('upgrade', 'Gildia nie posiada wystarczających diamentów w skarbcu.');
                return;
            }

            DB::transaction(function () use ($guild, $currency, $costGold, $costGems) {
                if ($currency === 'gold') {
                    $guild->gold -= $costGold;
                } else {
                    $guild->gems -= $costGems;
                }
                
                $guild->bonus_xp_level += 1;
                $guild->save();
                
                \App\Models\GuildLog::create([
                    'guild_id' => $guild->id,
                    'character_id' => $this->character->id,
                    'action' => 'upgrade_bonus_xp',
                    'amount' => $currency === 'gold' ? $costGold : $costGems,
                ]);
            });
        } elseif ($type === 'gold') {
            $currentLvl = $guild->bonus_gold_level;
            if ($currentLvl >= 20) {
                $this->addError('upgrade', 'Osiągnięto maksymalny poziom tego bonusu.');
                return;
            }
            $costGold = (int)(10000 * pow($currentLvl + 1, 1.5));
            $costGems = (int)(100 * pow($currentLvl + 1, 1.2));

            if ($currency === 'gold' && $guild->gold < $costGold) {
                $this->addError('upgrade', 'Gildia nie posiada wystarczającego złota w skarbcu.');
                return;
            }
            if ($currency === 'gems' && $guild->gems < $costGems) {
                $this->addError('upgrade', 'Gildia nie posiada wystarczających diamentów w skarbcu.');
                return;
            }

            DB::transaction(function () use ($guild, $currency, $costGold, $costGems) {
                if ($currency === 'gold') {
                    $guild->gold -= $costGold;
                } else {
                    $guild->gems -= $costGems;
                }
                
                $guild->bonus_gold_level += 1;
                $guild->save();

                \App\Models\GuildLog::create([
                    'guild_id' => $guild->id,
                    'character_id' => $this->character->id,
                    'action' => 'upgrade_bonus_gold',
                    'amount' => $currency === 'gold' ? $costGold : $costGems,
                ]);
            });
        }

        $this->refreshState();
    }

    public function toggleWarRoster(string $characterId): void
    {
        if (!$this->character->guild_id) return;
        
        $guild = Guild::find($this->character->guild_id);
        if (!$guild) return;

        $myMember = GuildMember::where('character_id', $this->character->id)->first();
        if (!$myMember || $myMember->role !== 'leader') {
            $this->addError('roster', 'Tylko lider może zarządzać drużyną wojenną.');
            return;
        }

        if ($guild->is_war_locked) {
            $this->addError('roster', 'Gildia bierze udział w wojnie. Nie można teraz zmieniać drużyny.');
            return;
        }

        $team = $guild->war_team ?? [];
        
        if (in_array($characterId, $team)) {
            $team = array_values(array_filter($team, fn($id) => $id !== $characterId));
        } else {
            if (count($team) >= 5) {
                $this->addError('roster', 'Drużyna wojenna może składać się z maksymalnie 5 członków.');
                return;
            }
            $team[] = $characterId;
        }

        $guild->war_team = $team;
        $guild->save();

        $this->refreshState();
    }

    public function kickMember(string $characterId): void
    {
        if (!$this->character->guild_id) return;
        
        $guild = Guild::find($this->character->guild_id);
        if (!$guild) return;

        $myMember = GuildMember::where('character_id', $this->character->id)->first();
        if (!$myMember || !in_array($myMember->role, ['leader', 'commander'])) {
            $this->addError('roster', 'Tylko lider lub dowódca może wyrzucać członków.');
            return;
        }

        $targetMember = GuildMember::where('character_id', $characterId)->where('guild_id', $guild->id)->first();
        if (!$targetMember) return;
        
        if ($targetMember->role === 'leader') {
            $this->addError('roster', 'Nie można wyrzucić lidera.');
            return;
        }
        
        if ($myMember->role === 'commander' && in_array($targetMember->role, ['leader', 'commander'])) {
            $this->addError('roster', 'Dowódca nie może wyrzucić innego dowódcy ani lidera.');
            return;
        }

        DB::transaction(function () use ($targetMember, $characterId) {
            $targetMember->delete();
            $char = \App\Infrastructure\Persistence\Character::find($characterId);
            if ($char) {
                $char->guild_id = null;
                $char->save();
            }
        });
        
        $team = $guild->war_team ?? [];
        if (in_array($characterId, $team)) {
            $team = array_values(array_filter($team, fn($id) => $id !== $characterId));
            $guild->war_team = $team;
            $guild->save();
        }

        $this->refreshState();
    }

    public function changeRole(string $characterId, string $newRole): void
    {
        if (!$this->character->guild_id) return;
        
        $guild = Guild::find($this->character->guild_id);
        if (!$guild) return;

        $myMember = GuildMember::where('character_id', $this->character->id)->first();
        if (!$myMember || !in_array($myMember->role, ['leader', 'commander'])) {
            $this->addError('roster', 'Brak uprawnień do zmiany ról.');
            return;
        }

        $targetMember = GuildMember::where('character_id', $characterId)->where('guild_id', $guild->id)->first();
        if (!$targetMember) return;

        if ($myMember->role === 'commander') {
            if (in_array($targetMember->role, ['leader', 'commander'])) {
                $this->addError('roster', 'Dowódca nie może zmieniać ról innych dowódców ani lidera.');
                return;
            }
            if (in_array($newRole, ['leader', 'commander'])) {
                $this->addError('roster', 'Dowódca nie może nadać roli lidera ani dowódcy.');
                return;
            }
        }
        
        $validRoles = ['commander', 'elder', 'member', 'novice'];
        
        if ($newRole === 'leader' && $myMember->role === 'leader') {
            DB::transaction(function () use ($myMember, $targetMember) {
                $myMember->role = 'commander';
                $myMember->save();
                
                $targetMember->role = 'leader';
                $targetMember->save();
            });
        } elseif (in_array($newRole, $validRoles)) {
            if ($targetMember->role === 'leader') {
                 $this->addError('roster', 'Nie można zmienić roli lidera w ten sposób.');
                 return;
            }
            $targetMember->role = $newRole;
            $targetMember->save();
        }

        $this->refreshState();
    }

    public function render()
    {
        return view('livewire.city.guild-component');
    }
}
