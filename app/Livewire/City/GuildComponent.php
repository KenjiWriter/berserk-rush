<?php

namespace App\Livewire\City;

use App\Application\Guilds\GuildWarService;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\GuildWar;
use App\Jobs\ProcessGuildWarJob;
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

    public string $viewMode = 'list'; // 'list', 'create', 'panel', 'browse'
    public string $panelTab = 'members'; // 'members', 'logs', 'wars'
    
    // Create form
    public string $newGuildName = '';
    public string $newGuildTitle = '';
    public int $newGuildMinLevel = 1;
    public bool $newGuildIsPublic = false;

    // Edit form
    public string $editGuildName = '';
    public string $editGuildTitle = '';
    public string $editGuildDescription = '';
    public int $editGuildMinLevel = 1;
    public bool $editGuildIsPublic = false;

    // List search
    public string $searchQuery = '';

    // Browse: war team roster preview (guild_id => list of character data) + hover tooltip data
    public ?string $expandedWarTeamGuildId = null;
    public array $warTeamMembersCache = [];
    public array $memberTooltipData = [];

    public function mount(Character $character)
    {
        if (Auth::user()->id !== $character->user_id) {
            abort(403);
        }

        Auth::user()->checkAndRepairTutorialStage($character);

        if (Auth::user()->game_stage < 35) {
            // Postać ma już wymagany poziom, ale utknęła wcześniej w łańcuchu samouczka -
            // wejście wprost pod /guild odbija do Hubu, gdzie pojawia się dymek Kapitana.
            if ($character->level >= 10) {
                Auth::user()->update(['game_stage' => 35]);
                return redirect()->route('city.hub', $character);
            }

            session()->flash('error', 'Gildia jest zablokowana do momentu osiągnięcia 10 poziomu postaci!');
            return redirect()->route('city.hub', $character);
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
            if ($this->viewMode !== 'browse') {
                $this->viewMode = 'panel';
            }
            if ($this->panelTab === 'settings') {
                $this->initEditGuild();
            }
        } else {
            $this->viewMode = 'list';
        }
    }

    public function initEditGuild(): void
    {
        if (!$this->character->guild_id) return;
        $guild = Guild::find($this->character->guild_id);
        if (!$guild) return;

        $this->editGuildName = $guild->name;
        $this->editGuildTitle = $guild->title ?? '';
        $this->editGuildDescription = $guild->description ?? '';
        $this->editGuildMinLevel = $guild->min_level;
        $this->editGuildIsPublic = (bool)$guild->is_public;
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

    public function browseGuilds(): void
    {
        $this->searchQuery = '';
        $this->viewMode = 'browse';
    }

    public function challengeGuildToWar(string $guildId, GuildWarService $service): void
    {
        if (!$this->character->guild_id) return;

        $myGuild = Guild::find($this->character->guild_id);
        if (!$myGuild) return;

        $myMember = GuildMember::where('character_id', $this->character->id)->first();
        if (!$myMember || $myMember->role !== 'leader') {
            $this->addError('challenge', 'Tylko lider może wyzwać inną gildię na wojnę.');
            return;
        }

        $targetGuild = Guild::find($guildId);
        if (!$targetGuild) return;

        $result = $service->challengeGuild($myGuild, $targetGuild);
        if ($result->isOk()) {
            $this->dispatch('notify', type: 'success', message: 'Wyzwanie na wojnę zostało wysłane do gildii "' . $targetGuild->name . '"!');
        } else {
            $this->addError('challenge', $result->getErrorMessage());
        }
    }

    public function acceptWarChallenge(string $warId, GuildWarService $service): void
    {
        if (!$this->character->guild_id) return;

        $myGuild = Guild::find($this->character->guild_id);
        if (!$myGuild) return;

        $myMember = GuildMember::where('character_id', $this->character->id)->first();
        if (!$myMember || $myMember->role !== 'leader') {
            $this->addError('war', 'Tylko lider może odpowiedzieć na wyzwanie wojenne.');
            return;
        }

        $war = GuildWar::find($warId);
        if (!$war) return;

        $result = $service->acceptWar($war, $myGuild);
        if (!$result->isOk()) {
            $this->addError('war', $result->getErrorMessage());
            return;
        }

        // Rozstrzygnięcie starcia następuje od razu - brak workera kolejki w tym środowisku.
        ProcessGuildWarJob::dispatchSync($war->id);

        $this->markWarChallengeMailAsClaimed($war->id);

        $war->refresh();
        if ($war->status === 'finished') {
            $won = $war->winner_guild_id === $myGuild->id;
            $this->dispatch('notify', type: $won ? 'success' : 'error', message: $won
                ? 'Wyzwanie zaakceptowane - Twoja gildia zwyciężyła w starciu!'
                : 'Wyzwanie zaakceptowane - Twoja gildia przegrała starcie.');
        } else {
            $this->dispatch('notify', type: 'success', message: 'Wyzwanie zostało zaakceptowane!');
        }

        $this->refreshState();
    }

    public function declineWarChallenge(string $warId, GuildWarService $service): void
    {
        if (!$this->character->guild_id) return;

        $myGuild = Guild::find($this->character->guild_id);
        if (!$myGuild) return;

        $myMember = GuildMember::where('character_id', $this->character->id)->first();
        if (!$myMember || $myMember->role !== 'leader') {
            $this->addError('war', 'Tylko lider może odpowiedzieć na wyzwanie wojenne.');
            return;
        }

        $war = GuildWar::find($warId);
        if (!$war) return;

        $result = $service->declineWar($war, $myGuild);
        if (!$result->isOk()) {
            $this->addError('war', $result->getErrorMessage());
            return;
        }

        $this->markWarChallengeMailAsClaimed($war->id);

        $this->dispatch('notify', type: 'info', message: 'Wyzwanie wojenne zostało odrzucone.');
    }

    private function markWarChallengeMailAsClaimed(string $warId): void
    {
        $mails = \App\Infrastructure\Persistence\Mail::where('to_character_id', $this->character->id)
            ->where('claimed', false)
            ->get();

        foreach ($mails as $mail) {
            if (!empty($mail->attachments)) {
                foreach ($mail->attachments as $att) {
                    if (($att['type'] ?? '') === 'guild_war_challenge' && ($att['guild_war_id'] ?? null) === $warId) {
                        $mail->update(['claimed' => true]);
                        break;
                    }
                }
            }
        }
    }

    public function toggleAutoDonateExp(): void
    {
        if (!$this->character->guild_id) return;

        $this->character->auto_donate_exp_guild = !$this->character->auto_donate_exp_guild;
        $this->character->save();

        $this->dispatch('notify', type: 'success', message: $this->character->auto_donate_exp_guild
            ? 'Automatyczna donacja EXP włączona - po przekroczeniu 50% paska doświadczenia nadwyżka trafi do gildii.'
            : 'Automatyczna donacja EXP wyłączona.');
    }

    public function toggleWarTeamPreview(string $guildId): void
    {
        if ($this->expandedWarTeamGuildId === $guildId) {
            $this->expandedWarTeamGuildId = null;
            return;
        }

        $guild = Guild::find($guildId);
        if (!$guild) return;

        $characterIds = $guild->war_team ?? [];
        $characters = Character::whereIn('id', $characterIds)->get()->keyBy('id');

        $members = [];
        foreach ($characterIds as $characterId) {
            if (!isset($characters[$characterId])) continue;
            $members[] = ['id' => $characterId, 'name' => $characters[$characterId]->name];
            $this->loadMemberTooltipData($characterId, $characters[$characterId]);
        }

        $this->warTeamMembersCache[$guildId] = $members;
        $this->expandedWarTeamGuildId = $guildId;
    }

    private function loadMemberTooltipData(string $characterId, Character $character): void
    {
        if (isset($this->memberTooltipData[$characterId])) return;

        $character->loadMissing(['equippedItems.template', 'guild', 'activeTitle']);

        $equippedPet = \App\Infrastructure\Persistence\Pet::where('character_id', $characterId)
            ->where('is_equipped', true)
            ->first();

        $equippedItems = $character->equippedItems->map(fn ($item) => [
            'name'          => $item->template?->name ?? 'Nieznany',
            'type'          => $item->template?->type ?? 'unknown',
            'icon'          => $item->template?->icon,
            'upgrade_level' => $item->upgrade_level,
            'rarity'        => $item->rarity,
            'combat_power'  => $item->getCombatPower(),
        ])->values()->toArray();

        $this->memberTooltipData[$characterId] = [
            'name'          => $character->name,
            'level'         => $character->level,
            'combat_power'  => $character->getTotalCombatPower(),
            'avatar'        => $character->avatar ?? 'plate.png',
            'guild'         => $character->guild ? $character->guild->name : null,
            'title'         => $character->activeTitle ? $character->activeTitle->prefix : null,
            'equipped_items' => $equippedItems,
            'pet'           => $equippedPet ? [
                'name' => $equippedPet->name,
                'rarity' => $equippedPet->tierName(),
                'level' => $equippedPet->level,
                'combat_power' => $equippedPet->getCombatPowerFor($character),
            ] : null,
        ];
    }

    public function setPanelTab(string $tab): void
    {
        $this->panelTab = $tab;
        if ($tab === 'settings') {
            $this->initEditGuild();
        }
    }

    public function updateGuildSettings(): void
    {
        if (!$this->character->guild_id) return;
        $guild = Guild::find($this->character->guild_id);
        if (!$guild) return;

        $myMember = GuildMember::where('character_id', $this->character->id)->first();
        if (!$myMember || $myMember->role !== 'leader') {
            $this->addError('settings', 'Tylko lider może edytować dane gildii.');
            return;
        }

        $this->validate([
            'editGuildName' => 'required|string|min:3|max:30|unique:guilds,name,' . $guild->id,
            'editGuildTitle' => 'nullable|string|max:50',
            'editGuildDescription' => 'nullable|string|max:1000',
            'editGuildMinLevel' => 'required|integer|min:1|max:100',
            'editGuildIsPublic' => 'required|boolean',
        ], [
            'editGuildName.required' => 'Nazwa jest wymagana.',
            'editGuildName.min' => 'Nazwa musi mieć min 3 znaki.',
            'editGuildName.max' => 'Nazwa może mieć max 30 znaków.',
            'editGuildName.unique' => 'Ta nazwa jest już zajęta.',
            'editGuildTitle.max' => 'Tytuł może mieć max 50 znaków.',
            'editGuildDescription.max' => 'Opis może mieć max 1000 znaków.',
            'editGuildMinLevel.min' => 'Minimalny poziom to 1.',
        ]);

        DB::transaction(function () use ($guild) {
            $guild->update([
                'name' => $this->editGuildName,
                'title' => $this->editGuildTitle,
                'description' => $this->editGuildDescription,
                'min_level' => $this->editGuildMinLevel,
                'is_public' => $this->editGuildIsPublic,
            ]);

            \App\Models\GuildLog::create([
                'guild_id' => $guild->id,
                'character_id' => $this->character->id,
                'action' => 'update_settings',
                'amount' => 0,
            ]);
        });

        $this->dispatch('notify', type: 'success', message: 'Dane gildii zostały pomyślnie zaktualizowane!');
        $this->refreshState();
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

    public function getGuildStashItemsProperty()
    {
        if ($this->viewMode !== 'panel' || !$this->character->guild_id) {
            return collect();
        }

        return \App\Infrastructure\Persistence\ItemInstance::with('template')
            ->where('guild_id', $this->character->guild_id)
            ->where('location', 'guild_stash')
            ->get();
    }

    public function depositToGuildStash(string $itemUlid, \App\Application\Storage\GuildStashService $service): void
    {
        $item = \App\Infrastructure\Persistence\ItemInstance::find($itemUlid);
        if (!$item) {
            $this->dispatch('notify', type: 'error', message: 'Przedmiot nie istnieje.');
            return;
        }

        $result = $service->deposit($this->character, $item);
        if ($result->isOk()) {
            $this->dispatch('notify', type: 'success', message: 'Przedmiot zdeponowany w magazynie gildii!');
            $this->character->refresh();
        } else {
            $this->dispatch('notify', type: 'error', message: $result->getErrorMessage());
        }
    }

    public function withdrawFromGuildStash(string $itemUlid, \App\Application\Storage\GuildStashService $service): void
    {
        $item = \App\Infrastructure\Persistence\ItemInstance::find($itemUlid);
        if (!$item) {
            $this->dispatch('notify', type: 'error', message: 'Przedmiot nie istnieje.');
            return;
        }

        $result = $service->withdraw($this->character, $item);
        if ($result->isOk()) {
            $this->dispatch('notify', type: 'success', message: 'Przedmiot wyciągnięty z magazynu gildii!');
            $this->character->refresh();
        } else {
            $this->dispatch('notify', type: 'error', message: $result->getErrorMessage());
        }
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
