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

// global-chat is a public channel — no auth required to listen
Broadcast::channel('global-chat', fn () => true);
