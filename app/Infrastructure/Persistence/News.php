<?php

namespace App\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    protected $table = 'news';

    protected $fillable = [
        'title',
        'content',
        'published_at',
        'discord_message_id',
        'source',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];
}
