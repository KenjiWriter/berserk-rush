<?php

namespace App\Livewire\City;

use Livewire\Component;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\Map;
use App\Infrastructure\Persistence\Dungeon;
use App\Infrastructure\Persistence\CharacterDungeonRun;
use App\Infrastructure\Persistence\WorldBossInstance;
use App\Infrastructure\Persistence\ItemInstance;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Attributes\On;

#[Layout('components.layouts.app')]
class Adventure extends Component
{
    public Character $character;
    public Collection $maps;
    
    #[Url]
    public string $tab = 'maps'; // 'maps', 'dungeons' or 'worldboss'

    public const BRACKET_LABELS = [
        'low' => '0-35',
        'mid' => '35-65',
        'high' => '65-99',
    ];

    public function mount(Character $character): void
    {
        if (Auth::user()->id !== $character->user_id) {
            abort(403, 'Nie możesz wejść do postaci innego gracza.');
        }

        $this->character = $character;

        if ($this->character->hasActiveMirror()) {
            session()->flash('error', 'Lustro jest aktywne! Nie możesz przeglądać ani rozpoczynać ręcznych przygód podczas trwania lustra.');
            $this->redirect(route('city.hub', $this->character), navigate: true);
            return;
        }

        $this->loadMaps();
    }

    private function loadMaps(): void
    {
        $this->maps = Map::with(['monsters.lootTable.entries.itemTemplate'])->orderBy('level_min')->get();
    }

    #[On('tutorial-completed')]
    public function refreshOnTutorial()
    {
        // Trigger a re-render so $gameStage in blade gets updated
    }

    public function enterMap(string $mapId): void
    {
        $map = Map::findOrFail($mapId);

        if (!$map->isAccessibleBy($this->character)) {
            $this->addError('map_access', 'Twój poziom nie pozwala na wejście na tę mapę.');
            return;
        }

        $user = Auth::user();
        if ($user && $user->game_stage == 10 && $map->level_min == 0) {
            $user->game_stage = 11;
            $user->save();
        }

        $this->redirect(
            route('adventure.map', ['character' => $this->character, 'map' => $map]),
            navigate: true
        );
    }

    public function enterDungeon(int $dungeonId, int $multiplier = 1): void
    {
        $dungeon = Dungeon::findOrFail($dungeonId);

        $this->redirect(
            route('city.dungeon.run', ['character' => $this->character, 'dungeon' => $dungeon, 'multiplier' => $multiplier]),
            navigate: true
        );
    }

    public function setTab(string $tab): void
    {
        if (in_array($tab, ['maps', 'dungeons', 'worldboss'])) {
            $this->tab = $tab;
        }
    }

    public function backToHub(): void
    {
        $this->redirect(route('city.hub', $this->character), navigate: true);
    }

    public function render()
    {
        $dungeons = collect();
        $dungeonCount = Dungeon::count();
        $activeRun = null;
        $keyCounts = [];
        
        if ($this->tab === 'dungeons') {
            $dungeons = Dungeon::with(['stages.monster.lootTable.entries.itemTemplate', 'entryItemTemplate'])->get();
            $activeRun = CharacterDungeonRun::where('character_id', $this->character->id)
                ->where('is_completed', false)
                ->where('is_failed', false)
                ->first();

            foreach ($dungeons as $d) {
                if ($d->entry_item_template_id) {
                    $keyCounts[$d->id] = (int) ItemInstance::where('owner_character_id', $this->character->id)
                        ->where('template_id', $d->entry_item_template_id)
                        ->whereIn('location', ['inventory', 'material_stash'])
                        ->sum('stack_size');
                } else {
                    $keyCounts[$d->id] = 999;
                }
            }
        }

        // Worldboss data jest potrzebna tylko na zakładce 'worldboss' - na mapach world
        // boss nie pojawia się już wcale (patrz nowa zakładka w adventure.blade.php).
        $worldBosses = collect();
        $topDamageDealers = [];
        $participatedBrackets = [];
        $nextResetAt = null;
        $resetCountdownLabel = null;

        if ($this->tab === 'worldboss') {
            app(\App\Application\Combat\WorldBossService::class)->ensureBossesSpawned();

            // whereNotNull('level_bracket') odsiewa ewentualne osierocone rekordy sprzed
            // rework'u przedziałów (migracja nie robi backfillu istniejących wierszy).
            $worldBosses = WorldBossInstance::with(['monster', 'map'])
                ->whereNotNull('level_bracket')
                ->get()
                ->keyBy('level_bracket');

            if ($worldBosses->isNotEmpty()) {
                $participatedInstanceIds = \App\Infrastructure\Persistence\WorldBossDamageLog::whereIn('world_boss_instance_id', $worldBosses->pluck('id'))
                    ->where('character_id', $this->character->id)
                    ->pluck('world_boss_instance_id')
                    ->toArray();

                $participatedBrackets = $worldBosses
                    ->filter(fn ($boss) => in_array($boss->id, $participatedInstanceIds))
                    ->pluck('level_bracket')
                    ->toArray();

                foreach ($worldBosses as $boss) {
                    $topDamageDealers[$boss->id] = \App\Infrastructure\Persistence\WorldBossDamageLog::with('character')
                        ->select('character_id', \Illuminate\Support\Facades\DB::raw('SUM(damage) as damage'))
                        ->where('world_boss_instance_id', $boss->id)
                        ->groupBy('character_id')
                        ->orderByDesc('damage')
                        ->limit(10)
                        ->get();
                }
            }

            $nextResetAt = now()->copy()->addHour()->startOfHour();
            $resetCountdownLabel = sprintf('%02d:%02d', intdiv(now()->diffInSeconds($nextResetAt), 60), now()->diffInSeconds($nextResetAt) % 60);
        }

        $activeQuestIds = $this->character->activeQuests()->pluck('quest_id')->toArray();

        return view('livewire.city.adventure', [
            'dungeons'          => $dungeons,
            'dungeonCount'      => $dungeonCount,
            'activeRun'         => $activeRun,
            'keyCounts'         => $keyCounts,
            'worldBosses'       => $worldBosses,
            'bracketLabels'     => self::BRACKET_LABELS,
            'participatedBrackets' => $participatedBrackets,
            'topDamageDealers'  => $topDamageDealers,
            'resetCountdownLabel' => $resetCountdownLabel,
            'activeQuestIds'    => $activeQuestIds,
        ]);
    }
}

