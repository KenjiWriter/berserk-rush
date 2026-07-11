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
        'currency_type',
        'price',
    ];

    protected $casts = [
        'is_limited' => 'boolean',
        'required_level' => 'integer',
        'max_quantity' => 'integer',
        'sold_quantity' => 'integer',
        'price' => 'integer',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(ItemTemplate::class, 'item_template_id');
    }
}
