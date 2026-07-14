<?php

namespace App\Livewire\Global;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Infrastructure\Persistence\Map;

class LevelUpModal extends Component
{
    public $show = false;
    public $newLevel = 0;
    public $unlockedMaps = [];

    #[On('open-level-up-modal')]
    public function handleOpen($level)
    {
        $this->newLevel = $level;
        
        // Find maps that unlock at exactly this level
        $this->unlockedMaps = Map::where('level_min', $level)->get();

        $this->show = true;
    }

    public function close()
    {
        $this->show = false;
    }

    public function render()
    {
        return view('livewire.global.level-up-modal');
    }
}
