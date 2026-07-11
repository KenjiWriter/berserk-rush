<?php

namespace App\Livewire\City;

use Livewire\Component;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\Map;
use App\Infrastructure\Persistence\Dungeon;
use App\Infrastructure\Persistence\CharacterDungeonRun;
use App\Infrastructure\Persistence\WorldBossInstance;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;

#[Layout('components.layouts.app')]
class Adventure extends Component
{
    public Character $character;
    public Collection $maps;
    
    #[Url]
    public string $tab = 'maps'; // 'maps' or 'dungeons'

    public function mount(Character $character): void
    {
        if (Auth::user()->id !== $character->user_id) {
            abort(403, 'Nie możesz wejść do postaci innego gracza.');
        }

        $this->character = $character;
        $this->loadMaps();
    }

    private function loadMaps(): void
    {
        $this->maps = Map::with(['monsters.lootTable.entries.itemTemplate'])->orderBy('level_min')->get();
    }

    public function enterMap(string $mapId): void
    {
        $map = Map::findOrFail($mapId);

        if (!$map->isAccessibleBy($this->character)) {
            $this->addError('map_access', 'Twój poziom nie pozwala na wejście na tę mapę.');
            return;
        }

        $this->redirect(
            route('adventure.map', ['character' => $this->character, 'map' => $map]),
            navigate: true
        );
    }

    public function enterDungeon(int $dungeonId): void
    {
        $dungeon = Dungeon::findOrFail($dungeonId);

        $this->redirect(
            route('city.dungeon.run', [$this->character, $dungeon]),
            navigate: true
        );
    }

    public function setTab(string $tab): void
    {
        if (in_array($tab, ['maps', 'dungeons'])) {
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
        $activeRun = null;
        
        if ($this->tab === 'dungeons') {
            $dungeons = Dungeon::with(['stages.monster.lootTable.entries.itemTemplate', 'entryItemTemplate'])->get();
            $activeRun = CharacterDungeonRun::where('character_id', $this->character->id)
                ->where('is_completed', false)
                ->where('is_failed', false)
                ->first();
        }

        // Get active world bosses for maps
        $activeWorldBosses = WorldBossInstance::where('is_defeated', false)
            ->with(['monster', 'map'])
            ->get()
            ->keyBy('map_id');
            
        // Get defeated world bosses to show when they respawn (next full hour)
        $defeatedWorldBosses = WorldBossInstance::where('is_defeated', true)
            ->where('updated_at', '>=', now()->startOfHour()) // Only those defeated this hour
            ->get()
            ->keyBy('map_id');

        // Check if character participated in any active world boss
        $topDamageDealers = [];
        $participatedBosses = [];
        if ($activeWorldBosses->isNotEmpty()) {
            $participatedBosses = \App\Infrastructure\Persistence\WorldBossDamageLog::whereIn('world_boss_instance_id', $activeWorldBosses->pluck('id'))
                ->where('character_id', $this->character->id)
                ->pluck('world_boss_instance_id')
                ->toArray();
                
            foreach ($activeWorldBosses as $boss) {
                $topDamageDealers[$boss->id] = \App\Infrastructure\Persistence\WorldBossDamageLog::with('character')
                    ->select('character_id', \Illuminate\Support\Facades\DB::raw('SUM(damage) as damage'))
                    ->where('world_boss_instance_id', $boss->id)
                    ->groupBy('character_id')
                    ->orderByDesc('damage')
                    ->limit(10)
                    ->get();
            }
        }

        return view('livewire.city.adventure', [
            'dungeons' => $dungeons,
            'activeRun' => $activeRun,
            'activeWorldBosses' => $activeWorldBosses,
            'defeatedWorldBosses' => $defeatedWorldBosses,
            'participatedBosses' => $participatedBosses,
            'topDamageDealers' => $topDamageDealers,
        ]);
    }
}

