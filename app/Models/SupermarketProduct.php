<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupermarketProduct extends Model
{
    protected $fillable = [
        'name', 'category', 'default_price', 'default_price_usd', 'is_necessary',
    ];

    protected $casts = [
        'default_price' => 'decimal:2',
        'default_price_usd' => 'decimal:2',
        'is_necessary' => 'boolean',
    ];
}
