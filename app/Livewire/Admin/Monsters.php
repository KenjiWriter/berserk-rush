<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Infrastructure\Persistence\Monster;
use App\Infrastructure\Persistence\Map;
use App\Infrastructure\Persistence\LootTable;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.app')]
class Monsters extends Component
{
    public $monsters, $maps, $lootTables;
    public $map_id, $name, $level, $type, $rank = 'regular', $loot_table_id, $avatar;
    public $hp = 100, $atk = 10, $def = 5, $crit = 0;
    public $previewCP = 0;
    public $editingId = null;
    public $availableAvatars = [];

    protected $rules = [
        'map_id' => 'required|exists:maps,id',
        'name' => 'required|string|max:255',
        'type' => 'required|string',
        'rank' => 'required|in:regular,boss,worldboss',
        'level' => 'required|integer|min:1',
        'hp' => 'required|integer|min:1',
        'atk' => 'required|integer|min:0',
        'def' => 'required|integer|min:0',
        'crit' => 'required|numeric|min:0|max:100',
        'loot_table_id' => 'nullable|exists:loot_tables,id',
        'avatar' => 'nullable|string',
    ];

    public function mount()
    {
        $this->maps = Map::all();
        $this->lootTables = LootTable::all();
        $this->loadData();
        $this->loadAvailableAvatars();
        $this->calculatePreviewCP();
    }

    public function updatedHp() { $this->calculatePreviewCP(); }
    public function updatedAtk() { $this->calculatePreviewCP(); }
    public function updatedDef() { $this->calculatePreviewCP(); }
    public function updatedCrit() { $this->calculatePreviewCP(); }

    public function calculatePreviewCP()
    {
        $this->previewCP = $this->calculateMonsterCP([
            'hp' => $this->hp,
            'atk' => $this->atk,
            'def' => $this->def,
            'crit' => $this->crit,
        ]);
    }

    public function calculateMonsterCP($stats)
    {
        $hp = (float)($stats['hp'] ?? 0);
        $atk = (float)($stats['atk'] ?? 0);
        $def = (float)($stats['def'] ?? 0);
        $crit = (float)($stats['crit'] ?? 0);
        
        return (int) round(($hp * 0.1) + ($atk * 1.0) + ($def * 1.5) + ($crit * 100.0));
    }

    public function loadAvailableAvatars()
    {
        $this->availableAvatars = [];
        $path = storage_path('app/assets/monsters/avatars');
        if (\Illuminate\Support\Facades\File::exists($path)) {
            $files = \Illuminate\Support\Facades\File::files($path);
            foreach ($files as $file) {
                if (in_array(strtolower($file->getExtension()), ['png', 'jpg', 'jpeg', 'gif', 'svg', 'webp'])) {
                    $this->availableAvatars[] = $file->getFilename();
                }
            }
        }
    }

    public function loadData()
    {
        $this->monsters = Monster::with(['map', 'lootTable'])->orderBy('level')->get();
    }

    public function save()
    {
        $this->validate();

        $data = [
            'map_id' => $this->map_id,
            'name' => $this->name,
            'level' => $this->level,
            'type' => $this->type ?? 'zwierzę',
            'rank' => $this->rank ?? 'regular',
            'stats' => ['hp' => $this->hp, 'atk' => $this->atk, 'def' => $this->def, 'crit' => $this->crit],
            'loot_table_id' => $this->loot_table_id ?: null,
            'avatar' => $this->avatar,
        ];

        if ($this->editingId) {
            Monster::findOrFail($this->editingId)->update($data);
        } else {
            Monster::create($data);
        }

        $this->reset(['name', 'level', 'hp', 'atk', 'def', 'crit', 'rank', 'avatar', 'editingId']);
        $this->calculatePreviewCP();
        $this->loadData();
        session()->flash('message', 'Potwór zapisany.');
    }

    public function edit($id)
    {
        $monster = Monster::findOrFail($id);
        $this->editingId = $monster->id;
        $this->map_id = $monster->map_id;
        $this->name = $monster->name;
        $this->type = $monster->type ?? 'zwierzę';
        $this->rank = $monster->rank ?? 'regular';
        $this->level = $monster->level;
        $this->hp = $monster->stats['hp'] ?? 100;
        $this->atk = $monster->stats['atk'] ?? 10;
        $this->def = $monster->stats['def'] ?? 5;
        $this->crit = $monster->stats['crit'] ?? 0;
        $this->loot_table_id = $monster->loot_table_id;
        $this->avatar = $monster->avatar;
        $this->calculatePreviewCP();
    }

    public function delete($id)
    {
        Monster::findOrFail($id)->delete();
        $this->loadData();
    }

    public function render()
    {
        return view('livewire.admin.monsters');
    }
}
