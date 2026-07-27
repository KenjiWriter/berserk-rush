<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Infrastructure\Persistence\ItemTemplate;
use Livewire\Attributes\Layout;
use Illuminate\Support\Str;

#[Layout('components.layouts.app')]
class ItemTemplates extends Component
{
    use WithPagination;

    public $template_id, $name, $type, $slot, $level_requirement;
    public $selectedStats = [];
    public $statValues = [];
    public $previewCP = 0;
    public $description, $icon;
    public $editingId = null;
    public $duration_minutes = null;
    public $search = '';
    public $filterType = '';

    protected $rules = [
        'template_id' => 'required|string|max:255',
        'name' => 'required|string|max:255',
        'type' => 'required|string',
        'slot' => 'nullable|string',
        'level_requirement' => 'required|integer|min:1',
        'selectedStats' => 'array',
        'statValues.*' => 'numeric',
        'duration_minutes' => 'nullable|integer|min:1',
    ];

    public $availableIcons = [];
    public $usedIcons = [];
    public $cacheBuster = 0;

    // Masowa zmiana statystyk przedmiotów
    public $showBulkModal = false;
    public $bulkType = '';
    public $bulkMinLevel = null;
    public $bulkMaxLevel = null;

    public $bulkAtkMult = 1.0;
    public $bulkDefMult = 1.0;
    public $bulkHpMult = 1.0;
    public $bulkManaMult = 1.0;
    public $bulkStrMult = 1.0;
    public $bulkAgiMult = 1.0;
    public $bulkVitMult = 1.0;
    public $bulkIntMult = 1.0;

    public function resetBulkMultipliers()
    {
        $this->bulkAtkMult = 1.0;
        $this->bulkDefMult = 1.0;
        $this->bulkHpMult = 1.0;
        $this->bulkManaMult = 1.0;
        $this->bulkStrMult = 1.0;
        $this->bulkAgiMult = 1.0;
        $this->bulkVitMult = 1.0;
        $this->bulkIntMult = 1.0;
    }

    public function applyBulkStatChanges()
    {
        $query = ItemTemplate::query();

        if ($this->bulkType) {
            $query->where('type', $this->bulkType);
        }
        if ($this->bulkMinLevel !== null && $this->bulkMinLevel !== '') {
            $query->where('level_requirement', '>=', (int)$this->bulkMinLevel);
        }
        if ($this->bulkMaxLevel !== null && $this->bulkMaxLevel !== '') {
            $query->where('level_requirement', '<=', (int)$this->bulkMaxLevel);
        }

        $itemsToUpdate = $query->get();
        $count = $itemsToUpdate->count();

        if ($count === 0) {
            session()->flash('message', 'Brak przedmiotów spełniających kryteria filtrów.');
            return;
        }

        foreach ($itemsToUpdate as $item) {
            $stats = $item->base_stats ?? [];
            $modified = false;

            // Attack multipliers
            if ((float)$this->bulkAtkMult !== 1.0) {
                foreach (['attack_min', 'attack_max', 'magic_attack_min', 'magic_attack_max'] as $key) {
                    if (isset($stats[$key])) {
                        $stats[$key] = (int) max(1, round($stats[$key] * (float)$this->bulkAtkMult));
                        $modified = true;
                    }
                }
            }

            // Defense multiplier
            if ((float)$this->bulkDefMult !== 1.0 && isset($stats['defense'])) {
                $stats['defense'] = (int) max(0, round($stats['defense'] * (float)$this->bulkDefMult));
                $modified = true;
            }

            // HP multiplier
            if ((float)$this->bulkHpMult !== 1.0 && isset($stats['hp_bonus'])) {
                $stats['hp_bonus'] = (int) max(0, round($stats['hp_bonus'] * (float)$this->bulkHpMult));
                $modified = true;
            }

            // Mana multiplier
            if ((float)$this->bulkManaMult !== 1.0 && isset($stats['mana_bonus'])) {
                $stats['mana_bonus'] = (int) max(0, round($stats['mana_bonus'] * (float)$this->bulkManaMult));
                $modified = true;
            }

            // STR multiplier
            if ((float)$this->bulkStrMult !== 1.0 && isset($stats['str_bonus'])) {
                $stats['str_bonus'] = (int) max(0, round($stats['str_bonus'] * (float)$this->bulkStrMult));
                $modified = true;
            }

            // AGI multiplier
            if ((float)$this->bulkAgiMult !== 1.0 && isset($stats['agi_bonus'])) {
                $stats['agi_bonus'] = (int) max(0, round($stats['agi_bonus'] * (float)$this->bulkAgiMult));
                $modified = true;
            }

            // VIT multiplier
            if ((float)$this->bulkVitMult !== 1.0 && isset($stats['vit_bonus'])) {
                $stats['vit_bonus'] = (int) max(0, round($stats['vit_bonus'] * (float)$this->bulkVitMult));
                $modified = true;
            }

            // INT multiplier
            if ((float)$this->bulkIntMult !== 1.0 && isset($stats['int_bonus'])) {
                $stats['int_bonus'] = (int) max(0, round($stats['int_bonus'] * (float)$this->bulkIntMult));
                $modified = true;
            }

            if ($modified) {
                $item->update(['base_stats' => $stats]);
            }
        }

        session()->flash('message', "⚡ Pomyślnie zaktualizowano statystyki dla {$count} szablonów przedmiotów!");
        $this->resetBulkMultipliers();
    }

