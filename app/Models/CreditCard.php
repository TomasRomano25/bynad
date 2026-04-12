<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Account;
use App\Models\CreditCardPayment;

class CreditCard extends Model
{
    protected $fillable = [
        'user_id', 'account_id', 'name', 'brand', 'last_four', 'bank', 'limit_amount', 'limit_amount_usd',
        'closing_day', 'due_day', 'color',
    ];

    protected $casts = [
        'limit_amount' => 'decimal:2',
        'limit_amount_usd' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(CreditCardExpense::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CreditCardPayment::class);
    }

    public function getUsedAmountAttribute(): float
    {
        return $this->expenses()->sum('installment_amount');
    }

    public function getAvailableAttribute(): float
    {
        return $this->limit_amount - $this->used_amount;
    }
}
