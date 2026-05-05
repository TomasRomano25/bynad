<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountRetention extends Model
{
    protected $fillable = [
        'account_id', 'amount', 'currency', 'description', 'date', 'released_at', 'notes',
    ];

    protected $casts = [
        'amount'      => 'decimal:2',
        'date'        => 'date',
        'released_at' => 'date',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function isActive(): bool
    {
        return $this->released_at === null;
    }
}
