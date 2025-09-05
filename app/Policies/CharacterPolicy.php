<?php

namespace App\Policies;

use App\Models\User;
use App\Infrastructure\Persistence\Character;

class CharacterPolicy
{
    public function view(User $user, Character $character): bool
    {
        return $user->id === $character->user_id;
    }

    public function create(User $user): bool
    {
        return $user->characters()->count() < 4;
    }

    public function update(User $user, Character $character): bool
    {
        return $user->id === $character->user_id;
    }

    public function delete(User $user, Character $character): bool
    {
        return $user->id === $character->user_id;
    }
}