    public function mount()
    {
        $this->loadAvailableIcons();
    }

    public function loadAvailableIcons()
    {
        $this->availableIcons = [];
        $paths = [
            public_path('assets/items'),
            storage_path('app/assets/items'),
        ];

        foreach ($paths as $path) {
            if (\Illuminate\Support\Facades\File::exists($path)) {
                $files = \Illuminate\Support\Facades\File::files($path);
                foreach ($files as $file) {
                    if (in_array(strtolower($file->getExtension()), ['png', 'jpg', 'jpeg', 'gif', 'svg', 'webp'])) {
                        $this->availableIcons[] = $file->getFilename();
                    }
                }
            }
        }
        $this->availableIcons = array_values(array_unique($this->availableIcons));
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedFilterType()
    {
        $this->resetPage();
    }

    public function updatedName($value)
    {
        $this->template_id = Str::slug($value);
    }

    public function updatedSelectedStats()
    {
        $this->calculatePreviewCP();
    }

    public function updatedStatValues()
    {
        $this->calculatePreviewCP();
    }

    public function calculatePreviewCP()
    {
        $cp = 0;
        $weights = [
            'attack_min' => 1.0, 'attack_max' => 1.0,
            'magic_attack_min' => 1.0, 'magic_attack_max' => 1.0,
            'defense' => 1.5,
            'hp_bonus' => 0.1, 'mana_bonus' => 0.1,
            'str_bonus' => 2.0, 'agi_bonus' => 2.0,
            'int_bonus' => 2.0, 'vit_bonus' => 2.0,
            'crit_chance' => 1.0,
        ];

        foreach ($this->selectedStats as $stat) {
            if ($stat && isset($this->statValues[$stat])) {
                $val = (float) $this->statValues[$stat];
                $weight = $weights[$stat] ?? 1.0;
                $cp += $val * $weight;
            }
        }

        // Default rarity multiplier for preview is 1.0 (common/base)
        $this->previewCP = (int) round($cp);
    }


    private function normalizeIconName($filename, $itemName)
    {
        if (empty($filename) || empty($itemName)) return $filename;

        $extension = \Illuminate\Support\Facades\File::extension($filename) ?: 'png';
        $expectedName = \Illuminate\Support\Str::slug($itemName) . '.' . $extension;

        if ($filename !== $expectedName) {
            $baseDir = storage_path('app/assets/items/icons');
            if (!\Illuminate\Support\Facades\File::exists($baseDir)) {
                $baseDir = storage_path('app/assets/items');
            }
            $oldPath = $baseDir . DIRECTORY_SEPARATOR . basename($filename);
            $newPath = $baseDir . DIRECTORY_SEPARATOR . $expectedName;

            if (\Illuminate\Support\Facades\File::exists($oldPath)) {
                if (\Illuminate\Support\Facades\File::exists($newPath) && $oldPath !== $newPath) {
                    \Illuminate\Support\Facades\File::delete($newPath);
                }
                
                if (in_array($filename, $this->usedIcons)) {
                    \Illuminate\Support\Facades\File::copy($oldPath, $newPath);
                } else {
                    \Illuminate\Support\Facades\File::move($oldPath, $newPath);
                }
            }
            return $expectedName;
        }

        return $filename;
    }

    public function setIcon($filename)
    {
        if ($this->editingId) {
            $item = ItemTemplate::find($this->editingId);
            if ($item) {
                $finalName = $this->normalizeIconName($filename, $item->name);
                $this->icon = $finalName;
                $item->update(['icon' => $finalName]);
                $this->cacheBuster = time();
                $this->loadAvailableIcons();
                session()->flash('message', 'Ikona została przypisana i zaktualizowana!');
            }
        } else {
            $this->icon = $filename;
        }
    }

    public function save()
    {
        if (empty($this->template_id)) {
            $this->template_id = Str::slug($this->name);
        }

        $this->validate();

        $stats = [];
        foreach ($this->selectedStats as $stat) {
            if ($stat && isset($this->statValues[$stat])) {
                $stats[$stat] = (int) $this->statValues[$stat];
            }
        }

        if ($this->type === 'consumable' && $this->duration_minutes) {
            $stats['duration_minutes'] = (int) $this->duration_minutes;
        }

        $finalIcon = $this->icon;
        if ($finalIcon && $this->name) {
            $finalIcon = $this->normalizeIconName($finalIcon, $this->name);
        }

        $data = [
            'id' => $this->template_id,
            'name' => $this->name,
            'type' => $this->type,
            'slot' => $this->slot ?: null,
            'level_requirement' => $this->level_requirement,
            'base_stats' => $stats,
            'description' => $this->description,
            'icon' => $finalIcon,
        ];

        if ($this->editingId) {
            $item = ItemTemplate::findOrFail($this->editingId);
            // If ID changed, we need to handle it, but for simplicity let's disable ID change or create new
            if ($this->editingId !== $this->template_id) {
                $item->delete();
                ItemTemplate::create($data);
            } else {
                $item->update($data);
            }
        } else {
            ItemTemplate::create($data);
        }

        $this->reset(['template_id', 'name', 'type', 'slot', 'level_requirement', 'selectedStats', 'statValues', 'previewCP', 'description', 'icon', 'editingId', 'duration_minutes']);
        session()->flash('message', 'Szablon przedmiotu zapisany.');
    }

    public function edit($id)
    {
        $template = ItemTemplate::findOrFail($id);
        $this->editingId = $template->id;
        $this->template_id = $template->id;
        $this->name = $template->name;
        $this->type = $template->type;
        $this->slot = $template->slot;
        $this->level_requirement = $template->level_requirement;
        
        $this->selectedStats = array_keys($template->base_stats ?? []);
        $this->statValues = $template->base_stats ?? [];
        if (isset($this->statValues['duration_minutes'])) {
            $this->duration_minutes = $this->statValues['duration_minutes'];
            // Remove duration_minutes from selectedStats so it doesn't show up in the checkboxes loop
            if (($key = array_search('duration_minutes', $this->selectedStats)) !== false) {
                unset($this->selectedStats[$key]);
            }
        } else {
            $this->duration_minutes = null;
        }

        $this->calculatePreviewCP();

        $this->description = $template->description;
        $this->icon = $template->icon;
    }

    public function delete($id)
    {
        ItemTemplate::findOrFail($id)->delete();
    }

    // public function generateId() { ... } // Removed since we use updatedName()

    public function render()
    {
        $query = ItemTemplate::query();

        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('id', 'like', '%' . $this->search . '%');
            });
        }

        if (!empty($this->filterType)) {
            $query->where('type', $this->filterType);
        }

        $templates = $query->orderBy('level_requirement')->paginate(20);
        $this->usedIcons = ItemTemplate::pluck('icon')->filter()->unique()->toArray();

        return view('livewire.admin.item-templates', [
            'templates' => $templates
        ]);
    }
}
