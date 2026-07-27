<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Infrastructure\Persistence\Character;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'permission_level',
        'gems',
        'stash_slots',
        'game_stage',
        'premium_until',
        'unlocked_avatars',
        'auth_provider',
        'auth_provider_id',
        'gender',
        'birthday',
        'age_range',
        'location',
        'hometown',
        'profile_url',
        'is_social_setup_pending',
        'muted_until',
        'last_active_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'premium_until' => 'datetime',
            'muted_until' => 'datetime',
            'last_active_at' => 'datetime',
            'unlocked_avatars' => 'array',
            'is_social_setup_pending' => 'boolean',
            'birthday' => 'date',
        ];
    }

    public function characters(): HasMany
    {
        return $this->hasMany(Character::class);
    }

    public function getCharacterSlots(): array
    {
        $characters = $this->characters()->latest()->take(4)->get();
        $slots = [];

        foreach ($characters as $character) {
            $slots[] = $character;
        }

        // Fill remaining slots with null
        while (count($slots) < 4) {
            $slots[] = null;
        }

        return $slots;
    }

    public function hasMaxCharacters(): bool
    {
        return $this->characters()->count() >= 4;
    }

    public function isModerator(): bool
    {
        return $this->permission_level === 9;
    }

    public function isAdmin(): bool
    {
        return $this->permission_level >= 10;
    }

    public function hasAdminAccess(): bool
    {
        return $this->permission_level >= 9;
    }

    public function hasPremium(): bool
    {
        return $this->premium_until && $this->premium_until->isFuture();
    }

    public function isMuted(): bool
    {
        return $this->muted_until && $this->muted_until->isFuture();
    }

    public function playerStashItems(): HasMany
    {
        return $this->hasMany(\App\Infrastructure\Persistence\ItemInstance::class, 'user_id')
            ->where('location', 'player_stash');
    }

    public function getStashCapacity(): int
    {
        return $this->stash_slots ?? 2;
    }

    public function getMuteRemainingSeconds(): int
    {
        if (!$this->isMuted()) {
            return 0;
        }

        return max(0, now()->diffInSeconds($this->muted_until, false));
    }

    public function checkAndRepairTutorialStage(?Character $character = null): void
    {
        if ($this->game_stage == 21) {
            if (!$character) {
                $activeCharacterId = session('active_character');
                if ($activeCharacterId) {
                    $character = $this->characters()->find($activeCharacterId);
                }
            }
            if (!$character) {
                $character = $this->characters()->orderBy('level', 'desc')->first();
            }

            if ($character && $character->level >= 5) {
                $this->game_stage = 22;
                $this->save();
            }
        }
    }
}
