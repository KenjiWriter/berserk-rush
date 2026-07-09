<?php

namespace App\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemRecipe extends Model
{
    use HasUlids;

    protected $fillable = [
        'result_item_template_id',
        'ingredients',
        'gold_cost',
    ];

    protected $casts = [
        'ingredients' => 'array',
        'gold_cost' => 'integer',
    ];

    public function resultItemTemplate(): BelongsTo
    {
        return $this->belongsTo(ItemTemplate::class, 'result_item_template_id', 'id');
    }
}
