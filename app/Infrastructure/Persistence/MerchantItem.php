<?php

namespace App\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchantItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_id',
        'item_template_id',
        'required_level',
        'is_limited',
        'max_quantity',
        'sold_quantity',
    ];

    protected $casts = [
        'is_limited' => 'boolean',
        'required_level' => 'integer',
        'max_quantity' => 'integer',
        'sold_quantity' => 'integer',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(ItemTemplate::class, 'item_template_id');
    }
}
