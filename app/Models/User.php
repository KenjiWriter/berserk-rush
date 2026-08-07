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

    /** Maksymalna liczba slotów Magazynu Gracza (`stash_slots`) po powiększaniu za gemy. */
    public const MAX_STASH_SLOTS = 64;

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
        'custom_avatar_url',
        'custom_avatar_label',
        'auth_provider',
        'auth_provider_id',
        'gender',
        'birthday',
        'age_range',
        'location',
        'hometown',
        'profile_url',
        'is_social_setup_pending',
        'deletion_code',
        'muted_until',
        'chat_terms_accepted_at',
        'last_active_at',
        'referral_code',
        'referred_by_user_id',
        'referral_signup_bonus_claimed_at',
        'referral_mirror_bonus_until',
        'referral_level30_reward_granted_at',
        'signup_source',
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
            'chat_terms_accepted_at' => 'datetime',
            'last_active_at' => 'datetime',
            'unlocked_avatars' => 'array',
            'is_social_setup_pending' => 'boolean',
            'birthday' => 'date',
            'referral_signup_bonus_claimed_at' => 'datetime',
            'referral_mirror_bonus_until' => 'datetime',
            'referral_level30_reward_granted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if (empty($user->referral_code)) {
                do {
                    $code = strtoupper(\Illuminate\Support\Str::random(8));
                } while (static::where('referral_code', $code)->exists());

                $user->referral_code = $code;
            }
        });
    }

    public function characters(): HasMany
    {
        return $this->hasMany(Character::class);
    }

    public function referredUsers(): HasMany
    {
        return $this->hasMany(User::class, 'referred_by_user_id');
    }

    public function referrer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_by_user_id');
    }

    public function getReferralLink(): string
    {
        return route('register') . '?ref=' . $this->referral_code;
    }

    public function character(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Character::class)->latestOfMany();
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
        return $this->permission_level === 8;
    }

    public function isAdmin(): bool
    {
        return $this->permission_level >= 9;
    }

    /**
     * Czy konto posiada indywidualny avatar ustawiony przez admina.
     */
    public function hasCustomAvatar(): bool
    {
        return !empty($this->custom_avatar_url);
    }

    /**
     * Zwraca URL indywidualnego avatara (ustawionego przez admina) lub null.
     */
    public function getCustomAvatarUrl(): ?string
    {
        return $this->custom_avatar_url ?: null;
    }

    public function hasAdminAccess(): bool
    {
        return $this->permission_level >= 8;
    }

    public function hasPremium(): bool
    {
        return $this->premium_until && $this->premium_until->isFuture();
    }

    public function isMuted(): bool
    {
        return $this->muted_until && $this->muted_until->isFuture();
    }

    public function hasAcceptedChatTerms(): bool
    {
        return $this->chat_terms_accepted_at !== null;
    }

    public function acceptChatTerms(): void
    {
        $this->update(['chat_terms_accepted_at' => now()]);
    }

    public function playerStashItems(): HasMany
    {
        return $this->hasMany(\App\Infrastructure\Persistence\ItemInstance::class, 'user_id')
            ->where('location', 'player_stash');
    }

    public function getStashCapacity(): int
    {
        return min(self::MAX_STASH_SLOTS, $this->stash_slots ?? 2);
    }

    public function isStashMaxed(): bool
    {
        return $this->getStashCapacity() >= self::MAX_STASH_SLOTS;
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
        // Gracz sam rozdał więcej punktów niż daje start (10) + pierwszy poziom (3),
        // czyli już wie jak działa panel atrybutów - pomijamy lekcję Kapitana o tym.
        if (in_array($this->game_stage, [13, 14], true)) {
            if (!$character) {
                $activeCharacterId = session('active_character');
                if ($activeCharacterId) {
                    $character = $this->characters()->find($activeCharacterId);
                }
            }
            if (!$character) {
                $character = $this->characters()->orderBy('level', 'desc')->first();
            }

            if ($character && $character->getTotalAttributePoints() > 13) {
                $this->game_stage = 15;
                $this->save();
                return;
            }
        }

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

        if ($this->game_stage == 34) {
            if (!$character) {
                $activeCharacterId = session('active_character');
                if ($activeCharacterId) {
                    $character = $this->characters()->find($activeCharacterId);
                }
            }
            if (!$character) {
                $character = $this->characters()->orderBy('level', 'desc')->first();
            }

            if ($character && $character->level >= 10) {
                $this->game_stage = 35;
                $this->save();
            }
        }

        if ($this->game_stage == 37) {
            if (!$character) {
                $activeCharacterId = session('active_character');
                if ($activeCharacterId) {
                    $character = $this->characters()->find($activeCharacterId);
                }
            }
            if (!$character) {
                $character = $this->characters()->orderBy('level', 'desc')->first();
            }

            if ($character && $character->level >= 15) {
                $this->game_stage = 38;
                $this->save();
            }
        }

        if ($this->game_stage == 40) {
            if (!$character) {
                $activeCharacterId = session('active_character');
                if ($activeCharacterId) {
                    $character = $this->characters()->find($activeCharacterId);
                }
            }
            if (!$character) {
                $character = $this->characters()->orderBy('level', 'desc')->first();
            }

            if ($character && $character->level >= 30) {
                $this->game_stage = 41;
                $this->save();
            }
        }
    }
}
