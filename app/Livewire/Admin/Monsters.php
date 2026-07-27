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
    public $hp = 100, $atk = 10, $def = 5, $agi = 5, $int = 1, $crit = 0, $dodge = 0;
    public $previewCP = 0;
    public $editingId = null;
    public $availableAvatars = [];
    public $usedAvatars = [];
    public $cacheBuster = 0;

    // Masowa zmiana statystyk
    public $showBulkModal = false;
    public $bulkMapId = '';
    public $bulkRank = '';
    public $bulkMinLevel = null;
    public $bulkMaxLevel = null;

    public $bulkHpMult = 1.0;
    public $bulkAtkMult = 1.0;
    public $bulkDefMult = 1.0;
    public $bulkAgiMult = 1.0;
    public $bulkIntMult = 1.0;
    public $bulkCritMult = 1.0;
    public $bulkDodgeMult = 1.0;

    protected $rules = [
        'map_id' => 'required|exists:maps,id',
        'name' => 'required|string|max:255',
        'type' => 'required|string',
        'rank' => 'required|in:regular,boss,worldboss',
        'level' => 'required|integer|min:1',
        'hp' => 'required|integer|min:1',
        'atk' => 'required|integer|min:0',
        'def' => 'required|integer|min:0',
        'agi' => 'required|integer|min:0',
        'int' => 'required|integer|min:0',
        'crit' => 'required|numeric|min:0|max:100',
        'dodge' => 'required|numeric|min:0|max:100',
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
    public function updatedAgi() { $this->calculatePreviewCP(); }
    public function updatedInt() { $this->calculatePreviewCP(); }
    public function updatedCrit() { $this->calculatePreviewCP(); }
    public function updatedDodge() { $this->calculatePreviewCP(); }

    public function calculatePreviewCP()
    {
        $this->previewCP = $this->calculateMonsterCP([
            'hp' => $this->hp,
            'atk' => $this->atk,
            'def' => $this->def,
            'agi' => $this->agi,
            'int' => $this->int,
            'crit' => $this->crit,
            'dodge' => $this->dodge,
        ]);
    }

    public function calculateMonsterCP($stats)
    {
        $hp = (float)($stats['hp'] ?? 0);
        $atk = (float)($stats['atk'] ?? 0);
        $def = (float)($stats['def'] ?? 0);
        $agi = (float)($stats['agi'] ?? 0);
        $int = (float)($stats['int'] ?? 0);
        $crit = (float)($stats['crit'] ?? 0);
        $dodge = (float)($stats['dodge'] ?? 0);
        
        return (int) round(($hp * 0.1) + ($atk * 1.0) + ($def * 1.5) + ($agi * 1.2) + ($int * 1.2) + ($crit * 100.0) + ($dodge * 100.0));
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
        $this->usedAvatars = $this->monsters->pluck('avatar')->filter()->unique()->toArray();
    }

    private function normalizeAvatarName($filename, $monsterName)
    {
        if (empty($filename) || empty($monsterName)) return $filename;

        $extension = \Illuminate\Support\Facades\File::extension($filename) ?: 'png';
        $expectedName = \Illuminate\Support\Str::slug($monsterName) . '.' . $extension;

        if ($filename !== $expectedName) {
            $baseDir = storage_path('app/assets/monsters/avatars');
            $oldPath = $baseDir . DIRECTORY_SEPARATOR . basename($filename);
            $newPath = $baseDir . DIRECTORY_SEPARATOR . $expectedName;

            if (\Illuminate\Support\Facades\File::exists($oldPath)) {
                if (\Illuminate\Support\Facades\File::exists($newPath) && $oldPath !== $newPath) {
                    \Illuminate\Support\Facades\File::delete($newPath);
                }
                \Illuminate\Support\Facades\File::move($oldPath, $newPath);
            }
            return $expectedName;
        }

        return $filename;
    }

    public function setAvatar($filename)
    {
        if ($this->editingId) {
            $monster = Monster::find($this->editingId);
            if ($monster) {
                $finalName = $this->normalizeAvatarName($filename, $monster->name);
                $this->avatar = $finalName;
                $monster->update(['avatar' => $finalName]);
                $this->cacheBuster = time();
                $this->loadAvailableAvatars();
                $this->loadData();
                session()->flash('message', 'Avatar został przypisany i (w razie potrzeby) zaktualizowany fizycznie!');
            }
        } else {
            $this->avatar = $filename;
        }
    }

    public function save()
    {
        $this->validate();

        $finalAvatar = $this->avatar;
        if ($finalAvatar && $this->name) {
            $finalAvatar = $this->normalizeAvatarName($finalAvatar, $this->name);
        }

        $data = [
            'map_id' => $this->map_id,
            'name' => $this->name,
            'level' => $this->level,
            'type' => $this->type ?? 'zwierzę',
            'rank' => $this->rank ?? 'regular',
            'stats' => [
                'hp' => $this->hp,
                'atk' => $this->atk,
                'def' => $this->def,
                'agi' => $this->agi,
                'int' => $this->int,
                'crit' => $this->crit,
                'dodge' => $this->dodge,
            ],
            'loot_table_id' => $this->loot_table_id ?: null,
            'avatar' => $finalAvatar,
        ];

        if ($this->editingId) {
            Monster::findOrFail($this->editingId)->update($data);
        } else {
            Monster::create($data);
        }

        $this->reset(['name', 'level', 'hp', 'atk', 'def', 'agi', 'int', 'crit', 'dodge', 'rank', 'avatar', 'editingId']);
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
        $this->type = $monster->type?->value ?? 'animal';
        $this->rank = $monster->rank?->value ?? 'regular';
        $this->level = $monster->level;
        $this->hp = $monster->stats['hp'] ?? 100;
        $this->atk = $monster->stats['atk'] ?? 10;
        $this->def = $monster->stats['def'] ?? 5;
        $this->agi = $monster->stats['agi'] ?? 5;
        $this->int = $monster->stats['int'] ?? 1;
        $this->crit = $monster->stats['crit'] ?? 0;
        $this->dodge = $monster->stats['dodge'] ?? 0;
        $this->loot_table_id = $monster->loot_table_id;
        $this->avatar = $monster->avatar;
        $this->calculatePreviewCP();
    }

    public function delete($id)
    {
        Monster::findOrFail($id)->delete();
        $this->loadData();
    }

    public function resetBulkMultipliers()
    {
        $this->bulkHpMult = 1.0;
        $this->bulkAtkMult = 1.0;
        $this->bulkDefMult = 1.0;
        $this->bulkAgiMult = 1.0;
        $this->bulkIntMult = 1.0;
        $this->bulkCritMult = 1.0;
        $this->bulkDodgeMult = 1.0;
    }

    public function applyBulkStatChanges()
    {
        $query = Monster::query();

        if ($this->bulkMapId) {
            $query->where('map_id', $this->bulkMapId);
        }
        if ($this->bulkRank) {
            $query->where('rank', $this->bulkRank);
        }
        if ($this->bulkMinLevel !== null && $this->bulkMinLevel !== '') {
            $query->where('level', '>=', (int)$this->bulkMinLevel);
        }
        if ($this->bulkMaxLevel !== null && $this->bulkMaxLevel !== '') {
            $query->where('level', '<=', (int)$this->bulkMaxLevel);
        }

        $monstersToUpdate = $query->get();
        $count = $monstersToUpdate->count();

        if ($count === 0) {
            session()->flash('message', 'Brak potworów spełniających kryteria filtrów.');
            return;
        }

        foreach ($monstersToUpdate as $monster) {
            $stats = $monster->stats ?? [];

            if ((float)$this->bulkHpMult !== 1.0) {
                $stats['hp'] = (int) max(1, round(($stats['hp'] ?? 100) * (float)$this->bulkHpMult));
            }
            if ((float)$this->bulkAtkMult !== 1.0) {
                $stats['atk'] = (int) max(0, round(($stats['atk'] ?? 10) * (float)$this->bulkAtkMult));
            }
            if ((float)$this->bulkDefMult !== 1.0) {
                $stats['def'] = (int) max(0, round(($stats['def'] ?? 5) * (float)$this->bulkDefMult));
            }
            if ((float)$this->bulkAgiMult !== 1.0) {
                $stats['agi'] = (int) max(0, round(($stats['agi'] ?? 5) * (float)$this->bulkAgiMult));
            }
            if ((float)$this->bulkIntMult !== 1.0) {
                $stats['int'] = (int) max(0, round(($stats['int'] ?? 1) * (float)$this->bulkIntMult));
            }
            if ((float)$this->bulkCritMult !== 1.0) {
                $stats['crit'] = max(0, round(($stats['crit'] ?? 0) * (float)$this->bulkCritMult, 4));
            }
            if ((float)$this->bulkDodgeMult !== 1.0) {
                $stats['dodge'] = max(0, round(($stats['dodge'] ?? 0) * (float)$this->bulkDodgeMult, 4));
            }

            $monster->update(['stats' => $stats]);
        }

        $this->loadData();
        session()->flash('message', "⚡ Pomyślnie zaktualizowano statystyki dla {$count} potworów!");
        $this->resetBulkMultipliers();
    }

    public function render()
    {
        return view('livewire.admin.monsters');
    }
}
