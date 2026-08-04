<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;
use App\Infrastructure\Persistence\Character;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

#[Layout('components.layouts.app')]
class Characters extends Component
{
    use WithPagination, WithFileUploads;

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

    // Custom Avatar Modal State
    public bool $showAvatarModal = false;
    public string $customAvatarUrl = '';
    public string $customAvatarLabel = '';
    public string $avatarTab = 'url'; // 'url' | 'upload'
    public $avatarFile = null; // plik do przesłania (Livewire TemporaryUploadedFile)

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

    public function revokeVip(?string $userId = null): void
    {
        $targetId = $userId ?? $this->selectedUserId;
        if (!$targetId) {
            return;
        }

        $user = User::find($targetId);
        if ($user) {
            $user->premium_until = null;
            $user->save();

            $this->dispatch('notify', message: "Zabrano status VIP dla konta gracza {$user->name}!", type: 'success');
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

    // --- Custom Avatar Actions ---
    public function openAvatarModal(string $userId, string $charName): void
    {
        $this->selectedUserId = $userId;
        $this->selectedCharacterName = $charName;
        $user = User::find($userId);
        $this->customAvatarUrl   = $user?->custom_avatar_url ?? '';
        $this->customAvatarLabel = $user?->custom_avatar_label ?? '';
        $this->avatarTab  = 'url';
        $this->avatarFile = null;
        $this->showAvatarModal = true;
    }

    public function closeAvatarModal(): void
    {
        $this->showAvatarModal = false;
        $this->selectedUserId = null;
        $this->selectedCharacterName = null;
        $this->customAvatarUrl   = '';
        $this->customAvatarLabel = '';
        $this->avatarTab  = 'url';
        $this->avatarFile = null;
        $this->resetErrorBag();
    }

    public function saveCustomAvatar(): void
    {
        if (!$this->selectedUserId) {
            return;
        }

        $label = trim($this->customAvatarLabel);
        $finalUrl = null;

        if ($this->avatarTab === 'upload') {
            // --- Tryb przesyłania pliku ---
            $this->validate([
                'avatarFile' => 'required|image|mimes:png,jpg,jpeg,webp|max:2048',
            ], [
                'avatarFile.required' => 'Wybierz plik avatara.',
                'avatarFile.image'    => 'Plik musi być obrazkiem.',
                'avatarFile.mimes'    => 'Dozwolone formaty: PNG, JPG, WebP.',
                'avatarFile.max'      => 'Plik może mieć maksymalnie 2 MB.',
            ]);

            $user = User::find($this->selectedUserId);
            if (!$user) { $this->closeAvatarModal(); return; }

            // Usuń stary plik, jeśli był uploadem (nie zewnętrznym URL)
            if ($user->custom_avatar_url) {
                $oldPath = str_replace(Storage::disk('public')->url(''), '', $user->custom_avatar_url);
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }

            // Zapisz nowy plik
            $path = $this->avatarFile->store('custom-avatars', 'public');
            $finalUrl = Storage::disk('public')->url($path);

        } else {
            // --- Tryb URL ---
            $url = trim($this->customAvatarUrl);

            if ($url !== '' && !filter_var($url, FILTER_VALIDATE_URL)) {
                $this->addError('customAvatarUrl', 'Podaj poprawny URL (http/https).');
                return;
            }

            $finalUrl = $url ?: null;
            $user = User::find($this->selectedUserId);
            if (!$user) { $this->closeAvatarModal(); return; }
        }

        $user->custom_avatar_url   = $finalUrl;
        $user->custom_avatar_label = $label ?: null;
        $user->save();

        $this->dispatch(
            'notify',
            message: $finalUrl
                ? "Ustawiono indywidualny avatar dla konta {$user->name}!"
                : "Usunięto indywidualny avatar dla konta {$user->name}.",
            type: 'success'
        );

        $this->closeAvatarModal();
    }

    public function removeCustomAvatar(string $userId): void
    {
        $user = User::find($userId);
        if ($user) {
            // Jeśli avatar był przesłany na serwer (URL wskazuje na /storage/custom-avatars/), usuń plik z dysku
            if ($user->custom_avatar_url && str_contains($user->custom_avatar_url, 'custom-avatars/')) {
                $oldPath = ltrim(parse_url($user->custom_avatar_url, PHP_URL_PATH), '/');
                // Usuń prefix "storage/" żeby dostać ścieżkę względem public disk
                $diskPath = preg_replace('#^storage/#', '', $oldPath);
                if (Storage::disk('public')->exists($diskPath)) {
                    Storage::disk('public')->delete($diskPath);
                }
            }

            $user->custom_avatar_url   = null;
            $user->custom_avatar_label = null;
            $user->save();

            $this->dispatch('notify', message: "Usunięto indywidualny avatar dla konta {$user->name}.", type: 'success');
        }

        // Zamknij modal jeśli był otwarty na tym samym userze
        if ($this->showAvatarModal && $this->selectedUserId === $userId) {
            $this->closeAvatarModal();
        }
    }

    // --- Moderator Actions ---
    public function grantModerator(string $userId): void
    {
        $user = User::find($userId);
        if ($user) {
            $user->permission_level = 8;
            $user->save();

            $this->dispatch('notify', message: "Przyznano rolę Moderatora dla konta gracza {$user->name}!", type: 'success');
        }
    }

    public function revokeModerator(string $userId): void
    {
        $user = User::find($userId);
        if ($user && $user->permission_level === 8) {
            $user->permission_level = 0;
            $user->save();

            $this->dispatch('notify', message: "Odebrano rolę Moderatora dla konta gracza {$user->name}!", type: 'success');
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
        } elseif ($this->filter === 'moderators') {
            $query->whereHas('user', function ($u) {
                $u->where('permission_level', 8);
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
