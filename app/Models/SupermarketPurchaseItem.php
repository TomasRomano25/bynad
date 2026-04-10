<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupermarketPurchaseItem extends Model
{
    protected $fillable = [
        'supermarket_purchase_id', 'supermarket_product_id', 'custom_name',
        'quantity', 'price', 'price_usd', 'is_necessary',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'price_usd' => 'decimal:2',
        'is_necessary' => 'boolean',
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(SupermarketPurchase::class, 'supermarket_purchase_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(SupermarketProduct::class, 'supermarket_product_id');
    }
}
