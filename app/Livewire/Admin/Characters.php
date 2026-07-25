<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Infrastructure\Persistence\Character;
use App\Models\User;
use Carbon\Carbon;

#[Layout('components.layouts.app')]
class Characters extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filter = 'all'; // all, online, vip, muted

    // VIP Modal State
    public bool $showVipModal = false;
    public ?string $selectedUserId = null;
    public ?string $selectedCharacterName = null;
    public int $vipDays = 30;

    // Gems Modal State
    public bool $showGemsModal = false;
    public int $gemsAmount = 500;

    // Mute Modal State
    public bool $showMuteModal = false;
    public int $muteMinutes = 60;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilter(): void
    {
        $this->resetPage();
    }

    // --- VIP Actions ---
    public function openVipModal(string $userId, string $charName): void
    {
        $this->selectedUserId = $userId;
        $this->selectedCharacterName = $charName;
        $this->vipDays = 30;
        $this->showVipModal = true;
    }

    public function closeVipModal(): void
    {
        $this->showVipModal = false;
        $this->selectedUserId = null;
        $this->selectedCharacterName = null;
    }

    public function grantVip(): void
    {
        if (!$this->selectedUserId || $this->vipDays <= 0) {
            return;
        }

        $user = User::find($this->selectedUserId);
        if ($user) {
            // If already has active premium, add days to premium_until; else start from now
            $baseDate = ($user->premium_until && $user->premium_until->isFuture()) ? $user->premium_until : now();
            $user->premium_until = $baseDate->copy()->addDays($this->vipDays);
            $user->save();

            $this->dispatch('notify', message: "Przekazano status VIP na {$this->vipDays} dni dla konta gracza {$user->name}!", type: 'success');
        }

        $this->closeVipModal();
    }

    // --- Gems Actions ---
    public function openGemsModal(string $userId, string $charName): void
    {
        $this->selectedUserId = $userId;
        $this->selectedCharacterName = $charName;
        $this->gemsAmount = 500;
        $this->showGemsModal = true;
    }

    public function closeGemsModal(): void
    {
        $this->showGemsModal = false;
        $this->selectedUserId = null;
        $this->selectedCharacterName = null;
    }

    public function addGems(): void
    {
        if (!$this->selectedUserId || $this->gemsAmount <= 0) {
            return;
        }

        $user = User::find($this->selectedUserId);
        if ($user) {
            $user->gems += $this->gemsAmount;
            $user->save();

            $this->dispatch('notify', message: "Dodano {$this->gemsAmount} gemów do konta gracza {$user->name}!", type: 'success');
        }

        $this->closeGemsModal();
    }

    // --- Mute Actions ---
    public function openMuteModal(string $userId, string $charName): void
    {
        $this->selectedUserId = $userId;
        $this->selectedCharacterName = $charName;
        $this->muteMinutes = 60;
        $this->showMuteModal = true;
    }

    public function closeMuteModal(): void
    {
        $this->showMuteModal = false;
        $this->selectedUserId = null;
        $this->selectedCharacterName = null;
    }

    public function muteUser(): void
    {
        if (!$this->selectedUserId || $this->muteMinutes <= 0) {
            return;
        }

        $user = User::find($this->selectedUserId);
        if ($user) {
            $user->muted_until = now()->addMinutes($this->muteMinutes);
            $user->save();

            $this->dispatch('notify', message: "Wyciszono użytkownika {$user->name} na {$this->muteMinutes} minut!", type: 'success');
        }

        $this->closeMuteModal();
    }

    public function unmuteUser(string $userId): void
    {
        $user = User::find($userId);
        if ($user) {
            $user->muted_until = null;
            $user->save();

            $this->dispatch('notify', message: "Odciszono użytkownika {$user->name}!", type: 'success');
        }
    }

    public function render()
    {
        $query = Character::with(['user', 'guild']);

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'ilike', '%' . $this->search . '%')
                  ->orWhereHas('user', function ($u) {
                      $u->where('name', 'ilike', '%' . $this->search . '%')
                        ->orWhere('email', 'ilike', '%' . $this->search . '%');
                  });
            });
        }

        if ($this->filter === 'online') {
            $query->where('last_active_at', '>=', now()->subMinutes(5));
        } elseif ($this->filter === 'vip') {
            $query->whereHas('user', function ($u) {
                $u->where('premium_until', '>', now());
            });
        } elseif ($this->filter === 'muted') {
            $query->whereHas('user', function ($u) {
                $u->where('muted_until', '>', now());
            });
        }

        $characters = $query->orderByRaw('CASE WHEN last_active_at IS NOT NULL AND last_active_at >= ? THEN 1 ELSE 0 END DESC', [now()->subMinutes(5)])
            ->orderByRaw('last_active_at DESC NULLS LAST')
            ->orderByDesc('updated_at')
            ->paginate(15);

        return view('livewire.admin.characters', [
            'characters' => $characters,
        ]);
    }
}
