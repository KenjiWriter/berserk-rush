<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. Given a channel name and a callback, which
| returns true/false indicating whether the current user can listen
| to that channel.
|
*/

// global-chat channel authorization for broadcast & presence
Broadcast::channel('global-chat', function ($user) {
    if (! $user) {
        return false;
    }

    return [
        'id' => $user->id,
        'name' => $user->name,
    ];
});
