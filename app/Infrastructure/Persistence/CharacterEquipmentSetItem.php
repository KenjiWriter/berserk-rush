<?php

namespace App\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharacterEquipmentSetItem extends Model
{
    use HasUlids;

    public const SET_PVP = 'pvp';
    public const SET_GUILD_WAR = 'guild_war';
    public const SET_1 = 'set_1';
    public const SET_2 = 'set_2';
    public const SET_3 = 'set_3';

    public const WEARABLE_SETS = [self::SET_1, self::SET_2, self::SET_3];
    public const VIP_ONLY_SETS = [self::SET_2, self::SET_3];
    public const ALL_SETS = [self::SET_PVP, self::SET_GUILD_WAR, self::SET_1, self::SET_2, self::SET_3];

    public const SLOTS = ['head', 'chest', 'main_hand', 'neck', 'ring', 'feet'];

    protected $fillable = [
        'character_id',
        'set_type',
        'slot',
        'item_instance_id',
    ];

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function itemInstance(): BelongsTo
    {
        return $this->belongsTo(ItemInstance::class);
    }
}
