<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Infrastructure\Persistence\CombatSkill;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

#[Layout('components.layouts.app')]
class CombatSkills extends Component
{
    public $skills;
    public $name, $description, $type = 'active', $required_weapon_type = 'any', $effect_type = 'direct_dmg';
    public $base_cooldown = 0, $base_duration = 0, $base_value = 0, $scaling_value = 0;
    public $required_level = 1, $unlock_cost = 0, $icon;
    public $editingId = null;
    public $availableIcons = [];
    public $usedIcons = [];
    public $cacheBuster = 0;

    protected $rules = [
        'name' => 'required|string|max:255',
        'description' => 'required|string',
        'type' => 'required|in:active,passive',
        'required_weapon_type' => 'required|string',
        'effect_type' => 'required|string',
        'base_cooldown' => 'required|integer|min:0',
        'base_duration' => 'required|integer|min:0',
        'base_value' => 'required|integer|min:0',
        'scaling_value' => 'required|integer|min:0',
        'required_level' => 'required|integer|min:1',
        'unlock_cost' => 'required|integer|min:0',
        'icon' => 'nullable|string',
    ];

    public function mount()
    {
        $this->loadData();
        $this->loadAvailableIcons();
    }

    public function loadAvailableIcons()
    {
        $this->availableIcons = [];
        $path = storage_path('app/assets/skills/icons');
        if (!File::exists($path)) {
            File::makeDirectory($path, 0755, true);
        }
        if (File::exists($path)) {
            $files = File::files($path);
            foreach ($files as $file) {
                if (in_array(strtolower($file->getExtension()), ['png', 'jpg', 'jpeg', 'gif', 'svg', 'webp'])) {
                    $this->availableIcons[] = $file->getFilename();
                }
            }
        }
    }

    public function loadData()
    {
        $this->skills = CombatSkill::orderBy('required_level')->get();
        $this->usedIcons = $this->skills->pluck('icon')->filter()->unique()->toArray();
    }

    private function normalizeIconName($filename, $skillName)
    {
        if (empty($filename) || empty($skillName)) return $filename;

        $extension = File::extension($filename) ?: 'png';
        $expectedName = Str::slug($skillName) . '.' . $extension;

        if ($filename !== $expectedName) {
            $baseDir = storage_path('app/assets/skills/icons');
            $oldPath = $baseDir . DIRECTORY_SEPARATOR . basename($filename);
            $newPath = $baseDir . DIRECTORY_SEPARATOR . $expectedName;

            if (File::exists($oldPath)) {
                if (File::exists($newPath) && $oldPath !== $newPath) {
                    File::delete($newPath);
                }
                File::move($oldPath, $newPath);
            }
            return $expectedName;
        }

        return $filename;
    }

    public function setIcon($filename)
    {
        if ($this->editingId) {
            $skill = CombatSkill::find($this->editingId);
            if ($skill) {
                $finalName = $this->normalizeIconName($filename, $skill->name);
                $this->icon = $finalName;
                $skill->update(['icon' => $finalName]);
                $this->cacheBuster = time();
                $this->loadAvailableIcons();
                $this->loadData();
                session()->flash('message', 'Ikona przypisana i zaktualizowana!');
            }
        } else {
            $this->icon = $filename;
        }
    }

    public function save()
    {
        $this->validate();

        $finalIcon = $this->icon;
        if ($finalIcon && $this->name) {
            $finalIcon = $this->normalizeIconName($finalIcon, $this->name);
        }

        $data = [
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'required_weapon_type' => $this->required_weapon_type,
            'effect_type' => $this->effect_type,
            'base_cooldown' => $this->base_cooldown,
            'base_duration' => $this->base_duration,
            'base_value' => $this->base_value,
            'scaling_value' => $this->scaling_value,
            'required_level' => $this->required_level,
            'unlock_cost' => $this->unlock_cost,
            'icon' => $finalIcon,
        ];

        if ($this->editingId) {
            CombatSkill::findOrFail($this->editingId)->update($data);
        } else {
            CombatSkill::create($data);
        }

        $this->reset(['name', 'description', 'type', 'required_weapon_type', 'effect_type', 'base_cooldown', 'base_duration', 'base_value', 'scaling_value', 'required_level', 'unlock_cost', 'icon', 'editingId']);
        $this->loadData();
        $this->loadAvailableIcons();
        session()->flash('message', 'Umiejętność zapisana.');
    }

    public function edit($id)
    {
        $skill = CombatSkill::findOrFail($id);
        $this->editingId = $skill->id;
        $this->name = $skill->name;
        $this->description = $skill->description;
        $this->type = $skill->type;
        $this->required_weapon_type = $skill->required_weapon_type;
        $this->effect_type = $skill->effect_type;
        $this->base_cooldown = $skill->base_cooldown;
        $this->base_duration = $skill->base_duration;
        $this->base_value = $skill->base_value;
        $this->scaling_value = $skill->scaling_value;
        $this->required_level = $skill->required_level;
        $this->unlock_cost = $skill->unlock_cost;
        $this->icon = $skill->icon;
    }

    public function delete($id)
    {
        CombatSkill::findOrFail($id)->delete();
        $this->loadData();
    }

    public function render()
    {
        return view('livewire.admin.combat-skills');
    }
}
