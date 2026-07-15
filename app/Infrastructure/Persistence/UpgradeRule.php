<?php

namespace App\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class UpgradeRule extends Model
{
    protected $table = 'upgrade_rules';

    protected $fillable = [
        'applies_to',
        'applies_value',
        'from_level',
        'to_level',
        'success_chance',
        'on_fail',
        'cost',
        'protect_item_ref_ulid',
    ];

    protected $casts = [
        'cost' => 'array',
        'success_chance' => 'float',
    ];

    public function protectItemTemplate()
    {
        return $this->belongsTo(ItemTemplate::class, 'protect_item_ref_ulid');
    }
}
