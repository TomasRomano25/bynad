<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Asset extends Model
{
    protected $fillable = [
        'user_id', 'name', 'type', 'value_ars', 'value_usd', 'currency_input', 'description',
    ];

    protected $casts = [
        'value_ars' => 'decimal:2',
        'value_usd' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
